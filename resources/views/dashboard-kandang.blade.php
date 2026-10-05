@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $jam = (int) now()->format('H');
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));
    $rentang = $from->equalTo($to)
        ? $from->locale('id')->translatedFormat('l, d F Y')
        : $from->locale('id')->translatedFormat('d M') . ' - ' . $to->locale('id')->translatedFormat('d M Y');
@endphp

<link rel="stylesheet" href="{{ asset('css/dashboard-v2.css') }}?v={{ @filemtime(public_path('css/dashboard-v2.css')) }}">
<link rel="stylesheet" href="{{ asset('css/dashboard-stok.css') }}?v={{ @filemtime(public_path('css/dashboard-stok.css')) }}">

<div class="dx">
    <div class="sk-hero">
        <div>
            <small>{{ $salam }}</small>
            <h2>{{ auth()->user()->name }}</h2>
            <div class="d">{{ $rentang }}</div>
        </div>
        <div class="dx-seg">
            @foreach ($periodeList as $key => $label)
                <a href="{{ route('dashboard', ['periode' => $key]) }}" class="{{ $periode === $key ? 'on' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @include('dashboard._stok')
</div>
@endsection