<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingMethodController extends Controller
{
    /**
     * Display a listing of shipping methods.
     */
    public function index(): View
    {
        $shippingMethods = ShippingMethod::orderBy('id', 'desc')->get();

        return view('admin.shipping_methods.index', compact('shippingMethods'));
    }

    /**
     * Show the form for creating a new shipping method.
     */
    public function create(): View
    {
        return view('admin.shipping_methods.create');
    }

    /**
     * Store a newly created shipping method.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fee' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        ShippingMethod::create($validated);

        return redirect()
            ->route('admin.shipping-methods.index')
            ->with('success', 'Thêm phương thức vận chuyển thành công.');
    }

    /**
     * Display the specified shipping method.
     */
    public function show(ShippingMethod $shippingMethod): View
    {
        return view('admin.shipping_methods.show', compact('shippingMethod'));
    }

    /**
     * Show the form for editing the specified shipping method.
     */
    public function edit(ShippingMethod $shippingMethod): View
    {
        return view('admin.shipping_methods.edit', compact('shippingMethod'));
    }

    /**
     * Update the specified shipping method.
     */
    public function update(
        Request $request,
        ShippingMethod $shippingMethod
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fee' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $shippingMethod->update($validated);

        return redirect()
            ->route('admin.shipping-methods.index')
            ->with('success', 'Cập nhật phương thức vận chuyển thành công.');
    }

    /**
     * Remove the specified shipping method.
     */
    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->delete();

        return redirect()
            ->route('admin.shipping-methods.index')
            ->with('success', 'Xóa phương thức vận chuyển thành công.');
    }
}