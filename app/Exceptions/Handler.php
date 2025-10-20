<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            Log::error('Exception caught', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        });
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
        // API requests nên trả về JSON với chi tiết lỗi (trong development)
        if ($request->is('api/*') || $request->wantsJson()) {
            // Determine status code
            $status = 500;
            if (method_exists($exception, 'getStatusCode')) {
                /** @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception */
                $status = $exception->getStatusCode();
            } elseif (method_exists($exception, 'getCode') && $exception->getCode() > 0) {
                $code = $exception->getCode();
                $status = ($code >= 400 && $code < 600) ? $code : 500;
            }
                
            $response = [
                'message' => $exception->getMessage() ?: 'Server Error',
            ];
            
            // Show details only in debug mode
            $debugMode = env('APP_DEBUG', false);
            if ($debugMode) {
                $response['exception'] = get_class($exception);
                $response['file'] = $exception->getFile();
                $response['line'] = $exception->getLine();
                $response['trace'] = collect($exception->getTrace())->take(5)->map(function ($trace) {
                    return [
                        'file' => $trace['file'] ?? 'unknown',
                        'line' => $trace['line'] ?? 0,
                        'function' => $trace['function'] ?? 'unknown',
                    ];
                })->all();
            }
            
            return response()->json($response, $status);
        }
        
        return parent::render($request, $exception);
    }
}
