<?php
/**
 * sms/sms_helper.php
 * -----------------------------------------------------------
 * Module 11 - SMS System
 * Real integration with Notify.lk (https://developer.notify.lk):
 *   POST https://app.notify.lk/api/v1/send
 *   fields: user_id, api_key, sender_id, to, message
 *   response: {"status":"success","data":"Sent"} on success
 *
 * Every attempt (real or simulated) is written to `sms_logs`
 * so Admin Reports (Module 18) can show delivery history.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/sms_config.php';

/**
 * Normalize a Sri Lankan local number (07XXXXXXXX) to the
 * international format Notify.lk expects (947XXXXXXXX).
 */
function normalize_lk_phone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);

    if (str_starts_with($digits, '0')) {
        return '94' . substr($digits, 1);
    }
    if (!str_starts_with($digits, '94')) {
        return '94' . $digits;
    }
    return $digits;
}

/**
 * Send an SMS (or simulate it) and log the attempt.
 * Returns true if sent/simulated, false if the real API call failed.
 */
function send_sms(PDO $pdo, ?int $patientId, string $phone, string $message): bool
{
    $to = normalize_lk_phone($phone);
    $status = 'failed';
    
$credentialsMissing =
    empty(SMS_USER_ID) ||
    empty(SMS_API_KEY);


    if (SMS_SIMULATION_MODE || $credentialsMissing) {
        $status = 'simulated';
    } elseif (!function_exists('curl_init')) {
        $status = 'failed'; // PHP curl extension not enabled -- enable it in php.ini
    } else {
        try {
            $ch = curl_init('https://app.notify.lk/api/v1/send');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'user_id'   => SMS_USER_ID,
                    'api_key'   => SMS_API_KEY,
                    'sender_id' => SMS_SENDER_ID,
                    'to'        => $to,
                    'message'   => $message,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response !== false) {
                $json = json_decode($response, true);
                $status = (isset($json['status']) && $json['status'] === 'success') ? 'sent' : 'failed';
            }
        } catch (Throwable $e) {
            $status = 'failed';
        }
    }

    $log = $pdo->prepare(
        "INSERT INTO sms_logs (patient_id, phone, message, status) VALUES (:pid, :phone, :msg, :status)"
    );
    $log->execute(['pid' => $patientId, 'phone' => $to, 'msg' => $message, 'status' => $status]);

    return in_array($status, ['sent', 'simulated'], true);
}

/**
 * Build the standard "booking confirmed" SMS text for an appointment.
 */
function build_booking_sms(string $doctorName, int $queueNumber, string $date, string $time): string
{
    return "Appointment Confirmed!\n"
         . "Dr. {$doctorName}\n"
         . "Queue No: {$queueNumber}\n"
         . "Date: " . date('d/m/Y', strtotime($date)) . "\n"
         . "Time: " . date('h:i A', strtotime($time)) . "\n"
         . "Please arrive 15 minutes early.";
}
