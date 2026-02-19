<?php
session_start();
include 'config/db.php';
include 'includes/functions.php';

// ၁။ ဘယ်သူက Logout လုပ်သွားတယ်ဆိုတာ Audit Log မှာ မှတ်တမ်းတင်ခြင်း
if (isset($_SESSION['user_id'])) {
    // Database concept: Accountability & Audit Trail
    logActivity($pdo, "User logged out from the system");
}

// ၂။ Session variables အားလုံးကို ဖျက်ပစ်ခြင်း
$_SESSION = array();

// ၃။ Session Cookie ကို ဖျက်ခြင်း (လုံခြုံရေး ပိုကောင်းစေရန်)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ၄။ Session ကို လုံးဝ ဖျက်ဆီးပစ်ခြင်း
session_destroy();

// ၅။ Login Page (index.php) သို့ ပြန်ပို့ခြင်း
header("Location: index.php?msg=logged_out");
exit();
?>