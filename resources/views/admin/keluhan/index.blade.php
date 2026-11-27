@extends('layouts.admin')

@section('judul', __('Keluhan'))

@section('isi')

<div class="ad-kartu">
    <div>
        <h2 style="font-size: 1.05rem" class="mb-1">{{ __('Keluhan Pelanggan') }}</h2>
        <p class="mb-0" style="color: var(--cb-ink-2); font-size: .875rem">
            {{ __('Ada :p1 keluhan di filter ini.', ['p1' => $daftar->total()]) }}
        </p>
    </div>

    <form class="mt-3" action="{{ route('admin.keluhan.index') }}" method="GET">
        <div class="d-flex gap-2 flex-wrap align-items-end">
            <div class="mb-3 mb-0">
                <span class="form-label d-block">{{ __('Status') }}</span>
                <div class="d-flex flex-wrap gap-1" role="group" aria-label="{{ __('Status') }}">
                    <input class="btn-check" type="radio" name="status" id="status-semua" value="" autocomplete="off" @checked(blank($status))>
                    <label class="btn btn-outline-dark btn-sm" for="status-semua">{{ __('Semua') }}</label>
                    @foreach($daftarStatus as $pilihan)
                        <input class="btn-check" type="radio" name="status" id="status-{{ $pilihan }}" value="{{ $pilihan }}" autocomplete="off" @checked($status === $pilihan)>
                        <label class="btn btn-outline-dark btn-sm" for="status-{{ $pilihan }}">{{ __(ucfirst($pilihan)) }}</label>
                    @endforeach
                </div>
            </div>
            <button class="btn btn-outline-dark btn-sm" type="submit">{{ __('Filter') }}</button>
            <a class="btn btn-link btn-sm p-0" href="{{ route('admin.keluhan.index') }}">{{ __('Reset') }}</a>
        </div>
    </form>
</div>

<div class="ad-kartu">
    <div class="ad-gulir">
        <table class="ad-tabel">
            <thead>
            <tr>
                <th>{{ __('Soal apa') }}</th>
                <th>{{ __('Pelanggan') }}</th>
                <th>{{ __('Pesanan') }}</th>
                <th>{{ __('Masuk') }}</th>
                <th>{{ __('Dijawab') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Aksi') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($daftar as $keluhan)
                <tr>
                    <td>{{ $keluhan->subject }}</td>
                    <td>
                        {{ $keluhan->pengguna?->name ?? '-' }}<br>
                        <span style="color: var(--cb-ink-3); font-size: .78rem">{{ $keluhan->pengguna?->email ?? '-' }}</span>
                    </td>
                    <td>
                        @if($keluhan->pesanan)
                            <a href="{{ route('admin.pesanan.show', $keluhan->pesanan) }}">{{ $keluhan->pesanan->order_code }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ tanggal_id($keluhan->created_at) }}</td>
                    <td>{{ $keluhan->responded_at ? tanggal_id($keluhan->responded_at) : __('Belum') }}</td>
                    <td>
                        <span class="status {{ $keluhan->status === 'selesai' ? 'status-success' : 'status-warning' }}">
                            {{ __(ucfirst($keluhan->status)) }}
                        </span>
                    </td>
                    <td>
                        <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.keluhan.show', $keluhan) }}">{{ __('Baca dan Jawab') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('Belum ada keluhan di filter ini.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $daftar->links() }}</div>
</div>

@endsection
