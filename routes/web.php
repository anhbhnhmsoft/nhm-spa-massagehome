<?php

use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    var_dump('hello world');
})->name('home');
