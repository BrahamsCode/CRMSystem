<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin as Admin;
use App\Http\Controllers\Admin\Customers;
use App\MyApp;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix(MyApp::ADMINS_SUBDIR)->middleware('auth:admin')->name('admin.')->group(function () {
	Route::get('/', function () {
		return redirect()->route('admin.home');
	})->withoutMiddleware('auth:admin');
	Route::get('/home', [Admin\HomeController::class, 'index'])->name('home');

	Route::prefix('customers')->name('customers.')->group(function () {
		// Daily operation
		Route::controller(Customers\CustomerController::class)->group(function () {
			Route::get('/', 'index')->name('index');
			Route::get('/create', 'create')->name('create');
			Route::post('/', 'store')->name('store');
			Route::get('/search', 'search')->name('search');
		});

		Route::get('/visits', [Customers\VisitController::class, 'index'])->name('visits');
		Route::post('/visits', [Customers\VisitController::class, 'store'])->name('visits.store');
		Route::get('/statistics', [Customers\StatisticController::class, 'index'])->name('statistics');

		// Address lookup from the Japanese postal code
		Route::get('/postal-code/{zip}', Customers\PostalCodeController::class)->name('postal-code');

		// Module settings: one controller per resource
		Route::get('/field-settings', [Customers\FieldSettingController::class, 'index'])->name('field-settings');
		Route::get('/groups', [Customers\CustomerGroupController::class, 'index'])->name('groups');
		Route::get('/visit-motives', [Customers\VisitMotiveController::class, 'index'])->name('visit-motives');
		Route::get('/ranks', [Customers\RankController::class, 'index'])->name('ranks');
		Route::get('/rank-schedules', [Customers\RankScheduleController::class, 'index'])->name('rank-schedules');
		Route::post('/rank-schedules', [Customers\RankScheduleController::class, 'store'])->name('rank-schedules.store');
		Route::get('/visit-interval', [Customers\VisitIntervalController::class, 'index'])->name('visit-interval');
		Route::put('/visit-interval', [Customers\VisitIntervalController::class, 'update'])->name('visit-interval.update');

		// Custom fields: every create and edit gets its own view, never a modal
		Route::controller(Customers\CustomCategoryController::class)->prefix('custom-categories')->name('custom-categories')->group(function () {
			Route::get('/', 'index');
			Route::post('/', 'updateFlags')->name('.flags');
			Route::get('/create', 'create')->name('.create');
			Route::post('/create', 'store')->name('.store');
			Route::get('/{category}/edit', 'edit')->name('.edit');
			Route::put('/{category}', 'update')->name('.update');
			Route::delete('/{category}', 'destroy')->name('.destroy');
		});

		Route::controller(Customers\CustomFieldController::class)->prefix('custom-fields')->name('custom-fields')->group(function () {
			Route::get('/', 'index');
			Route::get('/create', 'create')->name('.create');
			Route::post('/create', 'store')->name('.store');
			Route::get('/{field}/edit', 'edit')->name('.edit');
			Route::put('/{field}', 'update')->name('.update');
			Route::delete('/{field}', 'destroy')->name('.destroy');
		});

		// Declared last so it does not swallow /create, /search and the settings
		Route::get('/{customer}', [Customers\CustomerController::class, 'show'])->name('show');
	});

});