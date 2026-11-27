<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Retur dipilih per POTONG, bukan per jenis barang.
 *
 * ---------------------------------------------------------------------
 * KENAPA
 * ---------------------------------------------------------------------
 *
 * Pelanggan yang membeli dua potong baju yang sama persis, lalu cuma satu
 * yang kekecilan, dulu melihat satu baris saja beserta isian jumlah. Dia
 * tidak tahu yang mana yang dia maksud dan apakah yang satunya ikut
 * terbawa.
 *
 * Sekarang tiap potongnya digambar sendiri dengan kunci yang berbeda,
 * yaitu nomor baris pesanan diikuti nomor urut potongnya. Jadi dua baju
 * yang jenisnya sama tetap bisa dibedakan.
 *
 * Yang kedua, potong yang SUDAH diretur tidak ditawarkan lagi. Tanpa itu
 * satu baju bisa ditukar berkali kali dan stok toko bertambah dari barang
 * yang tidak pernah ada.
 */
class ReturPerSatuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** Pesanan yang salah satu barisnya berisi dua potong. */
    private function pesananDuaPotong(): array
    {
        $pesanan = Order::whereIn('status', ['DELIVERED', 'PICKED_UP'])
            ->where('fulfillment_method', 'DELIVERY')
            ->whereHas('item')->firstOrFail();

        $pesanan->update(['completed_at' => now()->subDay()]);

        $baris = $pesanan->item()->firstOrFail();
        $baris->update(['quantity' => 2]);

        return [User::findOrFail($pesanan->user_id), $pesanan->fresh('item'), $baris->fresh()];
    }

    /** Dua potong baju yang sama tampil sebagai dua baris terpisah. */
    public function test_dua_potong_tampil_sebagai_dua_baris(): void
    {
        [$pelanggan, $pesanan, $baris] = $this->pesananDuaPotong();

        $this->actingAs($pelanggan)
            ->get(route('retur.create', $pesanan))
            ->assertOk()
            ->assertSee('value="'.$baris->id.'-1"', false)
            ->assertSee('value="'.$baris->id.'-2"', false)
            // Penomoran "potong ke-1 dari 2" dilepas dari layar.
            //
            // Dua potong baju yang sama persis, warna sama, ukuran sama,
            // harga sama, tidak bisa dibedakan pelanggan di tangannya
            // sendiri. Menyuruhnya memilih potong yang mana hanya
            // membuat ragu tanpa mengubah apa pun yang dikerjakan toko.
            //
            // Yang tetap dijaga ada di dua baris di atas: barisnya
            // memang DUA dan nilainya berbeda, jadi pelanggan bisa
            // menukar satu dari dua tanpa ikut menukar keduanya. Itulah
            // yang sesungguhnya diuji berkas ini; penomorannya hanya
            // tulisan di permukaan.
            ->assertDontSee('potong ke-1 dari 2');
    }

    /** Yang dicentang satu, yang tercatat diretur juga satu. */
    public function test_hanya_potong_yang_dicentang_yang_diretur(): void
    {
        [$pelanggan, $pesanan, $baris] = $this->pesananDuaPotong();
        $toko = Store::where('is_active', true)->firstOrFail();

        $this->actingAs($pelanggan)->post(route('retur.store', $pesanan), [
            'item' => [$baris->id.'-1'],
            'exchange_type' => 'ganti_ukuran',
            'method' => 'kurir',
            'store_id' => $toko->id,
            'reason' => 'Kekecilan di bagian bahu',
            // Bukti pengajuan retur sekarang dua berkas dan keduanya wajib:
            // satu sampai empat foto, dan satu video pembukaan paket.
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 400, 400)],
            'video' => UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
        ])->assertRedirect();

        $retur = ReturnRequest::where('order_id', $pesanan->id)->latest('id')->firstOrFail();

        $this->assertSame(1, $retur->item()->count(),
            'Yang tercatat hanya satu potong, sebab yang dicentang satu.');
        $this->assertSame(1, (int) $retur->item()->first()->quantity,
            'Satu baris pilihan sama dengan satu potong.');
    }

    /** Potong yang sudah diretur tidak ditawarkan lagi. */
    public function test_potong_yang_sudah_diretur_tidak_muncul_lagi(): void
    {
        [$pelanggan, $pesanan, $baris] = $this->pesananDuaPotong();

        $retur = ReturnRequest::create([
            'return_code' => 'RTR-'.now()->format('Ymd').'-8001',
            'user_id' => $pelanggan->id,
            'order_id' => $pesanan->id,
            'type' => 'tukar',
            'exchange_type' => 'ganti_ukuran',
            'method' => 'kurir',
            'reason' => 'Kekecilan',
            'status' => 'menunggu_validasi',
        ]);

        ReturnItem::create([
            'return_request_id' => $retur->id,
            'order_item_id' => $baris->id,
            'product_variant_id' => $baris->product_variant_id,
            'product_name' => $baris->product_name,
            'quantity' => 1,
        ]);

        // Tinggal satu potong yang boleh diajukan, jadi nomor duanya hilang.
        $this->actingAs($pelanggan)
            ->get(route('retur.create', $pesanan))
            ->assertOk()
            ->assertSee('value="'.$baris->id.'-1"', false)
            ->assertDontSee('value="'.$baris->id.'-2"', false);
    }

    /** Kunci barang milik pesanan lain tetap ditolak. */
    public function test_barang_pesanan_lain_ditolak(): void
    {
        [$pelanggan, $pesanan] = $this->pesananDuaPotong();
        $toko = Store::where('is_active', true)->firstOrFail();

        $this->actingAs($pelanggan)->post(route('retur.store', $pesanan), [
            'item' => ['999999-1'],
            'exchange_type' => 'ganti_ukuran',
            'method' => 'kurir',
            'store_id' => $toko->id,
            'reason' => 'Mencoba mengirim barang pesanan lain',
            // Bukti pengajuan retur sekarang dua berkas dan keduanya wajib:
            // satu sampai empat foto, dan satu video pembukaan paket.
            'photos' => [UploadedFile::fake()->image('bukti.jpg', 400, 400)],
            'video' => UploadedFile::fake()->create('unboxing.mp4', 900, 'video/mp4'),
        ]);

        $this->assertSame(0, ReturnRequest::where('order_id', $pesanan->id)->count(),
            'Barang yang bukan milik pesanan ini tidak boleh diterima.');
    }
}
