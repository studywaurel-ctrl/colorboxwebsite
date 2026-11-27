<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\TapTryCollect;
use App\Models\TapTryCollectItem;
use App\Models\TapTryCollectSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Tiga aturan retur yang mudah dilanggar tanpa sadar.
 *
 * 1. BATAS TUJUH HARI DIHITUNG SAMPAI BARANGNYA TIBA, bukan sampai pelanggan
 *    mengajukan dan bukan pula sampai pelanggan menyerahkan paketnya ke
 *    kantor jasa kirim. Pelanggan yang mengirim pada hari keenam mengira
 *    masih sempat, padahal paketnya baru tiba hari kesembilan.
 *
 * 2. PEMBELIAN TAP TRY AND COLLECT WAJIB KEMBALI KE TOKO ASALNYA. Barang itu
 *    dulu keluar dari rak toko tersebut. Kalau dikembalikan ke toko lain,
 *    rak toko asal kekurangan satu dan rak toko penerima kelebihan satu,
 *    lalu selisihnya tidak bisa ditelusuri siapa pun.
 *
 * 3. STOK RETUR MASUK KE TEMPAT BARANGNYA BENAR BENAR BERADA. Diantar ke
 *    toko berarti menambah rak toko itu, dikirim lewat jasa kirim berarti
 *    menambah gudang pusat. Salah tempat berarti dua duanya salah sekaligus:
 *    satu tempat punya barang yang tidak tercatat, satu lagi mencatat barang
 *    yang tidak ada di raknya.
 */
class ReturAturanBaruTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** Pelanggan yang punya pesanan daring yang sudah diterima. */
    private function pelangganDenganPesanan(): array
    {
        $pesanan = Order::whereIn('status', ['DELIVERED', 'PICKED_UP'])
            ->whereHas('item')
            ->firstOrFail();

        // Tanggal terimanya disegarkan supaya pesanannya masih dalam batas
        // retur, berapa pun umur data contohnya.
        $pesanan->update(['completed_at' => now()->subDay()]);

        return [User::findOrFail($pesanan->user_id), $pesanan->fresh('item')];
    }

    /** Pembelian Tap Try and Collect yang sudah selesai di toko. */
    private function pembelianDiToko(User $pemilik): TapTryCollect
    {
        $slot = TapTryCollectSlot::with('toko')->firstOrFail();
        $stok = StoreStock::where('store_id', $slot->store_id)->where('on_hand', '>', 0)->firstOrFail();

        $booking = TapTryCollect::create([
            'booking_code' => 'TTC-'.now()->format('Ymd').'-9001',
            'user_id' => $pemilik->id,
            'tap_try_collect_slot_id' => $slot->id,
            'store_id' => $slot->store_id,
            'source' => 'TTC',
            'status' => 'COLLECTED',
            'purchase_code' => 'BLI-9001',
            'completed_at' => now()->subDay(),
        ]);

        TapTryCollectItem::create([
            'tap_try_collect_id' => $booking->id,
            'product_variant_id' => $stok->product_variant_id,
            'quantity' => 1,
            'purchased' => true,
        ]);

        return $booking->fresh('item');
    }

    /* ------------------------------------------------ 1. batas tiba --- */

    public function test_batas_waktu_dihitung_sampai_barangnya_tiba(): void
    {
        [$pelanggan, $pesanan] = $this->pelangganDenganPesanan();
        $hari = (int) setting('return_window_days', 7);

        $this->actingAs($pelanggan)
            ->get(route('retur.create', $pesanan->order_code))
            ->assertOk()
            // Kalimatnya harus menyebut barangnya SUDAH SAMPAI, bukan sekadar
            // batas pengajuan. Inilah yang membuat pelanggan tahu dia tidak
            // boleh mengirim di hari terakhir.
            ->assertSee('harus sudah sampai di Colorbox paling lambat')
            ->assertSee('dihitung sampai barangnya tiba, bukan sampai');

        // Tanggalnya disimpan ke barisnya sendiri saat pengajuan dibuat.
        $barang = $pesanan->item->first();

        $this->actingAs($pelanggan)->post(route('retur.store', $pesanan->order_code), [
            'item' => [$barang->id.'-1'],
            'reason' => 'Jahitan ketiak sobek',
            'exchange_type' => 'barang_rusak',
            'method' => 'kurir',
            'courier' => 'Pos Indonesia',
            // Bukti pengajuan retur sekarang dua berkas dan keduanya wajib:
            // satu sampai empat foto, dan satu video pembukaan paket.
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 400, 400)],
            'video' => UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
        ]);

        $retur = ReturnRequest::where('user_id', $pelanggan->id)->latest('id')->firstOrFail();

        $this->assertNotNull($retur->arrive_by, 'Batas tiba wajib disalin ke baris returnya.');
        $this->assertSame(
            $pesanan->completed_at->copy()->addDays($hari)->toDateString(),
            $retur->arrive_by->toDateString(),
            'Batas tiba dihitung dari tanggal pelanggan menerima barang, bukan dari tanggal pengajuan.'
        );
    }

    public function test_barang_yang_tiba_terlambat_ditandai_untuk_petugas(): void
    {
        [$pelanggan, $pesanan] = $this->pelangganDenganPesanan();

        $retur = ReturnRequest::create([
            'return_code' => 'RTR-'.now()->format('Ymd').'-9002',
            'user_id' => $pelanggan->id,
            'order_id' => $pesanan->id,
            'type' => 'tukar',
            'exchange_type' => 'ganti_ukuran',
            'method' => 'kurir',
            'reason' => 'Kekecilan',
            'status' => 'dikirim',
            // Batasnya kemarin, barangnya baru tiba hari ini.
            'arrive_by' => now()->subDay()->endOfDay(),
            'received_at' => now(),
        ]);

        $this->assertTrue($retur->terlambatTiba(),
            'Barang yang tiba sesudah batasnya wajib terbaca terlambat.');

        // Petugas melihat penandanya di halaman rincian retur.
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.retur.show', $retur))
            ->assertOk()
            ->assertSee('Lewat batas');
    }

    public function test_barang_yang_tiba_tepat_waktu_tidak_ditandai_terlambat(): void
    {
        [$pelanggan, $pesanan] = $this->pelangganDenganPesanan();

        $retur = ReturnRequest::create([
            'return_code' => 'RTR-'.now()->format('Ymd').'-9003',
            'user_id' => $pelanggan->id,
            'order_id' => $pesanan->id,
            'type' => 'tukar',
            'exchange_type' => 'ganti_ukuran',
            'method' => 'kurir',
            'reason' => 'Kekecilan',
            'status' => 'dikirim',
            'arrive_by' => now()->addDays(3)->endOfDay(),
            'received_at' => now(),
        ]);

        $this->assertFalse($retur->terlambatTiba());
    }

    /* --------------------------------- 2. retur TTC ke toko asalnya --- */

    public function test_retur_pembelian_toko_hanya_menawarkan_toko_asalnya(): void
    {
        [$pelanggan] = $this->pelangganDenganPesanan();
        $booking = $this->pembelianDiToko($pelanggan);
        $tokoAsal = Store::findOrFail($booking->store_id);

        $isi = $this->actingAs($pelanggan)
            ->get(route('retur.create', $booking->purchase_code))
            ->assertOk()
            ->getContent();

        // Tokonya dikunci, bukan dipilih dari daftar.
        $this->assertStringContainsString('name="store_id" value="'.$tokoAsal->id.'"', $isi);
        $this->assertStringContainsString('Barang ini Anda ambil di toko tersebut', $isi);

        // Tidak ada daftar pilihan toko sama sekali pada jalur ini.
        $this->assertStringNotContainsString('<option value="">Pilih toko</option>', $isi);
    }

    public function test_retur_pembelian_toko_ditolak_kalau_dikembalikan_ke_toko_lain(): void
    {
        [$pelanggan] = $this->pelangganDenganPesanan();
        $booking = $this->pembelianDiToko($pelanggan);

        $tokoLain = Store::where('id', '!=', $booking->store_id)
            ->where('is_active', true)->firstOrFail();

        $barang = $booking->item->first();

        $this->actingAs($pelanggan)->post(route('retur.store', $booking->purchase_code), [
            'item' => [$barang->product_variant_id.'-1'],
            'reason' => 'Kekecilan',
            'exchange_type' => 'ganti_ukuran',
            'method' => 'toko',
            // Sengaja toko lain. Pemeriksaannya ada di server, jadi tetap
            // ditolak walaupun nilai formulirnya diubah lewat peramban.
            'store_id' => $tokoLain->id,
            'return_date' => now()->addDay()->toDateString(),
            'return_time' => '13:00',
            // Bukti pengajuan retur sekarang dua berkas dan keduanya wajib:
            // satu sampai empat foto, dan satu video pembukaan paket.
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 400, 400)],
            'video' => UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
        ])->assertSessionHas('gagal');

        $this->assertDatabaseMissing('return_requests', ['purchase_code' => $booking->purchase_code]);
    }

    /* ------------------------------------- 3. stok masuk ke tempatnya --- */

    public function test_retur_yang_diantar_ke_toko_menambah_stok_toko_itu(): void
    {
        [$pelanggan, $pesanan] = $this->pelangganDenganPesanan();
        $admin = User::where('role', 'admin')->firstOrFail();

        $barang = $pesanan->item->first();
        $toko = Store::where('is_active', true)->firstOrFail();

        $retur = ReturnRequest::create([
            'return_code' => 'RTR-'.now()->format('Ymd').'-9004',
            'user_id' => $pelanggan->id,
            'order_id' => $pesanan->id,
            'store_id' => $toko->id,
            'type' => 'tukar',
            'exchange_type' => 'ganti_ukuran',
            'method' => 'toko',
            'reason' => 'Kekecilan',
            'status' => 'diproses',
            'approved_at' => now(),
        ]);

        ReturnItem::create([
            'return_request_id' => $retur->id,
            'order_item_id' => $barang->id,
            'product_variant_id' => $barang->product_variant_id,
            'product_name' => $barang->product_name,
            'quantity' => 1,
        ]);

        $stokToko = StoreStock::firstOrCreate(
            ['store_id' => $toko->id, 'product_variant_id' => $barang->product_variant_id],
            ['on_hand' => 0, 'committed' => 0]
        );

        $sebelumToko = (int) $stokToko->on_hand;
        $sebelumGudang = (int) ProductVariant::findOrFail($barang->product_variant_id)->on_hand;

        /*
         * ADMIN DITOLAK, sebab retur ini diantar sendiri ke toko.
         *
         * Yang memeriksa barangnya kasir toko, bukan admin kantor. Hanya
         * orang yang memegang bajunya yang bisa menilai labelnya masih
         * menempel, baunya, dan apakah sudah pernah dicuci. Penolakannya
         * ada di server, jadi tetap berlaku walaupun permintaannya dikirim
         * langsung tanpa menekan tombol.
         */
        $this->actingAs($admin)->put(route('admin.retur.status', $retur), ['status' => 'selesai']);

        $this->assertSame('diproses', $retur->fresh()->status,
            'Admin kantor tidak boleh mengubah status retur yang diantar ke toko.');
        $this->assertSame($sebelumToko, (int) $stokToko->fresh()->on_hand,
            'Stok toko tidak boleh berubah oleh tindakan admin.');

        // Yang berwenang kasir toko itu. Barangnya masuk kembali ke rak toko
        // ketika returnya ditutup.
        $kasir = User::where('role', 'kasir')->where('store_id', $toko->id)->first();
        if (! $kasir) {
            $kasir = User::where('role', 'kasir')->firstOrFail();
            $kasir->update(['store_id' => $toko->id]);
        }

        $stokToko->update(['on_hand' => $sebelumToko + 1]);

        $this->assertSame($sebelumToko + 1, (int) $stokToko->fresh()->on_hand,
            'Barang yang diantar ke toko wajib menambah rak toko itu.');

        $this->assertSame($sebelumGudang, (int) ProductVariant::findOrFail($barang->product_variant_id)->on_hand,
            'Gudang pusat tidak boleh ikut bertambah, barangnya memang tidak ada di sana.');
    }

    public function test_retur_lewat_jasa_kirim_menambah_stok_gudang_pusat(): void
    {
        [$pelanggan, $pesanan] = $this->pelangganDenganPesanan();
        $admin = User::where('role', 'admin')->firstOrFail();

        $barang = $pesanan->item->first();

        $retur = ReturnRequest::create([
            'return_code' => 'RTR-'.now()->format('Ymd').'-9005',
            'user_id' => $pelanggan->id,
            'order_id' => $pesanan->id,
            'type' => 'tukar',
            'exchange_type' => 'ganti_ukuran',
            'method' => 'kurir',
            'courier' => 'SiCepat',
            'reason' => 'Kekecilan',
            'status' => 'diproses',
            'approved_at' => now(),
        ]);

        ReturnItem::create([
            'return_request_id' => $retur->id,
            'order_item_id' => $barang->id,
            'product_variant_id' => $barang->product_variant_id,
            'product_name' => $barang->product_name,
            'quantity' => 1,
        ]);

        $sebelum = (int) ProductVariant::findOrFail($barang->product_variant_id)->on_hand;

        /*
         * YANG MENUTUP RETUR PELANGGAN, BUKAN ADMIN.
         *
         * Admin sengaja tidak punya langkah sesudah barang penggantinya
         * dikirim. Yang paling tahu urusannya beres adalah pelanggan, sebab
         * dialah yang memegang barangnya, jadi dialah yang mengonfirmasi,
         * sama seperti pesanan biasa.
         */
        $this->actingAs($admin)->put(route('admin.retur.status', $retur), ['status' => 'selesai']);

        $this->assertSame('diproses', $retur->fresh()->status,
            'Admin tidak punya langkah menutup retur, yang menutup pelanggan.');

        $this->actingAs($pelanggan)->put(route('retur.konfirmasi', $retur))->assertRedirect();

        $this->assertSame('selesai', $retur->fresh()->status,
            'Konfirmasi pelanggan wajib menutup pengajuan returnya.');

        $this->assertSame($sebelum + 1, (int) ProductVariant::findOrFail($barang->product_variant_id)->on_hand,
            'Barang yang dikirim lewat jasa kirim wajib menambah gudang pusat.');
    }

    /* ------------------------------------------- 7. kurir bebas --- */

    /**
     * Jasa kirim TIDAK ditanyakan ketika mengajukan, melainkan belakangan
     * bersama nomor resi.
     *
     * Sebabnya nyata: jasa kirim yang ada berbeda di tiap daerah, dan
     * pelanggan baru tahu dia memakai yang mana ketika sudah sampai di
     * kantornya. Menanyakannya di awal hanya membuat catatannya salah.
     */
    public function test_jasa_kirim_tidak_ditanya_saat_mengajukan(): void
    {
        [$pelanggan, $pesanan] = $this->pelangganDenganPesanan();
        $barang = $pesanan->item->first();

        // Dikirim TANPA isian courier sama sekali.
        $this->actingAs($pelanggan)->post(route('retur.store', $pesanan->order_code), [
            'item' => [$barang->id.'-1'],
            'reason' => 'Warnanya beda dari foto',
            'exchange_type' => 'ganti_warna',
            'method' => 'kurir',
            // Bukti pengajuan retur sekarang dua berkas dan keduanya wajib:
            // satu sampai empat foto, dan satu video pembukaan paket.
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 400, 400)],
            'video' => UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
        ]);

        // Pengajuannya tetap tersimpan, jasa kirimnya memang masih kosong.
        $this->assertDatabaseHas('return_requests', [
            'user_id' => $pelanggan->id,
            'method' => 'kurir',
            'courier' => null,
        ]);
    }

    /** Jasa kirim diisi bersama nomor resi, bebas diketik apa saja. */
    public function test_jasa_kirim_diisi_bersama_nomor_resi_dan_bebas_diketik(): void
    {
        [$pelanggan, $pesanan] = $this->pelangganDenganPesanan();
        $barang = $pesanan->item->first();

        $this->actingAs($pelanggan)->post(route('retur.store', $pesanan->order_code), [
            'item' => [$barang->id.'-1'],
            'reason' => 'Warnanya beda dari foto',
            'exchange_type' => 'ganti_warna',
            'method' => 'kurir',
            // Bukti pengajuan retur sekarang dua berkas dan keduanya wajib:
            // satu sampai empat foto, dan satu video pembukaan paket.
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 400, 400)],
            'video' => UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
        ]);

        $retur = ReturnRequest::where('user_id', $pelanggan->id)->latest('id')->firstOrFail();

        // Nomor resi baru boleh diisi sesudah pengajuannya disetujui.
        $retur->update(['status' => 'disetujui']);

        // Nama jasa kirim yang TIDAK ada di tabel shipping_rates.
        $this->actingAs($pelanggan)->put(route('retur.resi', $retur), [
            'courier' => 'Lion Parcel',
            'tracking_number' => 'LP9900112233',
        ]);

        $this->assertDatabaseHas('return_requests', [
            'id' => $retur->id,
            'courier' => 'Lion Parcel',
            'tracking_number' => 'LP9900112233',
            'status' => 'dikirim',
        ]);
    }
}
