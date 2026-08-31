<?php

namespace App\Http\Controllers;

use App\Services\AdminStaffListService;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    public function index(
        AdminStaffListService $adminStaffListService
    ): View {
        $data = $adminStaffListService->getListData();

        return view('admin.staff-list', $data);
    }
}
