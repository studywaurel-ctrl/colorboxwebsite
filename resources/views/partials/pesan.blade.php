{{-- Pesan flash dari controller: berhasil, ingat, gagal, dan daftar galat validasi. --}}
@if(session('berhasil') || session('ingat') || session('gagal') || $errors->any())
    <div class="container-xl pt-3">
        @if(session('berhasil'))
            <div class="alert alert-success d-flex gap-2" role="status">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <div>{{ session('berhasil') }}</div>
            </div>
        @endif

        @if(session('ingat'))
            <div class="alert alert-warning d-flex gap-2" role="status">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <div>{{ session('ingat') }}</div>
            </div>
        @endif

        @if(session('gagal'))
            <div class="alert alert-danger d-flex gap-2" role="alert">
                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                <div>{{ session('gagal') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong class="d-block mb-1">Periksa kembali isian berikut:</strong>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
