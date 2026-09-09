<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GeneralUserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->admin_status) {
            return redirect('/admin/attendance/list');
        }

        // 一般ユーザーの場合はそのまま処理を続行
        return $next($request);
    }
}
