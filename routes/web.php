<?php

use App\Livewire\Form;

Route::redirect('/', '/app');
\Illuminate\Support\Facades\Route::get('form', Form::class);
