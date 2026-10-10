@extends('layouts.app')

@section('title', 'Semua Menu')

@section('content')
@php
    $u = auth()->user();
    $kelompok = \App\Support\Menu::kelompok($u);
@endphp

<div class="mn">
    @foreach ($kelompok as $judul => $items)
        <section class="mn-group">
            <h3 class="mn-title">{{ $judul }}</h3>
            <div class="mn-grid">
                @foreach ($items as $i)
                    <a class="mn-item" href="{{ route($i['r']) }}">
                        <span class="mn-ico"><x-ikon :nama="$i['i']" /></span>
                        <span class="mn-label">{{ $i['t'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="mn-group">
        <h3 class="mn-title">Akun</h3>
        <div class="mn-grid">
            <a class="mn-item" href="{{ route('akun.password') }}">
                <span class="mn-ico"><x-ikon nama="key" /></span>
                <span class="mn-label">Ganti Password</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mn-form">
                @csrf
                <button type="submit" class="mn-item">
                    <span class="mn-ico"><x-ikon nama="sign-out" /></span>
                    <span class="mn-label">Keluar</span>
                </button>
            </form>
        </div>
    </section>
</div>
@endsection