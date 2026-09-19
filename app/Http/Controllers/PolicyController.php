<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PolicyController extends Controller
{
    /**
     * Điều kiện giao dịch chung & Thông tin người bán
     */
    public function terms()
    {
        return view('policies.terms');
    }

    /**
     * Chính sách đổi trả & hoàn tiền cho hoa tươi
     */
    public function returns()
    {
        return view('policies.returns');
    }

    /**
     * Chính sách bảo vệ dữ liệu cá nhân (Nghị định 13/2023/NĐ-CP)
     */
    public function privacy()
    {
        return view('policies.privacy');
    }
}