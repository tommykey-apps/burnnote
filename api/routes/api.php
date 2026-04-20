<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::post('/notes', [NoteController::class, 'store']);
Route::get('/notes/{id}/exists', [NoteController::class, 'exists'])
    ->where('id', '[0-9a-zA-Z_-]{16,64}');
Route::get('/notes/{id}', [NoteController::class, 'consume'])
    ->where('id', '[0-9a-zA-Z_-]{16,64}');
