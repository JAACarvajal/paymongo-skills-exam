<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Libraries\ResponseLibrary;

class CheckParkingInitialized
{
    /**
     * Check if parking is initialized
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (cache()->has('is_created') === false) {
            return ResponseLibrary::createJSONResponse(['message' => 'Parking not initialized.'], Response::HTTP_BAD_REQUEST);
        }

        return $next($request);
    }
}
