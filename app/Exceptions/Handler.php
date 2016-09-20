<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\ApiException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        ApiException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof ApiException) {
            header('Content-Type: application/json; charset=utf-8');
            return response($e->getMessage(), $e->getCode());
        } else {
            $data = [
                'message' => $e->getMessage(),
                'status_code' => $e->getCode(),
            ];
            if (env('APP_DEBUG', false)) {
                $data['error_file'] = $e->getFile();
                $data['error_line'] = $e->getLine();
                $data['traces'] = collect(explode('#', $e->getTraceAsString()))->map(function($item){
                    return '#'. $item;
                });
            }
            return response()->json($data, $e->getCode());
        }
    }
}
