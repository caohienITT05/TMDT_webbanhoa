(() => {
    const canvas = document.getElementById('finance-chart');
    const source = document.getElementById('finance-series');
    if (!canvas || !source) return;
    const rows = Object.entries(JSON.parse(source.textContent));
    const fields = ['shipping_cost_actual', 'packaging_cost_actual', 'payment_gateway_fee_actual', 'other_direct_cost'];
    const displayDate = value => {
        const [, month, day] = value.split('-');
        return `${day}/${month}`;
    };
    const series = [
        { label: 'Doanh thu thuần', color: '#be185d', value: row => row.net_product_revenue + row.shipping_income },
        { label: 'Giá vốn', color: '#7c3aed', value: row => row.cogs },
        { label: 'Chi phí trực tiếp khác', color: '#b45309', value: row => fields.some(key => row[key] === null) ? null : fields.reduce((total, key) => total + row[key], 0) },
        { label: 'Lợi nhuận trực tiếp', color: '#047857', value: row => row.order_profit },
    ];
    function draw() {
        const width = canvas.parentElement.clientWidth;
        const height = 288;
        const scale = window.devicePixelRatio || 1;
        canvas.width = width * scale;
        canvas.height = height * scale;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);
        ctx.font = '11px system-ui';
        const left = 80, right = width - 25, top = 60, bottom = 250;
        series.forEach((item, index) => {
            const x = 8 + (index % 2) * (width / 2), y = 15 + Math.floor(index / 2) * 20;
            ctx.fillStyle = item.color;
            ctx.fillRect(x, y - 7, 12, 3);
            ctx.fillText(item.label, x + 18, y);
        });
        const values = series.flatMap(item => rows.map(([, row]) => item.value(row))).filter(value => value !== null);
        const min = Math.min(0, ...values), max = Math.max(100, ...values), range = max - min;
        const y = value => bottom - (value - min) / range * (bottom - top);
        const x = index => rows.length < 2 ? (left + right) / 2 : left + index / (rows.length - 1) * (right - left);
        for (let step = 0; step <= 4; step++) {
            const value = min + range * step / 4;
            ctx.strokeStyle = '#e5e7eb'; ctx.beginPath(); ctx.moveTo(left, y(value)); ctx.lineTo(right, y(value)); ctx.stroke();
            ctx.fillStyle = '#6b7280'; ctx.textAlign = 'right';
            ctx.fillText(new Intl.NumberFormat('vi', { notation: 'compact' }).format(value / 100), left - 8, y(value) + 4);
        }
        ctx.textAlign = 'center';
        rows.forEach(([date], index) => {
            if (index === 0 || index === rows.length - 1 || index % Math.max(1, Math.ceil(rows.length / 5)) === 0) ctx.fillText(displayDate(date), x(index), bottom + 20);
        });
        series.forEach(item => {
            ctx.strokeStyle = item.color; ctx.fillStyle = item.color; ctx.lineWidth = 2;
            let previous = null;
            rows.forEach(([, row], index) => {
                const value = item.value(row);
                if (value === null) { previous = null; return; }
                const point = [x(index), y(value)];
                if (previous) { ctx.beginPath(); ctx.moveTo(...previous); ctx.lineTo(...point); ctx.stroke(); }
                ctx.beginPath(); ctx.arc(...point, 3, 0, Math.PI * 2); ctx.fill(); previous = point;
            });
        });
        if (!rows.length) { ctx.fillStyle = '#6b7280'; ctx.fillText('Chưa có dữ liệu', width / 2, 155); }
        ctx.textAlign = 'left';
    }
    new ResizeObserver(draw).observe(canvas.parentElement);
})();
