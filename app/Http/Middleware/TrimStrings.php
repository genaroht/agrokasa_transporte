<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * Campos que NO se recortan (por ejemplo passwords).
     */
    protected $except = [
        'password',
        'password_confirmation',
    ];
}
