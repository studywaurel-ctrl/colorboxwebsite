<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Store;
use App\Models\TapTryCollect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pengujian rantai antarperan.
 *
 * Satu pekerjaan di sini dimulai pelanggan dan diselesaikan admin atau kasir,
 * jadi yang diuji bukan satu halaman saja melainkan sambungannya: apa yang
 * dikirim pelanggan harus muncul di sisi petugas, dan hasil tindakan petugas
 * harus kembali terlihat oleh pelanggan.
 *
 * Jalankan: php artisan test
 */
class RantaiPeranTest extends TestCase
{
    use RefreshDatabase;

    private User $pelanggan;

    private User $admin;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        // Pukul 11.00, di dalam jam buka toko yang 10.00 sampai 22.00.
        Carbon::setTestNow(Carbon::parse('2026-10-06 11:00:00'));

        $this->seed();

        $this->pelanggan = User::where('role', 'pelanggan')->firstOrFail();
        $this->admin = User::where('role', 'admin')->firstOrFail();
        // Kasir yang dipakai pengujian diambil dari TOKO YANG PUNYA DATA
        // CONTOH, bukan kasir pertama menurut nomor akunnya.
        //
        // Dulu di sini dipakai kasir pertama begitu saja, dan pengujiannya
        // lolos hanya karena kebetulan data contoh berada di toko yang sama.
        // Begitu data contohnya dipindah ke cabang Bintaro Jaya Xchange,
        // yaitu cabang tempat penelitian ini dilakukan, pengujiannya langsung
        // gagal padahal sistemnya tidak ada yang salah.
        //
        // Sekarang tokonya ditentukan dari data yang ada, jadi pengujian ini
        // tidak lagi bergantung pada urutan nomor akun.
        $tokoBerdata = TapTryCollect::where('status', 'SCHEDULED')->value('store_id')
            ?? Donation::query()->value('store_id');

        $this->kasir = User::where('role', 'kasir')
            ->where('store_id', $tokoBerdata)
            ->firstOrFail();
    }

    public function test_retur_diajukan_pelanggan_lalu_disetujui_admin(): void
    {
        $pesanan = Order::where('user_id', $this->pelanggan->id)
            ->where('status', 'DELIVERED')
            ->firstOrFail();
        // Supaya pengajuan tidak tertolak batas waktu, tanggal selesainya
        // dibuat masih di dalam masa retur.
        $pesanan->update(['completed_at' => now()->subDay()]);

        $barang = $pesanan->item()->firstOrFail();
        $toko = Store::where('is_active', true)->firstOrFail();

        $this->actingAs($this->pelanggan)
            ->get(route('retur.create', $pesanan->order_code))
            ->assertOk()
            ->assertSee($pesanan->order_code);

        $this->actingAs($this->pelanggan)
            ->post(route('retur.store', $pesanan->order_code), [
                'item' => [$barang->id.'-1'],
                'reason' => 'Ukuran tidak sesuai badan',
                'exchange_type' => 'ganti_ukuran',
                'exchange_note' => 'Minta ditukar ke ukuran L',
                'method' => 'toko',
                'store_id' => $toko->id,
                // Tanggal paling cepat besok, sama dengan yang ditawarkan
                // halaman returnya.
                'return_date' => now()->addDay()->toDateString(),
                'return_time' => '13:00',
                // Bukti pengajuan retur dua berkas dan keduanya wajib:
                // satu sampai empat foto, dan satu video pembukaan paket.
                // UploadedFile::fake() membuat berkas palsu khusus untuk
                // pengujian, jadi tidak perlu menyimpan berkas sungguhan di
                // dalam repositori.
                'photos' => [\Illuminate\Http\UploadedFile::fake()->image('bukti-retur.jpg', 600, 600)],
                'video' => \Illuminate\Http\UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
            ]);

        $retur = ReturnRequest::where('user_id', $this->pelanggan->id)->firstOrFail();
        $this->assertSame('menunggu_validasi', $retur->status);
        $this->assertDatabaseHas('return_items', [
            'return_request_id' => $retur->id,
            'order_item_id' => $barang->id,
        ]);

        // Admin boleh MELIHAT daftarnya, sebab retur tetap urusan perusahaan.
        $this->actingAs($this->admin)
            ->get(route('admin.retur.index'))
            ->assertOk()
            ->assertSee($retur->return_code);

        /*
         * Tetapi admin TIDAK boleh memprosesnya, sebab returnya diantar
         * sendiri ke toko. Yang memeriksa barangnya kasir toko: hanya orang
         * yang memegang bajunya yang bisa menilai labelnya masih menempel,
         * baunya, dan apakah sudah pernah dicuci. Admin kantor cuma melihat
         * satu foto, dan dari foto tidak ada yang bisa dicium atau diraba.
         */
        $this->actingAs($this->admin)
            ->put(route('admin.retur.status', $retur), [
                'status' => 'disetujui',
                'admin_note' => 'Pengajuan disetujui, barang ditukar di toko.',
            ]);

        $this->assertSame('menunggu_validasi', $retur->fresh()->status,
            'Admin kantor tidak boleh memproses retur yang diantar ke toko.');

        // KASIR toko itu yang memeriksanya, lewat daftar centang syarat fisik.
        $kasirToko = \App\Models\User::where('role', 'kasir')
            ->where('store_id', $toko->id)->first();
        if (! $kasirToko) {
            $kasirToko = \App\Models\User::where('role', 'kasir')->firstOrFail();
            $kasirToko->update(['store_id' => $toko->id]);
        }

        $this->actingAs($kasirToko)
            ->get(route('kasir.retur.index'))
            ->assertOk()
            ->assertSee($retur->return_code);

        // Seluruh syarat tercentang berarti barangnya lolos pemeriksaan.
        $semuaSyarat = [];
        foreach (array_keys(\App\Http\Controllers\Kasir\ReturController::SYARAT) as $kunci) {
            $semuaSyarat[$kunci] = '1';
        }

        $this->actingAs($kasirToko)
            ->put(route('kasir.retur.periksa', $retur), [
                'syarat' => $semuaSyarat,
                'catatan' => 'Label masih menempel, barang bersih.',
            ]);

        $retur->refresh();
        $this->assertSame('diperiksa', $retur->status);
        $this->assertNotNull($retur->approved_at);

        $this->actingAs($this->pelanggan)
            ->get(route('retur.show', $retur))
            ->assertOk()
            ->assertSee('Label masih menempel, barang bersih.');
    }

    public function test_kasir_menyiapkan_booking_lalu_menerbitkan_kode_pembelian(): void
    {
        $booking = TapTryCollect::where('store_id', $this->kasir->store_id)
            ->where('status', 'SCHEDULED')
            ->firstOrFail();

        // Kasir menemukan booking itu dari kode yang dibawa pelanggan.
        $this->actingAs($this->kasir)
            ->post(route('kasir.cari'), ['kode' => $booking->booking_code])
            ->assertRedirect(route('kasir.booking.show', $booking));

        $this->actingAs($this->kasir)
            ->put(route('kasir.booking.siap', $booking));
        $this->assertSame('READY', $booking->fresh()->status);

        $item = $booking->item()->firstOrFail();
        $this->actingAs($this->kasir)
            ->put(route('kasir.booking.ambil', $booking), ['dibeli' => [$item->id]]);

        $booking->refresh();
        $this->assertSame('COLLECTED', $booking->status);
        $this->assertNotNull($booking->purchase_code,
            'Kode pembelian harus terbit supaya pelanggan bisa mengajukan retur dari struk toko.');

        // Hasilnya terlihat pada riwayat pelanggan.
        $this->actingAs($booking->pengguna)
            ->get(route('ttc.show', $booking))
            ->assertOk()
            ->assertSee($booking->purchase_code);
    }

    public function test_daftar_toko_hanya_memuat_yang_ada_stok_dan_diurutkan_dari_terdekat(): void
    {
        $varian = \App\Models\ProductVariant::whereHas('stokToko', fn ($q) => $q->whereColumn('on_hand', '>', 'committed'))
            ->firstOrFail();

        // Satu toko sengaja dikosongkan stoknya supaya bisa dipastikan hilang
        $kosong = \App\Models\StoreStock::where('product_variant_id', $varian->id)
            ->whereColumn('on_hand', '>', 'committed')
            ->firstOrFail();
        $kosong->update(['on_hand' => 0, 'committed' => 0]);

        $halaman = $this->actingAs($this->pelanggan)
            ->get(route('ttc.index', ['varian' => $varian->id]));

        $halaman->assertOk();
        $halaman->assertDontSee('Stok habis di toko ini');
        $halaman->assertDontSee(\App\Models\Store::find($kosong->store_id)->name);

        // Dengan lokasi tersimpan, toko terdekat harus muncul lebih dulu
        $dekat = \App\Models\Store::where('is_active', true)->whereNotNull('latitude')->firstOrFail();

        $daftar = $this->withSession(['lokasi' => [
            'lat' => (float) $dekat->latitude,
            'lng' => (float) $dekat->longitude,
        ]])->actingAs($this->pelanggan)->get(route('toko.index'));

        $daftar->assertOk();
        $daftar->assertSee('Diurutkan dari lokasi Anda');

        $isi = $daftar->getContent();
        $urutan = [];
        foreach (\App\Models\Store::where('is_active', true)->get() as $toko) {
            $letak = strpos($isi, $toko->name);
            if ($letak !== false) {
                $urutan[$toko->name] = $letak;
            }
        }
        asort($urutan);
        $this->assertSame($dekat->name, array_key_first($urutan),
            'Toko paling dekat harus berada di urutan pertama.');
    }

    /**
     * Admin menyiapkan pesanan BERURUTAN, dan tidak menutupnya sendiri.
     *
     * Pembagiannya sekarang begini:
     *
     *   admin      memproses, mengemas, lalu menyerahkan paketnya ke kurir.
     *              Tiga pekerjaan nyata, masing masing satu tombol, supaya
     *              tiap pesanan diperiksa orang sebelum kardusnya ditutup.
     *   jasa kirim mencatat perjalanan paketnya. Colorbox tidak menggambar
     *              perjalanan itu sendiri, cuma menyimpan nomor resinya.
     *   pelanggan  menyatakan barangnya diterima.
     *   admin      mengesahkan berkasnya sesudah itu.
     *
     * Yang dikunci pengujian ini: admin tidak bisa melompat, dan tidak bisa
     * menandai pesanan diterima atas nama pelanggan.
     */
    public function test_admin_menyiapkan_pesanan_berurutan(): void
    {
        $pesanan = Order::where('user_id', $this->pelanggan->id)
            ->where('fulfillment_method', 'DELIVERY')
            ->where('status', 'DELIVERED')
            ->firstOrFail();

        $pesanan->update(['status' => 'PAYMENT_CONFIRMED', 'completed_at' => null, 'verified_at' => null]);

        // Melompat langsung ke berangkat ditolak, sebab barangnya belum
        // pernah dikemas.
        $this->actingAs($this->admin)
            ->put(route('admin.pesanan.status', $pesanan), ['status' => 'IN_TRANSIT'])
            ->assertSessionHasErrors('status');

        // Berurutan, barulah diterima.
        foreach (['PROCESSING', 'PACKED'] as $langkah) {
            $this->actingAs($this->admin)
                ->put(route('admin.pesanan.status', $pesanan), ['status' => $langkah]);

            $this->assertSame($langkah, $pesanan->fresh()->status,
                'Langkah '.$langkah.' seharusnya diterima pada urutannya.');
        }

        // Menandai pesanan diterima pelanggan tetap TIDAK boleh. Yang
        // menyatakan barangnya sampai hanya pelanggan sendiri.
        $this->actingAs($this->admin)
            ->put(route('admin.pesanan.status', $pesanan), ['status' => 'DELIVERED'])
            ->assertSessionHasErrors('status');

        $this->assertSame('PACKED', $pesanan->fresh()->status);
    }

    /** Pengesahan akhir baru terbuka sesudah pelanggan mengonfirmasi. */
    public function test_pengesahan_akhir_sesudah_konfirmasi_pelanggan(): void
    {
        $pesanan = Order::where('user_id', $this->pelanggan->id)
            ->where('fulfillment_method', 'DELIVERY')
            ->firstOrFail();

        $pesanan->update(['status' => 'IN_TRANSIT', 'completed_at' => null, 'verified_at' => null]);

        // Belum dikonfirmasi pelanggan: pengesahannya ditolak.
        $this->actingAs($this->admin)->put(route('admin.pesanan.sahkan', $pesanan));
        $this->assertNull($pesanan->fresh()->verified_at,
            'Pesanan tidak boleh disahkan sebelum pelanggan menyatakan barangnya diterima.');

        // Pelanggan mengonfirmasi, barulah admin bisa menutup berkasnya.
        $this->actingAs($this->pelanggan)->put(route('pesanan.terima', $pesanan), [
            'receipt_photo' => \Illuminate\Http\UploadedFile::fake()->image('terima.jpg'),
        ]);
        $this->assertSame('DELIVERED', $pesanan->fresh()->status);

        $this->actingAs($this->admin)->put(route('admin.pesanan.sahkan', $pesanan));
        $this->assertNotNull($pesanan->fresh()->verified_at,
            'Pengesahan admin wajib tercatat waktunya.');
    }

    /** Petugas gudanglah yang menyerahkan paket, dengan satu tombol. */
    public function test_gudang_menyerahkan_paket_dengan_satu_tombol(): void
    {
        $pesanan = Order::where('user_id', $this->pelanggan->id)
            ->where('fulfillment_method', 'DELIVERY')
            ->firstOrFail();

        $pesanan->update(['status' => 'PAYMENT_CONFIRMED', 'completed_at' => null]);

        // Berjalan sendiri begitu pembayaran diterima, tanpa isian apa pun
        // dan tanpa tombol yang ditekan siapa pun.
        (new \App\Services\PenyerahanPaket)->serahkan($pesanan);

        $this->assertSame('IN_TRANSIT', $pesanan->fresh()->status);

        // Riwayatnya tetap runtut: dikemas dulu, baru berangkat.
        $this->assertDatabaseHas('tracking_events', ['order_id' => $pesanan->id, 'status' => 'PACKED']);
        $this->assertDatabaseHas('tracking_events', ['order_id' => $pesanan->id, 'status' => 'IN_TRANSIT']);

        // Nomor resinya ada tanpa pernah diketik siapa pun.
        $this->assertDatabaseHas('shipments', ['order_id' => $pesanan->id]);
    }

    /**
     * Antrean Pengemasan sudah tidak ada untuk siapa pun.
     *
     * Halaman itu dibuang sebab pengantarannya diserahkan kepada jasa kirim
     * pihak ketiga lewat API. Tidak ada pekerjaan yang benar benar dikerjakan
     * admin kantor di layar itu: dia tidak menjemput, tidak menimbang, dan
     * tidak menerbitkan nomor resi. Tombolnya cuma membuat pesanan menunggu
     * tanpa guna.
     */
    public function test_antrean_pengemasan_sudah_tidak_ada(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.pengemasan.index'),
            'Route antrean pengemasan seharusnya sudah tidak terdaftar.');

        // Alamat yang routenya sudah tidak terdaftar menjawab 404 untuk
        // siapa pun, sebab penjaga perannya tidak pernah sempat berjalan.
        $this->actingAs($this->admin)->get('/admin/pengemasan')->assertNotFound();
        $this->actingAs($this->kasir)->get('/admin/pengemasan')->assertNotFound();
    }

    /**
     * Admin kantor TIDAK bisa mengubah status kunjungan Tap Try and Collect
     * sama sekali, bahkan lewat alamatnya langsung.
     *
     * Dulu admin punya tombolnya, dengan urutan yang dijaga server. Tombol
     * itu dibuang seluruhnya: yang berhak menyatakan barang sudah disiapkan,
     * sudah diserahkan, atau pelanggannya tidak datang adalah kasir toko,
     * sebab kasirlah yang berdiri di depan pelanggan dan memegang barangnya.
     * Admin kantor tidak pernah menyaksikan kunjungannya.
     */
    public function test_admin_tidak_bisa_mengubah_status_booking(): void
    {
        $booking = \App\Models\TapTryCollect::where('status', 'SCHEDULED')->firstOrFail();

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.ttc.status'),
            'Route pengubah status kunjungan di admin seharusnya sudah tidak terdaftar.');

        $this->actingAs($this->admin)
            ->put('/admin/tap-try-collect/'.$booking->id.'/status', ['status' => 'COLLECTED'])
            ->assertNotFound();

        $this->assertSame('SCHEDULED', $booking->fresh()->status);

        // Halamannya tetap boleh DILIHAT admin, cuma tidak bisa diubah.
        $this->actingAs($this->admin)
            ->get(route('admin.ttc.show', $booking))
            ->assertOk()
            // Kalimatnya dipendekkan ketika tulisan panel admin dipangkas,
            // tetapi artinya sama: admin hanya melihat, kasir yang mengubah.
            ->assertSee('hanya untuk melihat', false)
            ->assertSee('kasir toko', false);
    }

    public function test_kasir_mencatat_kedatangan_lalu_memeriksa_donasi(): void
    {
        $donasi = Donation::where('store_id', $this->kasir->store_id)
            ->where('status', 'SCHEDULED')
            ->firstOrFail();
        $pendonor = $donasi->pengguna;
        $poinAwal = (int) $pendonor->points;

        $this->actingAs($this->kasir)
            ->post(route('kasir.cari'), ['kode' => $donasi->donation_code])
            ->assertRedirect(route('kasir.donasi.show', $donasi));

        $this->actingAs($this->kasir)->put(route('kasir.donasi.check-in', $donasi));
        $this->assertSame('CHECKED_IN', $donasi->fresh()->status);

        $this->actingAs($this->kasir)
            ->put(route('kasir.donasi.verifikasi', $donasi), [
                'jumlah_layak' => 3,
                'jumlah_tidak_layak' => 1,
                // Penolakan wajib disertai alasan dan foto buktinya.
                'reject_reason' => 'rusak',
                'reject_photo' => \Illuminate\Http\UploadedFile::fake()->image('tolak.jpg'),
                'verify_note' => 'Tiga pakaian layak pakai, satu berlubang.',
            ]);

        $donasi->refresh();
        $this->assertSame('VERIFIED', $donasi->status);
        $this->assertGreaterThan($poinAwal, (int) $pendonor->fresh()->points,
            'Poin pendonor harus bertambah sebanyak pakaian yang layak pakai.');

        $this->actingAs($pendonor)
            ->get(route('donasi.riwayat'))
            ->assertOk()
            ->assertSee($donasi->donation_code);
    }
}
