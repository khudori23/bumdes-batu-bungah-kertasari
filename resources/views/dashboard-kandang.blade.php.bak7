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

<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">

<div class="kd-head">
    <div>
        <h2>{{ $salam }}, {{ auth()->user()->name }}</h2>
        <div class="muted" style="margin-top:.2rem">{{ $rentang }}</div>
    </div>
    <div class="kd-seg">
        @foreach ($periodeList as $key => $label)
            <a href="{{ route('dashboard', ['periode' => $key]) }}" class="{{ $periode === $key ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

@include('dashboard._stok')
@endsection