<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('user.register');
    }

    public function store(
        RegisterRequest $request,
        CreatesNewUsers $creator,
        StatefulGuard $guard
    ): RedirectResponse {
        // ユーザー作成
        $user = $creator->create($request->validated());

        // 登録完了イベント通知
        event(new Registered($user));

        // 自動ログイン
        $guard->login($user);

        return redirect('/attendance');
    }
}
