<?php

return [
    // Tempo hutang bawaan, dalam hari. Bisa diubah lewat .env: KASIR_TEMPO_HARI=14
    'tempo_hari' => (int) env('KASIR_TEMPO_HARI', 7),
];