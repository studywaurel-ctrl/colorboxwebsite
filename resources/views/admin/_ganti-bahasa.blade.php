{{-- Tombol ganti bahasa ID / EN. Pasang di topbar layout: @include('admin._ganti-bahasa') --}}
@php $lokal = app()->getLocale(); @endphp
<div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Bahasa') }}">
    <a class="btn {{ $lokal === 'id' ? 'btn-dark' : 'btn-outline-dark' }}" href="{{ route('admin.bahasa', 'id') }}"
       @if($lokal === 'id') aria-current="true" @endif>ID</a>
    <a class="btn {{ $lokal === 'en' ? 'btn-dark' : 'btn-outline-dark' }}" href="{{ route('admin.bahasa', 'en') }}"
       @if($lokal === 'en') aria-current="true" @endif>EN</a>
</div>
