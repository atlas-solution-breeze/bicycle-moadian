<?php

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

Route::get('/', function () {
    Invoice::query()
        ->whereDoesntHave('moadianResult')->each(function ($invoice) {
            try {
                $invoice->send();
            } catch (Exception $exception) {
                dump($invoice);
                dump($exception);
            }
        });
});

Route::get('/products', function () {
	return (Product::query()->get()->toJson());
});

Route::get('logs', [LogViewerController::class, 'index']);
