@extends('layouts.app')

@section('judul', $alamat ? 'Ubah Alamat' : 'Tambah Alamat')

@section('isi')
    @php
        // Pilihan provinsi yang sering dipakai, tetap boleh diketik bebas
        $daftarProvinsi = ['DKI Jakarta', 'Banten', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta',
            'Jawa Timur', 'Bali', 'Sumatera Utara', 'Sumatera Selatan', 'Sulawesi Selatan',
            'Kalimantan Timur'];
        $judul = $alamat ? 'Ubah Alamat' : 'Tambah Alamat';
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
                                <li class="breadcrumb-item"><a href="{{ route('alamat.index') }}">Alamat Pengiriman</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ $judul }}</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">{{ $judul }}</h1>
                        <p class="text-muted mb-0">Alamat yang lengkap membantu kurir menemukan rumah Anda.</p>
                    </div>
                </div>

                <form method="post" class="cb-card" novalidate
                      action="{{ $alamat ? route('alamat.update', $alamat->id) : route('alamat.store') }}">
                    @csrf
                    @if($alamat)
                        @method('PUT')
                    @endif

                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="label" class="form-label">Label alamat</label>
                                <input type="text" id="label" name="label"
                                       class="form-control @error('label') is-invalid @enderror"
                                       value="{{ old('label', $alamat ? $alamat->label : 'Rumah') }}" maxlength="40">
                                @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Contoh: Rumah, Kantor, Kos.</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="recipient" class="form-label">Nama penerima <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" id="recipient" name="recipient"
                                       class="form-control @error('recipient') is-invalid @enderror"
                                       value="{{ old('recipient', $alamat ? $alamat->recipient : '') }}"
                                       maxlength="100" autocomplete="name" required>
                                @error('recipient')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor telepon <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="tel" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $alamat ? $alamat->phone : '') }}"
                                       maxlength="20" placeholder="08xxxxxxxxxx" autocomplete="tel" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="province" class="form-label">Provinsi <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" id="province" name="province" list="daftar-provinsi"
                                       class="form-control @error('province') is-invalid @enderror"
                                       value="{{ old('province', $alamat ? $alamat->province : '') }}"
                                       maxlength="60" required>
                                <datalist id="daftar-provinsi">
                                    @foreach($daftarProvinsi as $provinsi)
                                        <option value="{{ $provinsi }}"></option>
                                    @endforeach
                                </datalist>
                                @error('province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label">Kota atau kabupaten <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" id="city" name="city"
                                       class="form-control @error('city') is-invalid @enderror"
                                       value="{{ old('city', $alamat ? $alamat->city : '') }}" maxlength="60" required>
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="district" class="form-label">Kecamatan</label>
                                <input type="text" id="district" name="district"
                                       class="form-control @error('district') is-invalid @enderror"
                                       value="{{ old('district', $alamat ? $alamat->district : '') }}" maxlength="60">
                                @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="postal_code" class="form-label">Kode pos</label>
                                <input type="text" id="postal_code" name="postal_code"
                                       class="form-control @error('postal_code') is-invalid @enderror"
                                       value="{{ old('postal_code', $alamat ? $alamat->postal_code : '') }}"
                                       maxlength="10" inputmode="numeric" autocomplete="postal-code">
                                @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label for="detail" class="form-label">Alamat lengkap <span class="text-accent" aria-hidden="true">*</span></label>
                                <textarea id="detail" name="detail" rows="3" maxlength="191" required
                                          class="form-control @error('detail') is-invalid @enderror">{{ old('detail', $alamat ? $alamat->detail : '') }}</textarea>
                                @error('detail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Tulis nama jalan, nomor rumah, RT/RW, dan patokan bila ada.</div>
                            </div>
                        </div>
                    </div>

                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_primary" value="1"
                               @if(old('is_primary', $alamat ? $alamat->is_primary : false)) checked @endif>
                        <span class="form-check-label">Jadikan alamat utama</span>
                    </label>
                    <div class="form-text mb-3">Alamat utama dipakai lebih dulu saat checkout.</div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('alamat.index') }}" class="btn btn-outline-dark">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Alamat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
