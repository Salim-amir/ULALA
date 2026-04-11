<?php
/**
 * settings.php
 * Halaman pengaturan aplikasi:
 *  - Profil pengguna
 *  - Pengaturan toko (nama, info kontak)
 *  - Manajemen Kategori (CRUD ringan)
 *  - Ubah password
 */
// session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require_once 'config/db.php';

$page_title  = 'Pengaturan';
$active_menu = 'settings';

include 'layout/header.php';
// ── Flash dari proses_settings.php ────────────────────────────────
$flash_type = '';
$flash_msg  = '';
if (isset($_GET['success'])) {
    $flash_type = 'success';
    $flash_msg  = match($_GET['success']) {
        'profil'      => 'Data profil berhasil disimpan.',
        'password'    => 'Password berhasil diubah.',
        'kat_added'   => 'Kategori baru berhasil ditambahkan.',
        'kat_deleted' => 'Kategori berhasil dihapus.',
        default       => 'Perubahan berhasil disimpan.',
    };
}
if (isset($_GET['error'])) {
    $flash_type = 'danger';
    $flash_msg  = match($_GET['error']) {
        'wrong_password'    => 'Password lama tidak sesuai.',
        'password_mismatch' => 'Konfirmasi password baru tidak cocok.',
        'kat_in_use'        => 'Kategori tidak bisa dihapus karena masih digunakan produk.',
        'required'          => 'Semua field wajib diisi.',
        'db_error'          => 'Terjadi kesalahan database.',
        default             => 'Terjadi kesalahan.',
    };
}

// ── Ambil daftar kategori ─────────────────────────────────────────
$kategori_list = $pdo->query("
    SELECT k.id, k.nama_kategori, k.deskripsi,
           COUNT(p.id) AS jumlah_produk
    FROM kategori k
    LEFT JOIN produk p ON p.kategori_id = k.id
    GROUP BY k.id
    ORDER BY k.nama_kategori
")->fetchAll();

// ── Info pengguna dari session ────────────────────────────────────
$user = [
    'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'Admin',
    'username'     => $_SESSION['username']     ?? 'admin',
    'email'        => $_SESSION['email']        ?? 'admin@toko.com',
    'role'         => $_SESSION['role']         ?? 'Administrator',
];

// ── Tab aktif ─────────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'profil';

?>

<style>
.settings-layout { display: grid; grid-template-columns: 220px 1fr; gap: 24px; }
.settings-nav { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden; height: fit-content; position: sticky; top: calc(var(--topbar-h) + 16px); }
.settings-nav-item { display: flex; align-items: center; gap: 10px; padding: 12px 18px; font-size: 13px; font-weight: 500; color: var(--text-secondary); cursor: pointer; text-decoration: none; transition: var(--transition); border-left: 3px solid transparent; }
.settings-nav-item:hover { background: var(--surface-2); color: var(--primary); }
.settings-nav-item.active { background: var(--primary-light); color: var(--primary); border-left-color: var(--primary); font-weight: 700; }
.settings-nav-item i { width: 18px; text-align: center; font-size: 14px; }
.settings-nav-divider { height: 1px; background: var(--border); margin: 4px 0; }
.settings-panel { display: none; }
.settings-panel.active { display: block; animation: fadeInUp 0.25s ease; }
.section-title { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
.section-desc  { font-size: 12px; color: var(--text-muted); margin-bottom: 20px; }
.kat-row { display: flex; align-items: center; gap: 12px; padding: 11px 16px; border-bottom: 1px solid rgba(0,0,0,0.04); }
.kat-row:last-child { border-bottom: none; }
.kat-row:hover { background: var(--surface-2); }
.kat-name { font-size: 13px; font-weight: 600; flex: 1; }
.kat-count { font-size: 12px; color: var(--text-muted); font-family: var(--font-mono); }
@media (max-width: 768px) {
  .settings-layout { grid-template-columns: 1fr; }
  .settings-nav { position: static; display: flex; overflow-x: auto; gap: 0; }
  .settings-nav-item { border-left: none; border-bottom: 3px solid transparent; white-space: nowrap; }
  .settings-nav-item.active { border-left-color: transparent; border-bottom-color: var(--primary); }
  .settings-nav-divider { display: none; }
}
</style>

<div class="page-content">

  <?php if ($flash_msg): ?>
    <div id="php-flash" class="alert alert-<?= $flash_type ?>" style="margin-bottom:16px;">
      <i class="fa-solid <?= $flash_type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
      <?= htmlspecialchars($flash_msg) ?>
    </div>
  <?php endif; ?>

  <div class="settings-layout">

    <!-- ── Nav Sidebar ──────────────────────────────────────────── -->
    <nav class="settings-nav">
      <a href="?tab=profil"    class="settings-nav-item <?= $tab === 'profil'   ? 'active' : '' ?>">
        <i class="fa-regular fa-user"></i> Profil Saya
      </a>
      <a href="?tab=password"  class="settings-nav-item <?= $tab === 'password' ? 'active' : '' ?>">
        <i class="fa-solid fa-lock"></i> Ubah Password
      </a>
      <div class="settings-nav-divider"></div>
      <a href="?tab=kategori"  class="settings-nav-item <?= $tab === 'kategori' ? 'active' : '' ?>">
        <i class="fa-solid fa-tags"></i> Kategori Produk
      </a>
      <div class="settings-nav-divider"></div>
      <a href="?tab=tentang"   class="settings-nav-item <?= $tab === 'tentang'  ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-info"></i> Tentang Aplikasi
      </a>
    </nav>

    <!-- ── Konten Panel ──────────────────────────────────────────── -->
    <div>

      <!-- ── Panel: Profil ────────────────────────────────────────── -->
      <div class="settings-panel <?= $tab === 'profil' ? 'active' : '' ?>">
        <div class="form-card">
          <div class="form-card-header">
            <div class="fch-icon"><i class="fa-regular fa-user"></i></div>
            <div>
              <h3>Profil Saya</h3>
              <p>Perbarui informasi akun Anda</p>
            </div>
          </div>
          <div class="form-card-body">
            <!-- Avatar -->
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding:16px;background:var(--surface-2);border-radius:var(--radius-md);border:1px solid var(--border);">
              <div style="width:64px;height:64px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:white;font-size:22px;font-weight:800;flex-shrink:0;">
                <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
              </div>
              <div>
                <div style="font-size:16px;font-weight:700;"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($user['role']) ?></div>
              </div>
            </div>

            <!--
              ACTION → proses_settings.php
              Setelah DB users tersedia, uncomment dan sesuaikan.
            -->
            <form method="POST" action="proses_settings.php">
              <input type="hidden" name="action" value="profil">

              <div class="form-row grid-2">
                <div class="form-field">
                  <label for="nama_lengkap">Nama Lengkap</label>
                  <input type="text" id="nama_lengkap" name="nama_lengkap"
                         value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                </div>
                <div class="form-field">
                  <label for="username">Username</label>
                  <input type="text" id="username" name="username"
                         value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
              </div>

              <div class="form-row grid-2">
                <div class="form-field">
                  <label for="email">Email</label>
                  <input type="email" id="email" name="email"
                         value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-field">
                  <label>Role</label>
                  <input type="text" value="<?= htmlspecialchars($user['role']) ?>"
                         readonly style="background:var(--bg);color:var(--text-muted);">
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-accent">
                  <i class="fa-solid fa-floppy-disk"></i> Simpan Profil
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ── Panel: Password ────────────────────────────────────── -->
      <div class="settings-panel <?= $tab === 'password' ? 'active' : '' ?>">
        <div class="form-card">
          <div class="form-card-header">
            <div class="fch-icon"><i class="fa-solid fa-lock"></i></div>
            <div><h3>Ubah Password</h3><p>Gunakan password yang kuat dan unik</p></div>
          </div>
          <div class="form-card-body">
            <form method="POST" action="proses_settings.php" id="form-password">
              <input type="hidden" name="action" value="password">

              <div class="form-field" style="margin-bottom:16px;">
                <label for="password_lama">Password Lama</label>
                <div style="position:relative;">
                  <input type="password" id="password_lama" name="password_lama"
                         placeholder="Password saat ini" required style="padding-right:44px;">
                  <button type="button" class="input-toggle" onclick="togglePassSettings('password_lama',this)">
                    <i class="fa-regular fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="form-field" style="margin-bottom:16px;">
                <label for="password_baru">Password Baru</label>
                <div style="position:relative;">
                  <input type="password" id="password_baru" name="password_baru"
                         placeholder="Minimal 8 karakter" minlength="8" required style="padding-right:44px;">
                  <button type="button" class="input-toggle" onclick="togglePassSettings('password_baru',this)">
                    <i class="fa-regular fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="form-field" style="margin-bottom:24px;">
                <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                <div style="position:relative;">
                  <input type="password" id="konfirmasi_password" name="konfirmasi_password"
                         placeholder="Ulangi password baru" required style="padding-right:44px;">
                  <button type="button" class="input-toggle" onclick="togglePassSettings('konfirmasi_password',this)">
                    <i class="fa-regular fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-accent">
                  <i class="fa-solid fa-key"></i> Ubah Password
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ── Panel: Kategori ──────────────────────────────────────── -->
      <div class="settings-panel <?= $tab === 'kategori' ? 'active' : '' ?>">
        <div class="form-card">
          <div class="form-card-header">
            <div class="fch-icon"><i class="fa-solid fa-tags"></i></div>
            <div><h3>Manajemen Kategori</h3><p>Tambah atau hapus kategori produk</p></div>
          </div>

          <!-- Form tambah kategori -->
          <div style="padding:16px 24px;border-bottom:1px solid var(--border);background:var(--surface-2);">
            <form method="POST" action="proses_settings.php" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
              <input type="hidden" name="action" value="tambah_kategori">
              <div class="form-field" style="flex:1;min-width:180px;margin:0;">
                <label for="nama_kategori">Nama Kategori Baru</label>
                <input type="text" id="nama_kategori" name="nama_kategori"
                       placeholder="contoh: Herbal" required>
              </div>
              <div class="form-field" style="flex:2;min-width:200px;margin:0;">
                <label for="deskripsi_kat">Deskripsi (opsional)</label>
                <input type="text" id="deskripsi_kat" name="deskripsi"
                       placeholder="Deskripsi singkat kategori">
              </div>
              <button type="submit" class="btn-accent" style="height:42px;">
                <i class="fa-solid fa-plus"></i> Tambah
              </button>
            </form>
          </div>

          <!-- Tabel kategori -->
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nama Kategori</th>
                  <th>Deskripsi</th>
                  <th>Jumlah Produk</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($kategori_list)): ?>
                  <tr>
                    <td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);">
                      Belum ada kategori.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($kategori_list as $k): ?>
                    <tr>
                      <td style="font-family:var(--font-mono);color:var(--text-muted);"><?= $k['id'] ?></td>
                      <td style="font-weight:600;"><?= htmlspecialchars($k['nama_kategori']) ?></td>
                      <td style="font-size:12px;color:var(--text-secondary);">
                        <?= htmlspecialchars($k['deskripsi'] ?? '—') ?>
                      </td>
                      <td>
                        <span style="font-family:var(--font-mono);font-weight:700;">
                          <?= $k['jumlah_produk'] ?>
                        </span>
                        <span style="font-size:11px;color:var(--text-muted);"> produk</span>
                      </td>
                      <td>
                        <?php if ((int)$k['jumlah_produk'] === 0): ?>
                          <form method="POST" action="proses_settings.php" style="display:inline;">
                            <input type="hidden" name="action" value="hapus_kategori">
                            <input type="hidden" name="id" value="<?= $k['id'] ?>">
                            <button type="submit" class="btn-del"
                                    onclick="return confirm('Hapus kategori &quot;<?= htmlspecialchars(addslashes($k['nama_kategori'])) ?>&quot;?')"
                                    title="Hapus">
                              <i class="fa-solid fa-trash"></i>
                            </button>
                          </form>
                        <?php else: ?>
                          <span style="font-size:11px;color:var(--text-muted);font-style:italic;">Tidak bisa dihapus</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── Panel: Tentang ────────────────────────────────────────── -->
      <div class="settings-panel <?= $tab === 'tentang' ? 'active' : '' ?>">
        <div class="form-card">
          <div class="form-card-body" style="padding:32px;">
            <div style="text-align:center;margin-bottom:28px;">
              <div style="width:72px;height:72px;background:var(--primary);border-radius:var(--radius-lg);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;box-shadow:0 8px 24px rgba(13,122,106,0.3);">
                <i class="fa-solid fa-robot" style="color:white;font-size:28px;"></i>
              </div>
              <h2 style="font-size:22px;font-weight:800;letter-spacing:-0.5px;">Smart UMKM Assistant</h2>
              <p style="color:var(--text-muted);margin-top:4px;">Versi 1.0.0</p>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:480px;margin:0 auto;">
              <?php
              $info = [
                  ['fa-database',    'Database',    'PostgreSQL 15'],
                  ['fa-code',        'Backend',     'PHP 8.x + PDO'],
                  ['fa-shield-halved','Enkripsi',   'AES-256 Ready'],
                  ['fa-calendar',    'Build Date',  date('d M Y')],
              ];
              foreach ($info as [$icon, $label, $value]):
              ?>
                <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 16px;">
                  <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <i class="fa-solid <?= $icon ?>" style="color:var(--primary);font-size:13px;"></i>
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);"><?= $label ?></span>
                  </div>
                  <div style="font-size:14px;font-weight:700;"><?= $value ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /panel container -->
  </div><!-- /settings-layout -->
</div><!-- /page-content -->

<style>
/* Toggle password di settings (tidak ada .input-wrap di sini) */
.input-toggle {
  position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; color: var(--text-muted);
  padding: 4px; transition: var(--transition);
}
.input-toggle:hover { color: var(--primary); }
.form-card-body .form-field input[type="password"],
.form-card-body .form-field input[type="text"] {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: var(--font-main);
  font-size: 13px;
  background: var(--surface-2);
  outline: none;
  transition: var(--transition);
}
.form-card-body .form-field input:focus {
  border-color: var(--primary);
  background: white;
  box-shadow: 0 0 0 3px rgba(13,122,106,0.1);
}
</style>

<script>
function togglePassSettings(id, btn) {
    const el = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (el.type === 'password') {
        el.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        el.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}

document.getElementById('form-password')?.addEventListener('submit', function(e) {
    const baru = document.getElementById('password_baru').value;
    const konfirm = document.getElementById('konfirmasi_password').value;
    if (baru !== konfirm) {
        e.preventDefault();
        showFlash('danger', 'Konfirmasi password baru tidak cocok!');
    }
});
</script>

<?php include 'layout/footer.php'; ?>