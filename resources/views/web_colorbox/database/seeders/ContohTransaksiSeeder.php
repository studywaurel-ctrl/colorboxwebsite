<?php

namespace Database\Seeders;

use App\Models\Donation;
use App\Models\DonationSlot;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PointTransaction;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\TapTryCollect;
use App\Models\TapTryCollectItem;
use App\Models\TapTryCollectSlot;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh supaya setiap halaman langsung ada isinya ketika dicoba:
 * pesanan selesai (bisa diretur dan diulas), pesanan berjalan, pesanan
 * menunggu pembayaran, jadwal donasi hari ini, dan booking Tap Try and Collect.
 */
class ContohTransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $pelanggan = User::where('email', 'real.notmreli@gmail.com')->first();
        if (! $pelanggan) {
            return;
        }
        $alamat = $pelanggan->alamat()->first();
        $varian = ProductVariant::with('produk.gambar')->inRandomOrder()->take(6)->get();
        if ($varian->count() < 4) {
            return;
        }

        // ---------------------------------------------- pesanan sudah selesai
        $selesai = $this->buatPesanan($pelanggan, $alamat, [$varian[0], $varian[1]], 'DELIVERED', now()->subDays(9));
        $this->bayar($selesai, 'settlement', 'qris', now()->subDays(9)->addMinutes(12));
        $this->kirim($selesai, now()->subDays(8), now()->subDays(6));
        $this->riwayat($selesai, [
            ['PAYMENT_CONFIRMED', 'Pembayaran diterima', now()->subDays(9)->addMinutes(12)],
            ['PROCESSING', 'Pesanan diproses admin', now()->subDays(9)->addHours(3)],
            ['PACKED', 'Pesanan dikemas', now()->subDays(8)],
            ['PICKED_UP_BY_COURIER', 'Paket diserahkan kepada kurir', now()->subDays(8)->addHours(2)],
            ['IN_TRANSIT', 'Paket dalam perjalanan', now()->subDays(7)],
            ['OUT_FOR_DELIVERY', 'Paket menuju alamat pelanggan', now()->subDays(6)->subHours(4)],
            ['DELIVERED', 'Paket diterima pelanggan', now()->subDays(6)],
        ]);

        // ---------------------------------------------- pesanan sedang dikirim
        $berjalan = $this->buatPesanan($pelanggan, $alamat, [$varian[2]], 'IN_TRANSIT', now()->subDays(2));
        $this->bayar($berjalan, 'settlement', 'bank_transfer', now()->subDays(2)->addMinutes(30));
        $this->kirim($berjalan, now()->subDay(), null);
        $this->riwayat($berjalan, [
            ['PAYMENT_CONFIRMED', 'Pembayaran diterima', now()->subDays(2)->addMinutes(30)],
            ['PROCESSING', 'Pesanan diproses admin', now()->subDays(2)->addHours(4)],
            ['PACKED', 'Pesanan dikemas', now()->subDay()],
            ['PICKED_UP_BY_COURIER', 'Paket diserahkan kepada kurir', now()->subDay()->addHours(2)],
            ['IN_TRANSIT', 'Paket dalam perjalanan', now()->subDay()->addHours(3)],
        ]);

        // ------------------------------------- pesanan menunggu pembayaran
        $menunggu = $this->buatPesanan($pelanggan, $alamat, [$varian[3]], 'PENDING_PAYMENT', now()->subHours(2));
        $this->bayar($menunggu, 'pending', null, null);
        $this->riwayat($menunggu, [
            ['PENDING_PAYMENT', 'Pesanan dibuat, menunggu pembayaran', now()->subHours(2)],
        ]);

        // ---------------------------------------------------- pesanan batal
        $batal = $this->buatPesanan($pelanggan, $alamat, [$varian[4]], 'CANCELLED', now()->subDays(5));
        $batal->update(['cancelled_at' => now()->subDays(4)]);
        $this->bayar($batal, 'expire', null, null);
        $this->riwayat($batal, [
            ['PENDING_PAYMENT', 'Pesanan dibuat, menunggu pembayaran', now()->subDays(5)],
            ['CANCELLED', 'Batas waktu pembayaran terlampaui', now()->subDays(4)],
        ]);

        // ------------------------------------------------ donasi hari ini
        $tokoDonasi = Store::where('has_donation', true)->first();
        $slotDonasi = DonationSlot::where('store_id', $tokoDonasi?->id)
            ->whereDate('date', now()->toDateString())->orderBy('start_time')->first();
        if ($slotDonasi) {
            $donasi = Donation::updateOrCreate(['donation_code' => 'DON-'.now()->format('Ymd').'-0001'], [
                'user_id' => $pelanggan->id,
                'donation_slot_id' => $slotDonasi->id,
                'store_id' => $slotDonasi->store_id,
                'estimated_items' => 5,
                'condition_note' => 'Pakaian layak pakai, sudah dicuci bersih.',
                'status' => 'SCHEDULED',
            ]);
            if ($donasi->wasRecentlyCreated) {
                $slotDonasi->increment('booked_count');
            }
        }

        // donasi lama yang sudah disetujui beserta poinnya
        $slotLama = DonationSlot::where('store_id', $tokoDonasi?->id)->orderBy('date')->first();
        if ($slotLama) {
            $lama = Donation::updateOrCreate(['donation_code' => 'DON-'.now()->subMonth()->format('Ymd').'-0009'], [
                'user_id' => $pelanggan->id,
                'donation_slot_id' => $slotLama->id,
                'store_id' => $slotLama->store_id,
                'estimated_items' => 6,
                'accepted_items' => 4,
                'rejected_items' => 2,
                'verify_note' => '2 potong sudah tipis dan berlubang',
                'status' => 'VERIFIED',
                'points_awarded' => 4,
                'checked_in_at' => now()->subMonth(),
                'verified_at' => now()->subMonth()->addMinutes(20),
            ]);
            PointTransaction::firstOrCreate([
                'user_id' => $pelanggan->id,
                'source' => 'donasi',
                'amount' => 4,
            ], ['type' => 'masuk', 'note' => 'Donasi '.$lama->donation_code]);
        }

        // ------------------------------- Tap Try and Collect untuk besok
        // Varian sengaja dipilih yang memang ada stoknya di toko tersebut,
        // supaya contoh datanya sama dengan hasil pemesanan sungguhan.
        $tokoTtc = Store::where('has_ttc', true)->first();
        $stokContoh = $tokoTtc
            ? StoreStock::where('store_id', $tokoTtc->id)->where('stock', '>', 0)->first()
            : null;
        $slotTtc = TapTryCollectSlot::where('store_id', $tokoTtc?->id)
            ->whereDate('date', now()->addDay()->toDateString())->orderBy('start_time')->first();

        if ($slotTtc && $stokContoh) {
            $booking = TapTryCollect::updateOrCreate(['booking_code' => 'TTC-'.now()->format('Ymd').'-0001'], [
                'user_id' => $pelanggan->id,
                'tap_try_collect_slot_id' => $slotTtc->id,
                'store_id' => $slotTtc->store_id,
                'source' => 'TTC',
                'status' => 'SCHEDULED',
            ]);
            if ($booking->wasRecentlyCreated) {
                $slotTtc->increment('booked_count');
                TapTryCollectItem::create([
                    'tap_try_collect_id' => $booking->id,
                    'product_variant_id' => $stokContoh->product_variant_id,
                    'quantity' => 1,
                ]);
                // Barang ditahan, persis seperti ketika pelanggan memesan sendiri
                $stokContoh->increment('reserved');
            }
        }

        // ------------------------------------------------------ notifikasi
        $notifikasi = [
            ['Pesanan selesai', 'Pesanan '.$selesai->order_code.' sudah selesai. Terima kasih sudah belanja di Colorbox.', route('pesanan.show', $selesai->order_code)],
            ['Paket dalam perjalanan', 'Pesanan '.$berjalan->order_code.' sedang diantar kurir.', route('pesanan.show', $berjalan->order_code)],
            ['Menunggu pembayaran', 'Selesaikan pembayaran pesanan '.$menunggu->order_code.' sebelum batas waktu.', route('pesanan.show', $menunggu->order_code)],
        ];
        foreach ($notifikasi as [$judul, $isi, $tautan]) {
            Notification::firstOrCreate(
                ['user_id' => $pelanggan->id, 'title' => $judul],
                ['body' => $isi, 'url' => $tautan]
            );
        }
    }

    /** Membuat satu pesanan contoh beserta rinciannya. */
    private function buatPesanan(User $pelanggan, $alamat, array $varian, string $status, $waktu): Order
    {
        $kode = 'CBX-'.$waktu->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        $pesanan = Order::create([
            'order_code' => $kode,
            'user_id' => $pelanggan->id,
            'address_id' => $alamat?->id,
            'fulfillment_method' => 'DELIVERY',
            'subtotal' => 0,
            'shipping_cost' => 18000,
            'discount' => 0,
            'points_used' => 0,
            'total' => 0,
            'status' => $status,
            'paid_at' => in_array($status, ['PENDING_PAYMENT', 'CANCELLED']) ? null : $waktu,
            'completed_at' => $status === 'DELIVERED' ? $waktu->copy()->addDays(3) : null,
            'created_at' => $waktu,
            'updated_at' => $waktu,
        ]);

        $subtotal = 0;
        foreach ($varian as $v) {
            $harga = $v->produk->hargaJual();
            $gambar = $v->produk->gambar->first();
            OrderItem::create([
                'order_id' => $pesanan->id,
                'product_variant_id' => $v->id,
                'product_name' => $v->produk->name,
                'color' => $v->color,
                'size' => $v->size,
                'image_path' => $gambar?->path,
                'price' => $harga,
                'quantity' => 1,
                'subtotal' => $harga,
                'reviewed' => false,
            ]);
            $subtotal += $harga;
        }

        $ongkir = $subtotal >= (int) setting('free_shipping_threshold', 350000) ? 0 : 18000;
        $pesanan->update([
            'subtotal' => $subtotal,
            'shipping_cost' => $ongkir,
            'total' => $subtotal + $ongkir,
        ]);

        return $pesanan->fresh();
    }

    private function bayar(Order $pesanan, string $status, ?string $metode, $waktuBayar): void
    {
        Payment::updateOrCreate(['order_id' => $pesanan->id], [
            'payment_method' => $metode,
            'transaction_id' => $status === 'pending' ? null : 'DEMO-'.$pesanan->order_code,
            'gross_amount' => $pesanan->total,
            'transaction_status' => $status,
            'payment_status' => Payment::petaStatus($status),
            'paid_at' => $waktuBayar,
            'expired_at' => $pesanan->created_at?->copy()->addHours(24),
        ]);
    }

    private function kirim(Order $pesanan, $dikirim, $diterima): void
    {
        Shipment::updateOrCreate(['order_id' => $pesanan->id], [
            'courier' => 'JNE',
            'service' => 'Reguler',
            'tracking_number' => 'JNE'.random_int(100000000, 999999999),
            'cost' => $pesanan->shipping_cost,
            'status' => $diterima ? 'diterima' : 'dikirim',
            'shipped_at' => $dikirim,
            'delivered_at' => $diterima,
        ]);
    }

    private function riwayat(Order $pesanan, array $baris): void
    {
        foreach ($baris as [$status, $keterangan, $waktu]) {
            TrackingEvent::firstOrCreate([
                'order_id' => $pesanan->id,
                'status' => $status,
            ], [
                'description' => $keterangan,
                'happened_at' => $waktu,
            ]);
        }
    }
}
