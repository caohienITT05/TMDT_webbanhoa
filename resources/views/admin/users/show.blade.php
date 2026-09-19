@extends('layouts.admin')
@section('title', 'Chi tiết tài khoản: ' . $user->name)

@section('content')
    <div class="max-w-3xl bg-white rounded-xl shadow-md p-6 border border-gray-200 space-y-6">
        <div class="flex justify-between items-center pb-4 border-b">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
                <p class="text-xs text-gray-500">Ngày tham gia: {{ $user->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <x-admin.button :href="route('admin.users.index')" variant="secondary">Quay lại danh sách</x-admin.button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="p-4 bg-gray-50 rounded-lg">
                <span class="text-gray-500 text-xs block">Địa chỉ Email:</span>
                <span class="font-bold text-gray-900">{{ $user->email }}</span>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <span class="text-gray-500 text-xs block">Số điện thoại:</span>
                <span class="font-bold text-gray-900">{{ $user->phone ?? 'Chưa cập nhật' }}</span>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg md:col-span-2">
                <span class="text-gray-500 text-xs block">Địa chỉ mặc định:</span>
                <span class="font-medium text-gray-900">{{ $user->address ?? 'Chưa cập nhật địa chỉ' }}</span>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <span class="text-gray-500 text-xs block">Vai trò:</span>
                <span class="font-bold text-purple-700 uppercase">{{ $user->role }}</span>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <span class="text-gray-500 text-xs block">Trạng thái tài khoản:</span>
                <span class="font-bold {{ $user->is_active ? 'text-green-600' : 'text-red-600' }}">
                    {{ $user->is_active ? 'Đang hoạt động' : 'Đang bị khóa' }}
                </span>
            </div>
        </div>
    </div>
@endsection
