<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use Illuminate\Support\Facades\Route;

// No marketing page: signed-in users land on the dashboard, guests on login.
Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('companies', CompanyController::class);

    // Before the resource, so "import" isn't read as a contact id.
    Route::get('contacts/import', [ContactImportController::class, 'create'])->name('contacts.import.create');
    Route::post('contacts/import', [ContactImportController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('contacts.import.store');
    Route::resource('contacts', ContactController::class);

    Route::post('contacts/{contact}/activities', [ActivityController::class, 'store'])->name('contacts.activities.store');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    Route::get('deals', [DealController::class, 'index'])->name('deals.index');
    Route::post('deals', [DealController::class, 'store'])->name('deals.store');
    Route::put('deals/{deal}', [DealController::class, 'update'])->name('deals.update');
    Route::patch('deals/{deal}/move', [DealController::class, 'move'])->name('deals.move');
    Route::delete('deals/{deal}', [DealController::class, 'destroy'])->name('deals.destroy');
});

require __DIR__.'/settings.php';
