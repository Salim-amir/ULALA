<?php
/**
 * layout/header.php
 * ─────────────────────────────────────────────────────────────────
 * Reusable header untuk semua halaman aplikasi (bukan auth).
 * Cara pakai: <?php include 'layout/header.php'; ?>
 *
 * Variabel yang BISA di-set sebelum include ini:
 *   $page_title  – Judul yang muncul di <title> dan topbar (default: 'Dashboard')
 *   $active_menu – Nama halaman aktif untuk highlight sidebar (default: 'dashboard')
 *                  Nilai: 'dashboard' | 'input_penjualan' | 'ai_insights'
 *                         'kelola_produk' | 'laporan'
 * ─────────────────────────────────────────────────────────────────
 */

// ── Proteksi sesi (aktifkan setelah autentikasi PHP siap) ──────────
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ── Nilai default ──────────────────────────────────────────────────
$page_title  = $page_title  ?? 'Dashboard';
$active_menu = $active_menu ?? 'dashboard';

// ── Helper: tandai menu aktif ──────────────────────────────────────
function nav_class(string $menu, string $active): string {
    return $menu === $active ? 'nav-item active' : 'nav-item';
}

// ── Data user dari sesi (placeholder sampai DB terhubung) ──────────
$user_nama   = $_SESSION['nama_lengkap'] ?? 'User';
$user_role   = $_SESSION['role']         ?? 'Admin';
$user_initials = strtoupper(substr($user_nama, 0, 1) . (strpos($user_nama, ' ') !== false ? substr($user_nama, strpos($user_nama, ' ') + 1, 1) : ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> – ULALA Smart Assistant</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
/* ==============================
   CSS VARIABLES & RESET
============================== */
:root {
  --primary: #0d7a6a;
  --primary-dark: #095f52;
  --primary-light: #e6f4f1;
  --primary-mid: #1a9e8a;
  --accent: #f0a500;
  --accent-light: #fff8e6;
  --danger: #e53e3e;
  --danger-light: #fff5f5;
  --warning: #dd6b20;
  --warning-light: #fffaf0;
  --success: #38a169;
  --success-light: #f0fff4;
  --bg: #f0f4f3;
  --surface: #ffffff;
  --surface-2: #f7faf9;
  --border: #e2ece9;
  --text-primary: #1a2e2a;
  --text-secondary: #4a6360;
  --text-muted: #8aa8a3;
  --sidebar-bg: #0f2420;
  --sidebar-text: #8cb8b0;
  --sidebar-active: #ffffff;
  --sidebar-hover: #1a3530;
  --shadow-sm: 0 1px 3px rgba(13,122,106,0.08), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-md: 0 4px 16px rgba(13,122,106,0.1), 0 2px 4px rgba(0,0,0,0.05);
  --shadow-lg: 0 8px 32px rgba(13,122,106,0.15), 0 4px 8px rgba(0,0,0,0.06);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-xl: 24px;
  --font-main: 'Plus Jakarta Sans', sans-serif;
  --font-mono: 'DM Mono', monospace;
  --sidebar-w: 240px;
  --topbar-h: 64px;
  --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font-main);
  background: var(--bg);
  color: var(--text-primary);
  min-height: 100vh;
  font-size: 14px;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

/* ==============================
   APP SHELL
============================== */
.app-shell {
  display: flex;
  min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
  width: var(--sidebar-w);
  background: var(--sidebar-bg);
  position: fixed;
  top: 0; left: 0; bottom: 0;
  display: flex;
  flex-direction: column;
  z-index: 100;
  transition: transform var(--transition);
}

.sidebar-brand {
  padding: 20px 20px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  text-decoration: none;
}

.sidebar-brand-icon {
  width: 38px; height: 38px;
  background: var(--primary);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.sidebar-brand-icon i { color: white; font-size: 16px; }

.sidebar-brand-text h2 {
  font-size: 14px;
  font-weight: 800;
  color: white;
  letter-spacing: -0.3px;
}
.sidebar-brand-text span {
  font-size: 10px;
  color: var(--sidebar-text);
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.sidebar-nav {
  flex: 1;
  padding: 16px 12px;
  overflow-y: auto;
}

.nav-section-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.25);
  padding: 0 8px;
  margin-bottom: 8px;
  margin-top: 16px;
}
.nav-section-label:first-child { margin-top: 0; }

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  color: var(--sidebar-text);
  cursor: pointer;
  transition: var(--transition);
  font-size: 13px;
  font-weight: 500;
  position: relative;
  margin-bottom: 2px;
  text-decoration: none;
  user-select: none;
}
.nav-item i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; }
.nav-item span.nav-label { flex: 1; }
.nav-item:hover { background: var(--sidebar-hover); color: rgba(255,255,255,0.85); }
.nav-item.active { background: var(--primary); color: white; }
.nav-item.active i { color: rgba(255,255,255,0.9); }

.nav-badge {
  font-size: 10px;
  font-weight: 700;
  background: var(--danger);
  color: white;
  padding: 2px 6px;
  border-radius: 20px;
}

.sidebar-bottom {
  padding: 12px;
  border-top: 1px solid rgba(255,255,255,0.06);
}

.sidebar-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  margin-bottom: 4px;
  background: rgba(255,255,255,0.04);
}

.user-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}

.sidebar-user-info h4 { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.9); }
.sidebar-user-info span { font-size: 10px; color: var(--sidebar-text); }

.nav-item.logout { color: rgba(229,62,62,0.7); }
.nav-item.logout:hover { background: rgba(229,62,62,0.12); color: #fc8181; }

/* MAIN CONTENT */
.main-content {
  margin-left: var(--sidebar-w);
  flex: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Container bar abu-abu */
.stok-mini-bar {
    width: 60px;
    height: 6px;
    background: #eee;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 4px;
}

/* Isi bar */
.stok-mini-fill {
    height: 100%;
    transition: width 0.3s ease;
}

/* Warna dinamis */
.stok-mini-fill.low { background-color: var(--danger) !important; }   /* Merah saat kritis */
.stok-mini-fill.mid { background-color: var(--warning) !important; }  /* Kuning jika perlu */
.stok-mini-fill { background-color: var(--success); }                /* Hijau jika normal */

.topbar {
  height: var(--topbar-h);
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  position: sticky;
  top: 0;
  z-index: 50;
}

.topbar-left { display: flex; align-items: center; gap: 16px; }

.hamburger-btn {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-secondary);
  font-size: 18px;
  padding: 6px;
  border-radius: var(--radius-sm);
  transition: var(--transition);
}
.hamburger-btn:hover { background: var(--bg); color: var(--primary); }

#page-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.3px;
}

.topbar-right { display: flex; align-items: center; gap: 12px; }

.topbar-btn {
  width: 36px; height: 36px;
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--border);
  background: var(--surface);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  font-size: 14px;
  transition: var(--transition);
  position: relative;
  text-decoration: none;
}
.topbar-btn:hover { border-color: var(--primary); color: var(--primary); }

.notif-dot {
  position: absolute;
  top: 6px; right: 6px;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--danger);
  border: 1.5px solid white;
}

.topbar-user { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.topbar-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--primary-mid));
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 13px;
  font-weight: 700;
}
.topbar-user-info { line-height: 1.3; }
.topbar-user-info .u-name { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.topbar-user-info .u-role { font-size: 11px; color: var(--text-muted); }

.page-body { padding: 28px; flex: 1; }

/* ==============================
   HALAMAN UMUM – Page entry anim
============================== */
.page-content { animation: fadeInUp 0.3s ease; }

/* ==============================
   DASHBOARD
============================== */
.metric-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin-bottom: 24px;
}
.metric-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 22px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}
.metric-card::after {
  content: '';
  position: absolute;
  top: 0; right: 0;
  width: 80px; height: 80px;
  border-radius: 50%;
  background: radial-gradient(circle, var(--primary-light) 0%, transparent 70%);
  transform: translate(20px, -20px);
}
.metric-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.metric-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.metric-icon-wrap {
  width: 40px; height: 40px;
  border-radius: var(--radius-sm);
  background: var(--primary-light);
  display: flex; align-items: center; justify-content: center;
}
.metric-icon-wrap i { color: var(--primary); font-size: 16px; }
.metric-badge { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
.badge-green  { background: var(--success-light); color: var(--success); }
.badge-blue   { background: #ebf8ff; color: #2b6cb0; }
.badge-teal   { background: var(--primary-light); color: var(--primary); }
.metric-label { font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
.metric-value { font-size: 26px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; font-variant-numeric: tabular-nums; }
.metric-sub   { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

/* CHART */
.chart-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 24px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  margin-bottom: 24px;
}
.chart-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 8px; }
.chart-title h3 { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
.chart-title p  { font-size: 12px; color: var(--text-muted); }
.chart-tabs { display: flex; gap: 4px; background: var(--bg); padding: 4px; border-radius: var(--radius-sm); }
.chart-tab {
  padding: 6px 14px; border: none; border-radius: 6px;
  font-family: var(--font-main); font-size: 12px; font-weight: 600;
  cursor: pointer; transition: var(--transition); color: var(--text-secondary); background: transparent;
}
.chart-tab.active { background: var(--primary); color: white; }
.chart-tab:not(.active):hover { background: var(--border); }
.chart-area { width: 100%; height: 300px; }
.bar-wrap, .bar, .bar-label, .bar-tooltip { display: none !important; }

/* BOTTOM GRID */
.dashboard-bottom { display: grid; grid-template-columns: 1fr 320px; gap: 18px; }
.stok-kritis-card, .ai-reco-card {
  background: var(--surface); border-radius: var(--radius-lg);
  border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden;
}
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--border); }
.card-header-left { display: flex; align-items: center; gap: 10px; }
.card-icon { width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 13px; }
.card-icon.warn { background: var(--warning-light); }
.card-icon.warn i { color: var(--warning); }
.card-icon.ai { background: var(--primary-light); }
.card-icon.ai i { color: var(--primary); }
.card-header h3 { font-size: 14px; font-weight: 700; }

.btn-sm {
  padding: 6px 12px; border-radius: 6px; border: 1.5px solid var(--border);
  background: transparent; font-family: var(--font-main); font-size: 11px; font-weight: 700;
  cursor: pointer; transition: var(--transition); color: var(--text-secondary);
  display: flex; align-items: center; gap: 5px; text-decoration: none;
}
.btn-sm:hover { border-color: var(--primary); color: var(--primary); }

.stok-row { display: flex; align-items: center; gap: 14px; padding: 14px 22px; border-bottom: 1px solid rgba(0,0,0,0.04); transition: var(--transition); }
.stok-row:hover { background: var(--surface-2); }
.stok-row:last-child { border-bottom: none; }
.stok-thumb { width: 40px; height: 40px; border-radius: var(--radius-sm); background: var(--bg); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 16px; flex-shrink: 0; }
.stok-info { flex: 1; min-width: 0; }
.stok-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.stok-cat  { font-size: 11px; color: var(--text-muted); }
.stok-count { text-align: right; }
.stok-count .count { font-size: 16px; font-weight: 800; color: var(--danger); font-family: var(--font-mono); }
.stok-count .count-label { font-size: 10px; color: var(--text-muted); }

.status-pill { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.4px; }
.pill-danger  { background: var(--danger-light); color: var(--danger); }
.pill-warning { background: var(--warning-light); color: var(--warning); }
.pill-info    { background: #ebf8ff; color: #2b6cb0; }

.ai-reco-card { background: var(--primary-dark); border: none; }
.ai-reco-card .card-header { border-bottom-color: rgba(255,255,255,0.1); }
.ai-reco-card .card-header h3 { color: white; }
.ai-reco-body { padding: 18px 22px; }
.ai-stars { display: flex; gap: 3px; margin-bottom: 10px; }
.ai-stars i { color: var(--accent); font-size: 12px; }
.ai-reco-body h4 { font-size: 17px; font-weight: 800; color: white; margin-bottom: 10px; line-height: 1.3; }
.ai-reco-body p  { font-size: 13px; color: rgba(255,255,255,0.65); line-height: 1.7; margin-bottom: 16px; }
.ai-highlight    { color: rgba(255,255,255,0.9); font-weight: 700; }
.btn-white {
  display: flex; align-items: center; gap: 8px; padding: 10px 18px;
  background: white; color: var(--primary-dark); border: none; border-radius: var(--radius-sm);
  font-family: var(--font-main); font-size: 12px; font-weight: 700; cursor: pointer;
  transition: var(--transition); width: 100%; justify-content: center;
}
.btn-white:hover { background: var(--primary-light); }

/* ==============================
   FORM & INPUT PENJUALAN
============================== */
.form-card { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; }
.form-card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; }
.form-card-header .fch-icon { width: 38px; height: 38px; background: var(--primary-light); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; }
.form-card-header .fch-icon i { color: var(--primary); font-size: 16px; }
.form-card-header h3 { font-size: 15px; font-weight: 700; }
.form-card-header p  { font-size: 12px; color: var(--text-muted); }
.form-card-body { padding: 24px; }
.form-row { display: grid; gap: 16px; margin-bottom: 16px; }
.grid-2 { grid-template-columns: 1fr 1fr; }
.grid-3 { grid-template-columns: 1fr 1fr 1fr; }
.grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
.form-field label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: var(--text-secondary); margin-bottom: 7px; }
.form-field input,
.form-field select,
.form-field textarea {
  width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  font-family: var(--font-main); font-size: 13px; color: var(--text-primary);
  background: var(--surface-2); outline: none; transition: var(--transition);
}
.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus { border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(13,122,106,0.1); }
.form-field select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%238aa8a3' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
}
.divider-label { display: flex; align-items: center; gap: 12px; margin: 20px 0 16px; }
.divider-label span { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); white-space: nowrap; }
.divider-label::before, .divider-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }
.metode-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.metode-option { position: relative; }
.metode-option input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.metode-label { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 14px; border: 2px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition); text-align: center; background: var(--surface-2); }
.metode-label i    { font-size: 20px; color: var(--text-muted); transition: var(--transition); }
.metode-label span { font-size: 12px; font-weight: 700; color: var(--text-secondary); transition: var(--transition); }
.metode-option input[type="radio"]:checked + .metode-label { border-color: var(--primary); background: var(--primary-light); }
.metode-option input[type="radio"]:checked + .metode-label i    { color: var(--primary); }
.metode-option input[type="radio"]:checked + .metode-label span { color: var(--primary); }
.metode-label:hover { border-color: var(--primary-mid); }
.summary-box { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px; margin-top: 20px; }
.summary-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; font-size: 13px; }
.summary-row:not(:last-child) { border-bottom: 1px dashed var(--border); }
.summary-row .label { color: var(--text-secondary); }
.summary-row .value { font-weight: 700; color: var(--text-primary); font-family: var(--font-mono); }
.summary-row.total { padding-top: 12px; margin-top: 4px; }
.summary-row.total .label { font-size: 14px; font-weight: 700; color: var(--text-primary); }
.summary-row.total .value { font-size: 18px; color: var(--primary); }
.form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }
.btn-secondary {
  padding: 10px 20px; background: transparent; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  font-family: var(--font-main); font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-secondary);
  transition: var(--transition); display: flex; align-items: center; gap: 6px;
}
.btn-secondary:hover { border-color: var(--primary); color: var(--primary); }
.btn-accent {
  padding: 10px 24px; background: var(--primary); border: none; border-radius: var(--radius-sm);
  font-family: var(--font-main); font-size: 13px; font-weight: 700; cursor: pointer; color: white;
  transition: var(--transition); display: flex; align-items: center; gap: 6px;
}
.btn-accent:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13,122,106,0.25); }
/* Menghilangkan spin button bawaan browser */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}

.qty-control {
  display: flex;
  align-items: center;
  gap: 5px;
}

.btn-qty {
  width: 35px;
  height: 35px;
  border: 1px solid #ddd;
  background: #f8f9fa;
  cursor: pointer;
  font-size: 18px;
  border-radius: 4px;
  transition: 0.2s;
}

.btn-qty:hover {
  background: #e9ecef;
}

.input-qty {
  width: 50px;
  height: 35px;
  text-align: center;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-weight: bold;
}
/* ==============================
   AI INSIGHTS
============================== */
.ai-page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.ai-page-title h2 { font-size: 20px; font-weight: 800; }
.ai-page-title p  { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
.ai-sync-btn {
  display: flex; align-items: center; gap: 8px; padding: 10px 18px;
  background: var(--primary); color: white; border: none; border-radius: var(--radius-sm);
  font-family: var(--font-main); font-size: 13px; font-weight: 700; cursor: pointer; transition: var(--transition);
  text-decoration: none;
}
.ai-sync-btn:hover { background: var(--primary-dark); }
.ai-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 24px; }
.ai-insight-card { border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md); transition: var(--transition); }
.ai-insight-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
.aic-top { padding: 22px 22px 16px; position: relative; }
.aic-top::before { content: ''; position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; border-radius: 50%; background: rgba(255,255,255,0.06); }
.aic-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; margin-bottom: 14px; }
.aic-type-icon { width: 48px; height: 48px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 14px; }
.aic-top h3 { font-size: 17px; font-weight: 800; margin-bottom: 8px; line-height: 1.3; }
.aic-top p  { font-size: 13px; line-height: 1.7; opacity: 0.75; }
.aic-meta   { padding: 14px 22px; display: flex; align-items: center; justify-content: space-between; }
.aic-score  { display: flex; flex-direction: column; }
.aic-score .score-val   { font-size: 22px; font-weight: 800; font-family: var(--font-mono); }
.aic-score .score-label { font-size: 10px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; opacity: 0.6; }
.aic-action { padding: 8px 16px; border-radius: var(--radius-sm); border: 2px solid currentColor; font-family: var(--font-main); font-size: 12px; font-weight: 700; cursor: pointer; background: transparent; transition: var(--transition); display: flex; align-items: center; gap: 6px; }
.theme-restock { background: linear-gradient(145deg, #1a4a3a, #0d7a6a); color: white; }
.theme-restock .aic-badge { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.9); }
.theme-restock .aic-type-icon { background: rgba(255,255,255,0.1); color: white; }
.theme-restock .aic-meta { border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.15); }
.theme-restock .aic-action { color: white; }
.theme-restock .aic-action:hover { background: rgba(255,255,255,0.15); }
.theme-promo { background: linear-gradient(145deg, #7b3a0a, #dd6b20); color: white; }
.theme-promo .aic-badge { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.9); }
.theme-promo .aic-type-icon { background: rgba(255,255,255,0.1); color: white; }
.theme-promo .aic-meta { border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.15); }
.theme-promo .aic-action { color: white; }
.theme-promo .aic-action:hover { background: rgba(255,255,255,0.15); }
.theme-bundling { background: linear-gradient(145deg, #2a4a7f, #3182ce); color: white; }
.theme-bundling .aic-badge { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.9); }
.theme-bundling .aic-type-icon { background: rgba(255,255,255,0.1); color: white; }
.theme-bundling .aic-meta { border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.15); }
.theme-bundling .aic-action { color: white; }
.theme-bundling .aic-action:hover { background: rgba(255,255,255,0.15); }
.ai-history-card { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; }

/* ==============================
   TABLES
============================== */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: var(--text-muted); background: var(--surface-2); border-bottom: 1px solid var(--border); white-space: nowrap; }
tbody td { padding: 13px 16px; font-size: 13px; color: var(--text-primary); border-bottom: 1px solid rgba(0,0,0,0.04); vertical-align: middle; }
tbody tr:hover { background: var(--surface-2); }
tbody tr:last-child td { border-bottom: none; }
.td-product { display: flex; align-items: center; gap: 10px; }
.td-thumb { width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--bg); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 15px; flex-shrink: 0; }
.td-product-info .p-name { font-weight: 600; font-size: 13px; }
.td-product-info .p-sku  { font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); }
.action-btns { display: flex; gap: 6px; }
.btn-edit, .btn-del { width: 30px; height: 30px; border-radius: 6px; border: 1.5px solid var(--border); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; transition: var(--transition); }
.btn-edit { color: var(--primary); }
.btn-edit:hover { background: var(--primary-light); border-color: var(--primary); }
.btn-del  { color: var(--danger); }
.btn-del:hover  { background: var(--danger-light);  border-color: var(--danger); }

/* ==============================
   KELOLA PRODUK
============================== */
.table-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); gap: 12px; flex-wrap: wrap; }
.search-input-wrap { position: relative; flex: 1; min-width: 200px; max-width: 320px; }
.search-input-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; pointer-events: none; }
.search-input-wrap input { width: 100%; padding: 9px 12px 9px 36px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: var(--font-main); font-size: 13px; background: var(--surface-2); outline: none; transition: var(--transition); color: var(--text-primary); }
.search-input-wrap input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(13,122,106,0.1); }
.toolbar-right { display: flex; gap: 8px; align-items: center; }
.btn-add { display: flex; align-items: center; gap: 7px; padding: 9px 16px; background: var(--primary); color: white; border: none; border-radius: var(--radius-sm); font-family: var(--font-main); font-size: 12px; font-weight: 700; cursor: pointer; transition: var(--transition); text-decoration: none; }
.btn-add:hover { background: var(--primary-dark); }
.stok-bar-wrap { display: flex; align-items: center; gap: 8px; }
.stok-mini-bar { width: 60px; height: 6px; border-radius: 3px; background: var(--border); overflow: hidden; }
.stok-mini-fill { height: 100%; border-radius: 3px; background: var(--primary); transition: width 0.5s ease; }
.stok-mini-fill.low { background: var(--danger); }
.stok-mini-fill.mid { background: var(--warning); }

/* ==============================
   LAPORAN
============================== */
.filter-bar { display: flex; align-items: flex-end; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); background: var(--surface-2); flex-wrap: wrap; }
.filter-field { display: flex; flex-direction: column; gap: 5px; }
.filter-field label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted); }
.filter-field input, .filter-field select { padding: 8px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: var(--font-main); font-size: 13px; background: white; outline: none; transition: var(--transition); color: var(--text-primary); min-width: 150px; }
.filter-field input:focus, .filter-field select:focus { border-color: var(--primary); }
.btn-filter { display: flex; align-items: center; gap: 6px; padding: 9px 16px; background: var(--primary); color: white; border: none; border-radius: var(--radius-sm); font-family: var(--font-main); font-size: 12px; font-weight: 700; cursor: pointer; transition: var(--transition); }
.btn-filter:hover { background: var(--primary-dark); }
.laporan-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--border); border-bottom: 1px solid var(--border); }
.ls-item { padding: 16px 20px; background: var(--surface); }
.ls-item .ls-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 4px; }
.ls-item .ls-value { font-size: 18px; font-weight: 800; color: var(--text-primary); font-family: var(--font-mono); }

/* ==============================
   OVERLAY & MOBILE
============================== */
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99; backdrop-filter: blur(2px); }

/* ==============================
   ALERT / FLASH MESSAGES
============================== */
.alert { padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 18px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
.alert-success { background: var(--success-light); color: var(--success); border: 1px solid #c6f6d5; }
.alert-danger   { background: var(--danger-light);  color: var(--danger);  border: 1px solid #fed7d7; }
.alert-warning  { background: var(--warning-light); color: var(--warning); border: 1px solid #feebc8; }

/* ==============================
   ANIMATIONS
============================== */
@keyframes fadeInUp   { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn     { from { opacity: 0; } to { opacity: 1; } }
@keyframes spin       { to { transform: rotate(360deg); } }

/* ==============================
   RESPONSIVE
============================== */
@media (max-width: 1024px) {
  .metric-grid { grid-template-columns: repeat(3, 1fr); }
  .ai-grid { grid-template-columns: 1fr 1fr; }
  .dashboard-bottom { grid-template-columns: 1fr; }
  .laporan-summary { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  :root { --sidebar-w: 240px; }
  .sidebar { transform: translateX(-100%); box-shadow: none; }
  .sidebar.open { transform: translateX(0); box-shadow: var(--shadow-lg); }
  .sidebar-overlay.visible { display: block; }
  .main-content { margin-left: 0; }
  .hamburger-btn { display: flex; }
  .topbar-user-info { display: none; }
  .page-body { padding: 16px; }
  .metric-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
  .ai-grid { grid-template-columns: 1fr; }
  .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
  .metode-grid { grid-template-columns: 1fr; }
  .laporan-summary { grid-template-columns: 1fr 1fr; }
  .metric-value { font-size: 20px; }
  .chart-tabs { display: none; }
  .form-actions { flex-direction: column; }
  .form-actions .btn-secondary, .form-actions .btn-accent { width: 100%; justify-content: center; }
}
@media (max-width: 480px) {
  .metric-grid { grid-template-columns: 1fr; }
}

/* ==============================
   SWEETALERT2 CUSTOM THEME
============================== */
.swal2-popup {
  font-family: var(--font-main) !important;
  border-radius: var(--radius-lg) !important;
  padding: 2rem 2rem 1.5rem !important;
  box-shadow: 0 20px 60px rgba(13,122,106,0.18), 0 4px 12px rgba(0,0,0,0.1) !important;
}
.swal2-title {
  font-size: 17px !important;
  font-weight: 700 !important;
  color: var(--text-primary) !important;
  padding: 0 !important;
  margin-bottom: 6px !important;
}
.swal2-html-container {
  font-size: 13px !important;
  color: var(--text-secondary) !important;
  margin: 0 !important;
}
.swal2-icon {
  border-width: 2px !important;
  width: 52px !important;
  height: 52px !important;
  margin: 0 auto 16px !important;
}
.swal2-icon .swal2-icon-content { font-size: 26px !important; }
.swal2-actions { gap: 8px !important; margin-top: 20px !important; }
.swal2-confirm {
  font-family: var(--font-main) !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  border-radius: 8px !important;
  padding: 9px 22px !important;
  background: var(--primary) !important;
  box-shadow: none !important;
  transition: background 0.2s !important;
}
.swal2-confirm:hover { background: var(--primary-dark) !important; }
.swal2-confirm.swal2-styled.btn-danger-confirm {
  background: #e53e3e !important;
}
.swal2-confirm.swal2-styled.btn-danger-confirm:hover {
  background: #c53030 !important;
}
.swal2-cancel {
  font-family: var(--font-main) !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  border-radius: 8px !important;
  padding: 9px 22px !important;
  background: var(--bg) !important;
  color: var(--text-secondary) !important;
  box-shadow: none !important;
}
.swal2-cancel:hover { background: var(--border) !important; }
.swal2-timer-progress-bar { background: var(--primary) !important; }
.swal2-icon.swal2-warning { border-color: var(--warning) !important; color: var(--warning) !important; }
.swal2-icon.swal2-error { border-color: var(--danger) !important; }
.swal2-icon.swal2-success { border-color: var(--success) !important; }
.swal2-icon.swal2-success [class^=swal2-success-line] { background: var(--success) !important; }
.swal2-icon.swal2-success .swal2-success-ring { border-color: rgba(56,161,105,0.25) !important; }
</style>
</head>
<body>

<div class="app-shell">

  <!-- SIDEBAR OVERLAY (mobile) -->
  <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="sidebar-brand" style="text-decoration:none;">
      <div class="sidebar-brand-icon"><i class="fa-solid fa-robot"></i></div>
      <div class="sidebar-brand-text">
        <h2>ULALA Smart</h2>
        <span>Assistant</span>
      </div>
    </a>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Menu Utama</div>

      <a href="dashboard.php" class="<?= nav_class('dashboard', $active_menu) ?>">
        <i class="fa-solid fa-gauge-high"></i>
        <span class="nav-label">Dashboard</span>
      </a>

      <a href="input_penjualan.php" class="<?= nav_class('input_penjualan', $active_menu) ?>">
        <i class="fa-solid fa-cash-register"></i>
        <span class="nav-label">Input Penjualan</span>
      </a>

      <a href="ai_insights.php" class="<?= nav_class('ai_insights', $active_menu) ?>">
        <i class="fa-solid fa-brain"></i>
        <span class="nav-label">AI Insights</span>
        <span class="nav-badge">3</span>
      </a>

      <div class="nav-section-label">Manajemen</div>

      <a href="kelola_produk.php" class="<?= nav_class('kelola_produk', $active_menu) ?>">
        <i class="fa-solid fa-boxes-stacked"></i>
        <span class="nav-label">Kelola Produk</span>
      </a>

      <a href="laporan.php" class="<?= nav_class('laporan', $active_menu) ?>">
        <i class="fa-solid fa-chart-bar"></i>
        <span class="nav-label">Laporan</span>
      </a>
    </nav>

    <div class="sidebar-bottom">
      <div class="sidebar-user">
        <div class="user-avatar"><?= htmlspecialchars($user_initials) ?></div>
        <div class="sidebar-user-info">
          <h4><?= htmlspecialchars($user_nama) ?></h4>
          <span><?= htmlspecialchars($user_role) ?></span>
        </div>
      </div>
      <a href="#" class="nav-item logout" onclick="konfirmasiLogout(event)">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="nav-label">Logout</span>
      </a>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger-btn" onclick="toggleSidebar()">
          <i class="fa-solid fa-bars"></i>
        </button>
        <span id="page-title"><?= htmlspecialchars($page_title) ?></span>
      </div>
      <div class="topbar-right">
        <a href="settings.php" class="topbar-btn" title="Pengaturan">
          <i class="fa-solid fa-gear"></i>
        </a>
        <div class="topbar-user">
          <div class="topbar-avatar"><?= htmlspecialchars($user_initials) ?></div>
          <div class="topbar-user-info">
            <div class="u-name"><?= htmlspecialchars($user_nama) ?></div>
            <div class="u-role"><?= htmlspecialchars($user_role) ?></div>
          </div>
        </div>
      </div>
    </header>

    <div class="page-body">
    <!-- ↑↑↑ KONTEN HALAMAN DIMULAI DI SINI (via include halaman masing-masing) ↑↑↑ -->