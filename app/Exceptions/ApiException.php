<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\MessageBag;

class ApiException extends Exception
{
    // 可用状态码
    protected $_status
        = [
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ', // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded',
        ];

    /**
     * Api 异常/错误
     *
     * 在 App\Exceptions\Handler::class 中捕获并返回响应。
     *
     * 统一格式:
     * message: '登录失败',
     * status_code: 422,
     * errors: [
     *     'username': [
     *         '用户不存在',
     *     ],
     *     'password': [
     *         '密码不正确',
     *     ]
     * ],
     *
     * @param array  $errors  错误数据
     * @param int    $status  状态码
     * @param string $message 信息
     *
     * Author AlpFish 2016/9/20
     */
    public function __construct($errors = null, $status = 400, $message = '')
    {
        $status = isset( $this->_status[ $status ] ) ? $status : 400;
        if (empty( $message )) {
            $message = isset( $this->_status[ $status ] ) ? $this->_status[ $status ] : '';
        }
        $res = [
            'message'     => $message,
            'status_code' => $status,
        ];
        // errors 格式化
        $errors = is_array($errors) ? new MessageBag($errors) : $errors;
        if (!empty($errors)) {
            $res[ 'errors' ] = $errors;
        }

        header('HTTP/1.1 ' . $status . ' ' . $this->_status[ $status ]);
        header('Status:' . $status . ' ' . $this->_status[ $status ]); # 确保FastCGI模式下正常
        header('Content-Type: application/json; charset=utf-8');

        $message = json_encode($res);

        parent::__construct($message, $status);
    }
}