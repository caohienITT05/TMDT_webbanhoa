(() => {
    const source = document.getElementById('finance-breakdown-series');
    if (!source) return;
    const charts = JSON.parse(source.textContent);
    const compactMoney = value => new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 1 }).format(value / 100) + ' đ';
    const fullMoney = value => value === null || value === undefined ? 'Thiếu dữ liệu' : new Intl.NumberFormat('vi-VN').format(value / 100) + ' đ';
    const percent = value => value === null || value === undefined ? 'Thiếu dữ liệu' : new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 }).format(value) + '%';

    function render(canvas, config) {
        const stage = canvas.parentElement;
        const rowCount = config.labels.length;
        stage.style.minWidth = Math.max(420, rowCount * 94 + 78) + 'px';
        const width = stage.clientWidth;
        const height = 224;
        const ratio = window.devicePixelRatio || 1;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        const context = canvas.getContext('2d');
        context.scale(ratio, ratio);
        context.font = '11px system-ui';
        const values = config.series.flatMap(series => series.values).filter(value => value !== null && value !== undefined && Number.isFinite(value));
        const tooltip = document.querySelector(`[data-breakdown-tooltip="${canvas.dataset.chart}"]`);
        if (!rowCount || !values.length) {
            context.fillStyle = '#6b7280';
            context.textAlign = 'center';
            context.fillText('Chưa có dữ liệu đủ căn cứ trong kỳ đã chọn.', width / 2, height / 2);
            return null;
        }

        const left = 62, right = width - 12, top = 36, bottom = 184;
        let min = Math.min(0, ...values), max = Math.max(0, ...values);
        if (min === max) max = min + 1;
        const range = max - min;
        const group = (right - left) / rowCount;
        const seriesCount = config.series.length;
        const barWidth = Math.max(2, Math.min(24, group * 0.68 / seriesCount));
        const y = value => bottom - ((value - min) / range) * (bottom - top);
        const baseline = y(0);
        config.series.forEach((series, index) => {
            const x = 8 + index * Math.min(145, Math.max(78, (width - 20) / Math.max(1, seriesCount)));
            context.fillStyle = series.color;
            context.fillRect(x, 10, 11, 4);
            context.fillStyle = '#374151';
            context.textAlign = 'left';
            context.fillText(series.label, x + 16, 15);
        });
        for (let step = 0; step <= 4; step++) {
            const value = min + range * step / 4;
            const lineY = y(value);
            context.strokeStyle = '#e5e7eb';
            context.beginPath(); context.moveTo(left, lineY); context.lineTo(right, lineY); context.stroke();
            context.fillStyle = '#6b7280'; context.textAlign = 'right';
            const axisValue = config.unit === 'count' ? Math.round(value).toLocaleString('vi-VN') : config.unit === 'percent' ? percent(value) : compactMoney(value);
            context.fillText(axisValue, left - 6, lineY + 4);
        }
        config.labels.forEach((label, rowIndex) => {
            const center = left + group * rowIndex + group / 2;
            config.series.forEach((series, seriesIndex) => {
                const value = series.values[rowIndex];
                if (value === null || value === undefined || !Number.isFinite(value)) return;
                const offset = (seriesIndex - (seriesCount - 1) / 2) * barWidth;
                const valueY = y(value);
                context.fillStyle = series.color;
                context.fillRect(center + offset - barWidth / 2, Math.min(valueY, baseline), barWidth, Math.max(1, Math.abs(baseline - valueY)));
            });
            context.fillStyle = '#6b7280';
            context.textAlign = 'center';
            const shortLabel = label.length > 13 ? label.slice(0, 12) + '…' : label;
            context.fillText(shortLabel, center, bottom + 17);
        });
        return { left, group, tooltip };
    }

    document.querySelectorAll('.finance-breakdown-chart').forEach(canvas => {
        const config = charts[canvas.dataset.chart];
        if (!config) return;
        let layout;
        const draw = () => { layout = render(canvas, config); };
        new ResizeObserver(draw).observe(canvas.parentElement);
        canvas.addEventListener('mousemove', event => {
            if (!layout?.tooltip || !config.labels.length) return;
            const x = event.clientX - canvas.getBoundingClientRect().left;
            const index = Math.max(0, Math.min(config.labels.length - 1, Math.floor((x - layout.left) / layout.group)));
            const total = config.series.reduce((sum, series) => sum + series.values.reduce((subtotal, value) => subtotal + Math.abs(Number(value) || 0), 0), 0);
            const details = config.series.map(series => {
                const value = series.values[index];
                const shown = config.unit === 'count' ? (value === null ? 'Thiếu dữ liệu' : `${value} sản phẩm`) : config.unit === 'percent' ? percent(value) : fullMoney(value);
                const share = config.showShare && config.unit === 'money' && value !== null
                    ? ` (${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 1 }).format(Math.abs(value) / Math.max(1, total) * 100)}%)`
                    : '';
                return `${series.label}: ${shown}${share}`;
            });
            const extra = config.details?.[index] ? ` · ${config.details[index]}` : '';
            layout.tooltip.textContent = `${config.labels[index]} · ${details.join(' · ')}${extra}`;
        });
        canvas.addEventListener('mouseleave', () => {
            if (layout?.tooltip) layout.tooltip.textContent = 'Di chuột để xem số tiền, trạng thái dữ liệu và kỳ đối chiếu.';
        });
        canvas.addEventListener('click', event => {
            if (!config.links?.length || !layout || !config.labels.length) return;
            const x = event.clientX - canvas.getBoundingClientRect().left;
            const index = Math.max(0, Math.min(config.labels.length - 1, Math.floor((x - layout.left) / layout.group)));
            if (config.links[index]) window.location.assign(config.links[index]);
        });
        canvas.style.cursor = config.links?.length ? 'pointer' : 'default';
        draw();
    });
})();