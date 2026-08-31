<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // 管理者の遷移先
        if ($request->is('admin/logout')) {
            return redirect('/admin/login');
        }

        // 一般ユーザーの遷移先
        return redirect('/login');
    }
}
