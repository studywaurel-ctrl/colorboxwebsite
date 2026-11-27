@extends('layouts.admin')

@section('judul', __('Voucher'))

@section('isi')

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Tambah Voucher') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Jenis persen memotong sekian persen dari belanja, jenis potongan tetap memotong sejumlah rupiah. Batas potongan cuma berlaku untuk voucher persen.') }}
    </p>

    <form class="mt-2" action="{{ route('admin.voucher.store') }}" method="POST">
        @csrf

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="code">{{ __('Kode voucher') }}</label>
                <input class="form-control" type="text" id="code" name="code" maxlength="30" required
                       value="{{ old('code') }}" placeholder="HEMAT10">
            </div>
            <div class="mb-3">
                <label class="form-label" for="name">{{ __('Nama voucher') }}</label>
                <input class="form-control" type="text" id="name" name="name" maxlength="100" required
                       value="{{ old('name') }}" placeholder="{{ __('Diskon 10 persen') }}">
            </div>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="type">{{ __('Jenis potongan') }}</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="percent" @selected(old('type') === 'percent')>{{ __('Persen') }}</option>
                    <option value="amount" @selected(old('type') === 'amount')>{{ __('Potongan tetap (rupiah)') }}</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="value">{{ __('Nilai potongan') }}</label>
                <input class="form-control" type="number" id="value" name="value" min="1" max="10000000" required
                       value="{{ old('value') }}">
                <p class="form-text">{{ __('Isi 10 untuk 10 persen, atau 25000 untuk potongan Rp25.000.') }}</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="max_discount">{{ __('Batas potongan (Rp)') }}</label>
                <input class="form-control" type="number" id="max_discount" name="max_discount" min="0" max="10000000"
                       value="{{ old('max_discount') }}" placeholder="{{ __('Boleh dikosongin') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="min_purchase">{{ __('Minimal belanja (Rp)') }}</label>
                <input class="form-control" type="number" id="min_purchase" name="min_purchase" min="0" max="100000000"
                       value="{{ old('min_purchase', 0) }}">
            </div>
        </div>

        <div class="row g-3">
            <div class="mb-3">
                <label class="form-label" for="quota">{{ __('Kuota pemakaian') }}</label>
                <input class="form-control" type="number" id="quota" name="quota" min="1" max="100000" required
                       value="{{ old('quota', 100) }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="expired_at">{{ __('Berlaku sampai') }}</label>
                <input class="form-control" type="date" id="expired_at" name="expired_at" value="{{ old('expired_at') }}">
                <p class="form-text">{{ __('Boleh dikosongin kalau voucher nggak ada batas waktunya.') }}</p>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-check-label">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                {{ __('Voucher aktif') }}
            </label>
        </div>

        <button class="btn btn-primary btn-sm" type="submit">{{ __('Simpan Voucher') }}</button>
    </form>
</div>

<div class="ad-kartu">
    <h2 style="font-size: 1.05rem">{{ __('Daftar Voucher') }}</h2>
    <p style="color: var(--cb-ink-2); font-size: .875rem">
        {{ __('Ada :p1 voucher. Voucher yang pernah dipakai nggak bisa dihapus, cukup dimatikan. Klik "Edit" untuk mengubah satu voucher.', ['p1' => $daftar->total()]) }}
    </p>

    <div class="ad-gulir mt-2">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Kode') }}</th>
                <th>{{ __('Nama') }}</th>
                <th>{{ __('Potongan') }}</th>
                <th>{{ __('Minimal Belanja') }}</th>
                <th>{{ __('Kuota') }}</th>
                <th>{{ __('Terpakai') }}</th>
                <th>{{ __('Berlaku Sampai') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $voucher)
                <tr>
                    <td>{{ $voucher->code }}</td>
                    <td>{{ $voucher->name }}</td>
                    <td>
                        @if($voucher->type === 'percent')
                            {{ $voucher->value }}%
                            @if($voucher->max_discount)
                                <br><span style="color: var(--cb-ink-3); font-size: .78rem">{{ __('maksimal :p1', ['p1' => rupiah($voucher->max_discount)]) }}</span>
                            @endif
                        @else
                            {{ rupiah($voucher->value) }}
                        @endif
                    </td>
                    <td>{{ rupiah($voucher->min_purchase) }}</td>
                    <td>{{ $voucher->quota }}</td>
                    <td>{{ $voucher->used }}</td>
                    <td>{{ $voucher->expired_at ? tanggal_id($voucher->expired_at) : __('Nggak ada batas') }}</td>
                    <td>
                        @if($voucher->is_active)
                            <span class="status status-success">{{ __('Aktif') }}</span>
                        @else
                            <span class="status status-warning">{{ __('Nonaktif') }}</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('admin.voucher.destroy', $voucher) }}" method="POST"
                              data-konfirmasi="Hapus voucher {{ $voucher->code }}?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link btn-sm p-0" type="submit">{{ __('Hapus') }}</button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td colspan="9">
                        <details>
                            <summary class="form-label" style="cursor: pointer">{{ __('Edit voucher :p1', ['p1' => $voucher->code]) }}</summary>

                            <form class="mt-2" action="{{ route('admin.voucher.update', $voucher) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="mb-3">
                                        <label class="form-label" for="code-{{ $voucher->id }}">{{ __('Kode voucher') }}</label>
                                        <input class="form-control" type="text" id="code-{{ $voucher->id }}" name="code"
                                               maxlength="30" required value="{{ $voucher->code }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="name-{{ $voucher->id }}">{{ __('Nama voucher') }}</label>
                                        <input class="form-control" type="text" id="name-{{ $voucher->id }}" name="name"
                                               maxlength="100" required value="{{ $voucher->name }}">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="mb-3">
                                        <label class="form-label" for="type-{{ $voucher->id }}">{{ __('Jenis potongan') }}</label>
                                        <select class="form-control" id="type-{{ $voucher->id }}" name="type" required>
                                            <option value="percent" @selected($voucher->type === 'percent')>{{ __('Persen') }}</option>
                                            <option value="amount" @selected($voucher->type === 'amount')>{{ __('Potongan tetap (rupiah)') }}</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="value-{{ $voucher->id }}">{{ __('Nilai potongan') }}</label>
                                        <input class="form-control" type="number" id="value-{{ $voucher->id }}" name="value"
                                               min="1" max="10000000" required value="{{ $voucher->value }}">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="mb-3">
                                        <label class="form-label" for="max_discount-{{ $voucher->id }}">{{ __('Batas potongan (Rp)') }}</label>
                                        <input class="form-control" type="number" id="max_discount-{{ $voucher->id }}" name="max_discount"
                                               min="0" max="10000000" value="{{ $voucher->max_discount }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="min_purchase-{{ $voucher->id }}">{{ __('Minimal belanja (Rp)') }}</label>
                                        <input class="form-control" type="number" id="min_purchase-{{ $voucher->id }}" name="min_purchase"
                                               min="0" max="100000000" value="{{ $voucher->min_purchase }}">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="mb-3">
                                        <label class="form-label" for="quota-{{ $voucher->id }}">{{ __('Kuota pemakaian') }}</label>
                                        <input class="form-control" type="number" id="quota-{{ $voucher->id }}" name="quota"
                                               min="1" max="100000" required value="{{ $voucher->quota }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="expired_at-{{ $voucher->id }}">{{ __('Berlaku sampai') }}</label>
                                        <input class="form-control" type="date" id="expired_at-{{ $voucher->id }}" name="expired_at"
                                               value="{{ $voucher->expired_at ? $voucher->expired_at->format('Y-m-d') : '' }}">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="is_active" value="1" @checked($voucher->is_active)>
                                        {{ __('Voucher aktif') }}
                                    </label>
                                </div>

                                <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Simpan Perubahan') }}</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">{{ __('Belum ada voucher.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
