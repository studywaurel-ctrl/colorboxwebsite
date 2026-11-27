@props(['ikon' => 'inbox', 'judul', 'teks' => null])

{{-- Kotak pemberitahuan ketika sebuah daftar tidak berisi apa pun. --}}
<div class="empty-state">
    <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>
    <h3>{{ $judul }}</h3>
    @if($teks)<p>{{ $teks }}</p>@endif
    {{ $slot }}
</div>
