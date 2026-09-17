@extends('layouts.admin')

@section('title', 'Quản lý phương thức giao hàng')

@section('content')

<div class="bg-white border border-gray-200 rounded-lg shadow-sm">

    <!-- Header -->
    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">

        <div>
            <h2 class="text-lg font-semibold text-gray-800">
                Tất cả phương thức giao hàng
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Quản lý các phương thức giao hàng của BloomGift
            </p>
        </div>

        <a href="{{ route('admin.shipping-methods.create') }}"
           class="inline-flex items-center px-4 py-2
                  bg-red-600 hover:bg-red-700
                  text-white text-sm font-medium
                  rounded-md transition">
            + Thêm phương thức
        </a>

    </div>


    <!-- Success message -->
    @if(session('success'))
        <div class="mx-5 mt-4 px-4 py-3
                    bg-green-50 border border-green-200
                    text-green-700 text-sm rounded-md">
            {{ session('success') }}
        </div>
    @endif


    <!-- Error message -->
    @if(session('error'))
        <div class="mx-5 mt-4 px-4 py-3
                    bg-red-50 border border-red-200
                    text-red-700 text-sm rounded-md">
            {{ session('error') }}
        </div>
    @endif


    <!-- Table -->
    <div class="overflow-x-auto">

        <table class="w-full text-sm">

            <thead class="bg-gray-50 border-b border-gray-200">

                <tr class="text-left text-xs font-semibold text-gray-500 uppercase">

                    <th class="px-5 py-3 w-16">
                        #
                    </th>

                    <th class="px-5 py-3">
                        Tên phương thức
                    </th>

                    <th class="px-5 py-3">
                        Phí giao hàng
                    </th>

                    <th class="px-5 py-3">
                        Mô tả
                    </th>

                    <th class="px-5 py-3">
                        Trạng thái
                    </th>

                    <th class="px-5 py-3">
                        Thao tác
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-100">

                @forelse($shippingMethods as $shippingMethod)

                    <tr class="hover:bg-gray-50 transition">

                        <!-- ID -->
                        <td class="px-5 py-4 text-gray-500">
                            #{{ $shippingMethod->id }}
                        </td>


                        <!-- Name -->
                        <td class="px-5 py-4">

                            <span class="font-medium text-gray-800">
                                {{ $shippingMethod->name }}
                            </span>

                        </td>


                        <!-- Fee -->
                        <td class="px-5 py-4 text-gray-700">

                            {{ number_format($shippingMethod->fee, 0, ',', '.') }} đ

                        </td>


                        <!-- Description -->
                        <td class="px-5 py-4">

                            @if($shippingMethod->description)

                                <span class="text-gray-600">
                                    {{ \Illuminate\Support\Str::limit($shippingMethod->description, 60) }}
                                </span>

                            @else

                                <span class="text-gray-400 text-xs">
                                    Chưa có mô tả
                                </span>

                            @endif

                        </td>


                        <!-- Status -->
                        <td class="px-5 py-4">

                            @if($shippingMethod->is_active)

                                <span class="inline-flex items-center
                                             px-2.5 py-1
                                             rounded-md
                                             bg-green-50
                                             text-green-600
                                             text-xs font-medium">

                                    Đang hoạt động

                                </span>

                            @else

                                <span class="inline-flex items-center
                                             px-2.5 py-1
                                             rounded-md
                                             bg-gray-100
                                             text-gray-500
                                             text-xs font-medium">

                                    Tạm ẩn

                                </span>

                            @endif

                        </td>


                        <!-- Actions -->
                        <td class="px-5 py-4">

                            <div class="flex items-center gap-3">

                                <a href="{{ route('admin.shipping-methods.show', $shippingMethod) }}"
                                   class="text-xs text-gray-600 hover:text-gray-900">
                                    Xem
                                </a>


                                <a href="{{ route('admin.shipping-methods.edit', $shippingMethod) }}"
                                   class="text-xs text-yellow-600 hover:text-yellow-700">
                                    Sửa
                                </a>


                                <form
                                    action="{{ route('admin.shipping-methods.destroy', $shippingMethod) }}"
                                    method="POST"
                                    class="inline"
                                    onsubmit="return confirm('Bạn có chắc muốn xóa phương thức giao hàng này?')"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-xs text-red-600 hover:text-red-700"
                                    >
                                        Xóa
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6"
                            class="px-5 py-10 text-center text-gray-400">

                            Chưa có phương thức giao hàng nào.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <!-- Pagination -->
    @if(method_exists($shippingMethods, 'hasPages') && $shippingMethods->hasPages())

        <div class="px-5 py-4 border-t border-gray-200">

            {{ $shippingMethods->links() }}

        </div>

    @endif

</div>

@endsection
