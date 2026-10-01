<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PengaturanController extends Controller
{
    private function wajibAdmin(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya admin yang boleh mengubah pengaturan.');
    }

    public function edit()
    {
        $this->wajibAdmin();

        return view('pengaturan.edit');
    }

    public function update(Request $request)
    {
        $this->wajibAdmin();

        $data = $request->validate([
            'app_name'       => ['required', 'string', 'max:100'],
            'address'        => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'receipt_title'  => ['required', 'string', 'max:50'],
            'receipt_thanks' => ['nullable', 'string', 'max:200'],
            'receipt_footer' => ['nullable', 'string', 'max:300'],
            'warna_header'   => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'warna_aksen'    => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo'           => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'favicon'        => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico', 'max:512'],
        ]);

        $lama = Setting::semua();

        $simpan = [
            'app_name'          => trim($data['app_name']),
            'address'           => trim((string) ($data['address'] ?? '')),
            'phone'             => trim((string) ($data['phone'] ?? '')),
            'receipt_title'     => trim($data['receipt_title']),
            'receipt_thanks'    => trim((string) ($data['receipt_thanks'] ?? '')),
            'receipt_footer'    => trim((string) ($data['receipt_footer'] ?? '')),
            'receipt_show_logo' => $request->boolean('receipt_show_logo') ? '1' : '0',
            'warna_header'      => strtolower($data['warna_header']),
            'warna_aksen'       => strtolower($data['warna_aksen']),
        ];

        $hapusFile = [];
        foreach (['logo' => 'logo_path', 'favicon' => 'favicon_path'] as $field => $key) {
            if ($request->hasFile($field)) {
                $simpan[$key] = $request->file($field)->store('branding', 'public');
                $hapusFile[]  = $lama[$key];
            } elseif ($request->boolean('hapus_' . $field)) {
                $simpan[$key] = '';
                $hapusFile[]  = $lama[$key];
            }
        }

        Setting::simpan($simpan);

        foreach ($hapusFile as $f) {
            if ($f) {
                Storage::disk('public')->delete($f);
            }
        }

        return redirect()->route('pengaturan.edit')->with('status', 'Pengaturan tersimpan.');
    }
}