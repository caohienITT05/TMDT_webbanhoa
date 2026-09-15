@extends('layouts.admin')
@section('title', 'Quản lý tài khoản người dùng')

@section('content')
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-6">Danh sách người dùng & khách hàng</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 border border-gray-100 rounded-lg">
                <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold">
                    <tr>
                        <th class="p-3">Họ và tên</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Số điện thoại</th>
                        <th class="p-3">Vai trò</th>
                        <th class="p-3">Trạng thái</th>
                        <th class="p-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 font-semibold text-gray-900">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->email }}</td>
                            <td class="p-3">{{ $user->phone ?? '—' }}</td>
                            <td class="p-3">
                                <span
                                    class="px-2.5 py-1 text-xs rounded-full font-medium {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <td class="p-3">
                                @if($user->is_active)
                                    <span class="px-2.5 py-1 text-xs rounded-full bg-green-100 text-green-700 font-medium">Hoạt
                                        động</span>
                                @else
                                    <span class="px-2.5 py-1 text-xs rounded-full bg-red-100 text-red-700 font-medium">Bị
                                        khóa</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.toggle', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="text-xs font-semibold px-3 py-1 rounded {{ $user->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100' }}">
                                            {{ $user->is_active ? 'Khóa TK' : 'Mở khóa' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400 italic">Đang online</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>
@endsection