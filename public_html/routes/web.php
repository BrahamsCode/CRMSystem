<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin as Admin;
use App\Http\Controllers\Admin\Customers;
use App\Http\Controllers\Admin\Promotions;
use App\Http\Controllers\PromotionPublicController;
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
			Route::get('/export', 'export')->name('export');
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

	// Módulo 2: promociones (販売促進管理)
	Route::prefix('promotions')->name('promotions.')->group(function () {
		Route::get('/', [Promotions\DashboardController::class, 'index'])->name('index');

		// Envíos: newsletter, email con diseño y push
		Route::controller(Promotions\MessageController::class)->prefix('messages')->name('messages.')->group(function () {
			Route::get('/', 'index')->name('index');
			Route::get('/create', 'create')->name('create');
			Route::post('/', 'store')->name('store');
			Route::post('/audience', 'audience')->name('audience');
			Route::get('/{message}', 'show')->name('show');
			Route::get('/{message}/edit', 'edit')->name('edit');
			Route::put('/{message}', 'update')->name('update');
			Route::post('/{message}/duplicate', 'duplicate')->name('duplicate');
			Route::post('/{message}/cancel', 'cancel')->name('cancel');
			Route::delete('/{message}', 'destroy')->name('destroy');
		});

		// Envíos automáticos
		Route::controller(Promotions\RuleController::class)->prefix('automations')->name('rules.')->group(function () {
			Route::get('/', 'index')->name('index');
			Route::get('/create', 'create')->name('create');
			Route::post('/', 'store')->name('store');
			Route::get('/{rule}/edit', 'edit')->name('edit');
			Route::put('/{rule}', 'update')->name('update');
			Route::post('/{rule}/toggle', 'toggle')->name('toggle');
			Route::post('/{rule}/run', 'run')->name('run');
			Route::delete('/{rule}', 'destroy')->name('destroy');
		});

		Route::resource('templates', Promotions\TemplateController::class)->except('show');
		Route::get('/templates/{template}/json', [Promotions\TemplateController::class, 'json'])->name('templates.json');

		Route::get('/test-addresses', [Promotions\TestAddressController::class, 'index'])->name('test-addresses');
		Route::post('/test-addresses', [Promotions\TestAddressController::class, 'store'])->name('test-addresses.store');
		Route::delete('/test-addresses/{address}', [Promotions\TestAddressController::class, 'destroy'])->name('test-addresses.destroy');

		// Cupones
		Route::controller(Promotions\CouponController::class)->prefix('coupons')->name('coupons.')->group(function () {
			Route::get('/', 'index')->name('index');
			Route::get('/stats', 'stats')->name('stats');
			Route::get('/create', 'create')->name('create');
			Route::post('/', 'store')->name('store');
			Route::get('/{coupon}', 'show')->name('show');
			Route::get('/{coupon}/edit', 'edit')->name('edit');
			Route::put('/{coupon}', 'update')->name('update');
			Route::delete('/{coupon}', 'destroy')->name('destroy');
			Route::post('/{coupon}/issue', 'issue')->name('issue');
			Route::post('/issued/{issued}/use', 'use')->name('use');
		});

		// Fidelización
		Route::controller(Promotions\StampController::class)->prefix('stamps')->name('stamps')->group(function () {
			Route::get('/', 'edit');
			Route::put('/', 'update')->name('.update');
			Route::post('/rules', 'storeRule')->name('.rules.store');
			Route::delete('/rules/{rule}', 'destroyRule')->name('.rules.destroy');
			Route::post('/adjust', 'adjust')->name('.adjust');
		});
		Route::controller(Promotions\PointController::class)->prefix('points')->name('points')->group(function () {
			Route::get('/', 'edit');
			Route::put('/', 'update')->name('.update');
			Route::post('/adjust', 'adjust')->name('.adjust');
		});

		// Encuestas
		Route::controller(Promotions\SurveyController::class)->prefix('surveys')->name('surveys.')->group(function () {
			Route::get('/', 'index')->name('index');
			Route::get('/create', 'create')->name('create');
			Route::post('/', 'store')->name('store');
			Route::get('/{survey}', 'show')->name('show');
			Route::get('/{survey}/edit', 'edit')->name('edit');
			Route::put('/{survey}', 'update')->name('update');
			Route::delete('/{survey}', 'destroy')->name('destroy');
		});

		// Análisis
		Route::get('/analytics', [Promotions\AnalyticsController::class, 'index'])->name('analytics');
		Route::get('/visit-history', [Promotions\VisitHistoryController::class, 'index'])->name('visit-history');
		Route::get('/registration', [Promotions\RegistrationController::class, 'index'])->name('registration');
	});

});

// Enlaces de los mensajes de promoción: los abre el cliente, sin sesión
Route::prefix('p')->name('promotions.')->controller(PromotionPublicController::class)->group(function () {
	Route::get('/o/{recipient}.gif', 'open')->name('track.open');
	Route::get('/n/{recipient}', 'openPush')->name('track.open-push');
	Route::get('/c/{recipient}', 'click')->name('track.click')->middleware('signed');
	Route::get('/cupon/{issued}', 'coupon')->name('public.coupon');
	Route::get('/encuesta/{survey}', 'survey')->name('public.survey');
	Route::post('/encuesta/{survey}', 'answer')->name('public.survey.answer');
});
