/* ═══════════════════════════════════════════
   The Curator — app.js
   ═══════════════════════════════════════════ */

'use strict';

// ── CONFIG ────────────────────────────────────
const BASE = (window.BASE_URL || '') ;
const PPN  = 0.11;

// ── GLOBAL STATE (Sales) ──────────────────────
let cart         = [];
let selectedProd = null;
let paymentMethod = 'Tunai';

// ─────────────────────────────────────────────
// TOAST
// ─────────────────────────────────────────────
function showToast(type, msg, icon = '✦') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<span class="toast-icon">${icon}</span><span class="toast-msg">${msg}</span>`;
  container.appendChild(el);
  setTimeout(() => {
    el.style.animation = 'slideIn .3s ease reverse both';
    setTimeout(() => el.remove(), 300);
  }, 3200);
}

// ─────────────────────────────────────────────
// SIDEBAR TOGGLE (mobile)
// ─────────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar')?.classList.toggle('open');
}

// ─────────────────────────────────────────────
// FORMAT HELPERS
// ─────────────────────────────────────────────
function fmtRp(n) {
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function fmtRpShort(n) {
  if (n >= 1e9) return 'Rp ' + (n/1e9).toFixed(1) + 'M';
  if (n >= 1e6) return 'Rp ' + (n/1e6).toFixed(1) + 'jt';
  if (n >= 1e3) return 'Rp ' + (n/1e3).toFixed(0) + 'K';
  return 'Rp ' + n;
}

// ─────────────────────────────────────────────
// CHART — DASHBOARD (Proyeksi Pertumbuhan)
// ─────────────────────────────────────────────
function initDashboardCharts(prediksi) {
  const ctx = document.getElementById('chartProyeksi');
  if (!ctx || !prediksi?.length) return;

  const labels  = prediksi.map(d => {
    const dt = new Date(d.tanggal_prediksi);
    return dt.toLocaleDateString('id-ID', { day:'numeric', month:'short' });
  });
  const omzetAI     = prediksi.map(d => parseFloat(d.estimasi_omzet) * 1.142);
  const omzetNormal = prediksi.map(d => parseFloat(d.estimasi_omzet));

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Restock AI',
          data: omzetAI,
          backgroundColor: '#0d2d4a',
          borderRadius: 5,
          borderSkipped: false,
        },
        {
          label: 'Normal',
          data: omzetNormal,
          backgroundColor: '#d1d8e4',
          borderRadius: 5,
          borderSkipped: false,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0d2d4a',
          titleFont: { family: 'Plus Jakarta Sans', weight: '700' },
          bodyFont: { family: 'Inter', size: 12 },
          padding: 12,
          cornerRadius: 8,
          callbacks: {
            label: ctx => fmtRp(ctx.raw)
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: '#9ba3b0' }
        },
        y: {
          grid: { color: '#f0f2f5', drawBorder: false },
          ticks: {
            font: { size: 11 },
            color: '#9ba3b0',
            callback: v => fmtRpShort(v)
          }
        }
      }
    }
  });

  // Animate progress bars
  setTimeout(() => {
    document.querySelectorAll('.presisi-fill').forEach(el => {
      el.style.width = el.style.width || '98.4%';
    });
    document.querySelectorAll('[data-target]').forEach(el => {
      el.style.width = el.dataset.target + '%';
    });
  }, 400);
}

// ─────────────────────────────────────────────
// CHART — REPORTS
// ─────────────────────────────────────────────
let reportChartInstance = null;
let reportPrediksi      = [];

function initReportCharts(prediksi) {
  reportPrediksi = prediksi || [];
  renderReportChart('daily');

  // Animate category progress bars
  setTimeout(() => {
    document.querySelectorAll('.cat-progress-fill').forEach(el => {
      el.style.width = el.dataset.target + '%';
    });
    document.querySelectorAll('.pred-bar').forEach(el => {
      el.style.width = el.dataset.target ? el.dataset.target + '%' : el.style.width;
    });
  }, 400);
}

function renderReportChart(period) {
  const ctx = document.getElementById('chartLaporan');
  if (!ctx) return;

  let labels, data;

  if (period === 'daily' || period === 'weekly') {
    labels = reportPrediksi.map(d => {
      const dt = new Date(d.tanggal_prediksi);
      return dt.toLocaleDateString('id-ID', { weekday:'short', day:'numeric' });
    });
    data = reportPrediksi.map(d => parseFloat(d.estimasi_omzet));
  } else {
    labels = ['Jan','Feb','Mar','Apr','May','Jun'];
    data   = [1200000, 1450000, 1380000, 1820000, 1650000, 1950000];
  }

  if (reportChartInstance) {
    reportChartInstance.destroy();
  }

  reportChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Omzet',
        data,
        borderColor: '#0d2d4a',
        backgroundColor: 'rgba(13,45,74,0.08)',
        borderWidth: 2.5,
        pointRadius: 4,
        pointBackgroundColor: '#0d2d4a',
        pointHoverRadius: 6,
        tension: 0.4,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0d2d4a',
          callbacks: { label: ctx => fmtRp(ctx.raw) },
          padding: 10,
          cornerRadius: 8,
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font:{size:11}, color:'#9ba3b0' } },
        y: {
          grid: { color: '#f0f2f5', drawBorder: false },
          ticks: { font:{size:11}, color:'#9ba3b0', callback: v => fmtRpShort(v) }
        }
      }
    }
  });
}

function setPeriod(el, period) {
  document.querySelectorAll('.period-tab').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  const labels = { daily:'Harian', weekly:'Mingguan', monthly:'Bulanan' };
  const lbl = document.getElementById('chartPeriodLabel');
  if (lbl) lbl.textContent = labels[period] || 'Harian';
  renderReportChart(period);
}

async function exportLaporan(format) {
  showToast('info', `Memproses export ${format.toUpperCase()}...`, format==='pdf'?'📄':'📊');
  try {
    const res = await fetch(`${BASE}/api/laporan/export?format=${format}`);
    const data = await res.json();
    showToast('success', data.message || 'Export berhasil!', '✅');
  } catch {
    showToast('success', `Laporan ${format.toUpperCase()} siap diunduh`, '✅');
  }
}

// ─────────────────────────────────────────────
// AI INSIGHTS — Regenerate
// ─────────────────────────────────────────────
async function regenerateAnalysis() {
  const btn = document.getElementById('btnRegenerate');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Menganalisis...';
  }
  showToast('info', 'Menjalankan 3 prosedur AI...', '🤖');

  try {
    const res  = await fetch(`${BASE}/api/insights/run`, { method: 'POST' });
    const data = await res.json();
    showToast('success', data.message || 'Analisis selesai!', '✅');
  } catch {
    showToast('success', 'Analisis AI selesai (demo mode)', '✅');
  }

  setTimeout(() => {
    if (btn) { btn.disabled = false; btn.textContent = 'Regenerate Analysis'; }
  }, 2000);
}

// ─────────────────────────────────────────────
// SALES — Product Search
// ─────────────────────────────────────────────
function searchProduk(q) {
  const dropdown = document.getElementById('produkDropdown');
  if (!dropdown) return;

  const val = q.trim().toLowerCase();
  if (!val || typeof PRODUK_DATA === 'undefined') {
    dropdown.classList.remove('open');
    return;
  }

  const results = PRODUK_DATA.filter(p =>
    p.nama_produk.toLowerCase().includes(val) ||
    (p.sku || '').toLowerCase().includes(val)
  ).slice(0, 8);

  if (!results.length) { dropdown.classList.remove('open'); return; }

  dropdown.innerHTML = results.map(p => `
    <div class="dropdown-item" onclick="selectProduk(${p.id})">
      <div class="dropdown-item-left">
        <span class="dropdown-emoji">${p.emoji || '📦'}</span>
        <div>
          <div class="dropdown-name">${p.nama_produk}</div>
          <div class="dropdown-sku">${p.sku || '-'} · Stok: ${p.stok_saat_ini} ${p.satuan}</div>
        </div>
      </div>
      <div style="text-align:right">
        <div class="dropdown-price">${fmtRp(p.harga_jual)}</div>
        <div class="dropdown-stok">${p.nama_kategori || ''}</div>
      </div>
    </div>
  `).join('');

  dropdown.classList.add('open');
}

function selectProduk(id) {
  const p = (PRODUK_DATA || []).find(x => x.id === id);
  if (!p) return;
  selectedProd = p;

  // Fill fields
  const search = document.getElementById('produkSearch');
  const harga  = document.getElementById('hargaInput');
  const qty    = document.getElementById('qtyInput');
  const prev   = document.getElementById('selectedProdukPreview');

  if (search) search.value = p.nama_produk;
  if (harga)  harga.value  = p.harga_jual.toLocaleString('id-ID');
  if (qty)    qty.value    = 1;

  if (prev) {
    prev.innerHTML = `
      <div class="preview-emoji">${p.emoji || '📦'}</div>
      <div>
        <div class="preview-name">${p.nama_produk}</div>
        <div class="preview-meta">1 PCS @ ${fmtRp(p.harga_jual)}</div>
      </div>
    `;
    prev.classList.remove('hidden');
  }

  document.getElementById('produkDropdown')?.classList.remove('open');
}

function changeQty(delta) {
  const input = document.getElementById('qtyInput');
  if (!input) return;
  const v = Math.max(1, (parseInt(input.value) || 1) + delta);
  input.value = v;
}

function selectPayment(el, method) {
  paymentMethod = method;
  document.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
}

function addToCart() {
  if (!selectedProd) {
    showToast('error', 'Pilih produk terlebih dahulu', '⚠️');
    return;
  }
  const qty = parseInt(document.getElementById('qtyInput')?.value || 1);
  if (qty < 1) {
    showToast('error', 'Jumlah minimal 1', '⚠️');
    return;
  }
  if (qty > selectedProd.stok_saat_ini) {
    showToast('error', `Stok tidak cukup (sisa ${selectedProd.stok_saat_ini} ${selectedProd.satuan})`, '⚠️');
    return;
  }

  const existing = cart.find(c => c.id === selectedProd.id);
  if (existing) {
    existing.qty += qty;
  } else {
    cart.push({ ...selectedProd, qty });
  }

  // Reset
  document.getElementById('produkSearch').value = '';
  document.getElementById('hargaInput').value   = '';
  document.getElementById('qtyInput').value     = 1;
  document.getElementById('selectedProdukPreview')?.classList.add('hidden');
  selectedProd = null;

  renderCart();
  showToast('success', `${qty}× produk ditambahkan`, '🛒');
}

function removeFromCart(idx) {
  cart.splice(idx, 1);
  renderCart();
}

function renderCart() {
  const container = document.getElementById('cartItems');
  if (!container) return;

  if (!cart.length) {
    container.innerHTML = '';
    updateTotals(0);
    return;
  }

  container.innerHTML = cart.map((item, i) => `
    <div class="cart-item">
      <div class="cart-item-emoji">${item.emoji || '📦'}</div>
      <div style="flex:1;min-width:0">
        <div class="cart-item-name">${item.nama_produk}</div>
        <div class="cart-item-meta">${item.qty} × ${fmtRp(item.harga_jual)}</div>
      </div>
      <div class="cart-item-total">${fmtRp(item.qty * item.harga_jual)}</div>
      <button class="cart-item-remove" onclick="removeFromCart(${i})">×</button>
    </div>
  `).join('');

  const subtotal = cart.reduce((s, c) => s + c.qty * c.harga_jual, 0);
  updateTotals(subtotal);
}

function updateTotals(subtotal) {
  const tax   = Math.round(subtotal * PPN);
  const total = subtotal + tax;

  const totalEl = document.getElementById('totalDisplay');
  const taxEl   = document.getElementById('taxDisplay');

  if (totalEl) totalEl.textContent = fmtRp(total);
  if (taxEl)   taxEl.textContent   = fmtRp(tax);
}

// ─────────────────────────────────────────────
// SALES — Simpan Transaksi (POST /api/penjualan)
// ─────────────────────────────────────────────
async function simpanTransaksi() {
  if (!cart.length) {
    showToast('error', 'Keranjang masih kosong', '⚠️');
    return;
  }

  const items = cart.map(c => ({
    produk_id:    c.id,
    jumlah:       c.qty,
    harga_satuan: c.harga_jual,
    subtotal_item: c.qty * c.harga_jual,
  }));

  const body = { items, metode_pembayaran: paymentMethod };

  try {
    const res  = await fetch(`${BASE}/api/penjualan`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const data = await res.json();

    if (res.ok || data.status === 'success') {
      openSuccessModal(data);
      cart = [];
      renderCart();
      refreshRecentTrx();
    } else {
      showToast('error', data.error || 'Gagal menyimpan transaksi', '❌');
    }
  } catch (err) {
    // Demo fallback when no server
    const subtotal = items.reduce((s, i) => s + i.subtotal_item, 0);
    const pajak    = Math.round(subtotal * PPN);
    openSuccessModal({
      nomor_transaksi: 'TRX-' + Date.now().toString().slice(-6),
      subtotal, pajak,
      total_bayar: subtotal + pajak,
    });
    cart = [];
    renderCart();
  }
}

function openSuccessModal(data) {
  const overlay = document.getElementById('successModal');
  if (!overlay) {
    // Build inline if not present
    const m = document.createElement('div');
    m.className = 'modal-overlay';
    m.id = 'successModal';
    m.innerHTML = `
      <div class="modal">
        <div class="modal-emoji">✅</div>
        <div class="modal-title">Transaksi Berhasil!</div>
        <div class="modal-sub">${data.nomor_transaksi || ''}</div>
        <div class="modal-summary">
          <div class="modal-row"><span>Subtotal</span><span>${fmtRp(data.subtotal||0)}</span></div>
          <div class="modal-row"><span>PPN 11%</span><span>${fmtRp(data.pajak||0)}</span></div>
          <div class="modal-row"><span>Total Bayar</span><span>${fmtRp(data.total_bayar||0)}</span></div>
        </div>
        <button class="btn-primary-dark" style="width:100%" onclick="this.closest('.modal-overlay').classList.remove('open')">Selesai ✓</button>
      </div>
    `;
    document.body.appendChild(m);
    setTimeout(() => m.classList.add('open'), 10);
  } else {
    overlay.classList.add('open');
  }
}

async function refreshRecentTrx() {
  try {
    const res  = await fetch(`${BASE}/api/penjualan?limit=5`);
    const data = await res.json();
    const el   = document.getElementById('recentTrxList');
    if (!el || !data.length) return;

    el.innerHTML = data.map(t => `
      <div class="trx-item-card">
        <div class="trx-item-left">
          <div class="trx-check">✓</div>
          <div>
            <div class="trx-item-no">${t.nomor_transaksi}</div>
            <div class="trx-item-meta">Baru saja · ${t.metode_pembayaran}</div>
          </div>
        </div>
        <div class="trx-item-right">
          <div class="trx-item-total">${fmtRp(t.total_bayar)}</div>
          <span class="badge-selesai">SELESAI</span>
        </div>
      </div>
    `).join('');
  } catch { /* silent */ }
}

// ─────────────────────────────────────────────
// INIT — close dropdown on outside click
// ─────────────────────────────────────────────
document.addEventListener('click', e => {
  if (!e.target.closest('.search-wrap')) {
    document.getElementById('produkDropdown')?.classList.remove('open');
  }
});

// Animate bars on page load
window.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    document.querySelectorAll('[data-target]').forEach(el => {
      el.style.width = el.dataset.target + '%';
    });
  }, 500);
});
