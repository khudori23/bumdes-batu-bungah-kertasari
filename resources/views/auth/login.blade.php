<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - BUMDes Batu Bungah Kertasari</title>
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:#0f172a; font-family:system-ui,-apple-system,"Segoe UI",sans-serif; }
        .box { width:100%; max-width:380px; background:#fff; border-radius:12px; padding:2rem; }
        h1 { margin:0 0 .25rem; font-size:1.3rem; color:#0f172a; }
        p { margin:0 0 1rem; color:#64748b; font-size:.9rem; }
        label { display:block; font-size:.85rem; margin:.9rem 0 .3rem; }
        input[type=text], input[type=password] { width:100%; padding:.6rem .7rem; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; box-sizing:border-box; }
        .err { color:#e11d48; font-size:.85rem; margin-top:.25rem; }
        .row { margin-top:.9rem; font-size:.85rem; }
        button { width:100%; margin-top:1.2rem; background:#0d9488; color:#fff; border:0; padding:.7rem; border-radius:8px; font-size:1rem; cursor:pointer; }
    </style>
</head>
<body>
    <form class="box" method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <h1>BUMDes Batu Bungah Kertasari</h1>
        <p>Masuk untuk melanjutkan</p>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="{{ old('username') }}" autofocus autocomplete="username">
        @error('username') <div class="err">{{ $message }}</div> @enderror

        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password">
        @error('password') <div class="err">{{ $message }}</div> @enderror

        <div class="row"><label style="display:inline"><input type="checkbox" name="remember" value="1"> Ingat saya</label></div>

        <button type="submit">Masuk</button>
    </form>
</body>
</html>