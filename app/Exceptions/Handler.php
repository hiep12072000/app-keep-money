<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Handle API requests
        if ($request->is('api/*')) {
            // Handle authentication exceptions
            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthenticated. Please provide a valid token.',
                    'error' => true
                ], 401);
            }

            // Handle route not found exceptions
            if ($exception instanceof RouteNotFoundException) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Unauthenticated. Please provide a valid token.',
                    'error' => true
                ], 401);
            }

            // Handle validation exceptions
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed: ' . implode(', ', $exception->validator->errors()->all()),
                    'error' => true
                ], 422);
            }

            // Handle other exceptions
            return response()->json([
                'status' => 500,
                'message' => 'Internal server error: ' . $exception->getMessage(),
                'error' => true
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
