<?php

namespace App\Libraries;

use Illuminate\Http\JsonResponse;

class ResponseLibrary
{
    /**
     * Create JSON response
     * 
     * @param array $payload
     * @param int $code - defaults to 200
     * 
     * @return JsonResponse
     */
    public static function createJSONResponse(array $payload = null, string $message = 'success', int $code = 200) : JsonResponse
    {
        $responseData = [];

        if ($message !== null) {
            $responseData['message'] = $message;
        }

        if ($payload !== null) {
            $responseData['data'] = $payload;
        }

        return new JsonResponse($responseData, $code);
    }
}
