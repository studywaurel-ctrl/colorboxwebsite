<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\TapTryCollect;
use App\Models\TapTryCollectItem;
use App\Models\TapTryCollectSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tap Try and Collect: produk dipilih dari katalog, lalu di halaman ini
 * pelanggan menentukan toko, tanggal, dan jam kunjungan. Slot layanan ini
 * memakai tabel tap_try_collect_slots yang benar-benar terpisah dari slot
 * donasi (kuota dan jamnya sendiri).
 */
class TtcController extends Controller
{
    /**
     * Halaman pemesanan. Boleh dilihat tamu, memesannya harus masuk dulu.
     * Produk TIDAK dipilih di sini: pelanggan datang dari halaman produk
     * lewat tautan ttc.index dengan query varian (atau produk sebagai cadangan).
     */
    public function index(Request $request)
    {
        $request->validate([
            'varian' => ['nullable', 'integer'],
            'produk' => ['nullable', 'string', 'max:191'],
        ]);

        $varian = $this->varianDariPermintaan($request);

        // Tanpa produk: halaman ringkas yang mengarahkan pelanggan ke katalog
        if (! $varian) {
            return view('ttc.index', [
                'varian' => null,
                'toko' => collect(),
                'tokoBerstok' => [],
                'tanggalAwal' => now()->toDateString(),
                'tanggalAkhir' => now()->toDateString(),
                'menitSiap' => (int) setting('ttc_prep_minutes', 30),
            ]);
        }

        $toko = Store::where('is_active', true)
            ->where('has_ttc', true)
            ->orderBy('city')
            ->orderBy('name')
            ->get();

        // Toko yang memang menyimpan varian itu diberi penanda "Stok tersedia"
        $tokoBerstok = StoreStock::where('product_variant_id', $varian->id)
            ->where('stock', '>', 0)
            ->pluck('store_id')
            ->all();

        // Kalender dibatasi sesuai pengaturan booking_window_days
        $jumlahHari = max(1, (int) setting('booking_window_days', 14));

        return view('ttc.index', [
            'varian' => $varian,
            'toko' => $toko,
            'tokoBerstok' => $tokoBerstok,
            'tanggalAwal' => now()->toDateString(),
            'tanggalAkhir' => now()->addDays($jumlahHari - 1)->toDateString(),
            'menitSiap' => (int) setting('ttc_prep_minutes', 30),
        ]);
    }

    /**
     * Daftar jam Tap Try and Collect pada satu toko dan satu tanggal.
     * Dipanggil halaman index memakai fetch, jadi jawabannya berupa JSON.
     */
    public function slot(Request $request)
    {
        $data = $request->validate([
            'toko' => ['required', 'integer', 'exists:stores,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
        ]);

        $daftar = TapTryCollectSlot::where('store_id', $data['toko'])
            ->whereDate('date', $data['tanggal'])
            ->where('status', 'aktif')
            ->orderBy('start_time')
            ->get();

        $batas = $this->batasPenyiapan();

        $slot = [];
        foreach ($daftar as $satu) {
            // Slot dianggap penuh bila kuotanya habis atau waktunya terlalu dekat
            $terlaluDekat = $this->waktuMulai($satu)->lt($batas);

            $slot[] = [
                'id' => $satu->id,
                'jam' => jam_singkat($satu->start_time).' - '.jam_singkat($satu->end_time),
                'sisa' => $satu->sisaKuota(),
                'penuh' => $satu->sisaKuota() < 1 || $terlaluDekat,
            ];
        }

        return response()->json(['slot' => $slot]);
    }

    /** Menyimpan booking. Kuota dan batas penyiapan diperiksa ulang di dalam transaksi. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'tap_try_collect_slot_id' => ['required', 'integer', 'exists:tap_try_collect_slots,id'],
        ], [
            'product_variant_id.required' => 'Produk yang ingin dicoba belum dipilih. Silakan buka halaman produk terlebih dahulu.',
            'store_id.required' => 'Pilih toko tujuan terlebih dahulu.',
            'tap_try_collect_slot_id.required' => 'Pilih tanggal dan jam kunjungan terlebih dahulu.',
        ]);

        $varian = ProductVariant::with('produk')->find($data['product_variant_id']);

        if (! $varian || ! $varian->produk || ! $varian->produk->is_active) {
            return back()->withInput()->with('gagal', 'Produk yang dipilih sudah tidak tersedia.');
        }

        $slot = TapTryCollectSlot::with('toko')->find($data['tap_try_collect_slot_id']);

        if (! $slot || $slot->status !== 'aktif') {
            return back()->withInput()->with('gagal', 'Jam yang dipilih sudah tidak dilayani. Silakan pilih waktu lainnya.');
        }

        if ((int) $slot->store_id !== (int) $data['store_id']) {
            return back()->withInput()->with('gagal', 'Jam yang dipilih bukan milik toko tersebut. Silakan pilih ulang.');
        }

        if (! $slot->toko || ! $slot->toko->is_active || ! $slot->toko->has_ttc) {
            return back()->withInput()->with('gagal', 'Toko tersebut belum melayani Tap Try and Collect.');
        }

        if ($this->waktuMulai($slot)->lt($this->batasPenyiapan())) {
            return back()->withInput()->with('gagal', 'Jadwal ini sudah lewat batas penyiapan produk. Silakan pilih jam lain.');
        }

        // Satu pelanggan hanya boleh punya satu booking aktif pada slot yang sama
        $sudahAda = TapTryCollect::where('user_id', auth()->id())
            ->where('tap_try_collect_slot_id', $slot->id)
            ->whereIn('status', ['dijadwalkan', 'siap'])
            ->exists();

        if ($sudahAda) {
            return back()->withInput()->with('gagal', 'Anda sudah memiliki jadwal pada jam tersebut.');
        }

        // Status aktif, kuota, dan batas penyiapan diperiksa ULANG di dalam transaksi
        $hasil = DB::transaction(function () use ($slot, $varian) {
            $terkunci = TapTryCollectSlot::where('id', $slot->id)->lockForUpdate()->first();

            if (! $terkunci || $terkunci->status !== 'aktif') {
                return 'tutup';
            }

            if ($this->waktuMulai($terkunci)->lt($this->batasPenyiapan())) {
                return 'lewat';
            }

            if ($terkunci->booked_count >= $terkunci->quota) {
                return 'penuh';
            }

            $urut = TapTryCollect::whereDate('created_at', now()->toDateString())->count() + 1;

            $baru = TapTryCollect::create([
                'booking_code' => 'TTC-'.now()->format('Ymd').'-'.str_pad($urut, 4, '0', STR_PAD_LEFT),
                'user_id' => auth()->id(),
                'tap_try_collect_slot_id' => $terkunci->id,
                'store_id' => $terkunci->store_id,
                'status' => 'dijadwalkan',
                'total_paid' => 0,
            ]);

            TapTryCollectItem::create([
                'tap_try_collect_id' => $baru->id,
                'product_variant_id' => $varian->id,
                'quantity' => 1,
                'purchased' => false,
            ]);

            $terkunci->booked_count = $terkunci->booked_count + 1;
            $terkunci->save();

            return $baru;
        });

        if ($hasil === 'penuh') {
            return back()->withInput()->with('gagal', 'Maaf, slot baru saja penuh. Silakan pilih waktu lainnya.');
        }

        if ($hasil === 'lewat') {
            return back()->withInput()->with('gagal', 'Jadwal ini sudah lewat batas penyiapan produk. Silakan pilih jam lain.');
        }

        if (! $hasil instanceof TapTryCollect) {
            return back()->withInput()->with('gagal', 'Jam yang dipilih sudah tidak dilayani. Silakan pilih waktu lainnya.');
        }

        $booking = $hasil;

        Notification::create([
            'user_id' => auth()->id(),
            'title' => 'Jadwal Tap Try and Collect tercatat',
            'body' => 'Kode '.$booking->booking_code.' pada '.tanggal_id($slot->date, true)
                .' pukul '.jam_singkat($slot->start_time).' di '.$slot->toko->name.'.',
            'url' => route('ttc.show', $booking),
        ]);

        return redirect()->route('ttc.show', $booking)
            ->with('berhasil', 'Jadwal Tap Try and Collect '.$booking->booking_code.' berhasil dibuat.');
    }

    /** Riwayat booking milik pelanggan yang sedang masuk. */
    public function riwayat()
    {
        $daftar = TapTryCollect::where('user_id', auth()->id())
            ->with(['slot', 'toko', 'item.varian.produk'])
            ->orderByDesc('id')
            ->get();

        return view('ttc.riwayat', ['daftar' => $daftar]);
    }

    /** Rincian satu booking beserta QR untuk staf toko. */
    public function show(TapTryCollect $tapTryCollect)
    {
        abort_unless($tapTryCollect->user_id === auth()->id(), 404);

        $tapTryCollect->load(['slot', 'toko', 'item.varian.produk.gambar']);

        return view('ttc.show', [
            'booking' => $tapTryCollect,
            'menitSiap' => (int) setting('ttc_prep_minutes', 30),
            'alamatStaf' => route('staf.verifikasi', ['jenis' => 'ttc', 'kode' => $tapTryCollect->booking_code]),
        ]);
    }

    /** Membatalkan booking dan mengembalikan kuota slotnya. */
    public function batal(TapTryCollect $tapTryCollect)
    {
        abort_unless($tapTryCollect->user_id === auth()->id(), 404);

        if ($tapTryCollect->status !== 'dijadwalkan') {
            return back()->with('gagal', 'Jadwal ini sudah tidak bisa dibatalkan.');
        }

        DB::transaction(function () use ($tapTryCollect) {
            $slot = TapTryCollectSlot::where('id', $tapTryCollect->tap_try_collect_slot_id)->lockForUpdate()->first();

            if ($slot && $slot->booked_count > 0) {
                $slot->booked_count = $slot->booked_count - 1;
                $slot->save();
            }

            $tapTryCollect->status = 'dibatalkan';
            $tapTryCollect->save();
        });

        return redirect()->route('ttc.show', $tapTryCollect)
            ->with('berhasil', 'Jadwal '.$tapTryCollect->booking_code.' dibatalkan.');
    }

    /** Varian yang dibawa dari katalog: lewat id varian, atau slug produknya. */
    private function varianDariPermintaan(Request $request): ?ProductVariant
    {
        $varian = null;

        if ($request->query('varian')) {
            $varian = ProductVariant::with('produk.gambar')->find((int) $request->query('varian'));
        }

        if (! $varian && $request->query('produk')) {
            $produk = Product::where('slug', $request->query('produk'))
                ->where('is_active', true)
                ->with(['varian', 'gambar'])
                ->first();

            if ($produk) {
                // Varian pertama yang masih ada stoknya, bila tidak ada pakai varian pertama
                $varian = $produk->varian->firstWhere('stock', '>', 0) ?: $produk->varian->first();

                if ($varian) {
                    $varian->setRelation('produk', $produk);
                }
            }
        }

        if ($varian && (! $varian->produk || ! $varian->produk->is_active)) {
            return null;
        }

        return $varian;
    }

    /** Batas paling awal sebuah jadwal boleh dipesan (produk perlu disiapkan dulu). */
    private function batasPenyiapan(): Carbon
    {
        return now()->addMinutes((int) setting('ttc_prep_minutes', 30));
    }

    /** Waktu mulai sebuah slot sebagai tanggal sekaligus jam. */
    private function waktuMulai(TapTryCollectSlot $slot): Carbon
    {
        return Carbon::parse($slot->date->toDateString().' '.$slot->start_time);
    }
}
