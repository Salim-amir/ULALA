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
/* ============================
   APEX CHART INSTANCE
============================ */
let _apexChart = null;

function _buildApexOptions(dataset, chartType) {
  const labels  = dataset.map(d => d.label);
  const values  = dataset.map(d => d.val);
  const primary      = '#0d7a6a';
  const primaryLight = '#e6f4f1';
  const isArea = chartType === 'Harian';

  const sharedAxis = {
    xaxis: {
      categories: labels,
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: { style: { colors: '#8aa8a3', fontSize: '11px', fontWeight: 600 } }
    },
    yaxis: {
      min: 0,
      labels: {
        style: { colors: '#8aa8a3', fontSize: '11px' },
        formatter: val => {
          if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + 'M';
          if (val >= 1000)    return 'Rp ' + (val / 1000).toFixed(0) + 'K';
          return 'Rp ' + val;
        }
      }
    },
    grid: {
      borderColor: '#e2ece9',
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true } },
      padding: { top: 4, right: 12, bottom: 0, left: 8 }
    },
    tooltip: {
      theme: 'light',
      style: { fontSize: '12px', fontFamily: "\'Plus Jakarta Sans\', sans-serif" },
      y: { formatter: val => 'Rp ' + Math.round(val).toLocaleString('id-ID') },
      marker: { show: true }
    },
    dataLabels: { enabled: false },
  };

  if (isArea) {
    return {
      series: [{ name: 'Pendapatan', data: values }],
      chart: {
        type: 'area', height: 300,
        toolbar: { show: false }, zoom: { enabled: false },
        animations: { enabled: true, easing: 'easeinout', speed: 700 },
        fontFamily: "\'Plus Jakarta Sans\', sans-serif",
      },
      colors: [primary],
      fill: {
        type: 'gradient',
        gradient: {
          shade: 'light', type: 'vertical',
          gradientToColors: [primaryLight],
          opacityFrom: 0.55, opacityTo: 0.02, stops: [0, 100]
        }
      },
      stroke: { curve: 'smooth', width: 2.5 },
      markers: {
        size: 4,
        colors: ['#fff'],
        strokeColors: primary,
        strokeWidth: 2.5,
        hover: { size: 6 }
      },
      ...sharedAxis,
    };
  } else {
    const colWidth = labels.length <= 4 ? '42%' : labels.length <= 6 ? '52%' : '62%';
    return {
      series: [{ name: 'Pendapatan', data: values }],
      chart: {
        type: 'bar', height: 300,
        toolbar: { show: false }, zoom: { enabled: false },
        animations: { enabled: true, easing: 'easeinout', speed: 600, animateGradually: { enabled: true, delay: 80 } },
        fontFamily: "\'Plus Jakarta Sans\', sans-serif",
      },
      colors: [primary],
      fill: {
        type: 'gradient',
        gradient: {
          shade: 'light', type: 'vertical',
          gradientToColors: [primaryLight],
          opacityFrom: 1, opacityTo: 0.6, stops: [0, 100]
        }
      },
      stroke: { show: false },
      plotOptions: {
        bar: {
          borderRadius: 7,
          borderRadiusApplication: 'end',
          columnWidth: colWidth,
        }
      },
      states: {
        hover: { filter: { type: 'darken', value: 0.85 } }
      },
      ...sharedAxis,
    };
  }
}

function renderChart(dataset, chartType) {
  const container = document.getElementById('sales-chart');
  if (!container) return;

  const type = chartType || window._currentChartType || 'Mingguan';

  if (_apexChart) { _apexChart.destroy(); _apexChart = null; }
  container.innerHTML = '';

  _apexChart = new ApexCharts(container, _buildApexOptions(dataset, type));
  _apexChart.render();
}

function switchChartTab(btn) {
  document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  const tabName = btn.textContent.trim();
  window._currentChartType = tabName;

  if (typeof CHART_DATA !== 'undefined' && CHART_DATA[tabName]) {
    renderChart(CHART_DATA[tabName], tabName);
    return;
  }

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

  renderChart(data[tabName] || data['Mingguan'], tabName);
}

/* ============================
   INPUT PENJUALAN (input_penjualan.php)
============================ */
let itemCount = 1;

function stepQty(button, step) {
  const input = button.parentNode.querySelector('input[type="number"]');
  if (!input) return;
  const newValue = (parseInt(input.value) || 0) + step;
  const min = parseInt(input.min) || 1;
  const max = input.max ? parseInt(input.max) : Infinity;
  if (newValue >= min && newValue <= max) {
    input.value = newValue;
    if (typeof recalcTotal === 'function') recalcTotal();
  }
}

function formatRp(n) {
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function updateHarga(idx) {
  const sel = document.getElementById('produk_id_' + idx);
  if (!sel) return;
  const opt = sel.options[sel.selectedIndex];
  const harga = opt.dataset.harga || 0;
  const stok  = opt.dataset.stok;

  const hargaInput = document.getElementById('harga_satuan_' + idx);
  if (hargaInput) hargaInput.value = harga;

  const stokLabel = document.getElementById('stok_info_' + idx);
  const qtyInput  = document.getElementById('jumlah_' + idx);

  if (opt.value !== '') {
    if (stokLabel) stokLabel.innerHTML = `Stok tersedia: <span style="color:var(--primary)">${stok}</span>`;
    if (qtyInput)  qtyInput.max = stok;
  } else {
    if (stokLabel) stokLabel.innerHTML = 'Stok tersedia: -';
    if (qtyInput)  qtyInput.removeAttribute('max');
  }

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
  // Gunakan PRODUK_LIST yang di-inject oleh input_penjualan.php (data real dari DB)
  // Fallback ke array kosong jika tidak tersedia (halaman lain)
  const list = (typeof PRODUK_LIST !== 'undefined') ? PRODUK_LIST : [];
  let html = '<option value="">-- Pilih Produk --</option>';
  list.forEach(p => {
    const sel      = p.id == selectedId ? 'selected' : '';
    const disabled = p.stok <= 0 ? 'disabled' : '';
    const hargaFmt = p.harga.toLocaleString('id-ID');
    html += `<option value="${p.id}" data-harga="${p.harga}" data-stok="${p.stok}" ${sel} ${disabled}>${p.nama} — Rp ${hargaFmt}</option>`;
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
        <select name="produk_id[]" id="produk_id_${idx}" onchange="updateHarga(${idx})" required>
          ${buildProdukOptions(null)}
        </select>
        <small id="stok_info_${idx}" style="display:block; margin-top:4px; color:var(--text-muted); font-weight:600;">
          Stok tersedia: -
        </small>
      </div>
      <div class="form-field">
        <label>Jumlah</label>
        <div class="qty-control">
          <button type="button" class="btn-qty" onclick="stepQty(this, -1)">−</button>
          <input type="number" name="jumlah[]" id="jumlah_${idx}" class="input-qty" min="1" value="1" oninput="recalcTotal()" required>
          <button type="button" class="btn-qty" onclick="stepQty(this, 1)">+</button>
        </div>
      </div>
      <div class="form-field">
        <label>Harga Satuan (Rp)</label>
        <input type="number" name="harga_satuan[]" id="harga_satuan_${idx}" placeholder="0" min="0" oninput="recalcTotal()" required>
      </div>
    </div>
    <button type="button" class="btn-del" onclick="this.parentElement.remove(); recalcTotal();" style="margin-bottom:15px; width:auto; padding:0 10px;">
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
            <select name="produk_id[]" id="produk_id_0" onchange="updateHarga(0)" required>
              ${buildProdukOptions(null)}
            </select>
            <small id="stok_info_0" style="display:block; margin-top:4px; color:var(--text-muted); font-weight:600;">
              Stok tersedia: -
            </small>
          </div>
          <div class="form-field">
            <label>Jumlah</label>
            <div class="qty-control">
              <button type="button" class="btn-qty" onclick="stepQty(this, -1)">−</button>
              <input type="number" name="jumlah[]" id="jumlah_0" class="input-qty" min="1" value="1" oninput="recalcTotal()" required>
              <button type="button" class="btn-qty" onclick="stepQty(this, 1)">+</button>
            </div>
          </div>
          <div class="form-field">
            <label>Harga Satuan (Rp)</label>
            <input type="number" name="harga_satuan[]" id="harga_satuan_0" placeholder="0" min="0" oninput="recalcTotal()" required>
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

  // Cegah Enter di dalam form penjualan agar tidak trigger submit
  const formPenjualan = document.getElementById('form-penjualan');
  if (formPenjualan) {
    formPenjualan.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        const tag = e.target.tagName;
        // Boleh Enter hanya di textarea, bukan di select/input lain
        if (tag !== 'TEXTAREA') {
          e.preventDefault();
        }
      }
    });
  }
});

/* ============================
   GLOBAL SWEETALERT2 HELPERS
============================ */
const UlalaAlert = {
  // Konfirmasi logout
  logout() {
    Swal.fire({
      title: 'Keluar dari aplikasi?',
      html: 'Sesi kamu akan diakhiri dan kamu perlu login kembali.',
      icon: 'question',
      iconColor: '#0d7a6a',
      showCancelButton: true,
      confirmButtonText: '<i class="fa-solid fa-right-from-bracket" style="margin-right:6px;font-size:11px;"></i>Ya, Logout',
      cancelButtonText: 'Batal',
      reverseButtons: true,
      focusCancel: true,
    }).then(r => { if (r.isConfirmed) window.location.href = 'logout.php'; });
  },

  // Konfirmasi hapus generik
  hapus(nama, onConfirm) {
    Swal.fire({
      title: 'Hapus data ini?',
      html: `<span style="color:var(--text-primary);font-weight:600;">"${nama}"</span><br><span style="font-size:12px;color:#e53e3e;">Tindakan ini tidak dapat dibatalkan.</span>`,
      icon: 'warning',
      iconColor: '#dd6b20',
      showCancelButton: true,
      confirmButtonText: '<i class="fa-solid fa-trash" style="margin-right:6px;font-size:11px;"></i>Ya, Hapus',
      cancelButtonText: 'Batal',
      reverseButtons: true,
      customClass: { confirmButton: 'swal2-confirm btn-danger-confirm' },
    }).then(r => { if (r.isConfirmed) onConfirm(); });
  },

  // Notifikasi sukses (auto-close)
  sukses(pesan, judul = 'Berhasil!') {
    Swal.fire({
      title: judul,
      text: pesan,
      icon: 'success',
      timer: 2500,
      timerProgressBar: true,
      showConfirmButton: false,
    });
  },

  // Notifikasi error
  error(pesan, judul = 'Terjadi Kesalahan') {
    Swal.fire({ title: judul, text: pesan, icon: 'error', confirmButtonText: 'Tutup' });
  },

  // Notifikasi info
  info(pesan, judul = 'Informasi') {
    Swal.fire({ title: judul, text: pesan, icon: 'info', confirmButtonText: 'Mengerti' });
  },
};

// Global shortcut untuk logout (dipanggil dari onclick di header)
function konfirmasiLogout(e) {
  e && e.preventDefault();
  UlalaAlert.logout();
}
</script>
</body>
</html>