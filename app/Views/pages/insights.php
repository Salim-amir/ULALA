<?php // app/Views/pages/insights.php ?>

<!-- ── PAGE HEADER ────────────────────────── -->
<div class="page-header-row">
  <div>
    <div class="page-breadcrumb">
      <span class="badge-ai-powered" style="font-size:11px">AI POWERED</span>
      <span class="breadcrumb-time">Updated 5m ago</span>
    </div>
    <h1 class="page-title">Smart Insights (AI)</h1>
  </div>
  <div class="page-header-actions">
    <button class="btn-outline" onclick="showToast('info','Mengekspor laporan...','📄')">Export Report</button>
    <button class="btn-primary-dark" id="btnRegenerate" onclick="regenerateAnalysis()">Regenerate Analysis</button>
  </div>
</div>

<!-- ── TOP 3 PRODUK + STOCK PREDICTION ───── -->
<div class="insights-top-grid">

  <!-- Top 3 Produk -->
  <div class="card">
    <div class="card-header-flex">
      <div>
        <h3 class="card-title-lg">Top 3 Products</h3>
        <p class="card-sub">Performance analysis for the last 30 days</p>
      </div>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a4a6b" stroke-width="2" opacity="0.4"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </div>
    <div class="top3-list">
      <?php foreach(array_slice($topProduk,0,3) as $i=>$p):
        $tren = $p['tren'];
        $trenCls = $tren >= 0 ? 'tren-up' : 'tren-down';
        $trenIcon = $tren >= 0 ? '+' : '';
        $emojiMap = ['Beverages'=>'☕','Bakery'=>'🥐','Pantry'=>'🍯','Stationery'=>'📓'];
        $emoji = $emojiMap[$p['kategori']] ?? '📦';
      ?>
      <div class="top3-item">
        <div class="top3-rank">0<?= $i+1 ?></div>
        <div class="top3-img"><?= $emoji ?></div>
        <div class="top3-info">
          <div class="top3-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
          <div class="top3-meta"><?= $p['kategori'] ?> · <?= number_format($p['qty_terjual'],0,',','.') ?> sold</div>
        </div>
        <div class="top3-tren <?= $trenCls ?>">
          <div class="tren-pct"><?= $trenIcon ?><?= number_format($tren,1) ?>%</div>
          <div class="tren-lbl">TREND INDICATOR</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Stock Prediction Panel -->
  <div class="card card-navy">
    <div class="card-header-flex">
      <h3 class="card-title-lg white">Stock<br>Prediction</h3>
      <span class="badge-active-model">ACTIVE MODEL</span>
    </div>
    <div class="prediction-accuracy">
      <span class="acc-num">85</span><span class="acc-pct">%</span>
    </div>
    <div class="acc-label">Model Accuracy Rating</div>
    <div class="prediction-detail-box">
      <div class="pred-detail-label">FORECAST HORIZON</div>
      <div class="pred-detail-val">14 Days Out</div>
    </div>
    <div class="prediction-detail-box amber-box" style="margin-top:8px">
      <div class="pred-detail-label">NEXT CYCLE RISK</div>
      <div class="pred-detail-val amber">Medium (7% Variance)</div>
    </div>
    <p class="pred-quote">"Inventory levels are optimized for the upcoming weekend surge based on historical seasonal patterns."</p>
  </div>

</div>

<!-- ── RESTOCK RECOMMENDATIONS ────────────── -->
<div class="insight-section" style="margin-top:28px">
  <div class="insight-section-grid">

    <!-- Left: Urgent Restock Info -->
    <div class="restock-urgency-box">
      <div class="urgency-badge">✦ URGENT RESTOCK</div>
      <h3 class="urgency-title">Restock<br>Recommendations</h3>
      <p class="urgency-desc">High sales velocity detected in your bakery section. Current stock levels will deplete in 48 hours.</p>
      <button class="btn-outline-dark" onclick="showToast('info','Menghubungi supplier...','📞')">Order from Supplier</button>
    </div>

    <!-- Middle: Restock list -->
    <div class="restock-items-col">
      <?php foreach(array_slice($restockInsights,0,3) as $r):
        $pct = ($r['stok_saat_ini']/$r['stok_minimum']);
        $isCrit = $pct <= 0.5;
      ?>
      <div class="restock-line-item <?= $isCrit ? 'border-red':'border-amber' ?>">
        <div class="restock-line-info">
          <div class="restock-line-name"><?= htmlspecialchars($r['nama_produk']) ?></div>
          <div class="restock-line-meta">Inventory: <?= $r['stok_saat_ini'] ?> Units Left</div>
        </div>
        <div class="restock-line-icon <?= $isCrit ? 'icon-red':'icon-amber' ?>">
          <?= $isCrit ? '⚠' : 'ℹ' ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Right: Promo Recommendations -->
    <div class="promo-panel">
      <div class="promo-panel-header">
        <div>
          <h3 class="promo-title">Promo Recommendations</h3>
          <p class="promo-sub">AI strategies for slow-moving items</p>
        </div>
        <span>📢</span>
      </div>
      <?php foreach(array_slice($promoInsights,0,2) as $p): ?>
      <div class="promo-item" onclick="showToast('info','Membuka detail strategi...','💡')">
        <div class="promo-item-icon">🏷️</div>
        <div class="promo-item-body">
          <div class="promo-item-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
          <div class="promo-item-age">INVENTORY AGE: 45 DAYS</div>
          <div class="promo-item-prop">Proposed: "Buat Promo Bundling" →</div>
        </div>
      </div>
      <?php endforeach; ?>
      <button class="btn-ghost-full" onclick="showToast('info','Membuka semua marketing ideas...','✨')">View All Marketing Ideas</button>
    </div>

  </div>
</div>

<!-- ── THE CURATOR'S TIP ──────────────────── -->
<div class="curator-tip">
  <div class="curator-tip-icon">💡</div>
  <div class="curator-tip-body">
    <div class="curator-tip-title">The Curator's Tip</div>
    <p class="curator-tip-text">Kami menemukan pola: pelanggan yang membeli <strong>"<?= htmlspecialchars($topProduk[0]['nama_produk']??'Specialty Arabica Blend') ?>"</strong> cenderung juga membeli produk kategori lain. Pertimbangkan layout cross-merchandising untuk minggu depan.</p>
  </div>
</div>

<!-- Bundling Insights -->
<?php if(!empty($bundlingInsights)): ?>
<div class="section-header" style="margin-top:28px">
  <h2 class="section-title">Rekomendasi Bundling AI</h2>
  <span class="badge-count"><?= count($bundlingInsights) ?> saran</span>
</div>
<div class="bundling-grid">
  <?php foreach($bundlingInsights as $b): ?>
  <div class="bundling-card">
    <div class="bundling-icon">🛍️</div>
    <div class="bundling-name"><?= htmlspecialchars($b['nama_produk']) ?></div>
    <div class="bundling-msg"><?= htmlspecialchars($b['pesan_rekomendasi']) ?></div>
    <div class="bundling-skor">Presisi: <?= $b['skor_presisi'] ?>%</div>
    <button class="btn-dark-sm" style="margin-top:10px;width:100%" onclick="showToast('success','Bundle dibuat!','🎁')">Buat Bundle</button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
