<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · Kuligo Resto POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:#e8eef9;font-family:'DM Sans',sans-serif;color:#1b2230;display:grid;place-items:center;padding:24px}.login-brand{position:fixed;top:24px;left:28px;display:flex;align-items:center;gap:9px;color:#172033;text-decoration:none}.login-mark{display:grid;place-items:center;width:40px;height:40px}.login-mark img{display:block;width:100%;height:100%;object-fit:contain}.login-brand b{display:block;font-size:13px}.login-brand small{display:block;font-size:8px;letter-spacing:1px;color:#798396}.login-card{width:min(100%,490px);background:#fff;border:1px solid #e2e7f0;border-radius:21px;box-shadow:0 5px 13px #1e293b30;padding:29px 42px 35px;text-align:center}.login-card h1{font-size:23px;letter-spacing:.3px;margin:0 0 27px}.login-error{margin:-12px 0 15px;padding:10px;border-radius:8px;background:#fff0f0;color:#a53232;font-size:12px}.login-form{width:min(100%,310px);margin:0 auto;display:grid;gap:18px}.login-form select{justify-self:center;width:135px;height:31px;border:0;border-radius:99px;background:#1b1b1d;color:white;text-align:center;text-align-last:center;font:500 11px 'DM Sans',sans-serif;padding:0 10px}.login-form input{height:42px;border:1px solid #adb4c0;border-radius:99px;background:#f1f2f4;padding:0 15px;color:#202938;font:500 12px 'DM Sans',sans-serif;outline:0}.login-form input:focus,.login-form select:focus{box-shadow:0 0 0 3px #dce5f2}.login-form button{justify-self:center;width:136px;height:35px;margin-top:4px;border:0;border-radius:99px;background:#19191b;color:white;font:600 11px 'DM Sans',sans-serif;cursor:pointer}.login-form button:hover{background:#354154}.login-hint{margin:18px 0 0;color:#8b93a1;font-size:10px}@media(max-width:520px){.login-brand{top:17px;left:17px}.login-card{padding:26px 22px 30px}.login-card h1{font-size:21px}}
    </style>
</head>
<body>
    <a href="{{ route('login') }}" class="login-brand"><span class="login-mark"><img src="{{ asset('images/kuligo-food-mark.png') }}" alt=""></span><span><b>kuligo</b><small>RESTO POS</small></span></a>
    <main class="login-card">
        <h1>HALAMAN LOGIN</h1>
        @if($errors->any())<div class="login-error">{{ $errors->first() }}</div>@endif
        <form class="login-form" method="POST" action="{{ route('login') }}">
            @csrf
            <select name="role" aria-label="Pilih role" required><option value="">Pilih role</option><option value="admin" @selected(old('role') === 'admin')>Admin</option><option value="kasir" @selected(old('role') === 'kasir')>Kasir</option></select>
            <input name="username" type="text" value="{{ old('username') }}" placeholder="Username" autocomplete="username" required autofocus>
            <input name="password" type="password" placeholder="Password" autocomplete="current-password" required>
            <button type="submit">LOGIN</button>
        </form>
        <p class="login-hint">Pilih peran Kasir, lalu masukkan username dan password yang dibuat admin. Username tidak membedakan huruf besar/kecil.</p>
    </main>
</body>
</html>
