<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * The execution start time for calculating response time
     */
    protected float $executionStartTime;

    /**
     * Initialize controller
     */
    public function __construct()
    {
        $this->executionStartTime = microtime(true);
    }

    /**
     * Return successful response with data
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param bool $cached
     * @return JsonResponse
     */
    protected function successfulResponse(
        mixed $data = null,
        string $message = 'Request successful',
        int $statusCode = Response::HTTP_OK,
        bool $cached = false
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->format('Y-m-d, H:i:s'),
            'execution_time' => round((microtime(true) - $this->executionStartTime) * 1000, 2) . ' ms',
            'cached' => $cached
        ], $statusCode);
    }

    /**
     * Return error response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message = 'Request failed',
        mixed $errors = null,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->format('Y-m-d, H:i:s'),
            'execution_time' => round((microtime(true) - $this->executionStartTime) * 1000, 2) . ' ms',
            'cached' => false
        ], $statusCode);
    }

    /**
     * Return paginated response
     *
     * @param mixed $data
     * @param int $total
     * @param int $perPage
     * @param int $currentPage
     * @param string $message
     * @param bool $cached
     * @return JsonResponse
     */
    protected function jsonResponseWithPagination(
        mixed $data,
        int $total,
        int $perPage = 15,
        int $currentPage = 1,
        string $message = 'Request successful',
        bool $cached = false
    ): JsonResponse {
        $totalPages = ceil($total / $perPage);
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next_page' => $currentPage < $totalPages,
                'has_previous_page' => $currentPage > 1,
            ],
            'timestamp' => now()->format('Y-m-d, H:i:s'),
            'execution_time' => round((microtime(true) - $this->executionStartTime) * 1000, 2) . ' ms',
            'cached' => $cached
        ]);
    }

    /**
     * Return validation error response
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        mixed $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            $errors,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * Return unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            null,
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Return forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            null,
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Return not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            null,
            Response::HTTP_NOT_FOUND
        );
    }
}
