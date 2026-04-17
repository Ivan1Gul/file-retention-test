<?php

use App\Http\Controllers\StoredFileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/files');

Route::get('/files', [StoredFileController::class, 'index'])->name('files.index');
Route::post('/files', [StoredFileController::class, 'store'])->name('files.store');
Route::delete('/files/{storedFile}', [StoredFileController::class, 'destroy'])->name('files.destroy');
Route::get('/files/{storedFile}/download', [StoredFileController::class, 'download'])->name('files.download');
