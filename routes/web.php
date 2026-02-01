<?php

use App\Http\Controllers\RentalContractController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\BikeController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BonusSystemConfigController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TransactionExportController;


use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Bike;
use App\Models\Rental;
use App\Models\Payment;
use App\Models\BonusSystemConfig;

use App\Http\Requests\TariffRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;


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

Route::get('/bonus-config', function () {
    return Inertia::render('Bonus');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Redirect::away('/rents');
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

    Route::post('/clients', function (Request $request) {
        $controller = app(\App\Http\Controllers\ClientController::class);
        $controller->store($request);

        return back()->with('message', 'Клиент создан');
    })->name('clients.store');

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

Route::post('/rentals/{rental}/cancel-with-bike-change', function (Request $request, $rentalId) {
    $rental = app(Rental::class)->findOrFail($rentalId);
    
    $controller = app()->make(\App\Http\Controllers\RentalController::class);
    $result = app()->call([$controller, 'cancelWithBikeChange'], [
        'request' => $request,
        'rental' => $rental
    ]);
    
    if ($request->header('X-Inertia') && 
        $result->headers->get('Content-Type') === 'application/json') {
        $data = json_decode($result->getContent(), true);
        
        return redirect()->back()
            ->with('success', $data['message'] ?? 'Success')
            ->with('data', $data['data'] ?? []);
    }
    
    return $result;
})->name('rentals.cancel-with-bike-change');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');


    Route::delete('/rents/{rent}', function (Rental $rent) {
        app(RentalController::class)->destroy($rent);
        return Redirect::back()->with('message', 'Аренда удалена');
    });

    Route::post('/payments', function (StorePaymentRequest $request) {
        app(PaymentController::class)->store($request);
        return Redirect::back()->with('message', 'Платеж создан');
    });

    Route::put('/payments/{payment}', function (UpdatePaymentRequest $request, Payment $payment) {
        app(PaymentController::class)->update($request, $payment);
        return Redirect::back()->with('message', 'Платеж обновлен');
    });

    Route::delete('/payments/{payment}', function (Payment $payment) {
        app(PaymentController::class)->destroy($request, $payment);
        return Redirect::back()->with('message', 'Платеж удален');
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
        'filters' => $request->only([
            'search', 
            'client_id', 
            'bike_id', 
            'tariff_id', 
            'status', 
            'paid_status',
            'min_cost',
            'max_cost',
            'start_date',
            'end_date',
            'has_note'
        ]),
        'clients_options' => $clients,
        'bikes_options'   => $bikes,
        'tariffs_options' => $tariffs,
    ]);
})->name('rents.index');

use App\Http\Resources\PaymentResource;

Route::middleware('auth')->group(function () {

    Route::get('/payments', function (Request $request) {
        $search = $request->query('search');

        $payments = Payment::with(['client.customFields', 'rental'])
            ->when($search, function ($query) use ($search) {
                $query->where('purpose', 'like', "%{$search}%")
                      ->orWhere('article_ru', 'like', "%{$search}%")
                      ->orWhereHas('client', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhereHas('customFields', function ($q2) use ($search) {
                                $q2->where('field_value', 'like', "%{$search}%");
                            });
                      });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $clients = \App\Models\Client::with('customFields')
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

        return Inertia::render('Payments', [
            'payments' => PaymentResource::collection($payments),
            'filters'  => $request->only('search'),
            'clients_options' => $clients,
        ]);
    })->name('payments.index');
});

Route::post('/rentals/{rental}/generate-contract', [RentalContractController::class, 'generateRentalContract'])
   ->name('rentals.generate-contract');

Route::post('/rentals/{rental}/preview-contract', [RentalContractController::class, 'previewRentalContract'])
   ->name('rentals.preview-contract');

Route::post('/rentals/{rentalId}/contract/pdf', [RentalContractController::class, 'generateRentalContractPdf'])
   ->name('rentals.generate-contract-pdf');

Route::middleware('auth')->group(function () {
    Route::middleware('auth')->get('/bonus-config', function () {
        return Inertia::render('Bonus', [
            'configs' => \App\Models\BonusSystemConfig::all(),
        ]);
    })->name('bonus-config.index');
    Route::put('/bonus-config/{key}', function (Request $request, $key) {
        $config = \App\Models\BonusSystemConfig::where('key', $key)->first();
        
        if (!$config) {
            return back()->withErrors(['error' => 'Настройка не найдена']);
        }
        
        $config->update([
            'value' => $request->input('value')
        ]);
        
        return back()->with('success', 'Сохранено');
    })->name('bonus-config.update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/export', [ExportController::class, 'exportForm'])->name('export.form');
    Route::get('/export/columns/{table}', [ExportController::class, 'getTableColumns'])->name('export.columns');
    Route::post('/export/{table}', [ExportController::class, 'exportTable'])->name('export.table');
    Route::get('/transactions/export/direct', [TransactionExportController::class, 'directExport'])
        ->name('transactions.export.direct');
});
