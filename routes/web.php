<?php

use App\Models\Answer;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', ['answer' => Answer::today()]);
});
