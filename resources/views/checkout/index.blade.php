<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - BloomGift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">🌸 Thanh toán Đơn hàng</h2>

    <form action="{{ route('checkout.process') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Thông tin giao hàng -->
            <div class="col-md-7">
                <div class="card p-4 shadow-sm mb-4">
                    <h5 class="mb-3">Thông tin người nhận</h5>
                    <div class="mb-3">
                        <label class="form-label">Họ và tên *</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="text" name="customer_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ nhận hàng *</label>
                        <input type="text" name="customer_address" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú giao hàng</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="card p-4 shadow-sm mb-4">
                    <h5 class="mb-3">Dịch vụ đi kèm (Hoa & Quà)</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày giao hàng</label>
                            <input type="date" name="delivery_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Khung giờ giao</label>
                            <select name="delivery_time_slot" class="form-select">
                                <option value="8h-12h">Sáng (8h00 - 12h00)</option>
                                <option value="13h-17h">Chiều (13h00 - 17h00)</option>
                                <option value="18h-21h">Tối (18h00 - 21h00)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lời chúc trên thiệp</label>
                        <textarea name="gift_card_message" class="form-control" rows="2" placeholder="Nhập lời chúc muốn gửi tới người nhận..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Tóm tắt đơn hàng & Thanh toán -->
            <div class="col-md-5">
                <div class="card p-4 shadow-sm">
                    <h5 class="mb-3">Tóm tắt đơn hàng</h5>
                    <ul class="list-group mb-3">
                        @foreach($cartItems as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Sản phẩm ID: {{ $item->product_id }}</h6>
                                    <small class="text-muted">Số lượng: {{ $item->quantity }}</small>
                                </div>
                                <span>{{ number_format($item->quantity * ($item->product->price ?? 100000)) }} đ</span>
                            </li>
                        @endforeach
                        <li class="list-group-item d-flex justify-content-between fw-bold bg-light">
                            <span>Tổng tiền:</span>
                            <span class="text-danger">{{ number_format($total) }} đ</span>
                        </li>
                    </ul>

                    <h5 class="mb-3">Phương thức thanh toán</h5>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" value="COD" id="cod" checked>
                        <label class="form-check-label" for="cod">Thanh toán khi nhận hàng (COD)</label>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="radio" name="payment_method" value="PAYPAL" id="paypal">
                        <label class="form-check-label" for="paypal">Thanh toán qua PayPal Sandbox</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">Xác nhận đặt hàng</button>
                </div>
            </div>
        </div>
    </form>
</div>
</body>
</html>