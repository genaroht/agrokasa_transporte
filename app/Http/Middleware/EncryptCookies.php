<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * Cookies que NO se deben cifrar.
     */
    protected $except = [
        //
    ];
}
