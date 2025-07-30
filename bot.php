<?php
// ####################################################################
// ## HivalVCF 1.0 August Edition - Telegram Bot                     ##
// ## Author: AI Generated                                           ##
// ## Version: 1.0 (Procedural)                                      ##
// ## Date: July 2024                                                ##
// ####################################################################

// Report all errors for debugging, disable in production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// --- CONFIGURATION - FILL THIS IN! ---
define('BOT_TOKEN', '8352323564:AAF4ADdhLSVVVoXNT6o7qp8cz13FI5PdEJw'); // Get from @BotFather
define('PAYMENT_PROVIDER_TOKEN', '6073714100:TEST:TG_HpTW9cA5YBjYMBRzr_Z9b3QA'); // Get from @BotFather -> Payments
define('ADMIN_ID', '1151719271'); // Get from @userinfobot
define('BOT_USERNAME', 'HivalVCF_bot'); // Without @
define('CHANNEL_LINK', 'https://t.me/HivalVCF'); // Your update channel/group

define('DB_FILE', 'hivalvcf.sqlite'); // The database file created by setup.php

// --- CONSTANTS - DO NOT CHANGE ---
define('MAX_USERS', 50000);
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

define('PROMO_HOURS', 48);
define('PROMO_PRICE_USD', 4.00);
define('PROMO_REFERRALS', 4);

define('STANDARD_DAYS', 7);
define('STANDARD_PRICE_USD', 7.00);
define('STANDARD_REFERRALS', 7);

define('REFERRAL_BONUS_USD', 1.00);
define('MIN_WITHDRAWAL_USD', 2.00);


// ===================================================================
// == HELPER FUNCTIONS
// ===================================================================

/**
 * Sends a request to the Telegram API.
 * @param string $method
 * @param array $data
 * @return mixed
 */
functionapiRequest($method, $data = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL . $method);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

/**
 * Sends a message to a user.
 * @param int $chat_id
 * @param string $text
 * @param array|null $keyboard
 */
function sendMessage($chat_id, $text, $keyboard = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true,
    ];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    return apiRequest('sendMessage', $data);
}

/**
 * Sends an invoice for payment.
 * @param int $chat_id
 * @param float $amount
 * @param string $title
 * @param string $description
 * @param string $payload
 */
function sendInvoice($chat_id, $amount, $title, $description, $payload) {
    $prices = [
        ['label' => $title, 'amount' => intval($amount * 100)] // Amount in smallest currency unit (cents)
    ];
    $data = [
        'chat_id' => $chat_id,
        'title' => $title,
        'description' => $description,
        'payload' => $payload,
        'provider_token' => PAYMENT_PROVIDER_TOKEN,
        'currency' => 'USD',
        'prices' => json_encode($prices),
    ];
    return apiRequest('sendInvoice', $data);
}


// ===================================================================
// == DATABASE FUNCTIONS
// ===================================================================

/**
 * Establishes a PDO connection to the SQLite database.
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Send a critical error to admin if DB fails
            sendMessage(ADMIN_ID, "CRITICAL ERROR: Bot cannot connect to database! \nError: " . $e->getMessage());
            die("Database connection failed.");
        }
    }
    return $pdo;
}

/**
 * Gets a user from the database by their Telegram ID.
 * @param int $telegram_id
 * @return array|false
 */
function getUser($telegram_id) {
    $stmt = getDB()->prepare("SELECT * FROM users WHERE telegram_id = ?");
    $stmt->execute([$telegram_id]);
    return $stmt->fetch();
}

/**
 * Checks if a username or phone number already exists.
 * @param string $field 'username' or 'phone_number'
 * @param string $value
 * @return bool
 */
function isValueTaken($field, $value) {
    $stmt = getDB()->prepare("SELECT 1 FROM users WHERE {$field} = ?");
    $stmt->execute([$value]);
    return $stmt->fetchColumn() !== false;
}

/**
 * Counts the total number of active users.
 * @return int
 */
function getActiveUserCount() {
    $stmt = getDB()->query("SELECT COUNT(id) FROM users WHERE status = 'active'");
    return (int)$stmt->fetchColumn();
}


// ===================================================================
// == CORE BOT LOGIC FUNCTIONS
// ===================================================================

/**
 * Main function to handle user registration completion.
 * @param int $user_id The Telegram ID of the user becoming active.
 */
function completeRegistration($user_id) {
    $user = getUser($user_id);
    if (!$user || $user['status'] === 'active') {
        return; // Already active or doesn't exist
    }

    $db = getDB();
    $db->prepare("UPDATE users SET status = 'active' WHERE telegram_id = ?")->execute([$user_id]);

    // Send welcome message
    $welcome_text = "🎉 *Congratulations! You are now a fully registered member of HivalVCF!* 🎉\n\n";
    $welcome_text .= "Thank you for joining our community.\n\n";
    $welcome_text .= "To stay updated with news, announcements, and exclusive content, please join our official channel:";
    sendMessage($user_id, $welcome_text, ['inline_keyboard' => [[['text' => 'Join Our Channel', 'url' => CHANNEL_LINK]]]]);

    // Check for a referrer and award bonus if registration was via payment
    if ($user['referred_by']) {
        // We need to know if this user paid. This is tricky. Let's assume this function is called with a payment context.
        // A better way is to pass a 'method' parameter ('payment' or 'referral')
        // For simplicity, let's add the bonus if the referrer exists. The prompt implies bonus is on *payment*.
        // We'll modify the successful payment handler to call this with context.
        
        // This part is handled in the `successful_payment` block directly for clarity.
    }
}

/**
 * Shows the user their current status and options.
 * @param int $chat_id
 */
function showStatusDashboard($chat_id) {
    $user = getUser($chat_id);
    if (!$user) return;

    if ($user['status'] === 'active') {
        $text = "✅ *Your HivalVCF Status: Active*\n\n";
        $text .= "Welcome to the community!\n\n";
        $text .= "👤 *Username:* `{$user['username']}`\n";
        $text .= "📞 *Phone:* `{$user['phone_number']}`\n\n";
        $text .= "💰 *Withdrawable Balance:* `{$user['balance']}` USDT\n";
        $text .= "👥 *Completed Referrals:* `{$user['referral_count']}`\n\n";
        $text .= "You can earn more by inviting friends! You get `1 USDT` for every friend who registers with payment.";
        sendMessage($chat_id, $text);
        return;
    }
    
    if ($user['status'] === 'disqualified') {
        sendMessage($chat_id, "❌ *Your Status: Disqualified*\n\nUnfortunately, you did not complete the registration requirements within the 7-day timeframe. You are no longer eligible to join this program.");
        return;
    }

    // If pending registration
    $reg_timestamp = $user['registration_timestamp'];
    $now = time();
    
    $promo_ends = $reg_timestamp + (PROMO_HOURS * 3600);
    $deadline_ends = $reg_timestamp + (STANDARD_DAYS * 86400);

    $is_promo_active = $now < $promo_ends;

    $text = "⏳ *Your HivalVCF Status: Pending Registration*\n\n";
    $text .= "Complete your registration to become a full member and get access to the 25,000 contact list.\n\n";
    $text .= "You have two ways to complete your registration:\n";
    $text .= "1️⃣ *Pay the fee.*\n";
    $text .= "2️⃣ *Invite friends.*\n\n";

    $keyboard_buttons = [];

    // --- Time Left Calculation ---
    $time_left_total_seconds = $deadline_ends - $now;
    $days_left = floor($time_left_total_seconds / 86400);
    $hours_left = floor(($time_left_total_seconds % 86400) / 3600);
    $text .= "⏰ *TIME LEFT TO REGISTER:* {$days_left} days, {$hours_left} hours\n\n";

    if ($is_promo_active) {
        $time_left_promo_seconds = $promo_ends - $now;
        $promo_hours = floor($time_left_promo_seconds / 3600);
        $promo_minutes = floor(($time_left_promo_seconds % 3600) / 60);

        $text .= "🔥 *SPECIAL PROMO (FOMO)!* 🔥\n";
        $text .= "You have *{$promo_hours}h {$promo_minutes}m* left to use the promo offer!\n";
        $text .= "▪️ Pay just *".PROMO_PRICE_USD." USDT*.\n";
        $text .= "▪️ OR Invite *".PROMO_REFERRALS." friends* who complete registration.\n\n";
        
        $keyboard_buttons[] = [['text' => '🔥 Pay '.PROMO_PRICE_USD.' USDT (Promo)', 'callback_data' => 'pay_promo']];
    }

    $text .= "--- *Standard Offer* ---\n";
    $text .= "▪️ Pay *".STANDARD_PRICE_USD." USDT*.\n";
    $text .= "▪️ OR Invite *".STANDARD_REFERRALS." friends* who complete registration.\n\n";

    $keyboard_buttons[] = [['text' => '💳 Pay '.STANDARD_PRICE_USD.' USDT', 'callback_data' => 'pay_standard']];
    
    $referral_link = "https://t.me/" . BOT_USERNAME . "?start=ref_{$user['telegram_id']}";
    $text .= "--- *Your Progress* ---\n";
    $text .= "👥 *Completed Referrals:* `{$user['referral_count']} / " . ($is_promo_active ? PROMO_REFERRALS : STANDARD_REFERRALS) . "`\n";
    $text .= "🔗 *Your Referral Link:*\n`{$referral_link}`";
    
    $keyboard_buttons[] = [['text' => '🔄 Refresh Status', 'callback_data' => 'show_status']];

    sendMessage($chat_id, $text, ['inline_keyboard' => $keyboard_buttons]);
}

/**
 * Poor Man's Cron Job: Disqualify users who missed the deadline.
 * This should be run on every interaction.
 */
function runDisqualificationCheck() {
    $db = getDB();
    $deadline_timestamp = time() - (STANDARD_DAYS * 86400);
    
    $stmt = $db->prepare("SELECT telegram_id FROM users WHERE status LIKE '%pending%' AND registration_timestamp < ?");
    $stmt->execute([$deadline_timestamp]);
    $expired_users = $stmt->fetchAll();
    
    if (count($expired_users) > 0) {
        $update_stmt = $db->prepare("UPDATE users SET status = 'disqualified' WHERE telegram_id = ?");
        foreach($expired_users as $user) {
            $update_stmt->execute([$user['telegram_id']]);
            sendMessage($user['telegram_id'], "❌ *Registration Deadline Missed*\n\nYour 7-day window to complete registration has expired. You are now disqualified from the HivalVCF program.");
        }
    }
}


// ===================================================================
// == MAIN SCRIPT EXECUTION
// ===================================================================

$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    exit();
}

// Run the disqualification check on every valid update
runDisqualificationCheck();

// Initialize variables
$message = $update['message'] ?? null;
$callback_query = $update['callback_query'] ?? null;
$pre_checkout_query = $update['pre_checkout_query'] ?? null;
$chat_id = $message['chat']['id'] ?? $callback_query['from']['id'] ?? $pre_checkout_query['from']['id'] ?? null;
$user_id = $message['from']['id'] ?? $callback_query['from']['id'] ?? $pre_checkout_query['from']['id'] ?? null;
$text = $message['text'] ?? null;

if (!$chat_id || !$user_id) {
    exit();
}

// Get user data from DB
$user = getUser($user_id);

// --- HANDLE CALLBACK QUERIES (BUTTON CLICKS) ---
if ($callback_query) {
    $callback_data = $callback_query['data'];
    $callback_id = $callback_query['id'];

    if ($callback_data === 'pay_promo') {
        sendInvoice($chat_id, PROMO_PRICE_USD, 'HivalVCF Promo Entry', '48-hour promotional entry fee.', 'hival_promo_reg_' . $user_id);
    } elseif ($callback_data === 'pay_standard') {
        sendInvoice($chat_id, STANDARD_PRICE_USD, 'HivalVCF Standard Entry', 'Standard 7-day entry fee.', 'hival_std_reg_' . $user_id);
    } elseif ($callback_data === 'show_status') {
        // Edit message to prevent "loading" icon
        apiRequest('answerCallbackQuery', ['callback_query_id' => $callback_id]);
        showStatusDashboard($chat_id);
        exit(); // Important to exit after handling callback
    }
    
    // Acknowledge the callback
    apiRequest('answerCallbackQuery', ['callback_query_id' => $callback_id]);
    exit();
}

// --- HANDLE PRE-CHECKOUT QUERIES (PAYMENT CONFIRMATION) ---
if ($pre_checkout_query) {
    // Here you can add logic to check if the user is eligible to pay
    apiRequest('answerPreCheckoutQuery', [
        'pre_checkout_query_id' => $pre_checkout_query['id'],
        'ok' => true
    ]);
    exit();
}

// --- HANDLE SUCCESSFUL PAYMENTS ---
if (isset($message['successful_payment'])) {
    $payload = $message['successful_payment']['invoice_payload'];
    
    // Complete the user's registration
    $db = getDB();
    $db->prepare("UPDATE users SET status = 'active' WHERE telegram_id = ?")->execute([$user_id]);

    $welcome_text = "🎉 *Payment Successful! You are now a fully registered member of HivalVCF!* 🎉\n\n";
    $welcome_text .= "To stay updated with news, announcements, and exclusive content, please join our official channel:";
    sendMessage($user_id, $welcome_text, ['inline_keyboard' => [[['text' => 'Join Our Channel', 'url' => CHANNEL_LINK]]]]);

    // Award bonus to referrer
    if ($user && $user['referred_by']) {
        $referrer_id = $user['referred_by'];
        $db->prepare("UPDATE users SET balance = balance + ?, referral_count = referral_count + 1 WHERE telegram_id = ?")->execute([REFERRAL_BONUS_USD, $referrer_id]);
        sendMessage($referrer_id, "💰 *Referral Bonus!* \nYou've earned `".REFERRAL_BONUS_USD." USDT` because your referred user has just completed their registration via payment!");

        // Check if the referrer has now met their own goal
        $referrer = getUser($referrer_id);
        if ($referrer && $referrer['status'] !== 'active') {
             $reg_timestamp = $referrer['registration_timestamp'];
             $now = time();
             $promo_ends = $reg_timestamp + (PROMO_HOURS * 3600);
             $is_promo_active = $now < $promo_ends;

             $referrals_needed = $is_promo_active ? PROMO_REFERRALS : STANDARD_REFERRALS;

             if ($referrer['referral_count'] >= $referrals_needed) {
                 completeRegistration($referrer_id);
             }
        }
    }
    exit();
}


// --- HANDLE REGULAR MESSAGES AND COMMANDS ---
if ($text) {
    if (strpos($text, '/start') === 0) {
        
        if ($user) {
            sendMessage($chat_id, "👋 Welcome back! Let's check your status.");
            showStatusDashboard($chat_id);
        } else {
            // New user registration
            if (getActiveUserCount() >= MAX_USERS) {
                sendMessage($chat_id, "🙏 We're sorry, but HivalVCF has reached its maximum capacity of ".MAX_USERS." users. We are no longer accepting new registrations at this time. Thank you for your interest!");
                exit();
            }

            // Handle referral
            $referred_by = null;
            $parts = explode(' ', $text);
            if (isset($parts[1]) && strpos($parts[1], 'ref_') === 0) {
                $referred_by = (int)str_replace('ref_', '', $parts[1]);
            }

            // Create new user record
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO users (telegram_id, status, registration_timestamp, referred_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, 'collecting_username', time(), $referred_by]);

            $welcome_msg = "👋 *Welcome to HivalVCF 1.0 - August Edition!* \n\nLet's get you registered. This is a one-time setup and your details cannot be changed later.\n\nPlease enter your desired *unique username* (e.g., JohnDoe123).";
            sendMessage($chat_id, $welcome_msg);
        }

    } elseif ($text === '/status') {
        if ($user) {
            showStatusDashboard($chat_id);
        } else {
            sendMessage($chat_id, "You are not registered yet. Please press /start to begin.");
        }

    } elseif ($text === '/withdraw') {
        if (!$user || $user['status'] !== 'active') {
            sendMessage($chat_id, "You must be an active member to make a withdrawal.");
            exit();
        }
        if ($user['balance'] < MIN_WITHDRAWAL_USD) {
            sendMessage($chat_id, "❌ *Withdrawal Failed*\n\nYour balance is `{$user['balance']} USDT`. The minimum withdrawal amount is `".MIN_WITHDRAWAL_USD." USDT`.");
        } else {
            // Manual withdrawal process
            $amount_to_withdraw = $user['balance'];
            
            // Set user balance to 0 in DB
            getDB()->prepare("UPDATE users SET balance = 0 WHERE telegram_id = ?")->execute([$user_id]);

            // Notify admin
            $admin_message = "🏧 *New Withdrawal Request*\n\n";
            $admin_message .= "User: `{$user['username']}` (ID: `{$user_id}`)\n";
            $admin_message .= "Amount: `{$amount_to_withdraw} USDT`\n\n";
            $admin_message .= "Please process this payment to the user's Telegram Wallet manually.";
            sendMessage(ADMIN_ID, $admin_message);
            
            // Notify user
            sendMessage($chat_id, "✅ *Withdrawal Request Sent*\n\nYour request to withdraw `{$amount_to_withdraw} USDT` has been submitted. It will be processed manually by an administrator within 24-48 hours.");
        }

    } else {
        // Handle registration input (state machine based on status)
        if ($user) {
            if ($user['status'] === 'collecting_username') {
                if (!preg_match('/^[a-zA-Z0-9_]{4,32}$/', $text)) {
                    sendMessage($chat_id, "❌ Invalid username. Please use only letters, numbers, and underscores (_). It must be between 4 and 32 characters long. Try again.");
                } elseif (isValueTaken('username', $text)) {
                    sendMessage($chat_id, "❌ This username is already taken. Please choose another one.");
                } else {
                    getDB()->prepare("UPDATE users SET username = ?, status = 'collecting_phone' WHERE telegram_id = ?")->execute([$text, $user_id]);
                    sendMessage($chat_id, "✅ Great! Username set to `{$text}`.\n\nNow, please enter your unique phone number. It *must* start with a `+` and include your country code (e.g., `+14155552671`).");
                }
            } elseif ($user['status'] === 'collecting_phone') {
                if (!preg_match('/^\+[1-9]\d{1,14}$/', $text)) {
                    sendMessage($chat_id, "❌ Invalid phone number format. It must start with a `+` followed by your country code and number (no spaces or dashes). Example: `+14155552671`. Please try again.");
                } elseif (isValueTaken('phone_number', $text)) {
                    sendMessage($chat_id, "❌ This phone number is already registered in our system. Please use a unique one.");
                } else {
                    getDB()->prepare("UPDATE users SET phone_number = ?, status = 'pending' WHERE telegram_id = ?")->execute([$text, $user_id]);
                    sendMessage($chat_id, "✅ Phone number set! Your initial registration is complete.");
                    // Show the main dashboard now
                    showStatusDashboard($chat_id);
                }
            } else {
                 // If user is already pending or active, treat other text as a request for status
                 if ($user['status'] !== 'disqualified') {
                    showStatusDashboard($chat_id);
                 }
            }
        } else {
            sendMessage($chat_id, "I'm not sure what you mean. Please press /start to begin.");
        }
    }
}
?>
