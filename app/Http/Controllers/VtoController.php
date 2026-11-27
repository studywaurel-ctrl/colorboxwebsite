<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\VirtualTryOn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Virtual Try On.
 *
 * Dua jalur kerja dipilih lewat config('colorbox.try_on.mode'):
 *  - 'api'     : dipakai bila TRYON_API_URL diisi pada .env. Tangkapan kamera dan
 *                gambar produk dikirim ke layanan itu, gambar balasannya disimpan
 *                ke public/images/tryon.
 *  - 'preview' : bawaan. Hasil disusun di peramban memakai canvas, lalu dikirim
 *                ke sini sebagai data URL dan disimpan sebagai PNG.
 */
class VtoController extends Controller
{
    /** Memastikan sesi try-on memang milik pengunjung yang sedang membuka. */
    private function pastikanMilikSendiri(VirtualTryOn $vto): void
    {
        if ($vto->user_id) {
            abort_unless(auth()->check() && auth()->id() === $vto->user_id, 404);

            return;
        }

        // Tamu dicatat lewat daftar id yang disimpan pada session
        $milik = session('vto_milik', []);
        abort_unless(in_array($vto->id, $milik), 404);
    }

    /** Daftar sesi milik pengunjung yang sedang membuka halaman. */
    private function sesiMilikSendiri()
    {
        $daftar = VirtualTryOn::with('produk');

        if (auth()->check()) {
            $daftar->where('user_id', auth()->id());
        } else {
            $daftar->whereIn('id', session('vto_milik', []))->whereNull('user_id');
        }

        return $daftar;
    }

    /** Folder penyimpanan gambar try-on, dibuat bila belum ada. */
    private function folderGambar(): string
    {
        $folder = public_path('images/tryon');

        if (! is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        return $folder;
    }

    /** Menyimpan data URL gambar ke public/images/tryon, hasilnya jalur relatif. */
    private function simpanDataUrl(?string $isi, string $awalan): ?string
    {
        if (! $isi || ! preg_match('/^data:image\/(png|jpeg);base64,/', $isi, $cocok)) {
            return null;
        }

        $murni = base64_decode(substr($isi, strpos($isi, ',') + 1), true);

        if ($murni === false || strlen($murni) < 1) {
            return null;
        }

        if (strlen($murni) > (int) config('colorbox.try_on.max_upload_kb', 5120) * 1024) {
            return null;
        }

        $nama = $awalan.'-'.time().'-'.random_int(100, 999).'.'.($cocok[1] === 'png' ? 'png' : 'jpg');
        file_put_contents($this->folderGambar().'/'.$nama, $murni);

        return 'images/tryon/'.$nama;
    }

    /** Menghapus satu berkas gambar lama supaya folder tidak menumpuk. */
    private function hapusBerkas(?string $jalur): void
    {
        if ($jalur && file_exists(public_path($jalur))) {
            unlink(public_path($jalur));
        }
    }

    /** Gambar produk yang dipakai sebagai bahan try-on, mengikuti warna. */
    private function gambarProduk(Product $produk, ?string $warna): ?string
    {
        $gambar = $produk->gambar->firstWhere('color', $warna) ?: $produk->gambarUtama();

        if ($gambar && file_exists(public_path($gambar->path))) {
            return public_path($gambar->path);
        }

        return null;
    }

    /** Pilih produk dan warna sebelum panggung kamera dibuka. */
    public function index(Request $request)
    {
        $request->validate([
            'produk' => ['nullable', 'string', 'max:191'],
        ]);

        $produk = Product::where('is_active', true)
            ->with(['kategori', 'gambar', 'varian'])
            ->orderBy('name')
            ->get();

        $terpilih = null;
        if ($request->query('produk')) {
            $terpilih = $produk->firstWhere('slug', $request->query('produk'));
        }
        if (! $terpilih) {
            $terpilih = $produk->first();
        }

        return view('vto.index', [
            'produk' => $produk,
            'terpilih' => $terpilih,
            'riwayat' => $this->sesiMilikSendiri()
                ->whereNotNull('result_path')
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
        ]);
    }

    /** Membuat satu sesi try-on lalu membuka panggung kamera. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'color' => ['required', 'string', 'max:40'],
        ], [
            'product_id.required' => 'Pilih produk yang ingin dicoba.',
            'color.required' => 'Pilih warna produk terlebih dahulu.',
        ]);

        $sesi = VirtualTryOn::create([
            'user_id' => auth()->id(),
            'product_id' => $data['product_id'],
            'color' => $data['color'],
            'status' => 'dibuat',
            'engine' => config('colorbox.try_on.mode', 'preview'),
        ]);

        if (! auth()->check()) {
            $milik = session('vto_milik', []);
            $milik[] = $sesi->id;
            session(['vto_milik' => $milik]);
        }

        return redirect()->route('vto.show', $sesi);
    }

    /** Panggung kamera, pilihan warna dan ukuran, serta hasilnya. */
    public function show(VirtualTryOn $virtualTryOn)
    {
        $this->pastikanMilikSendiri($virtualTryOn);

        $virtualTryOn->load(['produk.gambar', 'produk.varian']);

        $produk = $virtualTryOn->produk;

        // Warna yang tersedia diambil dari varian, satu baris untuk satu warna
        $warna = $produk->varian->unique('color')->values();

        if (! $virtualTryOn->color && $warna->count() > 0) {
            $virtualTryOn->color = $warna->first()->color;
            $virtualTryOn->save();
        }

        return view('vto.show', [
            'sesi' => $virtualTryOn,
            'produk' => $produk,
            'warna' => $warna,
            'mode' => config('colorbox.try_on.mode', 'preview'),
            'riwayat' => $this->sesiMilikSendiri()
                ->whereNotNull('result_path')
                ->where('id', '!=', $virtualTryOn->id)
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
        ]);
    }

    /**
     * Menerima gambar dari panggung kamera.
     *
     * Mode 'preview': gambar yang dikirim sudah berupa hasil susunan canvas.
     * Mode 'api'    : gambar yang dikirim adalah tangkapan kamera, lalu diteruskan
     *                 ke layanan bersama gambar produk.
     */
    public function proses(Request $request, VirtualTryOn $virtualTryOn)
    {
        $this->pastikanMilikSendiri($virtualTryOn);

        $data = $request->validate([
            'gambar' => ['required', 'string'],
            'foto' => ['nullable', 'string'],
            'warna' => ['nullable', 'string', 'max:40'],
            'ukuran' => ['nullable', 'string', 'max:10'],
        ], [
            'gambar.required' => 'Belum ada gambar yang dikirim. Ambil gambar sekali lagi.',
        ]);

        $mode = config('colorbox.try_on.mode', 'preview');

        if (! empty($data['warna'])) {
            $virtualTryOn->color = $data['warna'];
        }
        if (! empty($data['ukuran'])) {
            $virtualTryOn->size = $data['ukuran'];
        }

        $virtualTryOn->engine = $mode;
        $virtualTryOn->status = 'diproses';
        $virtualTryOn->save();

        if ($mode === 'api') {
            return $this->prosesLayanan($virtualTryOn, $data);
        }

        return $this->prosesPeramban($virtualTryOn, $data);
    }

    /** Hasil sudah disusun di peramban, tinggal disimpan. */
    private function prosesPeramban(VirtualTryOn $virtualTryOn, array $data)
    {
        $hasil = $this->simpanDataUrl($data['gambar'], 'tryon-'.$virtualTryOn->id);

        if (! $hasil) {
            $virtualTryOn->status = 'gagal';
            $virtualTryOn->note = 'Gambar tidak dapat dibaca.';
            $virtualTryOn->save();

            return back()->with('gagal', 'Gambar hasil kamera tidak dapat dibaca. Silakan coba lagi.');
        }

        $foto = $this->simpanDataUrl($data['foto'] ?? null, 'foto-'.$virtualTryOn->id);

        $this->hapusBerkas($virtualTryOn->result_path);
        if ($foto) {
            $this->hapusBerkas($virtualTryOn->photo_path);
            $virtualTryOn->photo_path = $foto;
        }

        $virtualTryOn->result_path = $hasil;
        $virtualTryOn->status = 'selesai';
        $virtualTryOn->note = null;
        $virtualTryOn->save();

        return redirect()->route('vto.show', $virtualTryOn)->with('berhasil', 'Hasil Virtual Try On sudah siap.');
    }

    /** Tangkapan kamera dan gambar produk dikirim ke layanan try-on. */
    private function prosesLayanan(VirtualTryOn $virtualTryOn, array $data)
    {
        $foto = $this->simpanDataUrl($data['gambar'], 'foto-'.$virtualTryOn->id);

        if (! $foto) {
            $virtualTryOn->status = 'gagal';
            $virtualTryOn->note = 'Gambar tidak dapat dibaca.';
            $virtualTryOn->save();

            return back()->with('gagal', 'Gambar hasil kamera tidak dapat dibaca. Silakan coba lagi.');
        }

        $this->hapusBerkas($virtualTryOn->photo_path);
        $virtualTryOn->photo_path = $foto;
        $virtualTryOn->save();

        $gambarProduk = $this->gambarProduk($virtualTryOn->produk, $virtualTryOn->color);

        try {
            $kirim = Http::withToken((string) config('colorbox.try_on.api_key'))
                ->timeout((int) config('colorbox.try_on.timeout', 60))
                ->attach('person', file_get_contents(public_path($foto)), 'orang.png');

            if ($gambarProduk) {
                $kirim = $kirim->attach('garment', file_get_contents($gambarProduk), basename($gambarProduk));
            }

            $jawab = $kirim->post((string) config('colorbox.try_on.api_url'), [
                'product' => $virtualTryOn->produk->name,
                'color' => (string) $virtualTryOn->color,
                'size' => (string) $virtualTryOn->size,
            ]);

            if (! $jawab->successful()) {
                throw new \RuntimeException('Layanan menjawab dengan kode '.$jawab->status());
            }

            $hasil = $this->simpanJawabanLayanan($jawab, $virtualTryOn);

            if (! $hasil) {
                throw new \RuntimeException('Jawaban layanan tidak berisi gambar.');
            }
        } catch (\Throwable $galat) {
            Log::error('Virtual Try On gagal: '.$galat->getMessage(), ['sesi' => $virtualTryOn->id]);

            $virtualTryOn->status = 'gagal';
            $virtualTryOn->note = 'Layanan tidak dapat dihubungi.';
            $virtualTryOn->save();

            return redirect()->route('vto.show', $virtualTryOn)
                ->with('gagal', 'Layanan Virtual Try On sedang tidak dapat dihubungi. Coba lagi sebentar lagi.');
        }

        $this->hapusBerkas($virtualTryOn->result_path);
        $virtualTryOn->result_path = $hasil;
        $virtualTryOn->status = 'selesai';
        $virtualTryOn->note = null;
        $virtualTryOn->save();

        return redirect()->route('vto.show', $virtualTryOn)->with('berhasil', 'Hasil Virtual Try On sudah siap.');
    }

    /**
     * Gambar balasan layanan bisa datang tiga bentuk: berkas gambar langsung,
     * data base64 di dalam JSON, atau alamat gambar yang perlu diambil dahulu.
     */
    private function simpanJawabanLayanan($jawab, VirtualTryOn $virtualTryOn): ?string
    {
        $tipe = (string) $jawab->header('Content-Type');

        if (str_starts_with($tipe, 'image/')) {
            $nama = 'tryon-'.$virtualTryOn->id.'-'.time().'.'.(str_contains($tipe, 'jpeg') ? 'jpg' : 'png');
            file_put_contents($this->folderGambar().'/'.$nama, $jawab->body());

            return 'images/tryon/'.$nama;
        }

        $isi = $jawab->json();

        if (! is_array($isi)) {
            return null;
        }

        foreach (['image', 'image_base64', 'result', 'output'] as $kunci) {
            $nilai = $isi[$kunci] ?? null;
            if (is_string($nilai) && str_starts_with($nilai, 'data:image/')) {
                return $this->simpanDataUrl($nilai, 'tryon-'.$virtualTryOn->id);
            }
            if (is_string($nilai) && $nilai !== '' && ! str_starts_with($nilai, 'http')) {
                return $this->simpanDataUrl('data:image/png;base64,'.$nilai, 'tryon-'.$virtualTryOn->id);
            }
        }

        foreach (['image_url', 'url', 'result_url', 'output_url'] as $kunci) {
            $alamat = $isi[$kunci] ?? null;
            if (is_string($alamat) && str_starts_with($alamat, 'http')) {
                $ambil = Http::timeout(30)->get($alamat);
                if ($ambil->successful()) {
                    $nama = 'tryon-'.$virtualTryOn->id.'-'.time().'.png';
                    file_put_contents($this->folderGambar().'/'.$nama, $ambil->body());

                    return 'images/tryon/'.$nama;
                }
            }
        }

        return null;
    }

    /** Menghapus sesi try-on beserta berkas gambarnya. */
    public function destroy(VirtualTryOn $virtualTryOn)
    {
        $this->pastikanMilikSendiri($virtualTryOn);

        $this->hapusBerkas($virtualTryOn->photo_path);
        $this->hapusBerkas($virtualTryOn->result_path);

        $virtualTryOn->delete();

        return redirect()->route('vto.index')->with('berhasil', 'Sesi Virtual Try On dihapus.');
    }
}
