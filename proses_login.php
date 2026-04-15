<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pastikan 'username' sesuai dengan atribut name di <input> HTML kamu
    $identifier = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit;
    }

    try {
        // PERBAIKAN: Tambahkan kolom 'email' di SELECT agar bisa disimpan ke session
        $stmt = $pdo->prepare("SELECT id, nama_lengkap, username, email, password_hash, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                // Buat Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email']; // Sekarang ini tidak akan error lagi
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit;
            } else {
                header("Location: login.php?error=invalid_credentials");
                exit;
            }
        } else {
            header("Location: login.php?error=user_not_found");
            exit;
        }
    } catch (PDOException $e) {
        die("Error Login: " . $e->getMessage());
    }
} else {
    header("Location: login.php");
    exit;
}
