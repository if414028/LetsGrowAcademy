const colors = ['#4f46e5', '#64748b', '#0f766e', '#9333ea', '#c2410c', '#0369a1', '#be185d', '#475569'];
const number = new Intl.NumberFormat('id-ID');

export function totalSeries(labels, datasets) {
    return labels.map((_, index) => datasets.reduce((sum, dataset) => sum + Number(dataset.data[index] ?? 0), 0));
}

export function wrapTooltipText(text, width, measure) {
    const lines = [];
    let line = '';
    for (const character of text) {
        if (line && measure(line + character) > width) {
            const space = line.lastIndexOf(' ');
            if (space > 0) {
                lines.push(line.slice(0, space));
                line = line.slice(space + 1);
            } else {
                lines.push(line);
                line = '';
            }
        }
        line += character;
    }
    if (line.trim()) lines.push(line.trim());
    return lines;
}

export function initSalesTrend() {
    const root = document.querySelector('[data-sales-trend]');
    const canvas = root?.querySelector('canvas');
    if (!canvas) return;
    if (!window.Chart) {
        root.querySelector('[data-trend-error]').hidden = false;
        root.querySelector('.sales-trend__plot').hidden = true;
        return;
    }

    const { labels, datasets } = JSON.parse(root.querySelector('[data-trend-config]').textContent);
    const fontFamily = getComputedStyle(root).fontFamily;
    const fontSize = parseFloat(getComputedStyle(root).fontSize) * 0.8125;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const legend = root.querySelector('[data-trend-legend]');
    const view = root.querySelector('[data-trend-view]');
    const displayedSeries = () => view?.value === 'total'
        ? [{ label: 'Total penjualan', data: totalSeries(labels, datasets) }]
        : datasets;

    const chartSeries = (series) => series.map((dataset, index) => ({
        ...dataset,
        borderColor: colors[index % colors.length],
        borderWidth: index === 0 ? 2.5 : 2,
        borderDash: index === 0 ? [] : [6 + index * 2, 4],
        cubicInterpolationMode: 'monotone',
        tension: 0.35,
        pointRadius: labels.length === 1 ? 4 : 0,
        pointHoverRadius: 5,
        pointHitRadius: 18,
        pointBackgroundColor: '#ffffff',
        pointBorderWidth: 2,
        fill: index === 0,
        backgroundColor: (context) => {
            const { ctx, chartArea } = context.chart;
            if (!chartArea) return 'rgba(79, 70, 229, 0.08)';
            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, 'rgba(79, 70, 229, 0.14)');
            gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');
            return gradient;
        },
    }));

    const drawLegend = () => {
        legend.replaceChildren();
        chart.data.datasets.forEach((dataset, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'sales-trend__legend-item';
            button.setAttribute('aria-pressed', String(chart.isDatasetVisible(index)));
            button.setAttribute('aria-label', `Tampilkan seri ${dataset.label}`);
            const swatch = document.createElement('span');
            swatch.className = 'sales-trend__swatch';
            swatch.style.borderColor = dataset.borderColor;
            swatch.style.borderTopStyle = index === 0 ? 'solid' : 'dashed';
            swatch.setAttribute('aria-hidden', 'true');
            const label = document.createElement('span');
            label.textContent = dataset.label === 'Units' ? 'Unit terjual' : dataset.label;
            button.append(swatch, label);
            button.addEventListener('click', () => {
                const visible = !chart.isDatasetVisible(index);
                chart.setDatasetVisibility(index, visible);
                button.setAttribute('aria-pressed', String(visible));
                chart.update();
            });
            legend.append(button);
        });
    };

    window.__salesTrendChart?.destroy();
    const chart = new window.Chart(canvas, {
        type: 'line',
        data: { labels, datasets: chartSeries(displayedSeries()) },
        plugins: [{
            id: 'sales-trend-guide',
            beforeDatasetsDraw(chart) {
                const active = chart.getActiveElements();
                if (!active.length) return;
                const { ctx, chartArea } = chart;
                const x = active[0].element.x;
                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([4, 5]);
                ctx.lineWidth = 1;
                ctx.strokeStyle = '#94a3b8';
                ctx.moveTo(x, chartArea.top);
                ctx.lineTo(x, chartArea.bottom);
                ctx.stroke();
                ctx.restore();
            },
        }],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion.matches ? false : { duration: 500 },
            font: { family: fontFamily },
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 16, right: 10 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#071a2d',
                    padding: 14,
                    cornerRadius: 10,
                    bodySpacing: 7,
                    titleMarginBottom: 10,
                    usePointStyle: true,
                    displayColors: false,
                    titleFont: { family: fontFamily, size: fontSize, weight: '600' },
                    bodyFont: { family: fontFamily, size: fontSize },
                    callbacks: {
                        label: (context) => {
                            const text = `${context.dataset.label === 'Units' ? 'Unit terjual' : context.dataset.label}: ${number.format(context.parsed.y)} unit`;
                            const ctx = context.chart.ctx;
                            ctx.save();
                            ctx.font = `${fontSize}px ${fontFamily}`;
                            const lines = wrapTooltipText(text, Math.max(40, context.chart.width - 40), (line) => ctx.measureText(line).width);
                            ctx.restore();
                            return lines;
                        },
                    },
                },
            },
            scales: {
                x: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: { color: '#64748b', maxRotation: 0, maxTicksLimit: 8, padding: 14, font: { family: fontFamily, size: fontSize } },
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: Math.max(1, ...datasets.flatMap((dataset) => dataset.data)),
                    border: { display: false },
                    grid: { color: '#e9edf2', drawTicks: false },
                    ticks: { precision: 0, maxTicksLimit: 5, padding: 14, color: '#64748b', font: { family: fontFamily, size: fontSize } },
                },
            },
        },
    });
    window.__salesTrendChart = chart;
    drawLegend();
    view?.addEventListener('change', () => {
        chart.data.datasets = chartSeries(displayedSeries());
        chart.data.datasets.forEach((_, index) => chart.setDatasetVisibility(index, true));
        chart.update();
        drawLegend();
    });
    reducedMotion.addEventListener('change', () => {
        chart.options.animation = reducedMotion.matches ? false : { duration: 500 };
        chart.update('none');
    });
}
