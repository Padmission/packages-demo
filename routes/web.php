<?php

use App\Livewire\Form;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');
Route::get('form', Form::class);
