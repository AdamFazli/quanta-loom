<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\PostingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ImageController::class, 'index'])->name('images.index');
Route::get('/gallery/create', [ImageController::class, 'create'])->name('images.create');
Route::post('/gallery', [ImageController::class, 'store'])->name('images.store');
Route::get('/gallery/{id}', [ImageController::class, 'show'])->name('images.show');
Route::delete('/gallery/{id}', [ImageController::class, 'destroy'])->name('images.destroy');
Route::post('/api/gallery', [ImageController::class, 'storeApi'])->name('images.store.api');
Route::delete('/api/gallery/{id}', [ImageController::class, 'destroyApi'])->name('images.destroy.api');

Route::get('/postings', [PostingController::class, 'index'])->name('postings.index');
Route::get('/postings/create', [PostingController::class, 'create'])->name('postings.create');
Route::post('/postings', [PostingController::class, 'store'])->name('postings.store');
Route::get('/postings/{id}', [PostingController::class, 'show'])->name('postings.show');
Route::delete('/postings/{id}', [PostingController::class, 'destroy'])->name('postings.destroy');


Route::post('/api/postings', [PostingController::class, 'storeApi'])->name('postings.store.api');
Route::delete('/api/postings/{id}', [PostingController::class, 'destroyApi'])->name('postings.destroy.api');
Route::post('/api/postings/{id}/images', [PostingController::class, 'addImages'])->name('postings.add.images');

Route::delete('/api/postings/{postingId}/images/{imageId}', [ImageController::class, 'destroyFromPosting'])->name('postings.images.destroy');
Route::delete('/postings/{postingId}/images/{imageId}', [ImageController::class, 'destroyFromPostingWeb'])->name('postings.images.destroy.web');
Route::delete('/api/postings/{postingId}/images', [ImageController::class, 'destroyAllFromPosting'])->name('postings.images.destroy.all');

