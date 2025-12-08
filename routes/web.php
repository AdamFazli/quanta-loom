<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\PostingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ImageController::class, 'index'])->name('images.index');
Route::get('/gallery/create', [ImageController::class, 'create'])->name('images.create');
Route::post('/gallery', [ImageController::class, 'store'])->name('images.store');
Route::get('/gallery/{image}', [ImageController::class, 'show'])->name('images.show');
Route::delete('/gallery/{image}', [ImageController::class, 'destroy'])->name('images.destroy');
Route::post('/api/gallery', [ImageController::class, 'storeApi'])->name('images.store.api');
Route::delete('/api/gallery/{image}', [ImageController::class, 'destroyApi'])->name('images.destroy.api');

Route::get('/postings', [PostingController::class, 'index'])->name('postings.index');
Route::get('/postings/create', [PostingController::class, 'create'])->name('postings.create');
Route::post('/postings', [PostingController::class, 'store'])->name('postings.store');
Route::get('/postings/{posting}', [PostingController::class, 'show'])->name('postings.show');
Route::delete('/postings/{posting}', [PostingController::class, 'destroy'])->name('postings.destroy');
Route::get('/postings/{posting}/edit', [PostingController::class, 'edit'])->name('postings.edit');
Route::put('/postings/{posting}', [PostingController::class, 'update'])->name('postings.update');

Route::post('/api/postings', [PostingController::class, 'storeApi'])->name('postings.store.api');
Route::delete('/api/postings/{posting}', [PostingController::class, 'destroyApi'])->name('postings.destroy.api');
Route::post('/api/postings/{posting}/images', [PostingController::class, 'addImages'])->name('postings.add.images');

Route::delete('/api/postings/{posting}/images/{image}', [ImageController::class, 'destroyFromPosting'])->name('postings.images.destroy');
Route::delete('/postings/{posting}/images/{image}', [ImageController::class, 'destroyFromPostingWeb'])->name('postings.images.destroy.web');
Route::delete('/api/postings/{posting}/images', [ImageController::class, 'destroyAllFromPosting'])->name('postings.images.destroy.all');

