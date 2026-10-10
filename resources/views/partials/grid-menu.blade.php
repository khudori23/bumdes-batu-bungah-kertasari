@php $itemGrid = collect(\App\Support\Menu::kelompok(auth()->user()))->flatten(1); @endphp
@if ($itemGrid->isNotEmpty())
    <div class="dx-title">Menu</div>
    <div class="mn-grid mn-home">
        @foreach ($itemGrid as $i)
            <a class="mn-item" href="{{ route($i['r']) }}">
                <span class="mn-ico"><x-ikon :nama="$i['i']" /></span>
                <span class="mn-label">{{ $i['t'] }}</span>
            </a>
        @endforeach
    </div>
@endif