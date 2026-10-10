(() => {
    const source = document.getElementById('finance-comparison-series');
    if (!source) return;

    const series = JSON.parse(source.textContent);
    const money = value => new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 1 }).format(value / 100) + ' đ';
    const fullMoney = value => value === null ? 'Thiếu dữ liệu' : new Intl.NumberFormat('vi-VN').format(value / 100) + ' đ';

    function draw(canvas, rows) {
        const stage = canvas.parentElement;
        stage.style.minWidth = Math.max(640, rows.length * 108 + 78) + 'px';
        const width = stage.clientWidth;
        const height = 288;
        const scale = window.devicePixelRatio || 1;
        canvas.width = width * scale;
        canvas.height = height * scale;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        const context = canvas.getContext('2d');
        context.scale(scale, scale);
        context.font = '11px system-ui';

        const values = rows.flatMap(row => [row.revenue, row.profit]).filter(value => value !== null);
        if (!values.length) {
            context.fillStyle = '#6b7280';
            context.textAlign = 'center';
            context.fillText('Chưa có đủ dữ liệu tài chính để so sánh.', width / 2, height / 2);
            return null;
        }

        const left = 60, right = width - 18, top = 42, bottom = 240;
        const min = Math.min(0, ...values);
        const max = Math.max(100, ...values);
        const range = max - min || 1;
        const y = value => bottom - ((value - min) / range) * (bottom - top);
        const baseline = y(0);
        const groupWidth = (right - left) / rows.length;
        const barWidth = Math.max(4, Math.min(18, groupWidth * 0.28));
        const legends = [['Doanh thu', '#be185d'], ['Lợi nhuận trước thuế', '#047857']];

        legends.forEach(([label, color], index) => {
            const x = 8 + index * 150;
            context.fillStyle = color;
            context.fillRect(x, 12, 12, 4);
            context.fillStyle = '#374151';
            context.textAlign = 'left';
            context.fillText(label, x + 18, 17);
        });

        for (let step = 0; step <= 4; step++) {
            const value = min + (range * step) / 4;
            context.strokeStyle = '#e5e7eb';
            context.beginPath(); context.moveTo(left, y(value)); context.lineTo(right, y(value)); context.stroke();
            context.fillStyle = '#6b7280'; context.textAlign = 'right';
            context.fillText(money(value), left - 7, y(value) + 4);
        }

        rows.forEach((row, index) => {
            const center = left + groupWidth * index + groupWidth / 2;
            [[row.revenue, '#be185d', -barWidth / 2], [row.profit, '#047857', barWidth / 2]].forEach(([value, color, offset]) => {
                if (value === null) return;
                const valueY = y(value);
                context.fillStyle = color;
                context.fillRect(center + offset - barWidth / 2, Math.min(valueY, baseline), barWidth, Math.abs(baseline - valueY));
            });
            context.fillStyle = '#6b7280';
            context.textAlign = 'center';
            context.fillText(row.label, center, bottom + 18);
        });

        return { left, groupWidth };
    }

    document.querySelectorAll('.finance-comparison-chart').forEach(canvas => {
        const rows = series[canvas.dataset.series] || [];
        const tooltip = document.querySelector(`[data-finance-comparison-tooltip="${canvas.dataset.series}"]`);
        let geometry = null;
        const redraw = () => { geometry = draw(canvas, rows); };
        new ResizeObserver(redraw).observe(canvas.parentElement);
        canvas.addEventListener('mousemove', event => {
            if (!geometry || !rows.length || !tooltip) return;
            const x = event.clientX - canvas.getBoundingClientRect().left;
            const index = Math.max(0, Math.min(rows.length - 1, Math.floor((x - geometry.left) / geometry.groupWidth)));
            const row = rows[index];
            const margin = row.margin === null ? 'Thiếu dữ liệu' : new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 }).format(row.margin) + '%';
            tooltip.textContent = `${row.label}: Doanh thu ${fullMoney(row.revenue)} · Lợi nhuận ${fullMoney(row.profit)} · Biên lợi nhuận ${margin}.`;
        });
        canvas.addEventListener('mouseleave', () => {
            if (tooltip) tooltip.textContent = 'Di chuột trên biểu đồ để xem chi tiết từng kỳ.';
        });
        redraw();
    });
})();
