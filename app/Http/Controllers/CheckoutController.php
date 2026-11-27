<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Models\TrackingEvent;
use App\Models\Voucher;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Checkout: memilih alamat atau toko pengambilan, jasa kirim, poin, voucher,
 * lalu membuat pesanan beserta baris pembayarannya.
 *
 * Cara pembayaran yang sebenarnya (QRIS, Virtual Account, e-wallet) dipilih
 * pelanggan di jendela Snap milik Midtrans, jadi di halaman ini hanya
 * ditampilkan keterangannya saja.
 */
class CheckoutController extends Controller
{
    public function index()
    {
        $pengguna = auth()->user();
        $item = $this->itemTerpilih();

        if ($item->isEmpty()) {
            return redirect()->route('keranjang.index')
                ->with('ingat', 'Centang dulu barang yang ingin dibayar di keranjang.');
        }

        $subtotal = $this->hitungSubtotal($item);
        $batasGratis = (int) setting('free_shipping_threshold', 350000);
        $nilaiPoin = (int) setting('point_value', 100);
        $persenPoin = (int) setting('points_max_percent', 30);

        // Poin yang boleh dipakai dibatasi persentase dari subtotal
        $batasRupiahPoin = (int) floor($subtotal * $persenPoin / 100);
        $poinMaks = min((int) $pengguna->points, $nilaiPoin > 0 ? intdiv($batasRupiahPoin, $nilaiPoin) : 0);

        $voucher = $this->voucherSesi($subtotal);
        $diskon = $voucher ? $this->hitungDiskon($voucher, $subtotal) : 0;

        return view('checkout.index', [
            'item' => $item,
            'subtotal' => $subtotal,
            'alamat' => $pengguna->alamat()->orderByDesc('is_primary')->orderBy('id')->get(),
            'jasaKirim' => ShippingRate::where('is_active', true)->orderBy('cost')->orderBy('courier')->get(),
            'daftarToko' => Store::where('is_active', true)->where('has_ttc', true)->orderBy('name')->get(),
            'gratisOngkir' => $subtotal >= $batasGratis,
            'batasGratisOngkir' => $batasGratis,
            'nilaiPoin' => $nilaiPoin,
            'persenPoin' => $persenPoin,
            'poinMaks' => $poinMaks,
            'poinDimiliki' => (int) $pengguna->points,
            'voucher' => $voucher,
            'diskon' => $diskon,
        ]);
    }

    /** Menyimpan kode voucher ke session setelah diperiksa syaratnya. */
    public function voucher(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30'],
        ], [
            'kode.required' => 'Masukkan kode voucher terlebih dahulu.',
        ]);

        $kode = strtoupper(trim($data['kode']));
        $voucher = Voucher::where('code', $kode)->first();

        if (! $voucher || ! $voucher->is_active) {
            return back()->with('gagal', 'Kode voucher '.$kode.' tidak ditemukan atau sudah tidak berlaku.');
        }

        if ($voucher->expired_at && $voucher->expired_at->lt(now()->startOfDay())) {
            return back()->with('gagal', 'Voucher '.$kode.' sudah kedaluwarsa.');
        }

        if ($voucher->quota > 0 && $voucher->used >= $voucher->quota) {
            return back()->with('gagal', 'Kuota voucher '.$kode.' sudah habis dipakai.');
        }

        $subtotal = $this->hitungSubtotal($this->itemTerpilih());
        if ($subtotal < (int) $voucher->min_purchase) {
            return back()->with('gagal', 'Voucher '.$kode.' dipakai untuk belanja minimal '.rupiah($voucher->min_purchase).'.');
        }

        session(['kode_voucher' => $kode]);

        return back()->with('berhasil', 'Voucher '.$kode.' dipakai pada pesanan ini.');
    }

    public function hapusVoucher()
    {
        session()->forget('kode_voucher');

        return back()->with('ingat', 'Voucher dilepas dari pesanan ini.');
    }

    /** Membuat pesanan dari barang yang dicentang di keranjang. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'method' => ['required', 'in:kirim,ambil_toko'],
            'address_id' => ['required_if:method,kirim', 'nullable', 'integer', 'exists:addresses,id'],
            'shipping_rate_id' => ['required_if:method,kirim', 'nullable', 'integer', 'exists:shipping_rates,id'],
            'store_id' => ['required_if:method,ambil_toko', 'nullable', 'integer', 'exists:stores,id'],
            'poin' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:191'],
        ], [
            'method.required' => 'Pilih cara menerima barang.',
            'address_id.required_if' => 'Pilih alamat pengiriman terlebih dahulu.',
            'shipping_rate_id.required_if' => 'Pilih jasa kirim terlebih dahulu.',
            'store_id.required_if' => 'Pilih toko pengambilan terlebih dahulu.',
        ]);

        $pengguna = auth()->user();
        $item = $this->itemTerpilih();

        if ($item->isEmpty()) {
            return redirect()->route('keranjang.index')
                ->with('ingat', 'Centang dulu barang yang ingin dibayar di keranjang.');
        }

        // Alamat dan toko diperiksa lagi supaya tidak memakai milik orang lain
        $alamat = null;
        $toko = null;
        if ($data['method'] === 'kirim') {
            $alamat = Address::where('id', $data['address_id'])->where('user_id', $pengguna->id)->first();
            if (! $alamat) {
                return back()->withInput()->with('gagal', 'Alamat pengiriman tidak ditemukan pada akun Anda.');
            }
        } else {
            $toko = Store::where('id', $data['store_id'])->where('is_active', true)->where('has_ttc', true)->first();
            if (! $toko) {
                return back()->withInput()->with('gagal', 'Toko pengambilan tidak tersedia. Silakan pilih toko lain.');
            }
        }

        // Stok diperiksa ulang sesaat sebelum pesanan dibuat
        foreach ($item as $baris) {
            if (! $baris->varian || ! $baris->varian->produk) {
                return back()->with('gagal', 'Ada barang yang sudah tidak tersedia. Silakan periksa keranjang Anda.');
            }
            if ($baris->varian->stock < $baris->quantity) {
                return back()->with('gagal', 'Stok '.$baris->varian->produk->name.' ('.$baris->varian->label().') tinggal '.$baris->varian->stock.' potong.');
            }
        }

        $subtotal = $this->hitungSubtotal($item);

        // Ongkos kirim: gratis bila belanja sudah mencapai batas pengaturan
        $ongkir = 0;
        $jasaKirim = null;
        if ($data['method'] === 'kirim') {
            $jasaKirim = ShippingRate::find($data['shipping_rate_id']);
            $ongkir = $subtotal >= (int) setting('free_shipping_threshold', 350000) ? 0 : (int) $jasaKirim->cost;
        }

        $voucher = $this->voucherSesi($subtotal);
        $diskon = $voucher ? $this->hitungDiskon($voucher, $subtotal) : 0;

        // Poin: dibatasi persentase dari subtotal dan poin yang dimiliki
        $nilaiPoin = (int) setting('point_value', 100);
        $persenPoin = (int) setting('points_max_percent', 30);
        $batasRupiahPoin = (int) floor($subtotal * $persenPoin / 100);
        $poinMaks = min((int) $pengguna->points, $nilaiPoin > 0 ? intdiv($batasRupiahPoin, $nilaiPoin) : 0);

        $poin = min((int) ($data['poin'] ?? 0), $poinMaks);
        $potonganPoin = $poin * $nilaiPoin;

        // Potongan tidak boleh melebihi tagihan
        $sisa = $subtotal + $ongkir - $diskon;
        if ($potonganPoin > $sisa) {
            $poin = $nilaiPoin > 0 ? intdiv(max($sisa, 0), $nilaiPoin) : 0;
            $potonganPoin = $poin * $nilaiPoin;
        }

        $total = max(0, $subtotal + $ongkir - $diskon - $potonganPoin);

        $pesanan = DB::transaction(function () use ($pengguna, $item, $data, $alamat, $toko, $subtotal, $ongkir, $diskon, $poin, $total, $voucher) {
            // Kode pesanan: CBX-YYYYMMDD-0001 dari jumlah pesanan hari ini + 1
            $urut = Order::whereDate('created_at', now()->toDateString())->count() + 1;
            $kode = 'CBX-'.now()->format('Ymd').'-'.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);

            $pesanan = Order::create([
                'order_code' => $kode,
                'user_id' => $pengguna->id,
                'address_id' => $alamat?->id,
                'store_id' => $toko?->id,
                'method' => $data['method'],
                'voucher_id' => $voucher?->id,
                'subtotal' => $subtotal,
                'shipping_cost' => $ongkir,
                'discount' => $diskon,
                'points_used' => $poin,
                'total' => $total,
                'status' => 'menunggu_pembayaran',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($item as $baris) {
                $varian = $baris->varian;
                $produk = $varian->produk;
                $gambar = $produk->gambar->firstWhere('color', $varian->color) ?: $produk->gambar->first();
                $harga = $produk->hargaJual();

                OrderItem::create([
                    'order_id' => $pesanan->id,
                    'product_variant_id' => $varian->id,
                    'product_name' => $produk->name,
                    'color' => $varian->color,
                    'size' => $varian->size,
                    'image_path' => $gambar?->path,
                    'price' => $harga,
                    'quantity' => $baris->quantity,
                    'subtotal' => $harga * $baris->quantity,
                    'reviewed' => false,
                ]);

                // Stok dikunci supaya tidak dibeli orang lain selama menunggu bayar
                $varian->decrement('stock', $baris->quantity);
            }

            if ($poin > 0) {
                $pengguna->ubahPoin(-$poin, 'pesanan', 'Poin dipakai pada pesanan '.$pesanan->order_code);
            }

            if ($voucher) {
                $voucher->increment('used');
            }

            // Barang yang sudah dicheckout dikeluarkan dari keranjang
            CartItem::whereIn('id', $item->pluck('id'))->delete();

            Payment::create([
                'order_id' => $pesanan->id,
                'gross_amount' => $total,
                'transaction_status' => 'pending',
                'payment_status' => 'menunggu',
                'expired_at' => now()->addHours((int) setting('payment_expiry_hours', 24)),
            ]);

            TrackingEvent::create([
                'order_id' => $pesanan->id,
                'status' => 'menunggu_pembayaran',
                'description' => 'Pesanan dibuat, menunggu pembayaran',
                'happened_at' => now(),
            ]);

            return $pesanan;
        });

        session()->forget('kode_voucher');

        // Meminta token Snap ke Midtrans. Kalau kuncinya belum diisi di .env,
        // pesanan tetap ada dan keterangannya ditampilkan di halaman pembayaran.
        $pesanan->load(['item', 'pengguna', 'pembayaran']);
        $midtrans = new MidtransService();
        $hasil = $midtrans->buatTransaksi($pesanan);

        if ($hasil && ! empty($hasil['token'])) {
            $pesanan->pembayaran->update([
                'snap_token' => $hasil['token'],
                'snap_url' => $hasil['redirect_url'] ?? null,
            ]);
        }

        return redirect()->route('pembayaran.show', $pesanan->pembayaran)
            ->with('berhasil', 'Pesanan '.$pesanan->order_code.' dibuat. Silakan selesaikan pembayarannya.');
    }

    /** Barang di keranjang yang dicentang pelanggan. */
    private function itemTerpilih()
    {
        $keranjang = Cart::where('user_id', auth()->id())->first();
        if (! $keranjang) {
            return collect();
        }

        return $keranjang->item()
            ->where('selected', true)
            ->with(['varian.produk.gambar'])
            ->orderBy('id')
            ->get();
    }

    /** Menjumlahkan harga seluruh barang terpilih. */
    private function hitungSubtotal($item): int
    {
        $subtotal = 0;
        foreach ($item as $baris) {
            if ($baris->varian && $baris->varian->produk) {
                $subtotal += $baris->varian->produk->hargaJual() * $baris->quantity;
            }
        }

        return (int) $subtotal;
    }

    /** Voucher yang tersimpan di session, diperiksa ulang syaratnya. */
    private function voucherSesi(int $subtotal): ?Voucher
    {
        $kode = session('kode_voucher');
        if (! $kode) {
            return null;
        }

        $voucher = Voucher::where('code', $kode)->where('is_active', true)->first();

        $sah = $voucher
            && (! $voucher->expired_at || $voucher->expired_at->gte(now()->startOfDay()))
            && ($voucher->quota <= 0 || $voucher->used < $voucher->quota)
            && $subtotal >= (int) $voucher->min_purchase;

        if (! $sah) {
            session()->forget('kode_voucher');

            return null;
        }

        return $voucher;
    }

    /** Menghitung potongan voucher, tidak boleh melebihi subtotal. */
    private function hitungDiskon(Voucher $voucher, int $subtotal): int
    {
        if ($voucher->type === 'percent') {
            $diskon = (int) floor($subtotal * (int) $voucher->value / 100);
            if ($voucher->max_discount) {
                $diskon = min($diskon, (int) $voucher->max_discount);
            }
        } else {
            $diskon = (int) $voucher->value;
        }

        return (int) min($diskon, $subtotal);
    }
}
