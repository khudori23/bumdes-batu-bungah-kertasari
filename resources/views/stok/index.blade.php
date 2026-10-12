@extends('layouts.app')

@section('title', 'Stok')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard-v2.css') }}?v={{ @filemtime(public_path('css/dashboard-v2.css')) }}">

<div class="dx">
    <div class="dx-head">
        <div>
            <h2>Stok</h2>
            <div class="dx-mut" style="font-size:.88rem;margin-top:.2rem">Ayam hidup dan produk jadi. Hanya untuk dilihat.</div>
        </div>
        <div class="dx-seg">
            @foreach ($periodeList as $key => $label)
                <a href="{{ route('stok.index', ['periode' => $key]) }}" class="{{ $periode === $key ? 'on' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @include('dashboard._stok')
</div>
@endsection