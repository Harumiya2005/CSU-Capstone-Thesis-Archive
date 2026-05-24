<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



use Illuminate\Support\Facades\Artisan;

Route::get('/setup-db-secret-123', function () {
    try {
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true
        ]);
        return 'SUCCESS: Database tables built and dummy users seeded!';
    } catch (\Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
});
