<?php
namespace App\Libs\JWT;

use App\Libs\JWT\Src\JWT as Token;
use App\Libs\JWT\Src\ExpiredException;
use App\Libs\JWT\Src\TTLExpiredException;

class JWT
{
    // Claims 权利声明数组
    private static $claims;

    /* *
     * 为创建JWT设置Claims数组
     *
     * @param  array|string $key
     * @param  string $value
     * @return array $claims
     * */
    private static function setClaims($key = null, $value = null)
    {
        // 发行人
        // self::$claims[ 'iss' ] = env('JWT_ISS', '');
        // 收听人
        // self::$claims[ 'aud' ] = env('JWT_AUD', '');
        // 发行时间
        self::$claims[ 'iat' ] = time();
        // 生存时长
        self::$claims[ 'ttl' ] = env('JWT_TTL', 2 * 60) * 60;
        // 过期时长（可刷新时长）
        self::$claims[ 'exp' ] = env('JWT_REFRESH_TTL', 7 * 24 * 60) * 60; # 单位秒, .env 设置为分
        // 主题
        // self::$claims[ 'sub' ] = env('JWT_SUB', '');
        // not before
        // self::$claims[ 'nbf' ] = env('JWT_REFRESH_TTL', '');
        // JWT ID
        self::$claims[ 'jti' ] = time() . mt_rand(1999, 9999);

        //设置参数
        if (is_array($key)) {
            foreach ($key as $k => $v){
                self::$claims[ $k ] = $v;
            }
        } elseif (is_string($key) && isset( $value )){
            self::$claims[ $key ] = $value;
        }
    }

    /**
     * 创建一个JWT TOKEN
     *
     * @param  array|string $claims_key
     * @param  mixed        $claims_value
     *
     * @return string $token
     */
    public static function encode($claims_key, $claims_value = null)
    {
        self::setClaims($claims_key, $claims_value); #权利参数
        $key = env('JWT_SECRET', 'jEFZ7vSF9BW1mzQuQG1NjGaHHiEiK773'); #密钥
        $alg = env('JWT_ALG', 'HS256'); #算法
        // 编码
        $token = Token::encode(self::$claims, $key, $alg);

        return $token;
    }

    /**
     * 解密：从JWT中得到Claims数据
     *
     * @param  string $token
     *
     * @throws
     *
     * @return object|null $claims
     */
    public static function decode($token = null)
    {
        $token = is_null($token) ? self::findToken() : $token;
        $key   = env('JWT_SECRET', 'jEFZ7vSF9BW1mzQuQG1NjGaHHiEiK773');# 密钥
        $alg   = env('JWT_ALG', 'HS256');# 算法

        if ($token) {
            $claims = Token::decode($token, $key, [ $alg ]);
            if (isset($claims->ttl) && isset($claims->iat) && ( $claims->ttl + $claims->iat ) < time()) {
                throw new TTLExpiredException('JWT TTL 已过期，请刷新。');
            }
            return $claims;
        }

        return null;
    }

    /**
     * 刷新token
     *
     * @param  string $token
     *
     * @throws
     *
     * @return object|null $claims
     */
    public static function refresh($token)
    {
        $token = is_null($token) ? self::findToken() : $token;
        $key   = env('JWT_SECRET', 'jEFZ7vSF9BW1mzQuQG1NjGaHHiEiK773');# 密钥
        $alg   = env('JWT_ALG', 'HS256');# 算法

        if ($token) {
            try {
                // 正常刷新
                $claims = self::decode($token, $key, [ $alg ]);
                $claims->iat = time();
                return self::encode((array)$claims);
            } catch (ExpiredException $e) { # 过期不再刷新
                return null;
            } catch (TTLExpiredException $e) { # 捕获本类抛出的 TTL 过期异常
                // 从源头解码
                $claims = Token::decode($token, $key, [ $alg ]);
                // 获取设置值
                self::setClaims();
                $claims->iat = self::$claims['iat'];
                $claims->ttl = self::$claims['ttl'];
                $claims->exp = self::$claims['exp'];
                // 刷新
                return self::encode((array)$claims);
            }
        }
    }

    /**
     * 从HTTP请求中查找TOKEN
     *
     * @return string|null
     */
    public static function findToken()
    {
        if ($token = app('request')->get('token')) {
            return $token;
        }
        /*
        if ($token = app('request')->get('Authorization')) {
            return $token;
        }
        */

        return null;
    }
}