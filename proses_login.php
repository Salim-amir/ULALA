<?php
// Mulai sesi untuk menyimpan data login
session_start();

// Panggil koneksi database
require_once 'config/db.php';

// Pastikan request datang dari form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tangkap inputan dari form login
    // Di HTML Claude, name-nya adalah "username" tapi id-nya "login_identifier" (bisa email/username)
    $identifier = trim($_POST['username']);
    $password   = $_POST['password'];

    // Validasi kosong
    if (empty($identifier) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit;
    }

    try {
        // Cari user berdasarkan username ATAU email
        $stmt = $pdo->prepare("SELECT id, nama_lengkap, username, password_hash, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        // Jika user ditemukan
        if ($user) {
            // Verifikasi kecocokan password yang diinput dengan hash di database
            if (password_verify($password, $user['password_hash'])) {

                // PASSWORD BENAR! Buat Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']        = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Arahkan ke halaman utama
                header("Location: dashboard.php");
                exit;
            } else {
                // Password Salah
                header("Location: login.php?error=invalid_credentials");
                exit;
            }
        } else {
            // Username/Email tidak ditemukan
            header("Location: login.php?error=user_not_found");
            exit;
        }
    } catch (PDOException $e) {
        // Jika ada error database
        die("Error Login: " . $e->getMessage());
    }
} else {
    // Jika diakses langsung via URL
    header("Location: login.php");
    exit;
}
