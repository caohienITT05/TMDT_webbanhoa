<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // 1. Chuyển toàn bộ sản phẩm trong giỏ hàng tạm sang tài khoản vừa đăng nhập
        $sessionId = session()->getId();
        \App\Models\CartItem::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $request->user()->id]);

        // 2. Chuyển hướng đúng vai trò
        if ($request->user()->role === 'admin') {
            return redirect()->intended(route('admin.dashboard'));
        }


        // Khách hàng thông thường: ưu tiên quay lại trang trước đó (ví dụ trang checkout)
        return redirect()->intended(route('home'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
