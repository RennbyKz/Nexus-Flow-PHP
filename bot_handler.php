<?php
// bot_handler.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

include_once 'config/db.php'; 
include_once 'includes/functions.php';
include_once 'includes/generate_report.php';
$full_path = trim(ob_get_clean());
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) exit;

$callback_query = $update['callback_query'] ?? null;
if ($callback_query) {
    $chat_id = (string)$callback_query['message']['chat']['id'];
    $message = $callback_query['data']; 
} else {
    $chat_id = (string)($update['message']['chat']['id'] ?? '');
    $message = $update['message']['text'] ?? '';
}

try {
    // ၁။ Access Control: ဝန်ထမ်းဟုတ်မဟုတ် စစ်ဆေးခြင်း
    $authStmt = $pdo->prepare("
        SELECT u.username, r.role_name, b.branch_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        LEFT JOIN branches b ON u.branch_id = b.branch_id 
        WHERE u.telegram_chat_id = ? AND u.status = 'ACTIVE'
    ");
    $authStmt->execute([$chat_id]);
    $user = $authStmt->fetch();

        if (!$user) {
            // ၁။ User ဆီကို ပြန်ပို့မည့်စာ
            $user_reply = "🚫 <b>Unauthorized Access!</b>\nသင့်ကို Omni Flow စနစ်မှ အသိအမှတ်မပြုထားပါ။\n<b>Your Chat ID:</b> <code>$chat_id</code>\nAdmin ထံမှ ခွင့်ပြုချက်တောင်းခံပါ။";
            sendTelegramNotification($user_reply, $chat_id);

            // ၂။ Admin ဆီကို Noti ပို့ခြင်း
            $admin_id = "1162211533"; // သင့် Chat ID
            $admin_noti = "⚠️ <b>Access Request</b>\n--------------------------------\n"
                        . "အမည်မသိအသုံးပြုသူတစ်ဦး Bot ကို အသုံးပြုရန် ကြိုးစားနေပါသည်။\n"
                        . "<b>Chat ID:</b> <code>$chat_id</code>\n"
                        . "<b>Username:</b> @" . ($update['message']['from']['username'] ?? 'N/A');
            
            sendTelegramNotification($admin_noti, $admin_id);
            exit;
        }

    $current_role = strtoupper(trim($user['role_name']));

    // ၂။ Main Menu Buttons (Role အလိုက် ခလုတ်များကို Dynamic ပြောင်းလဲခြင်း)
    $row1 = [['text' => '📊 Status', 'callback_data' => '/status'], ['text' => '⚠️ Alerts', 'callback_data' => '/alerts']];
    $row2 = [['text' => '🔍 Find Item', 'callback_data' => '/search_info']];

    if ($current_role === 'ADMIN' || $current_role === 'AUDITOR') {
        $row1[] = ['text' => '🛡️ Audit Logs', 'callback_data' => '/audit'];
        $row2[] = ['text' => '📜 Generate PDF', 'callback_data' => '/report'];
    }

    $main_menu = ['inline_keyboard' => [$row1, $row2]];

    // ၃။ Commands Logic

    if ($message == "/start" || $message == "/menu") {
        $reply = "🛠 <b>Omni Flow Console</b>\nမင်္ဂလာပါ <b>" . htmlspecialchars($user['username']) . "</b>\nစနစ်ကို ထိန်းချုပ်ရန် ခလုတ်များကို အသုံးပြုပါ။";
        sendTelegramNotification($reply, $chat_id, $main_menu);
    } 

    elseif ($message == "/status") {
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(quantity * price) as val FROM products WHERE is_deleted = FALSE");
        $data = $stmt->fetch();
        $reply = "📊 <b>System Summary</b>\n"
               . "--------------------------------\n"
               . "📦 SKU: " . number_format($data['total'] ?? 0) . "\n"
               . "💰 Value: " . number_format($data['val'] ?? 0) . " MMK\n"
               . "👤 User: " . $user['username'];
        sendTelegramNotification($reply, $chat_id, $main_menu);
    }

    elseif ($message == "/alerts") {
        $stmt = $pdo->query("SELECT product_name, quantity FROM products WHERE quantity < 10 AND is_deleted = FALSE LIMIT 5");
        $items = $stmt->fetchAll();
        $reply = $items ? "⚠️ <b>Low Stock Detected</b>\n--------------------------------\n" : "✅ Stock အားလုံး ပုံမှန်ရှိပါသည်။";
        if ($items) { foreach($items as $i) $reply .= "• " . $i['product_name'] . " (Qty: " . $i['quantity'] . ")\n"; }
        sendTelegramNotification($reply, $chat_id, $main_menu);
    }

    elseif ($message == "/audit") {
        if ($current_role !== 'ADMIN' && $current_role !== 'AUDITOR') {
            sendTelegramNotification("🚫 ခွင့်ပြုချက်မရှိပါ။", $chat_id, $main_menu);
        } else {
            $stmt = $pdo->query("SELECT u.username, l.action FROM audit_logs l JOIN users u ON l.user_id = u.user_id ORDER BY l.timestamp DESC LIMIT 3");
            $logs = $stmt->fetchAll();
            $reply = "🛡️ <b>Security Logs</b>\n--------------------------------\n";
            foreach($logs as $l) $reply .= "👤 " . $l['username'] . ": " . $l['action'] . "\n";
            sendTelegramNotification($reply, $chat_id, $main_menu);
        }
    }

    elseif ($message == "/report") {
        if ($current_role !== 'ADMIN' && $current_role !== 'AUDITOR') {
            sendTelegramNotification("🚫 Access Denied! Admin နှင့် Auditor များသာ ထုတ်ယူခွင့်ရှိသည်။", $chat_id);
        } else {
            sendTelegramNotification("📄 <b>Audit Report</b> ထုတ်ပေးနေပါသည်။ ခဏစောင့်ပါ။", $chat_id);
            
            // PDF Script လမ်းကြောင်းကို ခေါ်ယူပြီး file path ကို ယူခြင်း
            ob_start();
            include 'includes/generate_report.php'; 
            $full_path = trim(ob_get_clean()); // echo လုပ်ထားသော file path တစ်ခုတည်းကိုသာ ယူသည်

            if (!empty($full_path) && file_exists($full_path)) {
                $caption = "📊 <b>Omni Flow Audit Report</b>\n👤 By: " . htmlspecialchars($user['username']);
                sendTelegramDocument($chat_id, $full_path, $caption);
                unlink($full_path); // ပို့ပြီးလျှင် Server မှ ဖျက်ထုတ်ပါ
            } else {
                sendTelegramNotification("❌ Report ထုတ်ရာတွင် Error ဖြစ်ပွားခဲ့ပါသည်။ Path ကိုစစ်ဆေးပါ။", $chat_id);
            }
        }
    }

    elseif (strpos($message, '/find') === 0) {
        $search = trim(str_replace('/find', '', $message));
        $stmt = $pdo->prepare("SELECT product_name, quantity FROM products WHERE product_name ILIKE ? LIMIT 3");
        $stmt->execute(["%$search%"]);
        $res = $stmt->fetchAll();
        $reply = $res ? "🔍 <b>Search Results:</b>\n" : "❌ မတွေ့ရှိပါ။";
        foreach($res as $r) $reply .= "📦 " . $r['product_name'] . ": " . $r['quantity'] . "\n";
        sendTelegramNotification($reply, $chat_id, $main_menu);
    }

    elseif ($message == "/search_info") {
        sendTelegramNotification("🔍 ပစ္စည်းရှာရန် <code>/find [အမည်]</code> ဟု ရိုက်ပို့ပါ။", $chat_id, $main_menu);
    }

} catch (PDOException $e) {
    sendTelegramNotification("⚠️ Error: " . $e->getMessage(), $chat_id);
}