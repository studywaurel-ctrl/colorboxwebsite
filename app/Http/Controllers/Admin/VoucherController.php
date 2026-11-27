<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Voucher potongan harga. Daftar dan formulirnya berada di satu halaman.
 */
class VoucherController extends Controller
{
    public function index()
    {
        return view('admin.voucher.index', [
            'daftar' => Voucher::orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan(), $this->pesanAturan());

        Voucher::create($this->isiVoucher($request, $data) + ['used' => 0]);

        return redirect()->route('admin.voucher.index')
            ->with('berhasil', 'Voucher '.strtoupper($data['code']).' berhasil dibuat.');
    }

    public function update(Request $request, Voucher $voucher)
    {
        $data = $request->validate($this->aturan($voucher->id), $this->pesanAturan());

        $voucher->update($this->isiVoucher($request, $data));

        return redirect()->route('admin.voucher.index')
            ->with('berhasil', 'Voucher '.$voucher->code.' sudah diperbarui.');
    }

    public function destroy(Voucher $voucher)
    {
        if ((int) $voucher->used > 0) {
            return back()->with('gagal',
                'Voucher '.$voucher->code.' sudah dipakai '.$voucher->used.' kali sehingga tidak bisa dihapus. Nonaktifkan saja.');
        }

        $kode = $voucher->code;
        $voucher->delete();

        return redirect()->route('admin.voucher.index')
            ->with('berhasil', 'Voucher '.$kode.' sudah dihapus.');
    }

    /* ------------------------------------------------------------------ */

    private function aturan(?int $abaikanId = null): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('vouchers', 'code')->ignore($abaikanId)],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:percent,amount'],
            'value' => ['required', 'integer', 'min:1', 'max:10000000'],
            'max_discount' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'min_purchase' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'quota' => ['required', 'integer', 'min:1', 'max:100000'],
            'expired_at' => ['nullable', 'date'],
        ];
    }

    private function pesanAturan(): array
    {
        return [
            'code.required' => 'Kode voucher wajib diisi.',
            'code.unique' => 'Kode voucher ini sudah dipakai.',
            'name.required' => 'Nama voucher wajib diisi.',
            'type.in' => 'Jenis voucher hanya boleh persen atau potongan tetap.',
            'value.required' => 'Nilai voucher wajib diisi.',
            'quota.required' => 'Kuota pemakaian voucher wajib diisi.',
        ];
    }

    private function isiVoucher(Request $request, array $data): array
    {
        return [
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'type' => $data['type'],
            'value' => $data['value'],
            'max_discount' => $data['max_discount'] ?? null,
            'min_purchase' => $data['min_purchase'] ?? 0,
            'quota' => $data['quota'],
            'expired_at' => $data['expired_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
