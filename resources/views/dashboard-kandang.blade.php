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

<style>
    .kd-head{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;flex-wrap:wrap}
    .kd-head h2{margin:0;font-size:1.35rem;letter-spacing:-.01em}
    .kd-seg{display:inline-flex;background:#eef2f6;border-radius:10px;padding:3px;gap:2px}
    .kd-seg a{padding:.38rem .8rem;border-radius:8px;font-size:.83rem;color:#475569;text-decoration:none;font-weight:500}
    .kd-seg a.on{background:#fff;color:#0f172a;box-shadow:0 1px 2px rgba(15,23,42,.14)}
</style>

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