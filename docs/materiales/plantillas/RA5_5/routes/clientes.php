<?php

use App\Http\Controllers\ClientesController;
use Illuminate\Support\Facades\Route;

Route::controller(ClientesController::class)
    ->prefix('clientes')
    ->name('clientes.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('show/{id}', 'show')->whereNumber('id')->name('show');
        Route::get('create', 'create')->name('create');
        Route::get('edit/{id}', 'edit')->whereNumber('id')->name('edit');
    });
