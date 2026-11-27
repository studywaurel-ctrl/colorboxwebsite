@extends('layouts.app')

@section('judul', 'Keluhan')

@section('isi')
    @php
        // Warna lencana status keluhan
        $nadaKeluhan = [
            'baru' => 'info',
            'diproses' => 'warning',
            'selesai' => 'success',
        ];
    @endphp

    <div class="container-xl section-sm">
        <div class="row g-4">
            <aside class="col-lg-3">
                @include('akun.menu')
            </aside>

            <div class="col-lg-9">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <div>
                        <nav aria-label="Jejak halaman">
                            <ol class="breadcrumb mb-1">
                                <li class="breadcrumb-item"><a href="{{ route('beranda') }}">Beranda</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('akun.index') }}">Akun Saya</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Keluhan</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Keluhan</h1>
                        <p class="text-muted mb-0">Kirim keluhan dan lihat jawaban dari tim Colorbox.</p>
                    </div>
                </div>

                {{-- Formulir keluhan baru --}}
                <form method="post" action="{{ route('keluhan.store') }}" class="cb-card" novalidate>
                    @csrf

                    <h2 class="h6 mb-3">Kirim Keluhan Baru</h2>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Judul keluhan <span class="text-accent" aria-hidden="true">*</span></label>
                        <input type="text" id="subject" name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject') }}" maxlength="120" required
                               placeholder="Contoh: Barang yang diterima berbeda warna">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="order_id" class="form-label">Pesanan terkait</label>
                        <select id="order_id" name="order_id" class="form-select @error('order_id') is-invalid @enderror">
                            <option value="">Tidak terkait pesanan tertentu</option>
                            @foreach($daftarPesanan as $pesanan)
                                <option value="{{ $pesanan->id }}" @if((string) old('order_id') === (string) $pesanan->id) selected @endif>
                                    {{ $pesanan->order_code }} · {{ tanggal_id($pesanan->created_at) }} · {{ rupiah($pesanan->total) }}
                                </option>
                            @endforeach
                        </select>
                        @error('order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Boleh dikosongkan. Memilih pesanan membantu tim kami memeriksa lebih cepat.</div>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Isi keluhan <span class="text-accent" aria-hidden="true">*</span></label>
                        <textarea id="message" name="message" rows="5" maxlength="2000" required
                                  class="form-control @error('message') is-invalid @enderror"
                                  placeholder="Tuliskan kejadiannya selengkap mungkin.">{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Kirim Keluhan</button>
                </form>

                <div class="section-head mt-4">
                    <h2 class="h5 mb-0">Keluhan Anda</h2>
                </div>

                @forelse($daftarKeluhan as $keluhan)
                    <div class="order-card">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="status status-{{ $nadaKeluhan[$keluhan->status] ?? 'muted' }}">
                                        {{ ucfirst($keluhan->status) }}
                                    </span>
                                    @if($keluhan->pesanan)
                                        <span class="code-pill ms-1">{{ $keluhan->pesanan->order_code }}</span>
                                    @endif
                                </div>
                                <strong>{{ $keluhan->subject }}</strong>
                                <div class="small text-muted">{{ $keluhan->message }}</div>
                                <div class="small text-soft">Dikirim {{ tanggal_id($keluhan->created_at, true) }}</div>
                            </div>
                        </div>

                        @if($keluhan->response)
                            <div class="alert alert-success mt-3 mb-0" role="status">
                                <strong class="d-block mb-1">Jawaban tim Colorbox</strong>
                                <div class="small">{{ $keluhan->response }}</div>
                                @if($keluhan->responded_at)
                                    <div class="small text-soft mt-1">Dijawab {{ tanggal_id($keluhan->responded_at, true) }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-chat-dots" aria-hidden="true"></i>
                        <h3>Belum ada keluhan</h3>
                        <p>Keluhan yang Anda kirim beserta jawabannya akan tercatat di halaman ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
