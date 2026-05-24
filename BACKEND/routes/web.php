<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Support\Facades\Artisan;

Route::get('/setup-storage-secret-123', function () {
    try {
        Artisan::call('storage:link');
        return 'SUCCESS: Storage bridge built! PDFs are now readable.';
    } catch (\Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
});
