@extends('layouts.app')
@section('title', __('Virtual Try On'))
@php
    $bodyLabels = ['slim' => __('Ramping'), 'regular' => __('Sedang'), 'curvy' => __('Berlekuk'), 'athletic' => __('Atletis'), 'plus' => __('Berisi')];
    $productsJson = $products->map(fn ($p) => ['id' => $p->id, 'slug' => $p->slug, 'name' => $p->name, 'price' => rupiah($p->price), 'image' => $p->imageFor(),
        'colors' => $p->colors()->values(), 'images' => $p->images->map(fn ($i) => ['color' => $i->color_name, 'src' => asset($i->path)])->values()])->values();
@endphp

@section('content')
<div class="container-xl section-sm">
    <x-breadcrumb :items="[__('Virtual Try On') => null]" />
    <h1 class="page-title">{{ __('Virtual Try On') }}</h1>
    <p class="text-muted" style="max-width: 720px">{{ __('Lihat perkiraan tampilan produk pada model yang disesuaikan dengan bentuk tubuh Anda, atau pada foto Anda sendiri. Hasil adalah ilustrasi untuk membantu memilih warna dan potongan, bukan foto asli.') }}</p>

    @if ($products->isEmpty())
        <x-empty icon="person-bounding-box" :title="__('Belum ada produk yang mendukung Virtual Try On')" />
    @else
    <div class="row g-4" id="vto" data-products='@json($productsJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
        <div class="col-lg-5">
            <div class="cb-card">
                <h2 class="h6">1. {{ __('Pilih produk') }}</h2>
                <label for="vtoProduct" class="visually-hidden">{{ __('Produk') }}</label>
                <select id="vtoProduct" class="form-select mb-3">
                    @foreach ($products as $p)<option value="{{ $p->id }}" @selected($selected?->id === $p->id)>{{ $p->name }}</option>@endforeach
                </select>
                <div class="vto-product mb-3">
                    <img id="vtoProductImg" src="{{ $selected?->imageFor($color) }}" alt="" class="mini-thumb" style="width: 72px">
                    <div><strong id="vtoProductName">{{ $selected?->name }}</strong><div class="small text-muted" id="vtoProductPrice">{{ $selected ? rupiah($selected->price) : '' }}</div></div>
                </div>
                <div class="form-label">{{ __('Warna') }}: <span class="fw-normal" id="vtoColorName"></span></div>
                <div class="d-flex flex-wrap gap-2" id="vtoColors"></div>
            </div>
            @if ($recent->isNotEmpty())
                <div class="cb-card">
                    <h2 class="h6">{{ __('Hasil terakhir') }}</h2>
                    <div class="d-flex gap-2">@foreach ($recent as $r)<a href="{{ route('vto.show', $r) }}" title="{{ $r->product->name }}"><img src="{{ media_url($r->result_path) }}" alt="{{ $r->product->name }}" class="mini-thumb" style="width: 64px"></a>@endforeach</div>
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="cb-card">
                <h2 class="h6">2. {{ __('Pilih cara mencoba') }}</h2>
                <ul class="nav nav-pills gap-2 mb-3" role="tablist">
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-dark active" data-bs-toggle="pill" data-bs-target="#tab-model" type="button" role="tab" aria-selected="true"><i class="bi bi-person me-1"></i>{{ __('Model') }}</button></li>
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-dark" data-bs-toggle="pill" data-bs-target="#tab-upload" type="button" role="tab" aria-selected="false"><i class="bi bi-upload me-1"></i>{{ __('Unggah Foto') }}</button></li>
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-dark" data-bs-toggle="pill" data-bs-target="#tab-camera" type="button" role="tab" aria-selected="false" id="cameraTabBtn"><i class="bi bi-camera me-1"></i>{{ __('Kamera') }}</button></li>
                </ul>

                <div class="tab-content">
                    {{-- Model --}}
                    <div class="tab-pane fade show active" id="tab-model" role="tabpanel">
                        <form method="post" action="{{ route('vto.model') }}" data-loading class="vto-form">
                            @csrf
                            <input type="hidden" name="product_id"><input type="hidden" name="color_name">
                            <div class="mb-3">
                                <div class="form-label">{{ __('Model') }}</div>
                                <div class="d-flex gap-2">
                                    @foreach (['female' => __('Wanita'), 'male' => __('Pria')] as $g => $label)
                                        <label class="size-option"><input type="radio" name="model_gender" value="{{ $g }}" @checked(old('model_gender', 'female') === $g)><span>{{ $label }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-label">{{ __('Bentuk tubuh') }}</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($bodyTypes as $bt)<label class="size-option"><input type="radio" name="body_type" value="{{ $bt }}" @checked(old('body_type', 'regular') === $bt)><span>{{ $bodyLabels[$bt] }}</span></label>@endforeach
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-label">{{ __('Warna kulit') }}</div>
                                <div class="d-flex gap-2">
                                    @foreach ($skinTones as $i => $tone)<label class="color-option skin-option"><input type="radio" name="skin_tone" value="{{ $tone }}" @checked(old('skin_tone', $skinTones[1]) === $tone)><span style="background: {{ $tone }}"></span><span class="visually-hidden">{{ __('Warna kulit :n', ['n' => $i + 1]) }}</span></label>@endforeach
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><x-field name="height_cm" type="number" :label="__('Tinggi badan (cm)')" :value="$height" :min="$cfg['height_min']" :max="$cfg['height_max']" required /></div>
                                <div class="col-6"><x-field name="weight_kg" type="number" :label="__('Berat badan (kg)')" :value="$weight" :min="$cfg['weight_min']" :max="$cfg['weight_max']" required /></div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" data-loading-text="{{ __('Membuat model...') }}"><i class="bi bi-person-plus me-1"></i>{{ __('Generate Model') }}</button>
                        </form>
                    </div>

                    {{-- Upload --}}
                    <div class="tab-pane fade" id="tab-upload" role="tabpanel">
                        <form method="post" action="{{ route('vto.photo') }}" enctype="multipart/form-data" data-loading class="vto-form" id="uploadForm">
                            @csrf
                            <input type="hidden" name="product_id"><input type="hidden" name="color_name">
                            <input type="hidden" name="source" value="upload"><input type="hidden" name="landmarks">
                            <label class="upload-drop mb-2" for="photoUpload">
                                <i class="bi bi-image fs-2 d-block text-soft"></i>
                                <strong>{{ __('Pilih foto seluruh badan') }}</strong>
                                <span class="small text-muted d-block">{{ __('JPG, JPEG, atau PNG, maksimal 5 MB. Berdiri tegak menghadap kamera dengan latar polos.') }}</span>
                            </label>
                            <input type="file" id="photoUpload" name="photo" accept="image/jpeg,image/png" class="visually-hidden" required>
                            @error('photo')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                            <img id="uploadPreview" class="upload-preview mb-2" alt="{{ __('Foto yang dipilih') }}" hidden>
                            <p class="small text-muted" id="uploadStatus" aria-live="polite"></p>
                            <button type="submit" class="btn btn-primary w-100" id="uploadSubmit" disabled data-loading-text="{{ __('Mengunggah...') }}">{{ __('Pakai Foto Ini') }}</button>
                        </form>
                    </div>

                    {{-- Camera --}}
                    <div class="tab-pane fade" id="tab-camera" role="tabpanel">
                        <form method="post" action="{{ route('vto.photo') }}" enctype="multipart/form-data" class="vto-form" id="cameraForm" data-loading>
                            @csrf
                            <input type="hidden" name="product_id"><input type="hidden" name="color_name">
                            <input type="hidden" name="source" value="camera"><input type="hidden" name="landmarks">
                            <input type="file" name="photo" class="visually-hidden" id="cameraFile" tabindex="-1" aria-hidden="true">
                            <div class="vto-stage mb-2">
                                <video id="cameraVideo" playsinline muted></video>
                                <div class="vto-frame" id="cameraFrame" aria-hidden="true"></div>
                                <div class="vto-hint" id="cameraHint" role="status">{{ __('Nyalakan kamera untuk memulai') }}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-dark flex-fill" id="cameraStart"><i class="bi bi-camera-video me-1"></i>{{ __('Nyalakan Kamera') }}</button>
                                <button type="button" class="btn btn-primary flex-fill" id="cameraCapture" disabled><i class="bi bi-camera me-1"></i>{{ __('Ambil Foto') }}</button>
                            </div>
                            <p class="small text-muted mt-2 mb-0">{{ __('Letakkan ponsel sejajar pinggang, mundur sampai kepala dan kaki masuk bingkai. Bingkai berubah hijau saat posisi sudah tepat.') }}</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
window.VTO = {
    initial: { productId: @json($selected?->id), color: @json($color) },
    maxBytes: {{ (int) $cfg['max_upload_kb'] * 1024 }},
    t: ([
        'bad_type' => __('Format foto harus JPG, JPEG, atau PNG.'),
        'too_big' => __('Ukuran foto maksimal 5 MB.'),
        'too_small' => __('Resolusi foto terlalu kecil. Minimal 300 × 400 piksel.'),
        'ready' => __('Foto siap dipakai.'),
        'detecting' => __('Memeriksa posisi tubuh...'),
        'no_body' => __('Tubuh tidak terdeteksi penuh. Foto tetap bisa dipakai, hasil mungkin kurang pas.'),
        'body_ok' => __('Posisi tubuh terdeteksi.'),
        'stand' => __('Berdiri di dalam bingkai'),
        'back' => __('Mundur sedikit'),
        'closer' => __('Maju sedikit'),
        'center' => __('Geser ke tengah bingkai'),
        'good' => __('Posisi sudah tepat'),
        'loading_model' => __('Menyiapkan pendeteksi posisi...'),
        'no_detector' => __('Pendeteksi posisi tidak tersedia. Pastikan seluruh tubuh di dalam bingkai, lalu ambil foto.'),
        'camera_denied' => __('Akses kamera ditolak. Izinkan kamera di pengaturan peramban atau gunakan Unggah Foto.'),
        'camera_unsupported' => __('Kamera tidak didukung di peramban ini. Gunakan Unggah Foto.'),
    ]),
};
</script>
<script src="{{ asset('js/vto.js') }}?v=1" defer></script>
@endpush
