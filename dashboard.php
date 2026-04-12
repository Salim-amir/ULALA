<?php
/**
 * dashboard.php — Data real dari PostgreSQL
 * Tabel: penjualan, detail_penjualan, produk, kategori,
 *        v_produk_kritis, ai_insights
 */
// session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php?error=session_expired'); exit; }

require_once 'config/db.php';

$page_title  = 'Dashboard Overview';
$active_menu = 'dashboard';

$bln_start  = date('Y-m-01 00:00:00');
$bln_end    = date('Y-m-t 23:59:59');
$lalu_start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
$lalu_end   = date('Y-m-t 23:59:59',  strtotime('last day of last month'));

// ── Metric 1: Total Pendapatan ─────────────────────────────────────
$s = $pdo->prepare("SELECT COALESCE(SUM(total_bayar),0) FROM penjualan WHERE dibuat_pada BETWEEN ? AND ?");
$s->execute([$bln_start, $bln_end]);
$total_pendapatan = (float)$s->fetchColumn();

$s->execute([$lalu_start, $lalu_end]);
$pendapatan_lalu = (float)$s->fetchColumn();
$pct_growth = $pendapatan_lalu > 0
    ? round((($total_pendapatan - $pendapatan_lalu) / $pendapatan_lalu) * 100, 1)
    : ($total_pendapatan > 0 ? 100 : 0);

// ── Metric 2: Total Transaksi ─────────────────────────────────────
$s = $pdo->prepare("SELECT COUNT(*) FROM penjualan WHERE dibuat_pada BETWEEN ? AND ?");
$s->execute([$bln_start, $bln_end]);
$total_transaksi = (int)$s->fetchColumn();

$s->execute([$lalu_start, $lalu_end]);
$transaksi_lalu = (int)$s->fetchColumn();

// ── Metric 3: Produk Aktif ────────────────────────────────────────
$total_produk_aktif = (int)$pdo->query("SELECT COUNT(*) FROM produk WHERE stok_saat_ini > 0")->fetchColumn();

// ── Stok Kritis ────────────────────────────────────────────────────
$produk_kritis = $pdo->query("
    SELECT vk.nama_produk, vk.stok_saat_ini, vk.status,
           COALESCE(k.nama_kategori, 'Umum') AS nama_kategori,
           p.stok_minimum
    FROM v_produk_kritis vk
    JOIN produk p ON p.nama_produk = vk.nama_produk
    LEFT JOIN kategori k ON k.id = p.kategori_id
    ORDER BY vk.stok_saat_ini ASC
    LIMIT 5
")->fetchAll();

// ── AI Rekomendasi ─────────────────────────────────────────────────
$ai_reco = $pdo->query("
    SELECT ai.pesan_rekomendasi, ai.skor_presisi, p.nama_produk, ai.tipe_insight
    FROM ai_insights ai
    JOIN produk p ON p.id = ai.produk_id
    WHERE ai.status = 'aktif'
    ORDER BY CASE ai.tipe_insight WHEN 'RESTOCK' THEN 1 WHEN 'PROMO' THEN 2 ELSE 3 END,
             ai.skor_presisi DESC
    LIMIT 1
")->fetch();


// ── Chart: Harian (7 hari terakhir) ──────────────────────────────
$chart_harian = [];
$day_labels = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
for ($d = 6; $d >= 0; $d--) {
    $tgl = date('Y-m-d', strtotime("-$d days"));
    $s   = $pdo->prepare("SELECT COALESCE(SUM(total_bayar),0) FROM penjualan WHERE DATE(dibuat_pada) = ?");
    $s->execute([$tgl]);
    $chart_harian[] = ['label' => $day_labels[(int)date('w', strtotime($tgl))], 'val' => (float)$s->fetchColumn()];
}

// ── Chart: Bulanan (6 bulan terakhir) ────────────────────────────
$chart_bulanan = [];
$bln_names = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
for ($m = 5; $m >= 0; $m--) {
    $ts  = date('Y-m-01 00:00:00', strtotime("-$m months"));
    $te  = date('Y-m-t 23:59:59',  strtotime("-$m months"));
    $s   = $pdo->prepare("SELECT COALESCE(SUM(total_bayar),0) FROM penjualan WHERE dibuat_pada BETWEEN ? AND ?");
    $s->execute([$ts, $te]);
    $chart_bulanan[] = ['label' => $bln_names[(int)date('n', strtotime("-$m months")) - 1], 'val' => (float)$s->fetchColumn()];
}

// ── Helpers ───────────────────────────────────────────────────────
function stok_pill(int $stok, int $min): array {
    if ($stok <= 0)              return ['pill-danger',  'Habis'];
    if ($stok <= ceil($min*0.3)) return ['pill-danger',  'Kritis'];
    if ($stok <= $min)           return ['pill-info',    'Restok Segera'];
    return                              ['pill-warning', 'Peringatan'];
}

include 'layout/header.php';
?>

<div class="page-content">

  <!-- METRIC CARDS -->
  <div class="metric-grid">
    <div class="metric-card">
      <div class="metric-top">
        <div class="metric-icon-wrap"><i class="fa-solid fa-wallet"></i></div>
        <span class="metric-badge <?= $pct_growth >= 0 ? 'badge-green' : 'pill-danger' ?>">
          <i class="fa-solid <?= $pct_growth >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?>" style="font-size:9px;"></i>
          <?= ($pct_growth >= 0 ? '+' : '') . $pct_growth ?>%
        </span>
      </div>
      <div class="metric-label">Total Pendapatan</div>
      <div class="metric-value"><?= rp($total_pendapatan) ?></div>
      <div class="metric-sub">Bulan <?= date('F Y') ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-top">
        <div class="metric-icon-wrap"><i class="fa-solid fa-cart-shopping"></i></div>
        <span class="metric-badge badge-blue">Bulan Ini</span>
      </div>
      <div class="metric-label">Total Transaksi</div>
      <div class="metric-value"><?= number_format($total_transaksi, 0, ',', '.') ?></div>
      <div class="metric-sub">
        <?= $transaksi_lalu > 0 ? 'Dari ' . number_format($transaksi_lalu, 0, ',', '.') . ' bulan lalu' : 'Belum ada data bulan lalu' ?>
      </div>
    </div>

    <div class="metric-card">
      <div class="metric-top">
        <div class="metric-icon-wrap"><i class="fa-solid fa-tag"></i></div>
        <span class="metric-badge badge-teal">Aktif</span>
      </div>
      <div class="metric-label">Total Produk Aktif</div>
      <div class="metric-value"><?= $total_produk_aktif ?></div>
      <div class="metric-sub"><?= count($produk_kritis) ?> produk stok kritis</div>
    </div>
  </div>

  <!-- CHART -->
  <div class="chart-card">
    <div class="chart-header">
      <div class="chart-title">
        <h3>Sales Trend Analisis</h3>
        <p>Visualisasi pendapatan dari data transaksi nyata</p>
      </div>
      <div class="chart-tabs">
        <button class="chart-tab active" onclick="switchChartTab(this)">Harian</button>
        <button class="chart-tab" onclick="switchChartTab(this)">Bulanan</button>
      </div>
    </div>
    <div class="chart-area" id="sales-chart"></div>
  </div>

  <!-- BOTTOM GRID -->
  <div class="dashboard-bottom">

    <!-- Stok Kritis -->
    <div class="stok-kritis-card">
      <div class="card-header">
        <div class="card-header-left">
          <div class="card-icon warn"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <h3>Stok Kritis</h3>
        </div>
        <a href="kelola_produk.php?stok=kritis" class="btn-sm">
          <i class="fa-solid fa-arrow-right"></i> Lihat Semua
        </a>
      </div>

      <?php if (empty($produk_kritis)): ?>
        <div style="padding:32px;text-align:center;color:var(--text-muted);">
          <i class="fa-solid fa-circle-check" style="font-size:28px;color:var(--success);display:block;margin-bottom:8px;"></i>
          Semua stok produk dalam kondisi aman.
        </div>
      <?php else: ?>
        <?php foreach ($produk_kritis as $p):
          [$pill_class, $pill_label] = stok_pill((int)$p['stok_saat_ini'], (int)$p['stok_minimum']);
        ?>
          <div class="stok-row">
            <div class="stok-thumb"><i class="fa-solid fa-box"></i></div>
            <div class="stok-info">
              <div class="stok-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
              <div class="stok-cat">Kategori: <?= htmlspecialchars($p['nama_kategori']) ?></div>
            </div>
            <div class="stok-count">
              <div class="count" style="color:var(--<?= (int)$p['stok_saat_ini'] <= 0 ? 'danger' : ((int)$p['stok_saat_ini'] <= (int)$p['stok_minimum'] * 0.5 ? 'danger' : 'warning') ?>);">
                <?= (int)$p['stok_saat_ini'] ?> Pcs
              </div>
              <div class="count-label">SISA STOK</div>
            </div>
            <span class="status-pill <?= $pill_class ?>"><?= $pill_label ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- AI Rekomendasi -->
    <div class="ai-reco-card">
      <div class="card-header">
        <div class="card-header-left">
          <div class="card-icon ai">
            <i class="fa-solid fa-wand-magic-sparkles" style="color:rgba(255,255,255,0.8);"></i>
          </div>
          <h3>Rekomendasi AI</h3>
        </div>
      </div>
      <?php if ($ai_reco): ?>
        <div class="ai-reco-body">
          <div class="ai-stars">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
            <i class="fa-regular fa-star"></i>
          </div>
          <h4><?= $ai_reco['tipe_insight'] === 'RESTOCK' ? 'Segera Restok Produk!' : 'Tindakan Diperlukan!' ?></h4>
          <p>
            Produk <span class="ai-highlight"><?= htmlspecialchars($ai_reco['nama_produk']) ?></span>:
            <?= htmlspecialchars($ai_reco['pesan_rekomendasi'] ?? '—') ?>
          </p>
          <a href="ai_insights.php" class="btn-white">
            <i class="fa-solid fa-brain"></i> Lihat Semua Insights
          </a>
        </div>
      <?php else: ?>
        <div class="ai-reco-body" style="text-align:center;padding:32px 22px;">
          <i class="fa-solid fa-brain" style="font-size:28px;color:rgba(255,255,255,0.35);margin-bottom:10px;display:block;"></i>
          <p>Belum ada rekomendasi AI aktif.</p>
          <a href="proses_sync_insights.php" class="btn-white" style="margin-top:12px;">
            <i class="fa-solid fa-rotate"></i> Jalankan Analisis
          </a>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /dashboard-bottom -->
</div><!-- /page-content -->

<script>
// 1. Data dari PHP
const CHART_DATA = {
    Harian:   <?= json_encode($chart_harian,  JSON_NUMERIC_CHECK) ?>,
    Bulanan:  <?= json_encode($chart_bulanan, JSON_NUMERIC_CHECK) ?>,
};

let salesChart; 
let selectedRange = 'Harian'; // Pastikan huruf kapital sesuai kunci di CHART_DATA

// 2. Fungsi untuk Inisialisasi atau Update Chart
function loadChart(range) {
    const data = CHART_DATA[range];
    const seriesData = data.map(item => item.val);
    const categoriesData = data.map(item => item.label);

    const commonOptions = {
        series: [{
            name: 'Pendapatan',
            data: seriesData
        }],
        xaxis: {
            categories: categoriesData
        }
    };

    // Jika chart belum ada (pertama kali load)
    if (!salesChart) {
        const options = {
            ...commonOptions,
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                animations: { enabled: true }
            },
            colors: ['#0d7a6a'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: 0.45, opacityTo: 0.05 }
            },
            markers: {
                size: 5, colors: ['#0d7a6a'], strokeColors: '#fff', strokeWidth: 2
            },
            yaxis: {
                min: 0,
                labels: {
                    style: { colors: '#8aa8a3', fontSize: '12px' },
                    formatter: function (val) {
                        if (val >= 1000000) return "Rp " + (val / 1000000).toFixed(1) + "M";
                        return "Rp " + val.toLocaleString('id-ID');
                    }
                }
            },
            grid: { borderColor: '#e2ece9', strokeDashArray: 4 },
            tooltip: { y: { formatter: (val) => "Rp " + val.toLocaleString('id-ID') } }
        };

        salesChart = new ApexCharts(document.querySelector("#sales-chart"), options);
        salesChart.render();
    } else {
        // Jika sudah ada, cukup update data dan kategorinya saja
        // Ini mencegah bug perubahan skala sumbu Y yang tidak konsisten
        salesChart.updateOptions(commonOptions);
    }
}

// 3. AUTO LOAD saat halaman dibuka
document.addEventListener("DOMContentLoaded", function() {
    loadChart(selectedRange);
});

// 4. Fungsi Switch Tab
function switchChartTab(btn) {
    document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    
    // Gunakan innerText untuk mengambil 'Harian' atau 'Bulanan'
    selectedRange = btn.innerText; 
    loadChart(selectedRange);
}
</script>


<?php include 'layout/footer.php'; ?>