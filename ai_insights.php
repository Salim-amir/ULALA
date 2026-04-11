<?php
/**
 * ai_insights.php
 * ─────────────────────────────────────────────────────────────────
 * Halaman rekomendasi AI berbasis data penjualan.
 *
 * Referensi tabel DB:
 *   ai_insights : id, produk_id, tipe_insight, pesan_rekomendasi,
 *                 skor_presisi, status, dibuat_pada
 *   produk      : id, nama_produk
 *
 * Tipe insight yang tersedia: RESTOCK | PROMO | BUNDLING
 * ─────────────────────────────────────────────────────────────────
 */

// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php?error=session_expired');
//     exit;
// }

$page_title  = 'AI Insights';
$active_menu = 'ai_insights';

// ── Flash pesan setelah sync ───────────────────────────────────────
$flash_type = '';
$flash_msg  = '';
if (isset($_GET['synced'])) {
    $flash_type = 'success';
    $flash_msg  = 'Insights berhasil diperbarui dari data terbaru!';
}

// ── TODO: Query DB ─────────────────────────────────────────────────
/*
require_once 'config/db.php';

// Ambil satu kartu per tipe (skor tertinggi & status aktif)
$stmt = $pdo->query("
    SELECT DISTINCT ON (ai.tipe_insight)
           ai.id, ai.tipe_insight, ai.pesan_rekomendasi,
           ai.skor_presisi, ai.status,
           ai.dibuat_pada, p.nama_produk
    FROM ai_insights ai
    JOIN produk p ON p.id = ai.produk_id
    WHERE ai.status = 'aktif'
    ORDER BY ai.tipe_insight, ai.skor_presisi DESC
");
$insight_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Riwayat semua insights (pagination — TODO)
$stmt_history = $pdo->query("
    SELECT ai.id, ai.tipe_insight, ai.pesan_rekomendasi,
           ai.skor_presisi, ai.status, ai.dibuat_pada,
           p.nama_produk
    FROM ai_insights ai
    JOIN produk p ON p.id = ai.produk_id
    ORDER BY ai.dibuat_pada DESC
    LIMIT 20
");
$insight_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
*/

// ── Placeholder data ───────────────────────────────────────────────
$insight_cards = [
    [
        'id'                 => 1,
        'tipe_insight'       => 'RESTOCK',
        'nama_produk'        => 'Kopi Arabica Gayo 250g',
        'pesan_rekomendasi'  => 'Stok tersisa 5 pcs. Berdasarkan tren, stok akan habis dalam 3 hari ke depan. Segera lakukan pemesanan ulang sebelum kehabisan.',
        'skor_presisi'       => 98.4,
        'status'             => 'aktif',
        'dibuat_pada'        => '2026-04-11',
    ],
    [
        'id'                 => 2,
        'tipe_insight'       => 'PROMO',
        'nama_produk'        => 'Temulawak Kering 500g',
        'pesan_rekomendasi'  => 'Produk Temulawak Kering kurang diminati bulan ini. Disarankan buat promo diskon 15% atau bundling untuk meningkatkan penjualan.',
        'skor_presisi'       => 85.5,
        'status'             => 'aktif',
        'dibuat_pada'        => '2026-04-10',
    ],
    [
        'id'                 => 3,
        'tipe_insight'       => 'BUNDLING',
        'nama_produk'        => 'Kopi Arabica Gayo 250g',
        'pesan_rekomendasi'  => 'Pelanggan sering membeli Kopi Arabica bersama Gula Semut Aren 1kg. Buat paket bundling untuk meningkatkan nilai transaksi rata-rata.',
        'skor_presisi'       => 92.0,
        'status'             => 'aktif',
        'dibuat_pada'        => '2026-04-09',
    ],
];

$insight_history = $insight_cards; // Sama, hanya untuk demo tabel riwayat

// ── Helper: konfigurasi tiap tipe insight (theme, icon, CTA) ───────
function insight_config(string $tipe): array {
    return match($tipe) {
        'RESTOCK'  => [
            'theme'  => 'theme-restock',
            'icon'   => 'fa-arrow-up-from-bracket',
            'label'  => 'RESTOCK',
            'cta'    => '<i class="fa-solid fa-truck"></i> Order Sekarang',
            'pill'   => 'badge-teal',
        ],
        'PROMO'    => [
            'theme'  => 'theme-promo',
            'icon'   => 'fa-tags',
            'label'  => 'PROMO',
            'cta'    => '<i class="fa-solid fa-percent"></i> Buat Promo',
            'pill'   => 'pill-warning',
        ],
        'BUNDLING' => [
            'theme'  => 'theme-bundling',
            'icon'   => 'fa-layer-group',
            'label'  => 'BUNDLING',
            'cta'    => '<i class="fa-solid fa-cubes"></i> Buat Bundling',
            'pill'   => 'badge-blue',
        ],
        default    => [
            'theme'  => 'theme-restock',
            'icon'   => 'fa-lightbulb',
            'label'  => $tipe,
            'cta'    => '<i class="fa-solid fa-arrow-right"></i> Tindak Lanjuti',
            'pill'   => 'badge-teal',
        ],
    };
}

// ── Helper: judul otomatis dari tipe + nama produk ─────────────────
function insight_title(string $tipe, string $produk): string {
    return match($tipe) {
        'RESTOCK'  => htmlspecialchars($produk) . ' Hampir Habis!',
        'PROMO'    => htmlspecialchars($produk) . ' Butuh Promo!',
        'BUNDLING' => 'Peluang Bundling ' . htmlspecialchars($produk) . '!',
        default    => 'Insight untuk ' . htmlspecialchars($produk),
    };
}

include 'layout/header.php';
?>

<div class="page-content">

  <?php if ($flash_msg): ?>
    <div id="php-flash" class="alert alert-<?= $flash_type ?>">
      <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flash_msg) ?>
    </div>
  <?php endif; ?>

  <!-- ── Page Header ──────────────────────────────────────────────── -->
  <div class="ai-page-header">
    <div class="ai-page-title">
      <h2>AI Insights</h2>
      <p>Rekomendasi cerdas berbasis data penjualan terkini Anda</p>
    </div>
    <!--
      Tombol ini memanggil syncInsights() (JS di footer.php).
      Untuk implementasi nyata: arahkan ke proses_sync_insights.php
      yang memanggil prosedur PostgreSQL hitung_ai_restock(), dll.
    -->
    <a href="proses_sync_insights.php" class="ai-sync-btn" id="btn-sync"
       onclick="handleSyncClick(event)">
      <i class="fa-solid fa-rotate" id="sync-icon"></i> Perbarui Insights
    </a>
  </div>

  <!-- ── Insight Cards ─────────────────────────────────────────────── -->
  <?php if (empty($insight_cards)): ?>
    <div class="form-card" style="padding:48px;text-align:center;">
      <i class="fa-solid fa-brain" style="font-size:40px;color:var(--text-muted);margin-bottom:16px;display:block;"></i>
      <h3 style="margin-bottom:8px;">Belum Ada Insight</h3>
      <p style="color:var(--text-muted);margin-bottom:20px;">Klik "Perbarui Insights" untuk menganalisis data penjualan terbaru.</p>
      <a href="proses_sync_insights.php" class="btn-accent" style="display:inline-flex;">
        <i class="fa-solid fa-rotate"></i> Jalankan Analisis
      </a>
    </div>
  <?php else: ?>
    <div class="ai-grid">
      <?php foreach ($insight_cards as $insight):
        $cfg   = insight_config($insight['tipe_insight']);
        $judul = insight_title($insight['tipe_insight'], $insight['nama_produk']);
      ?>
        <div class="ai-insight-card <?= $cfg['theme'] ?>">
          <div class="aic-top">
            <div class="aic-badge">
              <i class="fa-solid fa-circle-dot" style="font-size:8px;"></i>
              <?= htmlspecialchars($cfg['label']) ?>
            </div>
            <div class="aic-type-icon">
              <i class="fa-solid <?= $cfg['icon'] ?>"></i>
            </div>
            <h3><?= $judul ?></h3>
            <p><?= htmlspecialchars($insight['pesan_rekomendasi']) ?></p>
          </div>
          <div class="aic-meta">
            <div class="aic-score">
              <span class="score-val"><?= number_format((float)$insight['skor_presisi'], 1) ?>%</span>
              <span class="score-label">Skor Presisi</span>
            </div>
            <!--
              TODO: Ganti href dengan link aksi nyata:
                RESTOCK  → kelola_produk.php?action=restok&id=X
                PROMO    → promo.php?produk_id=X
                BUNDLING → bundling.php?produk_id=X
            -->
            <button class="aic-action" onclick="handleInsightAction(<?= $insight['id'] ?>, '<?= $insight['tipe_insight'] ?>')">
              <?= $cfg['cta'] ?>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>


  <!-- ── Tabel Riwayat Insights ─────────────────────────────────────── -->
  <div class="ai-history-card">
    <div class="card-header">
      <div class="card-header-left">
        <div class="card-icon ai"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <h3>Riwayat Insights</h3>
      </div>
      <span style="font-size:12px;color:var(--text-muted);">
        <?= count($insight_history) ?> entri ditampilkan
      </span>
    </div>

    <div class="table-wrap">
      <!--
        Kolom referensi:
          ai_insights.id, produk.nama_produk, ai_insights.tipe_insight,
          ai_insights.pesan_rekomendasi, ai_insights.skor_presisi,
          ai_insights.status, ai_insights.dibuat_pada
      -->
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Produk</th>
            <th>Tipe Insight</th>
            <th>Pesan Rekomendasi</th>
            <th>Skor Presisi</th>
            <th>Status</th>
            <th>Dibuat Pada</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($insight_history)): ?>
            <tr>
              <td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted);">
                Belum ada riwayat insight.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($insight_history as $row):
              $cfg = insight_config($row['tipe_insight']);
            ?>
              <tr>
                <td>
                  <span style="font-family:var(--font-mono);color:var(--text-muted);">
                    #<?= htmlspecialchars((string)$row['id']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                <td>
                  <span class="status-pill <?= $cfg['pill'] ?>">
                    <?= htmlspecialchars($row['tipe_insight']) ?>
                  </span>
                </td>
                <td style="max-width:280px;font-size:12px;color:var(--text-secondary);">
                  <?= htmlspecialchars($row['pesan_rekomendasi']) ?>
                </td>
                <td>
                  <strong style="font-family:var(--font-mono);">
                    <?= number_format((float)$row['skor_presisi'], 1) ?>%
                  </strong>
                </td>
                <td>
                  <span class="status-pill <?= $row['status'] === 'aktif' ? 'badge-green' : 'pill-warning' ?>">
                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                  </span>
                </td>
                <td style="font-family:var(--font-mono);font-size:12px;">
                  <?= htmlspecialchars(date('d M Y', strtotime($row['dibuat_pada']))) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div><!-- /ai-history-card -->

</div><!-- /page-content -->

<script>
/* ── Sync button: tampilkan loading lalu redirect ke proses PHP ── */
function handleSyncClick(e) {
    e.preventDefault();
    const icon = document.getElementById('sync-icon');
    const btn  = document.getElementById('btn-sync');
    icon.style.animation = 'spin 0.8s linear infinite';
    btn.style.opacity    = '0.7';
    btn.style.pointerEvents = 'none';
    // Redirect setelah animasi singkat agar terasa responsif
    setTimeout(() => { window.location.href = 'proses_sync_insights.php'; }, 600);
}

/* ── Aksi tiap insight card ─────────────────────────────────────── */
function handleInsightAction(id, tipe) {
    const urls = {
        RESTOCK:  'kelola_produk.php?action=restok&insight_id=' + id,
        PROMO:    'promo.php?insight_id=' + id,
        BUNDLING: 'bundling.php?insight_id=' + id,
    };
    window.location.href = urls[tipe] || '#';
}
</script>

<?php include 'layout/footer.php'; ?>