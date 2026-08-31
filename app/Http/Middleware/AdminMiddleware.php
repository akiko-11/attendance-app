<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->admin_status) {

            // 現在のログインユーザーが一般ユーザーの場合
            // 管理者画面には入れない
            abort(403);
        }

        return $next($request);
    }
}
