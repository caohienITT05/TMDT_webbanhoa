<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::latest()->paginate(10);

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        return view('admin.vouchers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:vouchers,code',
            ],
            'type' => [
                'required',
                Rule::in(['percent', 'fixed']),
            ],
            'value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'min_order_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'max_discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'starts_at' => [
                'nullable',
                'date',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],
            'usage_limit' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['used_count'] = 0;
        $data['is_active'] = $request->boolean('is_active');

        Voucher::create($data);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Thêm voucher thành công.');
    }

    public function show(Voucher $voucher)
    {
        return view('admin.vouchers.show', compact('voucher'));
    }

    public function edit(Voucher $voucher)
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers', 'code')->ignore($voucher->id),
            ],
            'type' => [
                'required',
                Rule::in(['percent', 'fixed']),
            ],
            'value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'min_order_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'max_discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'starts_at' => [
                'nullable',
                'date',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],
            'usage_limit' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');

        $voucher->update($data);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Cập nhật voucher thành công.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Xóa voucher thành công.');
    }
}