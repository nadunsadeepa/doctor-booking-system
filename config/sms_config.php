<?php
/**
 * config/sms_config.php
 * -----------------------------------------------------------
 * Notify.lk credentials (a Sri Lankan SMS gateway — one of the
 * two options named in the spec, alongside Twilio).
 * Get your User ID / API Key from: https://app.notify.lk (Settings > API)
 *
 * SMS_SIMULATION_MODE = true (default): no real SMS is sent.
 * Every message is still logged to `sms_logs` with status
 * 'simulated' so you can build/demo the whole flow with zero
 * cost and zero risk of misfires. Flip it to false once you've
 * filled in real credentials below.
 * -----------------------------------------------------------
 */

define('SMS_USER_ID', '32587');           // from app.notify.lk settings
define('SMS_API_KEY', '9CWByEWmUK3ARD4FtriF');           // from app.notify.lk settings
define('SMS_SENDER_ID', 'NotifyDEMO'); // replace with your approved sender ID (demo ID can't send OTP-style content)
define('SMS_SIMULATION_MODE', false);
