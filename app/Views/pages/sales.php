<?php // app/Views/pages/sales.php ?>

<div class="page-header">
  <div>
    <h1 class="page-title">Input Penjualan</h1>
    <p class="page-sub">Catat transaksi baru dengan cepat. Sistem kurasi kami akan menyinkronkan stok secara otomatis.</p>
  </div>
</div>

<div class="sales-grid">

  <!-- ── LEFT: Form ──────────────────────── -->
  <div class="sales-left">

    <!-- Detail Produk -->
    <div class="card">
      <div class="card-title-row">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a4a6b" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        <span class="card-section-title">Detail Produk</span>
      </div>

      <div class="form-group">
        <label class="form-label">CARI PRODUK</label>
        <div class="search-wrap">
          <input type="text" id="produkSearch" class="form-input search-input"
                 placeholder="Masukkan nama produk atau SKU..."
                 autocomplete="off" oninput="searchProduk(this.value)">
          <div class="search-shortcut"><kbd>⌘</kbd><kbd>K</kbd></div>
          <div id="produkDropdown" class="produk-dropdown"></div>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">JUMLAH (PCS)</label>
          <div class="qty-control">
            <button class="qty-btn" onclick="changeQty(-1)">−</button>
            <input type="number" id="qtyInput" value="1" min="1" class="qty-input">
            <button class="qty-btn" onclick="changeQty(1)">+</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">HARGA SATUAN</label>
          <div class="harga-input-wrap">
            <span class="rp-prefix">Rp</span>
            <input type="text" id="hargaInput" class="form-input harga-input" placeholder="0" readonly>
          </div>
        </div>
      </div>

      <button class="btn-add-produk" onclick="addToCart()">+ Tambah ke Transaksi</button>

      <!-- Selected product preview -->
      <div id="selectedProdukPreview" class="selected-preview hidden"></div>

      <!-- Cart items -->
      <div id="cartItems" class="cart-items"></div>
    </div>

    <!-- Total Pembayaran -->
    <div class="total-card">
      <div class="total-card-left">
        <div class="total-label">TOTAL PEMBAYARAN</div>
        <div class="total-value" id="totalDisplay">Rp 0</div>
      </div>
      <div class="total-card-right">
        <div class="tax-label">PAJAK (11%)</div>
        <div class="tax-value" id="taxDisplay">Rp 0</div>
      </div>
    </div>

  </div>

  <!-- ── RIGHT: Pembayaran + Transaksi ───── -->
  <div class="sales-right">

    <!-- Metode Pembayaran -->
    <div class="card">
      <div class="card-title-row">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a4a6b" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        <span class="card-section-title">Metode Pembayaran</span>
      </div>
      <div class="payment-methods">
        <button class="payment-btn active" data-method="Tunai" onclick="selectPayment(this,'Tunai')">
          <span class="payment-icon">💵</span>
          <span>TUNAI</span>
        </button>
        <button class="payment-btn" data-method="QRIS" onclick="selectPayment(this,'QRIS')">
          <span class="payment-icon">📱</span>
          <span>QRIS</span>
        </button>
        <button class="payment-btn" data-method="Transfer" onclick="selectPayment(this,'Transfer')">
          <span class="payment-icon">🏦</span>
          <span>TRANSFER</span>
        </button>
      </div>
      <button class="btn-simpan" onclick="simpanTransaksi()">
        Simpan Transaksi →
      </button>
    </div>

    <!-- Recent Transactions -->
    <div class="card">
      <div class="card-title-row" style="justify-content:space-between">
        <div style="display:flex;align-items:center;gap:8px">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a4a6b" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span class="card-section-title">Recent Transactions</span>
        </div>
        <a href="#" class="link-sm">LIHAT SEMUA</a>
      </div>
      <div id="recentTrxList">
        <?php foreach($recentTrx as $t): ?>
        <div class="trx-item-card">
          <div class="trx-item-left">
            <div class="trx-check">✓</div>
            <div>
              <div class="trx-item-no"><?= htmlspecialchars($t['nomor_transaksi']) ?></div>
              <div class="trx-item-meta">
                <?= date('i', strtotime($t['dibuat_pada'])) ?> mins ago · <?= $t['metode_pembayaran'] ?>
              </div>
            </div>
          </div>
          <div class="trx-item-right">
            <div class="trx-item-total">Rp <?= number_format($t['total_bayar'],0,',','.') ?></div>
            <span class="badge-selesai">SELESAI</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- AI Recommendation -->
      <div class="ai-rec-box">
        <div class="ai-rec-icon">💡</div>
        <div class="ai-rec-body">
          <div class="ai-rec-title">AI Recommendation</div>
          <div class="ai-rec-text">Transaksi tunai meningkat 15% hari ini. Pastikan ketersediaan uang kembalian di kasir.</div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Data produk untuk JS -->
<script>
const PRODUK_DATA = <?= json_encode(array_map(function($p) {
  $emojiMap = [1=>'☕',2=>'🥐',3=>'🍯',4=>'📓'];
  $p['emoji'] = $emojiMap[$p['kategori_id']??0] ?? '📦';
  return $p;
}, $produkList)) ?>;
const NOMOR_TRX_BARU = <?= json_encode($nomorBaru) ?>;
</script>
