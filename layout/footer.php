<!-- ↓↓↓ KONTEN HALAMAN BERAKHIR DI SINI ↓↓↓ -->
    </div><!-- /page-body -->
  </div><!-- /main-content -->

</div><!-- /app-shell -->

<!-- ==============================
     JAVASCRIPT GLOBAL
     Fungsi-fungsi ini tersedia di semua halaman yang meng-include footer ini.
============================== -->
<script>
/* ============================
   SIDEBAR MOBILE TOGGLE
============================ */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  sidebar.classList.toggle('open');
  overlay.classList.toggle('visible');
}

function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('visible');
}

// Tutup sidebar jika resize ke desktop
window.addEventListener('resize', () => {
  if (window.innerWidth > 768) closeSidebar();
});

/* ============================
   SALES CHART (dashboard.php)
   Dipanggil dari dashboard.php setelah DOM siap.
============================ */
function renderChart(dataset) {
  const container = document.getElementById('sales-chart');
  if (!container) return;
  container.innerHTML = '';

  const max = Math.max(...dataset.map(d => d.val));
  const containerH = 152;

  dataset.forEach((d, i) => {
    const barH = Math.round((d.val / max) * containerH);
    const isHighlight = d.val === max;

    const wrap = document.createElement('div');
    wrap.className = 'bar-wrap';

    const bar = document.createElement('div');
    bar.className = 'bar' + (isHighlight ? ' highlight' : '');
    bar.style.height = '4px';
    bar.style.transition = `height 0.7s cubic-bezier(0.4,0,0.2,1) ${i * 0.08}s`;

    if (isHighlight) {
      const tip = document.createElement('div');
      tip.className = 'bar-tooltip';
      tip.textContent = 'Rp ' + (d.val / 1000000).toFixed(1) + 'M';
      bar.appendChild(tip);
    }

    const lbl = document.createElement('div');
    lbl.className = 'bar-label';
    lbl.textContent = d.label;

    wrap.appendChild(bar);
    wrap.appendChild(lbl);
    container.appendChild(wrap);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => { bar.style.height = barH + 'px'; });
    });
  });
}

function switchChartTab(btn) {
  document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');

  const data = {
    'Harian': [
      {label:'Sen', val:820000},{label:'Sel', val:1100000},{label:'Rab', val:950000},
      {label:'Kam', val:1300000},{label:'Jum', val:1750000},{label:'Sab', val:2100000},{label:'Min', val:1450000}
    ],
    'Mingguan': [
      {label:'Minggu 1', val:3200000},{label:'Minggu 2', val:4800000},
      {label:'Minggu 3', val:3700000},{label:'Minggu 4', val:4100000}
    ],
    'Bulanan': [
      {label:'Jan', val:18000000},{label:'Feb', val:21000000},
      {label:'Mar', val:19500000},{label:'Apr', val:24500000}
    ]
  };

  renderChart(data[btn.textContent] || data['Mingguan']);
}

/* ============================
   INPUT PENJUALAN (input_penjualan.php)
============================ */
let itemCount = 1;

function formatRp(n) {
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function updateHarga(idx) {
  const sel = document.getElementById('produk_id_' + idx);
  if (!sel) return;
  const opt = sel.options[sel.selectedIndex];
  const harga = opt.dataset.harga || 0;
  const hargaInput = document.getElementById('harga_satuan_' + idx);
  if (hargaInput) hargaInput.value = harga;
  recalcTotal();
}

function recalcTotal() {
  let subtotal = 0;
  // Iterasi semua baris item yang ada di DOM
  document.querySelectorAll('.detail-item').forEach((item, i) => {
    const jumlahEl = item.querySelector('[name="jumlah[]"]');
    const hargaEl  = item.querySelector('[name="harga_satuan[]"]');
    const jumlah = parseFloat(jumlahEl?.value) || 0;
    const harga  = parseFloat(hargaEl?.value)  || 0;
    subtotal += jumlah * harga;
  });

  const pajakPct = parseFloat(document.getElementById('pajak_persen')?.value) || 0;
  const pajak    = subtotal * (pajakPct / 100);
  const total    = subtotal + pajak;

  const elSub   = document.getElementById('disp-subtotal');
  const elPajak = document.getElementById('disp-pajak');
  const elTotal = document.getElementById('disp-total');

  if (elSub)   elSub.textContent   = formatRp(subtotal);
  if (elPajak) elPajak.textContent = formatRp(pajak) + ' (' + pajakPct + '%)';
  if (elTotal) elTotal.textContent = formatRp(total);

  const inSub   = document.getElementById('subtotal');
  const inPajak = document.getElementById('pajak');
  const inTotal = document.getElementById('total_bayar');
  if (inSub)   inSub.value   = subtotal.toFixed(2);
  if (inPajak) inPajak.value = pajak.toFixed(2);
  if (inTotal) inTotal.value = total.toFixed(2);
}

function buildProdukOptions(selectedId) {
  /* 
   * TODO: Ganti dengan <select> yang diisi dinamis dari PHP/AJAX.
   * Saat ini menggunakan data statis sebagai placeholder.
   */
  const products = [
    {id: 1, nama: 'Kopi Arabica Gayo 250g',   harga: 85000},
    {id: 2, nama: 'Madu Hutan Murni 500ml',   harga: 120000},
    {id: 3, nama: 'Gula Semut Aren 1kg',       harga: 45000},
    {id: 4, nama: 'Teh Hijau Organik 100g',    harga: 35000},
    {id: 5, nama: 'Temulawak Kering 500g',     harga: 75000},
  ];
  let html = '<option value="">-- Pilih Produk --</option>';
  products.forEach(p => {
    const sel = p.id == selectedId ? 'selected' : '';
    const hargaFmt = p.harga.toLocaleString('id-ID');
    html += `<option value="${p.id}" data-harga="${p.harga}" ${sel}>${p.nama} — Rp ${hargaFmt}</option>`;
  });
  return html;
}

function addItem() {
  const container = document.getElementById('detail-items');
  if (!container) return;
  const idx = itemCount;

  const div = document.createElement('div');
  div.className = 'detail-item';
  div.dataset.index = idx;
  div.innerHTML = `
    <div class="form-row grid-4" style="margin-bottom:10px;">
      <div class="form-field" style="grid-column:span 2;">
        <label>Produk</label>
        <select name="produk_id[]" id="produk_id_${idx}" onchange="updateHarga(${idx})">
          ${buildProdukOptions(null)}
        </select>
      </div>
      <div class="form-field">
        <label>Jumlah</label>
        <input type="number" name="jumlah[]" id="jumlah_${idx}" min="1" value="1" oninput="recalcTotal()">
      </div>
      <div class="form-field">
        <label>Harga Satuan</label>
        <input type="number" name="harga_satuan[]" id="harga_satuan_${idx}" placeholder="0" oninput="recalcTotal()">
      </div>
    </div>
    <button type="button" class="btn-sm" style="margin-bottom:10px;color:var(--danger);border-color:var(--danger);" onclick="removeItem(this)">
      <i class="fa-solid fa-trash"></i> Hapus Baris
    </button>`;

  container.appendChild(div);
  itemCount++;
  recalcTotal();
}

function removeItem(btn) {
  btn.closest('.detail-item').remove();
  recalcTotal();
}

function resetPenjualan() {
  const form = document.getElementById('form-penjualan');
  if (form) form.reset();

  const container = document.getElementById('detail-items');
  if (container) {
    container.innerHTML = `
      <div class="detail-item" data-index="0">
        <div class="form-row grid-4" style="margin-bottom:10px;">
          <div class="form-field" style="grid-column:span 2;">
            <label>Produk</label>
            <select name="produk_id[]" id="produk_id_0" onchange="updateHarga(0)">
              ${buildProdukOptions(null)}
            </select>
          </div>
          <div class="form-field">
            <label>Jumlah</label>
            <input type="number" name="jumlah[]" id="jumlah_0" min="1" value="1" oninput="recalcTotal()">
          </div>
          <div class="form-field">
            <label>Harga Satuan</label>
            <input type="number" name="harga_satuan[]" id="harga_satuan_0" placeholder="0" oninput="recalcTotal()">
          </div>
        </div>
      </div>`;
  }
  itemCount = 1;
  recalcTotal();
}

/* ============================
   PASSWORD TOGGLE (login.php & register.php)
============================ */
function togglePass(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa-regular fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa-regular fa-eye';
  }
}

/* ============================
   AI INSIGHTS – Sync button
============================ */
function syncInsights() {
  const icon = document.getElementById('sync-icon');
  if (!icon) return;
  icon.style.animation = 'spin 0.8s linear infinite';
  // TODO: ganti dengan fetch() ke endpoint PHP yang menjalankan prosedur hitung_ai_*
  setTimeout(() => {
    icon.style.animation = '';
    showFlash('success', 'Insights berhasil diperbarui!');
  }, 1200);
}

/* ============================
   KELOLA PRODUK – Live search (client-side)
   Untuk filter server-side, gunakan form GET ke kelola_produk.php?q=...
============================ */
function filterProduk(val) {
  const rows = document.querySelectorAll('#produk-tbody tr');
  val = val.toLowerCase();
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}

/* ============================
   FLASH MESSAGE HELPER
============================ */
function showFlash(type, message) {
  const existing = document.getElementById('flash-msg');
  if (existing) existing.remove();

  const icons = { success: 'fa-circle-check', danger: 'fa-circle-xmark', warning: 'fa-triangle-exclamation' };
  const div = document.createElement('div');
  div.id = 'flash-msg';
  div.className = `alert alert-${type}`;
  div.style.cssText = 'position:fixed;top:80px;right:24px;z-index:9999;min-width:280px;max-width:400px;animation:fadeInDown 0.3s ease;';
  div.innerHTML = `<i class="fa-solid ${icons[type] || 'fa-info-circle'}"></i> ${message}`;
  document.body.appendChild(div);
  setTimeout(() => div.remove(), 4000);
}

/* ============================
   AUTO-DISMISS PHP FLASH MESSAGES
============================ */
document.addEventListener('DOMContentLoaded', () => {
  const phpFlash = document.getElementById('php-flash');
  if (phpFlash) setTimeout(() => phpFlash.style.opacity = '0', 3500);
});
</script>
</body>
</html>