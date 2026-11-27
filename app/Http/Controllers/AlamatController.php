<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

/**
 * Buku alamat pengiriman milik pelanggan (tambah, ubah, hapus, jadikan utama).
 */
class AlamatController extends Controller
{
    /** Daftar alamat, alamat utama diletakkan paling atas. */
    public function index(Request $request)
    {
        $daftarAlamat = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return view('alamat.index', ['daftarAlamat' => $daftarAlamat]);
    }

    /** Formulir alamat baru. */
    public function create()
    {
        return view('alamat.form', ['alamat' => null]);
    }

    /** Menyimpan alamat baru. */
    public function store(Request $request)
    {
        $data = $this->periksaMasukan($request);
        $data['user_id'] = $request->user()->id;

        // Alamat pertama otomatis menjadi alamat utama
        $punyaAlamat = Address::where('user_id', $request->user()->id)->count();
        $data['is_primary'] = $request->boolean('is_primary') || $punyaAlamat === 0;

        $alamat = Address::create($data);

        if ($alamat->is_primary) {
            $this->tandaiUtama($alamat);
        }

        return redirect()->route('alamat.index')->with('berhasil', 'Alamat berhasil ditambahkan.');
    }

    /** Formulir ubah alamat. */
    public function edit(Request $request, Address $address)
    {
        $this->pastikanMilikSendiri($request, $address);

        return view('alamat.form', ['alamat' => $address]);
    }

    /** Menyimpan perubahan alamat. */
    public function update(Request $request, Address $address)
    {
        $this->pastikanMilikSendiri($request, $address);

        $data = $this->periksaMasukan($request);
        $address->fill($data);

        if ($request->boolean('is_primary')) {
            $address->is_primary = true;
        }

        $address->save();

        if ($address->is_primary) {
            $this->tandaiUtama($address);
        }

        return redirect()->route('alamat.index')->with('berhasil', 'Alamat berhasil diperbarui.');
    }

    /** Menjadikan satu alamat sebagai alamat utama. */
    public function jadikanUtama(Request $request, Address $address)
    {
        $this->pastikanMilikSendiri($request, $address);

        $address->is_primary = true;
        $address->save();
        $this->tandaiUtama($address);

        return redirect()->route('alamat.index')->with('berhasil', 'Alamat utama berhasil diubah.');
    }

    /** Menghapus alamat. */
    public function destroy(Request $request, Address $address)
    {
        $this->pastikanMilikSendiri($request, $address);

        $tadinyaUtama = $address->is_primary;
        $address->delete();

        // Kalau yang dihapus alamat utama, alamat tersisa paling awal dijadikan utama
        if ($tadinyaUtama) {
            $pengganti = Address::where('user_id', $request->user()->id)->orderBy('id')->first();
            if ($pengganti) {
                $pengganti->is_primary = true;
                $pengganti->save();
            }
        }

        return redirect()->route('alamat.index')->with('berhasil', 'Alamat berhasil dihapus.');
    }

    /** Aturan pemeriksaan isian alamat, dipakai saat tambah maupun ubah. */
    private function periksaMasukan(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'recipient' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'province' => ['required', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:60'],
            'district' => ['nullable', 'string', 'max:60'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'detail' => ['required', 'string', 'max:191'],
        ], [
            'recipient.required' => 'Nama penerima wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'city.required' => 'Kota atau kabupaten wajib diisi.',
            'detail.required' => 'Alamat lengkap wajib diisi.',
        ]);
    }

    /** Alamat lain milik pengguna yang sama dibuat bukan utama lagi. */
    private function tandaiUtama(Address $alamat): void
    {
        Address::where('user_id', $alamat->user_id)
            ->where('id', '!=', $alamat->id)
            ->update(['is_primary' => false]);
    }

    /** Alamat hanya boleh diurus oleh pemiliknya. */
    private function pastikanMilikSendiri(Request $request, Address $alamat): void
    {
        if ($alamat->user_id !== $request->user()->id) {
            abort(403, 'Alamat ini bukan milik Anda.');
        }
    }
}
