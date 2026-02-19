<?php
// ၁။ Authentication စစ်ဆေးခြင်း (Login မဝင်ထားလျှင် Login Page သို့ ပို့မည်)
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }
}

// ၂။ Role-based Access Control (RBAC) - သတ်မှတ်ထားသော Role သာ ပေးဝင်မည်
function authorize($allowed_roles = []) {
    if (!isset($_SESSION['role']) || !in_array(strtoupper($_SESSION['role']), $allowed_roles)) {
        echo "<script>alert('Access Denied: You do not have permission.'); window.location='../index.php';</script>";
        exit();
    }
}

// ၃။ Audit Logging (အချက် ၁၀ - ဘယ်သူ ဘာလုပ်သွားလဲ မှတ်တမ်းတင်ခြင်း)
function logActivity($pdo, $action) {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        try {
            // ⭐ 'timestamp' column နာမည်ကို အသုံးပြုထားပါသည်
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, timestamp) VALUES (?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$userId, $action]);
        } catch (PDOException $e) {
            error_log("Log failed: " . $e->getMessage());
        }
    }
}

// ၄။ Smart Stock Alert (အချက် ၇ - JSONB metadata မှ threshold ကို စစ်ဆေးခြင်း)
function isLowStock($current_qty, $metadata_json) {
    $metadata = json_decode($metadata_json, true);
    // threshold မပါလျှင် default 5 ဟု သတ်မှတ်မည်
    $threshold = isset($metadata['threshold']) ? (int)$metadata['threshold'] : 5;
    return $current_qty < $threshold;
}

// ၅။ Branch Status UI Badge (ONLINE/OFFLINE ခွဲခြားပြသရန်)
function getStatusBadge($status) {
    if (strtoupper($status) == 'ONLINE') {
        return '<span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-[10px] font-bold">ONLINE</span>';
    }
    return '<span class="bg-red-100 text-red-600 px-3 py-1 rounded-full text-[10px] font-bold">OFFLINE</span>';
}

// ၆။ Distributed Transaction (အချက် ၆ နှင့် ၈ - Branch အချင်းချင်း ပစ္စည်းလွှဲခြင်း)
// ALL SUCCESS OR ALL FAIL (Atomic Operation)
function transferStock($pdo, $fromBranch, $toBranch, $productId, $qty) {
    try {
        $pdo->beginTransaction(); // ⭐ Transaction စတင်ခြင်း

        $stmt = $pdo->prepare("CALL sp_transfer_stock(?, ?, ?, ?, ?)");
        $userId = $_SESSION['user_id'] ?? null;
        $stmt->execute([$fromBranch, $toBranch, $productId, $qty, $userId]);

        $pdo->commit(); // ⭐ အောင်မြင်မှ အတည်ပြုခြင်း
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // ❌ အမှားရှိရင် ပြန်ဖျက်ခြင်း
        }
        $_SESSION['transfer_error'] = $e->getMessage();
        return false;
    }
}

// ၇။ Currency Formatting
function formatMoney($amount) {
    return '$' . number_format($amount, 2);
}

// ၈။ Database Backup Trigger (အချက် ၁၁ - Disaster Recovery)
function triggerSystemBackup() {
    // ဤနေရာတွင် pg_dump သို့မဟုတ် mysqldump command များကို exec() ဖြင့် ခေါ်ယူနိုင်သည်
    return true;
}

function getMyBranchData($pdo, $branchId) {
    // Branch ID အလိုက် ပစ္စည်းစာရင်းကို ဆွဲယူခြင်း
    $stmt = $pdo->prepare("SELECT * FROM products WHERE branch_id = ?");
    $stmt->execute([$branchId]);
    return $stmt->fetchAll();
}

function sendTelegramNotification($message, $specific_chat_id = null, $keyboard = null) {
    $apiToken = "8579952108:AAH-RwWv68eKqCyhFB_BIwpcM_F7UdZRbKM";
    $chatID = $specific_chat_id ?: "1162211533"; 

    $data = [
        'chat_id' => $chatID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    // အကယ်၍ ခလုတ်တွေ ပါလာရင် data ထဲမှာ ထည့်ပေါင်းမယ်
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }

    $url = "https://api.telegram.org/bot" . $apiToken . "/sendMessage";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
function sendTelegramDocument($chat_id, $file_path, $caption) {
    $apiToken = "8579952108:AAH-RwWv68eKqCyhFB_BIwpcM_F7UdZRbKM";
    $url = "https://api.telegram.org/bot$apiToken/sendDocument";

    // File path ကို သေချာစေရန် absolute path ယူခြင်း
    $absolute_path = realpath($file_path);

    if (!$absolute_path || !file_exists($absolute_path)) {
        error_log("Telegram Document Error: File not found at " . $file_path);
        return false;
    }

    $post_fields = [
        'chat_id'   => $chat_id,
        'document'  => new CURLFile($absolute_path),
        'caption'   => $caption,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL Error ကြောင့် ပို့မရတာမျိုး မဖြစ်စေရန်

    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log('Curl Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    return $result;
}
?>