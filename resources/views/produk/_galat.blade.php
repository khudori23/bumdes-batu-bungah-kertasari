@if ($errors->any())
    <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
        @foreach ($errors->all() as $e)
            <div class="err" style="margin:0">{{ $e }}</div>
        @endforeach
    </div>
@endif