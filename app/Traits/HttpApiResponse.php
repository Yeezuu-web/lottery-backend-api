<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait HttpApiResponse
{
    /**
     * Return a successful response
     */
    protected function success(
        array $data = [],
        string $message = 'Success',
        int $code = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $code);
    }

    /**
     * Return an error response
     */
    protected function error(
        string $message = 'Error',
        int $code = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        array $data = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        if (! empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a validation error response
     */
    protected function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Return an unauthorized response
     */
    protected function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a forbidden response
     */
    protected function forbidden(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a not found response
     */
    protected function notFound(
        string $message = 'Not found'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return a created response
     */
    protected function created(
        array $data = [],
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a no content response
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return a paginated response
     */
    protected function paginated(
        array $data,
        array $pagination,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
