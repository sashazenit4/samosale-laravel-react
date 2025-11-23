<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\BikeController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\RentalController;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Bike;
use App\Models\Tariff;
use App\Models\Rental;

use App\Http\Requests\TariffRequest;


Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/clients', function () {
    return Inertia::render('Clients');
});

Route::get('/bikes', function () {
    return Inertia::render('Bikes');
});

Route::get('/payments', function () {
    return Inertia::render('Payments');
});

Route::get('/rents', function () {
    return Inertia::render('Rents');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/clients', function (Request $request) {
        $search = $request->query('search');

        $query = Client::with('customFields');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('customFields', function ($q2) use ($search) {
                      $q2->where('field_value', 'like', "%{$search}%");
                  });
            });
        }

        return Inertia::render('Clients', [
            'clients' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    })->name('clients.index');

    Route::put('/clients/{id}', function (Request $request, $id) {
        $controller = app(\App\Http\Controllers\ClientController::class);
        $controller->update($request, $id); // JSON игнорируется

        return back()->with('message', 'Клиент обновлён');
    });
    Route::delete('/clients/{id}', function ($id) {
        // Вызываем ваш контроллер (или модель напрямую)
        app(\App\Http\Controllers\ClientController::class)->destroy($id);

        return back()->with('message', 'Клиент удалён');
    })->name('clients.destroy');
});

Route::middleware('auth')->group(function () {

    Route::get('/bikes', function (Request $request) {
        $tab = $request->query('tab', 'bikes');
        $search = $request->query('search');

        $data = [
            'filters' => ['search' => $search, 'tab' => $tab],
        ];

        if ($tab === 'bikes') {
            $query = \App\Models\Bike::query();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('bike_number', 'like', "%{$search}%")
                      ->orWhere('frame_number', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            }
            $data['bikes'] = $query->paginate(10)->withQueryString();
        }

        if ($tab === 'equipment') {
            $query = \App\Models\Equipment::query();
            if ($search) {
                $query->where('number', 'like', "%{$search}%");
            }
            $data['equipment'] = $query->paginate(10)->withQueryString();
        }

        if ($tab === 'tariffs') {
            $query = \App\Models\Tariff::query();
            if ($search) {
                $query->where('program', 'like', "%{$search}%");
            }
            $data['tariffs'] = $query->paginate(10)->withQueryString();
        }

        return Inertia::render('Bikes', $data);
    })->name('bikes.index');

    // CRUD
    Route::post('/bikes', function (Request $request) {
        app(BikeController::class)->store($request);
        return Redirect::back()->with('message', 'Велосипед создан');
    });

    Route::put('/bikes/{bike}', function (Request $request, Bike $bike) {
        app(BikeController::class)->update($request, $bike);
        return Redirect::back()->with('message', 'Велосипед обновлён');
    });

    Route::delete('/bikes/{bike}', function (Bike $bike) {
        app(BikeController::class)->destroy($bike);
        return Redirect::back()->with('message', 'Велосипед удалён');
    });

    Route::post('/tariffs', function (TariffRequest $request) {
        app(\App\Http\Controllers\TariffController::class)->store($request);
        return Redirect::back()->with('message', 'Тариф создан');
    });

    Route::put('/tariffs/{tariff}', function (TariffRequest $request, \App\Models\Tariff $tariff) {
        app(\App\Http\Controllers\TariffController::class)->update($request, $tariff);
        return Redirect::back()->with('message', 'Тариф обновлён');
    });

    Route::delete('/tariffs/{tariff}', function (\App\Models\Tariff $tariff) {
        app(\App\Http\Controllers\TariffController::class)->destroy($tariff);
        return Redirect::back()->with('message', 'Тариф удалён');
    });

    Route::post('/equipment', function (Request $request) {
        app(\App\Http\Controllers\EquipmentController::class)->store($request);
        return Redirect::back()->with('message', 'Аккумулятор создан');
    });

    Route::put('/equipment/{equipment}', function (Request $request, \App\Models\Equipment $equipment) {
        app(\App\Http\Controllers\EquipmentController::class)->update($request, $equipment);
        return Redirect::back()->with('message', 'Аккумулятор обновлён');
    });

    Route::delete('/equipment/{equipment}', function (\App\Models\Equipment $equipment) {
        app(\App\Http\Controllers\EquipmentController::class)->destroy($equipment);
        return Redirect::back()->with('message', 'Аккумулятор удалён');
    });

    Route::post('/rents', function (Request $request) {
        app(RentalController::class)->store($request);
        return Redirect::back()->with('message', 'Аренда создана');
    });

    Route::put('/rents/{rent}', function (Request $request, Rental $rent) {
        app(RentalController::class)->update($request, $rent);
        return Redirect::back()->with('message', 'Аренда обновлена');
    });

    Route::post('/rents/{rent}/complete-early', function (Request $request, Rental $rent) {
        app(RentalController::class)->completeEarly($request, $rent);
        return Redirect::back()->with('message', 'Аренда обновлена');
    });

    Route::post('/rents/{rent}/complete', function (Rental $rent) {
        app(RentalController::class)->complete($rent);
        return Redirect::back()->with('message', 'Аренда обновлена');
    });
    Route::post('/rents/{rent}/mark-paid', function (Rental $rent) {
        app(RentalController::class)->markAsPaid($rent);
        return Redirect::back()->with('message', 'Аренда обновлена');
    });


    Route::delete('/rents/{rent}', function (Rental $rent) {
        app(RentalController::class)->destroy($rent);
        return Redirect::back()->with('message', 'Аренда удалена');
    });
});

Route::middleware('auth')->get('/rents', function (Request $request) {
    $apiResponse = app(RentalController::class)->index($request);

    if (!$apiResponse->getData()->success) {
        abort(500, 'Не удалось загрузить аренды');
    }

    $rents = $apiResponse->getData()->data;
    $meta  = $apiResponse->getData()->meta;

    $clients = \App\Models\Client::with('customFields')
        ->withoutActiveRentals()
        ->select('user_id as user_id', 'name')
        ->get()
        ->map(fn($c) => [
            'user_id'       => $c->user_id,
            'name'          => $c->name,
            'custom_fields' => $c->customFields->map(fn($cf) => [
                'field_name'  => $cf->field_name,
                'field_value' => $cf->field_value,
                'field_type'  => $cf->field_type,
            ])->toArray(),
        ]);

    $bikes = \App\Models\Bike::select('id', 'bike_number', 'frame_number', 'status')->get();
    $tariffs = \App\Models\Tariff::select('id', 'program', 'power', 'price_week1', 'price_week2', 'price_month')->get();

    return Inertia::render('Rents', [
        'rents' => [
            'data' => $rents,
            'meta' => $meta,
        ],
        'filters' => $request->only('search'),
        'clients_options' => $clients,
        'bikes_options'   => $bikes,
        'tariffs_options' => $tariffs,
    ]);
})->name('rents.index');
