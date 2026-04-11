# The Curator — Smart UMKM Assistant
> Editorial Intelligence Dashboard · PHP MVC + PostgreSQL

---

## 📁 Struktur Folder

```
the-curator/
├── index.php                    ← Front Controller
├── .htaccess                    ← Apache URL Rewriting
│
├── config/
│   ├── app.php                  ← Konfigurasi aplikasi & MOCK_MODE
│   └── database.php             ← PDO PostgreSQL connection
│
├── database/
│   └── seeder.php               ← Mock data (sesuai ulala_db_new.sql)
│
├── routes/
│   └── web.php                  ← URL Router (Web + API)
│
├── app/
│   ├── Models/
│   │   ├── BaseModel.php
│   │   ├── KategoriModel.php
│   │   ├── ProdukModel.php
│   │   ├── PenjualanModel.php
│   │   ├── DetailPenjualanModel.php
│   │   ├── AiInsightModel.php
│   │   └── PrediksiPerformaModel.php
│   │
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── DashboardController.php
│   │   ├── SalesController.php
│   │   ├── InsightController.php
│   │   ├── ReportController.php
│   │   └── ApiController.php
│   │
│   └── Views/
│       ├── layouts/
│       │   └── main.php         ← Master layout (sidebar + topbar)
│       └── pages/
│           ├── dashboard.php
│           ├── sales.php
│           ├── insights.php
│           └── reports.php
│
└── public/
    ├── css/app.css
    └── js/app.js
```

---

## 🚀 Setup (XAMPP / Laragon)

### 1. Letakkan folder
```
C:/xampp/htdocs/the-curator/
```

### 2. Mode Demo (tanpa DB)
File `config/app.php` sudah set `MOCK_MODE = true`.  
Buka browser: `http://localhost/the-curator/`

### 3. Hubungkan ke PostgreSQL (pgAdmin)
Edit `config/app.php`:
```php
define('MOCK_MODE', false);
```
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'curator_db');       // nama DB di pgAdmin
define('DB_USER', 'postgres');
define('DB_PASS', 'yourpassword');
```
Import `ulala_db_new.sql` ke pgAdmin, lalu akses aplikasi.

---

## 🌐 URL Routes

| URL | Controller | Keterangan |
|-----|-----------|------------|
| `/the-curator/` | DashboardController | Dashboard utama |
| `/the-curator/sales` | SalesController | Input Penjualan |
| `/the-curator/insights` | InsightController | AI Insights |
| `/the-curator/reports` | ReportController | Laporan & Ekspor |

### REST API Endpoints

| Method | URL | Keterangan |
|--------|-----|-----------|
| GET | `/api/produk` | Semua produk |
| GET | `/api/produk/kritis` | View v_produk_kritis |
| GET | `/api/penjualan` | List transaksi |
| POST | `/api/penjualan` | Simpan transaksi → trigger kurangi stok |
| GET | `/api/insights` | AI insights aktif |
| POST | `/api/insights/run` | Jalankan 3 stored procedure AI |
| GET | `/api/prediksi` | Data prediksi_performa |
| GET | `/api/dashboard/stats` | Statistik dashboard |
| GET | `/api/laporan/kategori` | Performa per kategori |
| GET | `/api/laporan/export?format=pdf` | Export laporan |

---

## ⚙️ Stored Procedures (PostgreSQL)

Dipanggil via `POST /api/insights/run`:
- `hitung_ai_restock()` — Insert RESTOCK insights untuk produk kritis
- `hitung_ai_slow_moving()` — Insert PROMO insights untuk produk lambat
- `hitung_ai_bundling()` — Insert BUNDLING insights dari pola beli

---

## 🗃️ Database Triggers

- **`trg_kurangi_stok`** — Otomatis kurangi `stok_saat_ini` saat INSERT ke `detail_penjualan`
- **`v_produk_kritis`** — View produk dengan stok ≤ stok_minimum
