<?php

namespace App\Services;

use App\Models\User;

class AdminStaffListService
{
    public function getListData(): array
    {
        $users = User::select('id', 'name', 'email')
            ->where('admin_status', false)
            ->get();

        return [
            'users' => $users,
        ];
    }
}
