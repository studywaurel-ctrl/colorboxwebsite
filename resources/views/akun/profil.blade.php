@extends('layouts.app')

@section('judul', 'Profil Saya')

@section('isi')
    @php
        $potongNama = preg_split('/\s+/', trim($pengguna->name));
        $inisial = mb_strtoupper(mb_substr($potongNama[0] ?? 'C', 0, 1));
        if (count($potongNama) > 1) {
            $inisial .= mb_strtoupper(mb_substr(end($potongNama), 0, 1));
        }
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
                                <li class="breadcrumb-item active" aria-current="page">Profil</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Profil Saya</h1>
                        <p class="text-muted mb-0">Perbarui data diri, foto, dan kata sandi akun Anda.</p>
                    </div>
                </div>

                {{-- Data diri --}}
                <form method="post" action="{{ route('profil.update') }}" enctype="multipart/form-data" class="cb-card">
                    @csrf
                    @method('PUT')

                    <h2 class="h6 mb-3">Data Diri</h2>

                    <div class="d-flex gap-3 align-items-center mb-3">
                        <span class="avatar lg">
                            @if($pengguna->photo)
                                <img src="{{ asset($pengguna->photo) }}" alt="Foto profil {{ $pengguna->name }}">
                            @else
                                {{ $inisial }}
                            @endif
                        </span>
                        <div class="flex-fill">
                            <label for="photo" class="form-label">Foto profil</label>
                            <input type="file" id="photo" name="photo" accept="image/*"
                                   class="form-control @error('photo') is-invalid @enderror">
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">JPG atau PNG, ukuran paling besar 2 MB. Kosongkan bila tidak ingin mengganti.</div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama lengkap <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $pengguna->name) }}" maxlength="100" autocomplete="name" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" class="form-control" value="{{ $pengguna->email }}" disabled>
                                <div class="form-text">Email tidak dapat diubah sendiri. Hubungi tim Colorbox bila perlu diganti.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor telepon</label>
                                <input type="tel" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $pengguna->phone) }}" maxlength="20"
                                       placeholder="08xxxxxxxxxx" autocomplete="tel">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="birth_date" class="form-label">Tanggal lahir</label>
                                <input type="date" id="birth_date" name="birth_date"
                                       class="form-control @error('birth_date') is-invalid @enderror"
                                       value="{{ old('birth_date', $pengguna->birth_date ? $pengguna->birth_date->format('Y-m-d') : '') }}">
                                @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gender" class="form-label">Jenis kelamin</label>
                                <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                                    <option value="">Tidak ingin menyebutkan</option>
                                    <option value="perempuan" @if(old('gender', $pengguna->gender) === 'perempuan') selected @endif>Perempuan</option>
                                    <option value="laki-laki" @if(old('gender', $pengguna->gender) === 'laki-laki') selected @endif>Laki-laki</option>
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Level member</label>
                                <div><span class="status status-accent">{{ $pengguna->member_level }}</span></div>
                                <div class="form-text">Level naik mengikuti jumlah belanja dan donasi Anda.</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-2">Simpan Profil</button>
                </form>

                {{-- Ganti kata sandi --}}
                <form method="post" action="{{ route('sandi.update') }}" class="cb-card">
                    @csrf
                    @method('PUT')

                    <h2 class="h6 mb-3">Ganti Kata Sandi</h2>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Kata sandi saat ini <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="password" id="current_password" name="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       autocomplete="current-password" required>
                                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="password" class="form-label">Kata sandi baru <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="password" id="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       autocomplete="new-password" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Ulangi kata sandi baru <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="form-control" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-text">Paling sedikit 8 karakter. Sebaiknya berisi huruf dan angka.</div>
                    <button type="submit" class="btn btn-outline-dark mt-3">Ganti Kata Sandi</button>
                </form>
            </div>
        </div>
    </div>
@endsection
