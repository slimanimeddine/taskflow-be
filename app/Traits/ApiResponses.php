<?php

namespace App\Traits;

trait ApiResponses
{
    protected function success(string $message, $data = null, int $statusCode = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => $statusCode,
        ], $statusCode);
    }

    protected function successNoData(string $message = 'No Content', int $statusCode = 200)
    {
        return response()->json([
            'message' => $message,
            'status' => $statusCode,
        ], $statusCode);
    }

    protected function error(string $message, int $statusCode = 400)
    {
        return response()->json([
            'message' => $message,
            'status' => $statusCode,
        ], $statusCode);
    }

    protected function unauthorized(string $message = 'Unauthorized')
    {
        return $this->error($message, 403);
    }

    protected function notFound(string $message = 'Not Found')
    {
        return $this->error($message, 404);
    }

    protected function rateLimitExceeded(string $message = 'Rate Limit Exceeded')
    {
        return $this->error($message, 429);
    }
}
