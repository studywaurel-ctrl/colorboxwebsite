@extends('layouts.app')

@section('judul', $judul)
@section('deskripsi', $ringkasan)

@section('isi')
<div class="container-xl section-sm">
    <x-remah :tautan="['Bantuan' => route('bantuan.index'), $judul => null]" />

    <div class="row g-4">
        <aside class="col-lg-3">
            <nav class="account-nav" aria-label="Topik bantuan">
                <div class="nav-label">Topik</div>
                @foreach(\App\Http\Controllers\BantuanController::TOPIK as $alamat => $keterangan)
                    <a href="{{ route('bantuan.show', $alamat) }}" class="{{ $alamat === $halaman ? 'active' : '' }}"
                       @if($alamat === $halaman) aria-current="page" @endif>{{ $keterangan[0] }}</a>
                @endforeach
            </nav>
        </aside>

        <article class="col-lg-9 prose">
            <h1 class="page-title mb-2">{{ $judul }}</h1>
            <p class="lead text-muted">{{ $ringkasan }}</p>

            @foreach($isi as $bagian)
                <h2>{{ $bagian['judul'] }}</h2>

                @foreach($bagian['isi'] as $paragraf)
                    <p>{{ $paragraf }}</p>
                @endforeach

                @if(isset($bagian['daftar']))
                    <ul>
                        @foreach($bagian['daftar'] as $butir)
                            <li>{{ $butir }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(isset($bagian['tabel']))
                    <div class="cb-card p-0 mb-3">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        @foreach($bagian['tabel']['kepala'] as $kolom)
                                            <th scope="col" class="{{ $loop->first ? 'ps-3' : '' }}">{{ $kolom }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bagian['tabel']['baris'] as $baris)
                                        <tr>
                                            @foreach($baris as $sel)
                                                <td class="{{ $loop->first ? 'ps-3' : '' }}">{{ $sel }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(isset($bagian['tautan']))
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($bagian['tautan'] as $tautan)
                            <a href="{{ $tautan[2] ? route($tautan[1], $tautan[2]) : route($tautan[1]) }}"
                               class="btn btn-outline-dark btn-sm">{{ $tautan[0] }}</a>
                        @endforeach
                    </div>
                @endif
            @endforeach

            <h2>Topik bantuan lainnya</h2>
            <div class="tabs-scroll">
                @foreach(\App\Http\Controllers\BantuanController::TOPIK as $alamat => $keterangan)
                    @if($alamat !== $halaman)
                        <a href="{{ route('bantuan.show', $alamat) }}">{{ $keterangan[0] }}</a>
                    @endif
                @endforeach
            </div>
        </article>
    </div>
</div>
@endsection
