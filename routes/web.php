<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('courses.index')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

    Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    Route::post('/progress/{lesson}', [LessonProgressController::class, 'store'])->name('progress.store');

    Route::get('/stream/lessons/{lesson}', [StreamController::class, 'lesson'])->name('stream.lessons');
    Route::get('/stream/resources/{resource}', [StreamController::class, 'resource'])->name('stream.resources');
    Route::get('/resources/{resource}/preview', [StreamController::class, 'previewTextResource'])->name('resources.preview');
    Route::get('/lessons/{lesson}/thumbnail', [StreamController::class, 'lessonThumbnail'])->name('lessons.thumbnail');
    Route::get('/lessons/{lesson}/preview-manifest', [StreamController::class, 'lessonPreviewManifest'])->name('lessons.preview.manifest');
    Route::get('/lessons/{lesson}/preview-sprite', [StreamController::class, 'lessonPreviewSprite'])->name('lessons.preview.sprite');
});
