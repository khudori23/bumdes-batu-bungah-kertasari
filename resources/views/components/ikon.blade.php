@props(['nama'])
@php
    $dir = resource_path('svg/ikon/');
    $nama = preg_replace('/[^a-z0-9-]/', '', (string) $nama);
    $file = is_file($dir . $nama . '.svg') ? $dir . $nama . '.svg' : $dir . 'squares-four.svg';
    $svg = is_file($file) ? file_get_contents($file) : '';
    $svg = preg_replace('/<svg\b/', '<svg aria-hidden="true" focusable="false"', $svg, 1);
@endphp
{!! $svg !!}