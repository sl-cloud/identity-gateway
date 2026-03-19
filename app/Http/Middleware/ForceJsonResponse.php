<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * Ensures all API responses are JSON, including error responses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force Accept header to JSON
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        // If the response is not JSON, convert it
        if (! $response->headers->has('Content-Type') ||
            ! str_contains($response->headers->get('Content-Type'), 'application/json')) {

            // For error responses (redirects, etc.), convert to JSON
            if ($response->isRedirection() || $response->isClientError() || $response->isServerError()) {
                $status = $response->getStatusCode();

                // Don't modify actual JSON responses
                if ($this->isJsonResponse($response)) {
                    return $response;
                }

                // Convert HTML error pages to JSON
                $data = [
                    'error' => $this->getErrorCodeForStatus($status),
                    'error_description' => $this->getErrorDescriptionForStatus($status),
                    'status' => $status,
                ];

                return response()->json($data, $status);
            }
        }

        return $response;
    }

    /**
     * Check if a response is already JSON.
     */
    protected function isJsonResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'application/json');
    }

    /**
     * Get an appropriate error code for an HTTP status.
     */
    protected function getErrorCodeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'invalid_request',
            401 => 'unauthorized',
            403 => 'access_denied',
            404 => 'not_found',
            405 => 'method_not_allowed',
            422 => 'invalid_request',
            429 => 'rate_limit_exceeded',
            500 => 'server_error',
            503 => 'temporarily_unavailable',
            default => 'error',
        };
    }

    /**
     * Get an appropriate error description for an HTTP status.
     */
    protected function getErrorDescriptionForStatus(int $status): string
    {
        return match ($status) {
            400 => 'The request is malformed or missing required parameters',
            401 => 'Authentication is required to access this resource',
            403 => 'You do not have permission to access this resource',
            404 => 'The requested resource was not found',
            405 => 'The HTTP method is not allowed for this endpoint',
            422 => 'The request contains invalid data',
            429 => 'Too many requests. Please try again later',
            500 => 'An internal server error occurred',
            503 => 'The service is temporarily unavailable',
            default => 'An error occurred',
        };
    }
}
