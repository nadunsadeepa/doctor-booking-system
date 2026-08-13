<?php
/**
 * ai/ai_analysis_helper.php
 * -----------------------------------------------------------
 * Module 15 - AI Report Scanner (step 2: AI analysis)
 * Sends the OCR'd text to Gemini or OpenAI with a tightly
 * constrained prompt: classification + a short neutral summary
 * only -- never a diagnosis, treatment suggestion, or medical
 * opinion. Any uncertainty defaults to "Needs Doctor Review".
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/ai_config.php';

const AI_VALID_CLASSIFICATIONS = ['Normal', 'Needs Doctor Review', 'Abnormal Values Detected'];

function build_screening_prompt(string $reportText): string
{
    return <<<PROMPT
You are a triage SCREENING assistant inside a hospital appointment system. You are not a doctor.

You will be given OCR-extracted text from a patient-uploaded medical report (e.g. a lab or blood test report). OCR text can contain errors or garbled characters -- account for that.

Your ONLY job:
1. Classify the report into EXACTLY ONE of these three categories: Normal, Needs Doctor Review, Abnormal Values Detected.
2. Write a short, neutral, purely descriptive summary (1-2 sentences) of what stands out, if anything. Do NOT interpret what it might mean, do NOT name a possible condition or diagnosis, do NOT suggest treatment.

Strict rules:
- If the text is unclear, incomplete, doesn't look like a medical report, or you are uncertain in ANY way, you MUST classify it as "Needs Doctor Review".
- Never write a diagnosis, a possible condition, a treatment, or medical advice of any kind.
- Never tell the patient not to see a doctor.

Respond with ONLY these two lines, nothing else. No markdown, no bold, no asterisks, no extra commentary:
Classification: <Normal|Needs Doctor Review|Abnormal Values Detected>
Summary: <your 1-2 sentence neutral summary>

Report text:
---
{$reportText}
---
PROMPT;
}

/**
 * Parse "Classification: X / Summary: Y" out of the model's raw reply.
 * Falls back to the safest option (Needs Doctor Review) if parsing
 * fails or the model returned something outside the allowed set.
 */
function parse_ai_screening_response(string $raw): array
{
    // Strip common markdown noise (**, __, #, -) that models add despite
    // being told not to -- keeps the regexes below reliable either way.
    $clean = preg_replace('/[*_#]+/', '', $raw);

    $classification = 'Needs Doctor Review';
    $summary = 'The AI response could not be parsed reliably -- please have a doctor review this report.';

    if (preg_match('/Classification:\s*(.+)/i', $clean, $m)) {
        $candidate = trim($m[1]);
        foreach (AI_VALID_CLASSIFICATIONS as $valid) {
            if (strcasecmp($candidate, $valid) === 0) {
                $classification = $valid;
                break;
            }
        }
    }

    if (preg_match('/Summary:\s*(.+)/is', $clean, $m)) {
        $summary = trim($m[1]);
    }

    return ['classification' => $classification, 'summary' => $summary];
}

/**
 * Send the extracted report text for classification.
 * Returns ['classification' => ..., 'summary' => ...].
 * Throws Exception if not configured or the API call fails
 * (callers should treat that as "Needs Doctor Review" too).
 */
function ai_classify_report(string $reportText): array
{
    $prompt = build_screening_prompt($reportText);

    if (AI_PROVIDER === 'openai') {
        return ai_call_openai($prompt);
    }
    if (AI_PROVIDER === 'gemini') {
        return ai_call_gemini($prompt);
    }
    return ai_call_groq($prompt); // default
}

function ai_call_groq(string $prompt): array
{
    if (GROQ_API_KEY === '') {
        throw new Exception('AI screening is not configured yet (missing Groq API key). Get a free one at console.groq.com.');
    }

    $body = json_encode([
        'model'       => GROQ_MODEL,
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.1,
    ]);

    // Groq's API is OpenAI-compatible
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Could not reach Groq: ' . $curlError);
    }

    $data = json_decode($response, true);

    if (isset($data['error'])) {
        throw new Exception('Groq error: ' . $data['error']['message']);
    }

    $text = $data['choices'][0]['message']['content'] ?? '';
    if (trim($text) === '') {
        throw new Exception('Groq returned an empty response.');
    }

    return parse_ai_screening_response($text);
}

function ai_call_gemini(string $prompt): array
{
    if (GEMINI_API_KEY === '') {
        throw new Exception('AI screening is not configured yet (missing Gemini API key).');
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;
    $body = json_encode([
        'contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]],
        'generationConfig' => ['temperature' => 0.1],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Could not reach Gemini: ' . $curlError);
    }

    $data = json_decode($response, true);

    if (isset($data['error'])) {
        throw new Exception('Gemini error: ' . $data['error']['message']);
    }

    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (trim($text) === '') {
        throw new Exception('Gemini returned an empty response.');
    }

    return parse_ai_screening_response($text);
}

function ai_call_openai(string $prompt): array
{
    if (OPENAI_API_KEY === '') {
        throw new Exception('AI screening is not configured yet (missing OpenAI API key).');
    }

    $body = json_encode([
        'model'       => OPENAI_MODEL,
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.1,
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Could not reach OpenAI: ' . $curlError);
    }

    $data = json_decode($response, true);

    if (isset($data['error'])) {
        throw new Exception('OpenAI error: ' . $data['error']['message']);
    }

    $text = $data['choices'][0]['message']['content'] ?? '';
    if (trim($text) === '') {
        throw new Exception('OpenAI returned an empty response.');
    }

    return parse_ai_screening_response($text);
}
