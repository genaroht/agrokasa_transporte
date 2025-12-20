<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controlador base de la aplicación.
 * TODOS los demás controladores (web y API) extienden de aquí.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
