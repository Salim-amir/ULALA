<?php
// ─── database/seeder.php ──────────────────────
// Data persis sesuai ulala_db_new.sql +
// data tambahan untuk demo UI lengkap

class Seeder
{
    // ── TABLE: kategori ───────────────────────
    public static function kategori(): array {
        return [
            ['id'=>1,'nama_kategori'=>'Beverages', 'deskripsi'=>'Minuman premium & specialty coffee'],
            ['id'=>2,'nama_kategori'=>'Bakery',    'deskripsi'=>'Roti & kue artisan segar'],
            ['id'=>3,'nama_kategori'=>'Pantry',    'deskripsi'=>'Bahan makanan & condiment pilihan'],
            ['id'=>4,'nama_kategori'=>'Stationery','deskripsi'=>'Alat tulis & aksesoris premium'],
        ];
    }

    // ── TABLE: produk (dari SQL + tambahan demo) ──
    public static function produk(): array {
        return [
            // From SQL dump
            ['id'=>1,'sku'=>'PRD-001','kategori_id'=>1,'nama_produk'=>'Kopi Arabika 250g',
             'harga_jual'=>125000,'stok_saat_ini'=>4,'stok_minimum'=>10,'satuan'=>'pcs','url_gambar'=>null],
            ['id'=>2,'sku'=>'PRD-045','kategori_id'=>1,'nama_produk'=>'Madu Murni 500ml',
             'harga_jual'=>85000,'stok_saat_ini'=>12,'stok_minimum'=>5,'satuan'=>'pcs','url_gambar'=>null],
            ['id'=>3,'sku'=>'PRD-022','kategori_id'=>1,'nama_produk'=>'Teh Hijau Organik',
             'harga_jual'=>45000,'stok_saat_ini'=>2,'stok_minimum'=>5,'satuan'=>'pcs','url_gambar'=>null],
            // Tambahan demo
            ['id'=>4,'sku'=>'PRD-011','kategori_id'=>2,'nama_produk'=>'Butter Croissant',
             'harga_jual'=>35000,'stok_saat_ini'=>8,'stok_minimum'=>15,'satuan'=>'pcs','url_gambar'=>null],
            ['id'=>5,'sku'=>'PRD-032','kategori_id'=>3,'nama_produk'=>'Wildflower Honey',
             'harga_jual'=>110000,'stok_saat_ini'=>9,'stok_minimum'=>5,'satuan'=>'botol','url_gambar'=>null],
            ['id'=>6,'sku'=>'PRD-007','kategori_id'=>4,'nama_produk'=>'Classic Leather Journal',
             'harga_jual'=>125000,'stok_saat_ini'=>3,'stok_minimum'=>8,'satuan'=>'pcs','url_gambar'=>null],
            ['id'=>7,'sku'=>'PRD-018','kategori_id'=>4,'nama_produk'=>'Pulpen Premium Set',
             'harga_jual'=>120000,'stok_saat_ini'=>15,'stok_minimum'=>5,'satuan'=>'set','url_gambar'=>null],
            ['id'=>8,'sku'=>'PRD-055','kategori_id'=>1,'nama_produk'=>'Cold Brew Concentrate',
             'harga_jual'=>155000,'stok_saat_ini'=>6,'stok_minimum'=>5,'satuan'=>'botol','url_gambar'=>null],
            ['id'=>9,'sku'=>'PRD-060','kategori_id'=>1,'nama_produk'=>'Specialty Arabica Blend',
             'harga_jual'=>165000,'stok_saat_ini'=>18,'stok_minimum'=>8,'satuan'=>'pcs','url_gambar'=>null],
            ['id'=>10,'sku'=>'PRD-072','kategori_id'=>2,'nama_produk'=>'Oat Milk (Barista Ed.)',
             'harga_jual'=>48000,'stok_saat_ini'=>12,'stok_minimum'=>20,'satuan'=>'liter','url_gambar'=>null],
            ['id'=>11,'sku'=>'PRD-081','kategori_id'=>3,'nama_produk'=>'Gula Semut Organik',
             'harga_jual'=>55000,'stok_saat_ini'=>320,'stok_minimum'=>10,'satuan'=>'pack','url_gambar'=>null],
            ['id'=>12,'sku'=>'PRD-090','kategori_id'=>1,'nama_produk'=>'Dark Roast Whole Bean',
             'harga_jual'=>145000,'stok_saat_ini'=>5,'stok_minimum'=>10,'satuan'=>'kg','url_gambar'=>null],
        ];
    }

    // Emoji map by kategori (untuk UI)
    public static function emojiMap(): array {
        return [
            1 => '☕', // Beverages
            2 => '🥐', // Bakery
            3 => '🍯', // Pantry
            4 => '📓', // Stationery
        ];
    }

    // ── TABLE: penjualan ──────────────────────
    public static function penjualan(): array {
        return [
            ['id'=>1,'nomor_transaksi'=>'#TRX-99421','metode_pembayaran'=>'QRIS',
             'subtotal'=>125000,'pajak'=>13750,'total_bayar'=>138750,'dibuat_pada'=>'2026-04-05 10:23:13'],
            ['id'=>2,'nomor_transaksi'=>'#TRX-99420','metode_pembayaran'=>'Tunai',
             'subtotal'=>76577,'pajak'=>8423,'total_bayar'=>85000,'dibuat_pada'=>'2026-04-05 09:45:00'],
            ['id'=>3,'nomor_transaksi'=>'#TRX-99419','metode_pembayaran'=>'Tunai',
             'subtotal'=>1081081,'pajak'=>118919,'total_bayar'=>1200000,'dibuat_pada'=>'2026-04-05 09:12:00'],
            ['id'=>4,'nomor_transaksi'=>'#TRX-99418','metode_pembayaran'=>'QRIS',
             'subtotal'=>239640,'pajak'=>26360,'total_bayar'=>266000,'dibuat_pada'=>'2026-04-05 08:58:00'],
            ['id'=>5,'nomor_transaksi'=>'#TRX-99417','metode_pembayaran'=>'Transfer',
             'subtotal'=>375000,'pajak'=>41250,'total_bayar'=>416250,'dibuat_pada'=>'2026-04-04 16:30:00'],
            ['id'=>6,'nomor_transaksi'=>'#TRX-99416','metode_pembayaran'=>'QRIS',
             'subtotal'=>450000,'pajak'=>49500,'total_bayar'=>499500,'dibuat_pada'=>'2026-04-04 15:05:00'],
            ['id'=>7,'nomor_transaksi'=>'#TRX-99415','metode_pembayaran'=>'Tunai',
             'subtotal'=>310000,'pajak'=>34100,'total_bayar'=>344100,'dibuat_pada'=>'2026-04-04 14:20:00'],
        ];
    }

    // ── TABLE: detail_penjualan ───────────────
    public static function detailPenjualan(): array {
        return [
            ['id'=>1,'penjualan_id'=>1,'produk_id'=>1,'jumlah'=>1,'harga_satuan'=>125000,'subtotal_item'=>125000],
            ['id'=>2,'penjualan_id'=>2,'produk_id'=>2,'jumlah'=>1,'harga_satuan'=>85000,'subtotal_item'=>85000],
            ['id'=>3,'penjualan_id'=>3,'produk_id'=>9,'jumlah'=>2,'harga_satuan'=>165000,'subtotal_item'=>330000],
            ['id'=>4,'penjualan_id'=>3,'produk_id'=>7,'jumlah'=>1,'harga_satuan'=>120000,'subtotal_item'=>120000],
        ];
    }

    // ── TABLE: ai_insights (dari SQL dump) ────
    public static function aiInsights(): array {
        return [
            ['id'=>1,'produk_id'=>3,'tipe_insight'=>'RESTOCK',
             'pesan_rekomendasi'=>'Stok tersisa 2. Berdasarkan tren, stok akan habis dalam 24 jam.',
             'skor_presisi'=>98.40,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
            ['id'=>2,'produk_id'=>1,'tipe_insight'=>'RESTOCK',
             'pesan_rekomendasi'=>'Stok tersisa 4. Berdasarkan tren, stok akan habis dalam 28 hari.',
             'skor_presisi'=>98.40,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
            ['id'=>3,'produk_id'=>3,'tipe_insight'=>'PROMO',
             'pesan_rekomendasi'=>'Produk Teh Hijau Organik kurang diminati bulan ini. Disarankan buat promo diskon atau bundling.',
             'skor_presisi'=>85.50,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
            ['id'=>4,'produk_id'=>2,'tipe_insight'=>'PROMO',
             'pesan_rekomendasi'=>'Produk Madu Murni 500ml kurang diminati bulan ini. Disarankan buat promo diskon atau bundling.',
             'skor_presisi'=>85.50,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
            // Tambahan demo
            ['id'=>5,'produk_id'=>4,'tipe_insight'=>'RESTOCK',
             'pesan_rekomendasi'=>'Stok tersisa 8. Demand meningkat 45% sejak Senin. Segera restock.',
             'skor_presisi'=>95.80,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
            ['id'=>6,'produk_id'=>6,'tipe_insight'=>'RESTOCK',
             'pesan_rekomendasi'=>'Stok tersisa 3. Stok akan habis dalam 48 jam.',
             'skor_presisi'=>96.20,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
            ['id'=>7,'produk_id'=>11,'tipe_insight'=>'BUNDLING',
             'pesan_rekomendasi'=>'Pelanggan sering membeli produk ini bersama Kopi Arabika 250g. Buat paket bundling!',
             'skor_presisi'=>92.00,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
            ['id'=>8,'produk_id'=>10,'tipe_insight'=>'RESTOCK',
             'pesan_rekomendasi'=>'Stok tersisa 12. High sales velocity — stok habis dlm 48 jam.',
             'skor_presisi'=>97.10,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
            ['id'=>9,'produk_id'=>12,'tipe_insight'=>'RESTOCK',
             'pesan_rekomendasi'=>'Dark Roast Whole Bean — stok tersisa 5kg. Segera order.',
             'skor_presisi'=>96.80,'status'=>'aktif','dibuat_pada'=>'2026-04-05 23:04:24'],
        ];
    }

    // ── TABLE: prediksi_performa ──────────────
    public static function prediksiPerforma(): array {
        return [
            ['id'=>1,'tanggal_prediksi'=>'2026-04-05','estimasi_omzet'=>320000,'persentase_pertumbuhan'=>8.2,'kategori_fokus'=>'Beverages','catatan_ai'=>'Weekend surge expected'],
            ['id'=>2,'tanggal_prediksi'=>'2026-04-06','estimasi_omzet'=>415000,'persentase_pertumbuhan'=>10.5,'kategori_fokus'=>'Beverages','catatan_ai'=>''],
            ['id'=>3,'tanggal_prediksi'=>'2026-04-07','estimasi_omzet'=>388000,'persentase_pertumbuhan'=>9.8,'kategori_fokus'=>'Bakery','catatan_ai'=>''],
            ['id'=>4,'tanggal_prediksi'=>'2026-04-08','estimasi_omzet'=>462000,'persentase_pertumbuhan'=>12.1,'kategori_fokus'=>'Beverages','catatan_ai'=>''],
            ['id'=>5,'tanggal_prediksi'=>'2026-04-09','estimasi_omzet'=>524000,'persentase_pertumbuhan'=>14.2,'kategori_fokus'=>'Stationery','catatan_ai'=>''],
            ['id'=>6,'tanggal_prediksi'=>'2026-04-10','estimasi_omzet'=>497000,'persentase_pertumbuhan'=>13.6,'kategori_fokus'=>'Pantry','catatan_ai'=>''],
            ['id'=>7,'tanggal_prediksi'=>'2026-04-11','estimasi_omzet'=>583000,'persentase_pertumbuhan'=>16.3,'kategori_fokus'=>'Beverages','catatan_ai'=>'Peak day'],
        ];
    }

    // ── VIEW: v_produk_kritis ─────────────────
    public static function viewProdukKritis(): array {
        $produk = self::produk();
        return array_values(array_filter($produk, fn($p) =>
            $p['stok_saat_ini'] <= $p['stok_minimum']
        ));
    }

    // ── Helper: Top produk terlaris ───────────
    public static function topProduk(): array {
        return [
            ['rank'=>1,'nama_produk'=>'Specialty Arabica Blend','kategori'=>'Beverages','qty_terjual'=>1240,'tren'=>24.8],
            ['rank'=>2,'nama_produk'=>'Butter Croissant',       'kategori'=>'Bakery',   'qty_terjual'=>890, 'tren'=>12.2],
            ['rank'=>3,'nama_produk'=>'Wildflower Honey',       'kategori'=>'Pantry',   'qty_terjual'=>450, 'tren'=>-4.1],
            ['rank'=>4,'nama_produk'=>'Classic Leather Journal','kategori'=>'Stationery','qty_terjual'=>380,'tren'=>8.5],
            ['rank'=>5,'nama_produk'=>'Cold Brew Concentrate',  'kategori'=>'Beverages','qty_terjual'=>320, 'tren'=>19.3],
        ];
    }

    // ── Helper: Performa kategori ─────────────
    public static function kategoriPerforma(): array {
        return [
            ['nama'=>'Premium Stationery','omzet'=>8420000,'pct'=>46],
            ['nama'=>'Art Prints',        'omzet'=>5100000,'pct'=>28],
            ['nama'=>'Editorial Journals','omzet'=>3280000,'pct'=>18],
            ['nama'=>'Digital Accessories','omzet'=>1450000,'pct'=>8],
        ];
    }
}
