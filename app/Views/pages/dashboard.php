<?php
// app/Views/pages/dashboard.php
// Helper: format rupiah
function rp(float $n): string {
    if($n >= 1000000) return 'Rp ' . number_format($n/1000000,1,',','.') . 'jt';
    if($n >= 1000)    return 'Rp ' . number_format($n/1000,0,',','.') . 'K';
    return 'Rp ' . number_format($n,0,',','.');
}

$emojiMap = [1=>'☕',2=>'🥐',3=>'🍯',4=>'📓',0=>'📦'];
?>

<!-- ── AI HERO BANNER ─────────────────────── -->
<div class="ai-hero">
  <div class="ai-hero-left">
    <div class="ai-badge-row">
      <span class="badge-ai-powered">✦ REAL-TIME AI ENGINE ACTIVE</span>
    </div>
    <h1 class="ai-hero-title">Smart Insights: Rekomendasi Restock</h1>
    <p class="ai-hero-desc">Mesin AI kami baru saja menganalisis tren penjualan 24 jam terakhir. Kami mendeteksi lonjakan permintaan pada kategori kopi dan madu. Ikuti saran stok di bawah untuk memaksimalkan omzet Anda minggu ini.</p>
  </div>
  <div class="ai-hero-right">
    <div class="ai-presisi-box">
      <div class="presisi-label">STATUS ANALISIS</div>
      <div class="presisi-value"><?= number_format($skorPresisi,1) ?>%</div>
      <div class="presisi-bar"><div class="presisi-fill" style="width:<?= $skorPresisi ?>%"></div></div>
      <div class="presisi-sub">Model Accuracy Rating</div>
    </div>
  </div>
</div>

<!-- ── METRIC ROW ─────────────────────────── -->
<div class="metrics-row">
  <?php
  $metrics = [
    ['label'=>'Total Omzet Bulan Ini','value'=>rp($omzetBulanIni),'change'=>'+22.3%','up'=>true,'icon'=>'💰'],
    ['label'=>'Total Transaksi','value'=>number_format($totalTrx),'change'=>'+18 hari ini','up'=>true,'icon'=>'🧾'],
    ['label'=>'Prediksi Omzet Tambahan','value'=>'Rp 12.450.000','change'=>'+14.2% vs Normal','up'=>true,'icon'=>'📈'],
    ['label'=>'Produk Stok Kritis','value'=>count($produkKritis),'change'=>'Perlu restock','up'=>false,'icon'=>'⚠️'],
  ];
  foreach($metrics as $m): ?>
  <div class="metric-card">
    <div class="metric-icon"><?= $m['icon'] ?></div>
    <div class="metric-body">
      <div class="metric-label"><?= $m['label'] ?></div>
      <div class="metric-value"><?= $m['value'] ?></div>
      <div class="metric-change <?= $m['up']?'up':'down' ?>">
        <?= $m['up'] ? '↑' : '↓' ?> <?= $m['change'] ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── PREDIKSI + CHART ROW ───────────────── -->
<div class="two-col-grid" style="margin-bottom:24px">

  <!-- Prediksi Omzet Tambahan -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-label">PREDIKSI OMZET TAMBAHAN</div>
        <div class="prediksi-big">Rp<br><span><?= number_format(12450000,0,',','.') ?></span></div>
        <div class="prediksi-growth">↗ +14.2% vs Proyeksi Normal</div>
        <p class="prediksi-note">Estimasi keuntungan jika Anda melakukan restock hari ini sesuai saran AI sebelum pukul 16:00 WIB.</p>
      </div>
      <button class="btn-primary-dark" onclick="showToast('success','Memproses restock order...','🚀')">Ambil Peluang Sekarang →</button>
    </div>
  </div>

  <!-- Chart Proyeksi -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Proyeksi Pertumbuhan Stok</div>
      <div class="chart-legend">
        <span class="legend-dot navy"></span> Restock AI
        <span class="legend-dot gray" style="margin-left:12px"></span> Normal
      </div>
    </div>
    <p class="card-sub">Berdasarkan data historis 30 hari terakhir</p>
    <div class="chart-wrap"><canvas id="chartProyeksi"></canvas></div>
  </div>

</div>

<!-- ── RESTOCK CARDS ──────────────────────── -->
<div class="section-header">
  <div>
    <h2 class="section-title">Rekomendasi Restock Segera</h2>
    <p class="section-sub">Item yang memerlukan tindakan cepat untuk menghindari 'Out of Stock'</p>
  </div>
  <a href="<?= BASE_URL ?>/insights" class="link-action">Lihat Semua Inventori ↗</a>
</div>

<div class="restock-grid">
<?php
$kritis = array_slice($produkKritis ?? [], 0, 3);
foreach($kritis as $p):
  $pct  = $p['stok_saat_ini'] / max($p['stok_minimum'],1);
  $lvl  = $pct <= 0.3 ? 'KRITIS' : ($pct <= 0.7 ? 'TINGGI' : 'NORMAL');
  $insight = array_values(array_filter($restockInsights??[], fn($i)=>$i['produk_id']==$p['id']))[0] ?? null;
  $emoji = $emojiMap[$p['kategori_id'] ?? 0] ?? '📦';
?>
<div class="restock-card <?= strtolower($lvl) ?>">
  <div class="restock-badge badge-<?= strtolower($lvl) ?>"><?= $lvl ?></div>
  <div class="restock-emoji"><?= $emoji ?></div>
  <div class="restock-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
  <div class="restock-meta">
    <span class="restock-sisa <?= $lvl==='KRITIS'?'text-red':'text-amber' ?>">Sisa: <?= $p['stok_saat_ini'] ?> unit</span>
    <span class="restock-sku">ID: <?= htmlspecialchars($p['sku']??'-') ?></span>
  </div>
  <?php if($insight): ?>
  <div class="restock-insight <?= $lvl==='KRITIS'?'icon-red':'icon-amber' ?>">
    <?= $lvl==='KRITIS' ? '🔴' : '↗' ?> <?= htmlspecialchars(substr($insight['pesan_rekomendasi'],0,40)) ?>...
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<!-- ── TREN PROMO + STRATEGI ──────────────── -->
<div class="section-header" style="margin-top:32px">
  <h2 class="section-title">Analisis Tren &amp; Promo</h2>
</div>

<div class="tren-grid">
  <div class="tren-list">
    <?php foreach(array_slice($promoInsights??[],0,2) as $i):
      $lvl = $i['tipe_insight'] === 'PROMO' ? 'LOW DEMAND' : 'OVERSTOCK';
      $cls = $lvl === 'LOW DEMAND' ? 'amber' : 'teal';
      $emoji = $emojiMap[$i['kategori_id'] ?? 0] ?? '📦';
    ?>
    <div class="tren-item">
      <div class="tren-icon"><?= $emoji ?></div>
      <div class="tren-body">
        <div class="tren-name-row">
          <span class="tren-name"><?= htmlspecialchars($i['nama_produk']??'Produk') ?></span>
          <span class="tren-badge badge-<?= $cls ?>"><?= $lvl ?></span>
        </div>
        <p class="tren-desc"><?= htmlspecialchars(substr($i['pesan_rekomendasi'],0,80)) ?></p>
        <button class="btn-outline-sm" onclick="showToast('info','Membuat strategi promo...','✨')">
          <?= $i['tipe_insight']==='PROMO' ? 'Buat Promo Diskon' : 'Bundling Produk' ?>
        </button>
      </div>
    </div>
    <?php endforeach; ?>

    <?php foreach(array_slice($bundlingInsights??[],0,1) as $i): ?>
    <div class="tren-item">
      <div class="tren-icon">🛍️</div>
      <div class="tren-body">
        <div class="tren-name-row">
          <span class="tren-name"><?= htmlspecialchars($i['nama_produk']??'Produk') ?></span>
          <span class="tren-badge badge-teal">BUNDLING</span>
        </div>
        <p class="tren-desc"><?= htmlspecialchars(substr($i['pesan_rekomendasi'],0,80)) ?></p>
        <button class="btn-dark-sm" onclick="showToast('success','Bundle created!','🎁')">Bundling Produk</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="strategi-box">
    <div class="strategi-icon">💡</div>
    <h3 class="strategi-title">Ingin Strategi Lebih Detail?</h3>
    <p class="strategi-desc">Buka modul "Smart Strategy" untuk mendapatkan rencana pemasaran lengkap yang disesuaikan dengan demografi pelanggan Anda.</p>
    <a href="<?= BASE_URL ?>/insights" class="strategi-link">Eksplorasi Strategi AI →</a>
  </div>
</div>

<!-- Recent Transactions -->
<div class="section-header" style="margin-top:32px">
  <h2 class="section-title">Transaksi Terbaru</h2>
  <a href="<?= BASE_URL ?>/sales" class="link-action">Lihat Semua</a>
</div>
<div class="trx-table-wrap">
  <table class="trx-table">
    <thead><tr><th>No. Transaksi</th><th>Metode</th><th>Subtotal</th><th>Pajak</th><th>Total</th><th>Waktu</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach($recentTrx as $t): ?>
    <tr>
      <td><span class="trx-no"><?= htmlspecialchars($t['nomor_transaksi']) ?></span></td>
      <td><span class="badge-method"><?= $t['metode_pembayaran'] ?></span></td>
      <td><?= rp($t['subtotal']) ?></td>
      <td class="text-muted"><?= rp($t['pajak']) ?></td>
      <td class="trx-total"><?= rp($t['total_bayar']) ?></td>
      <td class="text-muted"><?= date('H:i', strtotime($t['dibuat_pada'])) ?></td>
      <td><span class="badge-selesai">SELESAI</span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
// Pass prediksi data ke JS
$prediksiJs = json_encode(array_values($prediksi ?? []));
$pageScript = "initDashboardCharts($prediksiJs);";
?>
