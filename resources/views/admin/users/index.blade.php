@extends('layouts.admin')
@section('title', 'Quản lý khách hàng & Tài khoản')

@section('content')
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-4 border-b border-gray-100">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Quản lý Tài khoản</h2>
                <p class="text-sm text-gray-500">Xem danh sách người dùng, khách hàng và quản lý quyền truy cập</p>
            </div>
        </div>

        <!-- Bộ lọc và Tìm kiếm -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tìm tên, email, SĐT..."
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-rose-500">

            <select name="role" class="rounded-lg border border-gray-300 px-4 py-2 text-sm bg-white">
                <option value="">-- Tất cả vai trò --</option>
                <option value="customer" {{ request('role') == 'customer' ? 'selected' : '' }}>Khách hàng (Customer)</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Quản trị viên (Admin)</option>
            </select>

            <select name="status" class="rounded-lg border border-gray-300 px-4 py-2 text-sm bg-white">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Bị khóa</option>
            </select>

            <div class="flex gap-2">
                <x-admin.button type="submit" variant="primary">Lọc</x-admin.button>
                <x-admin.button :href="route('admin.users.index')" variant="secondary">Đặt lại</x-admin.button>
            </div>
        </form>

        <!-- Bảng người dùng -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 border border-gray-200 rounded-lg">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-bold border-b border-gray-200">
                    <tr>
                        <th class="p-3.5">Họ và tên</th>
                        <th class="p-3.5">Email</th>
                        <th class="p-3.5">Số điện thoại</th>
                        <th class="p-3.5">Vai trò</th>
                        <th class="p-3.5">Trạng thái</th>
                        <th class="p-3.5 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-rose-50/40 transition">
                            <td class="p-3.5 font-bold text-gray-900">{{ $user->name }}</td>
                            <td class="p-3.5">{{ $user->email }}</td>
                            <td class="p-3.5 font-mono">{{ $user->phone ?? '—' }}</td>
                            <td class="p-3.5">
                                <span
                                    class="admin-badge {{ $user->role === 'admin' ? 'admin-badge--progress' : 'admin-badge--info' }}">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <td class="p-3.5">
                                @if($user->is_active)
                                    <span class="admin-badge admin-badge--success">Hoạt động</span>
                                @else
                                    <span class="admin-badge admin-badge--danger">Bị khóa</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="admin-table-actions">
                                    <x-admin.button :href="route('admin.users.show', $user)" variant="detail" size="sm">Chi tiết</x-admin.button>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.toggle', $user) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Bạn có chắc muốn thay đổi trạng thái tài khoản này?');">
                                            @csrf
                                            @method('PATCH')
                                            <x-admin.button type="submit" :variant="$user->is_active ? 'danger' : 'secondary'" size="sm">
                                                {{ $user->is_active ? 'Khóa' : 'Mở khóa' }}
                                            </x-admin.button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400">Không tìm thấy người dùng nào phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4"><x-admin.pagination :paginator="$users" item-label="tài khoản" /></div>
    </div>
@endsection
