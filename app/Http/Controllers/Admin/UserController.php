<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::latest();

        // Tìm kiếm theo tên, email hoặc SĐT
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Lọc theo vai trò (admin/customer)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Lọc theo trạng thái hoạt động
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản của chính mình!');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'mở khóa' : 'khóa';
        return back()->with('success', "Đã {$statusText} tài khoản {$user->email} thành công!");
    }
}