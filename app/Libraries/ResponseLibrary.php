<?php

namespace App\Libraries;

use Illuminate\Http\JsonResponse;

class ResponseLibrary
{
    /**
     * Create JSON response
     * @param array $payload
     * @param int $code - defaults to 200
     * 
     * @return JsonResponse
     */
    public static function createJSONResponse(array $payload, int $code = 200) : JsonResponse
    {
        return new JsonResponse($payload, $code);
    }
}
