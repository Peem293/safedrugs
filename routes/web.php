<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return filament('admin/login');
});
