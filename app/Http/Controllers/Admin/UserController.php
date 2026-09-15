<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function toggleStatus(User $user)
    {
        // Không cho phép tự khóa tài khoản của chính mình
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản của chính mình!');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'mở khóa' : 'khóa';
        return back()->with('success', "Đã {$statusText} tài khoản {$user->email} thành công!");
    }
}