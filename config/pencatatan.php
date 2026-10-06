<?php

return [
    // false = semua form kembali otomatis memakai tanggal hari ini
    'tanggal_manual' => env('PENCATATAN_TANGGAL_MANUAL', true),

    // Tanggal paling awal yang boleh dipilih saat mengejar pencatatan
    'mulai' => env('PENCATATAN_MULAI', '2026-10-01'),
];