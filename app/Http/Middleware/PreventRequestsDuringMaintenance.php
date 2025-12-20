<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * URIs que se permiten incluso en modo mantenimiento.
     */
    protected $except = [
        //
    ];
}
