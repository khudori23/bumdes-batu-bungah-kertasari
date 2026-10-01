<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AkunController extends Controller
{
    public function showPassword()
    {
        return view('akun.password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->password_lama, $user->password)) {
            return back()->withErrors(['password_lama' => 'Password lama tidak cocok.']);
        }

        // Cast 'hashed' di model User akan meng-hash otomatis
        $user->password = $request->password_baru;
        $user->save();

        return redirect()->route('dashboard')->with('status', 'Password berhasil diganti.');
    }
}