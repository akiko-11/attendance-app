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
    /**
     * ユーザー登録画面を表示する。
     *
     * @return View ユーザー登録画面
     */
    public function create(): View
    {
        return view('user.register');
    }

    /**
     * 新規ユーザーを登録し、自動ログイン後に勤怠画面へ遷移する。
     *
     * @param  RegisterRequest  $request  ユーザー登録情報を含むリクエスト
     * @param  CreatesNewUsers  $creator  ユーザー作成処理を行うサービス
     * @param  StatefulGuard  $guard  認証状態を管理するガード
     * @return RedirectResponse 勤怠画面へのリダイレクト
     */
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
