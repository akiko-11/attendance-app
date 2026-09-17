<?php

namespace App\Http\Controllers;

use App\Services\AdminStaffListService;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    /**
     * 管理者向けのスタッフ一覧を取得して表示する。
     *
     * @param  AdminStaffListService  $adminStaffListService  スタッフ一覧を取得するサービス
     * @return View スタッフ一覧画面
     */
    public function index(
        AdminStaffListService $adminStaffListService
    ): View {
        $data = $adminStaffListService->getListData();

        return view('admin.staff-list', $data);
    }
}
