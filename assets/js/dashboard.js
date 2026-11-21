async function fetchDashboard() {
    const res = await fetch(APP.baseUrl + 'api/dashboard/super-admin.php', { headers: { Accept: 'application/json' } });
    const json = await res.json();
    if (!json.success) return;
    const d = json.data;
    const cards = [
        ['Today\'s Sales', '₹' + Number(d.cards.today_sales).toFixed(2), 'bi-currency-rupee'],
        ['Today\'s Bills', d.cards.today_bills, 'bi-receipt'],
        ['Total Products', d.cards.total_products, 'bi-box-seam'],
        ['Total Stock', d.cards.total_stock, 'bi-stack'],
        ['Low Stock', d.cards.low_stock, 'bi-exclamation-triangle'],
        ['Out of Stock', d.cards.out_of_stock, 'bi-x-octagon'],
        ['Active Admins', d.cards.active_admins, 'bi-shield-check'],
        ['Active Billers', d.cards.active_billers, 'bi-person-badge'],
        ['Cash Sales', '₹' + Number(d.cards.cash_sales).toFixed(2), 'bi-cash'],
        ['GPay/UPI Sales', '₹' + Number(d.cards.gpay_sales).toFixed(2), 'bi-phone']
    ];
    document.getElementById('statGrid').innerHTML = cards.map(c => `<div class="stat-card"><i class="bi ${c[2]}"></i><span>${c[0]}</span><strong>${c[1]}</strong></div>`).join('');
    document.getElementById('recentBills').innerHTML = d.recent_bills.map(b => `<li><span>${b.invoice_number}<small class="d-block">${b.biller_name}</small></span><strong>₹${Number(b.total).toFixed(2)}</strong></li>`).join('') || '<li>No bills yet.</li>';
    document.getElementById('recentAct').innerHTML = d.recent_activities.map(a => `<li><span>${a.action}<small class="d-block">${a.description || ''}</small></span></li>`).join('') || '<li>No activity.</li>';
    document.getElementById('topProd').innerHTML = d.top_products.map(p => `<li><span>${p.product_name}</span><strong>${p.qty}</strong></li>`).join('') || '<li>No sales yet.</li>';
    document.getElementById('lowStock').innerHTML = d.low_stock_products.map(p => `<li><span>${p.product_name}</span><strong>${p.stock_quantity}</strong></li>`).join('') || '<li>None</li>';
    document.getElementById('outStock').innerHTML = d.out_of_stock_products.map(p => `<li><span>${p.product_name}</span></li>`).join('') || '<li>None</li>';

    const labels = d.sales_chart.map(r => r.d);
    const values = d.sales_chart.map(r => Number(r.t));
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: { labels, datasets: [{ label: 'Sales', data: values, borderColor: '#6B3E26', backgroundColor: 'rgba(196,163,90,.25)', fill: true, tension: .3 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
    new Chart(document.getElementById('payChart'), {
        type: 'doughnut',
        data: {
            labels: d.payment_chart.map(r => r.name),
            datasets: [{ data: d.payment_chart.map(r => Number(r.t)), backgroundColor: ['#6B3E26', '#C4A35A', '#2C2420', '#E8D5A3'] }]
        }
    });
}
if (document.getElementById('statGrid')) fetchDashboard();
