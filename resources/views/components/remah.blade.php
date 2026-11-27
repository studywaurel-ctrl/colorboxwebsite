@props(['tautan' => []])

{{-- Remah roti. Isinya array: label => alamat. Label terakhir ditulis tanpa tautan. --}}
<nav aria-label="Anda berada di">
    <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('beranda') }}">Beranda</a></li>
        @foreach($tautan as $label => $alamat)
            @if($loop->last || ! $alamat)
                <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $alamat }}">{{ $label }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>
