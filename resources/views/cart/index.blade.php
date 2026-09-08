<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - BloomGift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">🛒 Giỏ hàng của bạn</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($cartItems->isEmpty())
        <div class="alert alert-info">Giỏ hàng đang trống.</div>
    @else
        <div class="table-responsive bg-white p-3 shadow-sm rounded">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartItems as $item)
                        <tr>
                            <td>
                                <strong>ID Sản phẩm: {{ $item->product_id }}</strong>
                            </td>
                            <td style="width: 150px;">
                                <form action="{{ route('cart.update', $item->id) }}" method="POST" class="d-flex">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" class="form-control me-2">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Lưu</button>
                                </form>
                            </td>
                            <td>
                                <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="text-end mt-3">
                <a href="#" class="btn btn-success btn-lg">Tiến hành Thanh toán ➔</a>
            </div>
        </div>
    @endif

    <!-- Form giả lập thêm sản phẩm để test -->
    <div class="card mt-5">
        <div class="card-header bg-secondary text-white">🧪 Thử nghiệm thêm sản phẩm vào giỏ</div>
        <div class="card-body">
            <form action="{{ route('cart.add') }}" method="POST" class="row g-3 align-items-center">
                @csrf
                <div class="col-auto">
                    <label class="col-form-label">Product ID:</label>
                    <input type="number" name="product_id" value="1" class="form-control" required>
                </div>
                <div class="col-auto">
                    <label class="col-form-label">Số lượng:</label>
                    <input type="number" name="quantity" value="1" min="1" class="form-control" required>
                </div>
                <div class="col-auto mt-4">
                    <button type="submit" class="btn btn-primary">Thêm vào giỏ</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>