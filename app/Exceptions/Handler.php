<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (
            NotFoundHttpException $e,
            Request $request
        ) {
            if (
                $request->is('api/*')
                && $e->getPrevious() instanceof ModelNotFoundException
            ) {
                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。',
                ], 404);
            }
        });

        $this->renderable(function (
            AccessDeniedHttpException $e,
            Request $request
        ) {
            if (
                $request->is('api/*')
                && $e->getPrevious() instanceof AuthorizationException
            ) {
                return response()->json([
                    'error' => 'この操作を実行する権限がありません。',
                ], 403);
            }
        });
    }
}
