<?php
/**
 * logout.php
 * Menghapus semua data session dan mengarahkan kembali ke login.
 */

// 1. Mulai session agar PHP tahu session mana yang mau dihapus
session_start();

// 2. Kosongkan semua variabel $_SESSION
$_SESSION = [];

// 3. Jika menggunakan cookie untuk session (standar PHP), hapus cookie-nya juga
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan session di sisi server
session_destroy();

// 5. Arahkan kembali ke halaman login dengan pesan sukses
header("Location: login.php?success=logout");
exit;