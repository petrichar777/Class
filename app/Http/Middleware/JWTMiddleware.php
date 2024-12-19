<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // 验证 JWT Token 并获取用户
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof TokenExpiredException) {
                return response()->json(['status' => 'Token 已过期'], 401);
            } elseif ($e instanceof TokenInvalidException) {
                return response()->json(['status' => 'Token 无效'], 401);
            } else {
                return response()->json(['status' => 'Token 未提供'], 401);
            }
        }
        // 将用户对象注入请求
        $request->user = $user;

        return $next($request);
    }
}
