<?php // app/Views/pages/reports.php
function rpFmt(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}
?>

<!-- ── PAGE HEADER ────────────────────────── -->
<div class="page-header-row" style="align-items:flex-start">
  <div>
    <h1 class="page-title">Laporan &amp; Ekspor</h1>
    <p class="page-sub">Curated analysis of your store's editorial performance.</p>
  </div>
  <div class="period-tabs">
    <button class="period-tab active" onclick="setPeriod(this,'daily')">Daily</button>
    <button class="period-tab" onclick="setPeriod(this,'weekly')">Weekly</button>
    <button class="period-tab" onclick="setPeriod(this,'monthly')">Monthly</button>
  </div>
</div>

<!-- ── MAIN REPORT GRID ───────────────────── -->
<div class="report-grid">

  <!-- LEFT: Performance by Category -->
  <div class="report-left">
    <div class="card">
      <div class="card-header-flex">
        <h3 class="card-title-lg">Performance by Category</h3>
        <span class="badge-top-cat">Top Categories</span>
      </div>

      <div class="cat-perf-list">
        <?php
        $colors = ['#1a4a6b','#2a6496','#4a8ab0','#7aafc8'];
        foreach($kategoriPerf as $idx=>$k):
          $color = $colors[$idx % count($colors)];
        ?>
        <div class="cat-perf-item">
          <div class="cat-perf-header">
            <span class="cat-perf-name"><?= htmlspecialchars($k['nama']) ?></span>
            <span class="cat-perf-amount"><?= rpFmt($k['omzet']) ?> (<?= $k['pct'] ?>%)</span>
          </div>
          <div class="cat-progress-track">
            <div class="cat-progress-fill" style="width:0%;background:<?= $color ?>" data-target="<?= $k['pct'] ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Editorial Insight -->
      <div class="editorial-insight">
        <div class="editorial-icon">💡</div>
        <div class="editorial-body">
          <div class="editorial-title">"Editorial Insight"</div>
          <p class="editorial-text">Stationery demand meningkat 12% minggu ini. Pertimbangkan bundling dengan kategori lain untuk penjualan margin tinggi.</p>
        </div>
      </div>
    </div>

    <!-- Sales Chart -->
    <div class="card" style="margin-top:20px">
      <div class="card-header-flex">
        <h3 class="card-title-lg">Grafik Penjualan</h3>
        <span class="badge-top-cat" id="chartPeriodLabel">Harian</span>
      </div>
      <div class="chart-wrap"><canvas id="chartLaporan"></canvas></div>
    </div>

    <!-- Export Cards -->
    <div class="export-grid" style="margin-top:20px">
      <div class="export-card" onclick="exportLaporan('pdf')">
        <div class="export-icon export-pdf">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div class="export-body">
          <div class="export-title">Export PDF</div>
          <div class="export-desc">Get a professional, branded report for stakeholders.</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </div>

      <div class="export-card" onclick="exportLaporan('excel')">
        <div class="export-icon export-excel">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
        </div>
        <div class="export-body">
          <div class="export-title">Export Excel</div>
          <div class="export-desc">Download raw data for deep-dive analysis and taxes.</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </div>
    </div>
  </div>

  <!-- RIGHT: Profit + Stats -->
  <div class="report-right">

    <!-- Total Profit Card -->
    <div class="card card-navy">
      <div class="card-label-light">TOTAL PROFIT THIS MONTH</div>
      <div class="profit-value">Rp 18.250.000</div>
      <div class="growth-row">
        <span class="growth-icon">↗</span>
        <span class="growth-text">Growth Forecast: +<?= number_format($growthForecast,1) ?>%</span>
      </div>
      <p class="growth-note">Based on current trajectory, your net profit is projected to reach Rp 21M by the end of next month.</p>
    </div>

    <!-- Quick Stats -->
    <div class="card" style="margin-top:16px">
      <h3 class="card-title-lg" style="margin-bottom:16px">Quick Stats</h3>
      <?php
      $stats = [
        ['label'=>'Total Transaksi','value'=>number_format($totalTrx),'icon'=>'🧾'],
        ['label'=>'Omzet Bulan Ini','value'=>'Rp '.number_format($omzetBulanIni,0,',','.'),'icon'=>'💰'],
        ['label'=>'Rata-rata Nilai Transaksi','value'=>$totalTrx>0?'Rp '.number_format($omzetBulanIni/$totalTrx,0,',','.'):'—','icon'=>'📊'],
        ['label'=>'Growth Forecast','value'=>'+'.number_format($growthForecast,1).'%','icon'=>'📈'],
      ];
      foreach($stats as $s): ?>
      <div class="quick-stat-row">
        <div class="qs-left">
          <span class="qs-icon"><?= $s['icon'] ?></span>
          <span class="qs-label"><?= $s['label'] ?></span>
        </div>
        <span class="qs-value"><?= $s['value'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Prediksi Performa mini -->
    <div class="card" style="margin-top:16px">
      <div class="card-header-flex" style="margin-bottom:12px">
        <h3 class="card-title-lg">Prediksi 7 Hari</h3>
        <span style="font-size:11px;color:#888">prediksi_performa</span>
      </div>
      <?php foreach(array_slice($prediksi,0,5) as $p): ?>
      <div class="prediksi-row">
        <span class="pred-date"><?= date('D d/m', strtotime($p['tanggal_prediksi'])) ?></span>
        <div class="pred-bar-wrap">
          <div class="pred-bar" style="width:<?= min(100,$p['persentase_pertumbuhan']*5) ?>%"></div>
        </div>
        <span class="pred-pct">+<?= $p['persentase_pertumbuhan'] ?>%</span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- System Status Bar -->
<div class="system-status-bar">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c9a227" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
  <span>System Status: All fiscal reports are synchronized. Last automated export was generated 4 hours ago.</span>
</div>

<?php
$prediksiJs = json_encode(array_values($prediksi ?? []));
$pageScript = "initReportCharts($prediksiJs);";
?>
