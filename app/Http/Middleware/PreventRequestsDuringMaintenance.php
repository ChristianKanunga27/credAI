<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be ignored by maintenance mode.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}