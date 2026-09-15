<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trang Quản Trị BloomGift (Admin Dashboard)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                <p class="text-lg font-bold text-green-600">Xin chào Quản trị viên: {{ Auth::user()->name }}!</p>
                <p class="mt-2 text-sm text-gray-600">Hệ thống phân quyền Admin & Auth của BloomGift đã hoạt động hoàn
                    tất.</p>
            </div>
        </div>
    </div>
</x-app-layout>