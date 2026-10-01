@if ($errors->any())
    <div style="background:#ffe4e6;border:1px solid #fecdd3;color:#be123c;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
        <ul style="margin:0;padding-left:1.1rem">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif