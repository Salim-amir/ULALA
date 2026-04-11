<?php
session_start();
require_once 'config/db.php';

// Pastikan request datang dari form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username     = trim($_POST['username']);
    $email        = trim($_POST['email']);
    $password     = $_POST['password'];
    
    // Validasi sederhana
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password)) {
        header("Location: login.php?tab=register&error=empty_fields");
        exit;
    }

    // Hash password menggunakan algoritma bcrypt standar PHP
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Cek apakah email atau username sudah dipakai
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmtCheck->execute([$email, $username]);
        if ($stmtCheck->rowCount() > 0) {
            header("Location: login.php?tab=register&error=user_exists");
            exit;
        }

        // Insert ke database PostgreSQL
        $sql = "INSERT INTO users (nama_lengkap, username, email, password_hash) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama_lengkap, $username, $email, $password_hash]);

        // Jika sukses, lempar kembali ke halaman login dengan pesan sukses
        header("Location: login.php?success=register_success");
        exit;

    } catch (PDOException $e) {
        // Tangkap error jika ada masalah di database
        die("Error Registrasi: " . $e->getMessage());
    }
} else {
    // Jika ada yang iseng akses file ini langsung via URL
    header("Location: login.php");
    exit;
}
?>