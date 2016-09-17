<?php


namespace App\Http\Middleware;


use Closure;

class GetQueryLog
{
    /**
     * Eloquent 获取SQL查询语句, 调试中间件
     */
    public function handle($request, Closure $next)
    {
        if (env('APP_ENV', 'production') == 'local') {
            app('db')->enableQueryLog();
        }

        $response = $next($request);

        if (env('APP_ENV', 'production') == 'local') {
            $content = collect(json_decode($response->getContent()))
                ->put('__ELOQUENT_QUERY_LOG__', app('db')->getQueryLog());
            $response->setContent($content->toJson());
        }

        return $response;
    }
}