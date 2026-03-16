<?php

use Illuminate\Support\Facades\Route;
use Webkul\PluginManager\Package;

if (! Package::isPluginInstalled('website')) {
    Route::redirect('/', '/admin/login');

    Route::redirect('/login', '/admin/login')
        ->name('login');
}
