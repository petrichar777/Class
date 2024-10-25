<?php
/**
 * Created by PhpStorm.
 * User: wlalala
 * Date: 2019-04-17
 * Time: 13:55
 */

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JWTRoleAuth extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param Closure $next
     * @param null $role
     * @return mixed
     */
    public function handle($request, Closure $next, $role = null)
    {
        //检查此次请求中，是否携带token，如果没有则抛出异常
        $this->checkForToken($request);

        try {
            // 解析token角色

            $token_role = $this->auth->parseToken()->getClaim('role');

        } catch (JWTException $e) {
            /**
             * token解析失败，说明请求中没有可用的token。
             * 为了可以全局使用（不需要token的请求也可通过），这里让请求继续。
             * 因为这个中间件的责职只是校验token里的角色。
             */
            return response()->json([
                'code' => 1003,
                'message' => 'token异常'
            ], 401);
        }

        // 判断token角色。
        if ($token_role != $role) {
            return response()->json([
                'code' => 1005,
                'message' => 'token非当前用户'
            ], 401);
        }

        return $next($request);
    }
}

