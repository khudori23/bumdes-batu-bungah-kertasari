@props(['nama'])
@php
    $berkasIkon = public_path('img/ikon3d/' . $nama . '.png');
    $versiIkon = @filemtime($berkasIkon) ?: 1;
@endphp
<img {{ $attributes->merge([
    'class' => 'ik',
    'src' => asset('img/ikon3d/' . $nama . '.png') . '?v=' . $versiIkon,
    'alt' => '',
    'width' => 64,
    'height' => 64,
    'decoding' => 'async',
    'draggable' => 'false',
]) }}>