<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Daftar toko Colorbox: alamat, jam buka, dan layanan yang tersedia
 * (Tap Try and Collect serta donasi pakaian).
 */
class TokoAdminController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari', ''));

        $kueri = Store::orderBy('city')->orderBy('name');

        if ($cari !== '') {
            $kueri->where(function ($bagian) use ($cari) {
                $bagian->where('name', 'like', '%'.$cari.'%')
                    ->orWhere('city', 'like', '%'.$cari.'%')
                    ->orWhere('code', 'like', '%'.$cari.'%');
            });
        }

        return view('admin.toko.index', [
            'daftar' => $kueri->paginate(15)->withQueryString(),
            'cari' => $cari,
        ]);
    }

    public function create()
    {
        return view('admin.toko.form', ['toko' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan(), $this->pesanAturan());

        Store::create($this->isiToko($request, $data));

        return redirect()->route('admin.toko.index')
            ->with('berhasil', 'Toko '.$data['name'].' berhasil ditambahkan.');
    }

    public function edit(Store $store)
    {
        return view('admin.toko.form', ['toko' => $store]);
    }

    public function update(Request $request, Store $store)
    {
        $data = $request->validate($this->aturan($store->id), $this->pesanAturan());

        $store->update($this->isiToko($request, $data));

        return redirect()->route('admin.toko.index')
            ->with('berhasil', 'Data toko '.$store->name.' sudah diperbarui.');
    }

    public function destroy(Store $store)
    {
        $nama = $store->name;
        $store->delete();

        return redirect()->route('admin.toko.index')
            ->with('berhasil', 'Toko '.$nama.' sudah dihapus.');
    }

    /* ------------------------------------------------------------------ */

    private function aturan(?int $abaikanId = null): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('stores', 'code')->ignore($abaikanId)],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:191'],
            'floor' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i', 'after:open_time'],
        ];
    }

    private function pesanAturan(): array
    {
        return [
            'code.required' => 'Kode toko wajib diisi.',
            'code.unique' => 'Kode toko ini sudah dipakai toko lain.',
            'name.required' => 'Nama toko wajib diisi.',
            'city.required' => 'Kota wajib diisi.',
            'address.required' => 'Alamat toko wajib diisi.',
            'open_time.date_format' => 'Jam buka ditulis dengan bentuk 10:00.',
            'close_time.date_format' => 'Jam tutup ditulis dengan bentuk 22:00.',
            'close_time.after' => 'Jam tutup harus lebih malam daripada jam buka.',
        ];
    }

    private function isiToko(Request $request, array $data): array
    {
        return [
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'city' => $data['city'],
            'address' => $data['address'],
            'floor' => $data['floor'] ?? null,
            'phone' => $data['phone'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'open_time' => $data['open_time'],
            'close_time' => $data['close_time'],
            'has_ttc' => $request->boolean('has_ttc'),
            'has_donation' => $request->boolean('has_donation'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
