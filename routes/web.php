<?php

use atikrahman\ServerHealth\Controllers\MonitorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is the monitor url to check health
|
*/

Route::get('/laravel-server',[MonitorController::class, 'index']);
