document.getElementById('liveSearch')?.addEventListener('input', async function () {
    const q = this.value.trim();
    const tbody = document.querySelector('#productTable tbody');
    if (!tbody || q.length < 1) return;
    document.getElementById('searchStatus') && (document.getElementById('searchStatus').textContent = 'Searching...');
    try {
        const res = await fetch(APP.baseUrl + 'api/products/search.php?q=' + encodeURIComponent(q));
        const json = await res.json();
        if (!json.success) return;
        tbody.innerHTML = (json.data || []).map(p => `<tr>
            <td><img class="product-thumb" src="${p.product_image ? (APP.baseUrl + 'uploads/' + p.product_image) : (APP.baseUrl + 'assets/img/product-placeholder.svg')}" alt=""></td>
            <td>${p.product_name}</td><td>${p.barcode || ''}</td><td>${p.category_name || ''}</td>
            <td>₹${Number(p.selling_price).toFixed(2)}</td><td>${p.stock_quantity}</td><td>${p.status}</td><td></td>
        </tr>`).join('');
    } catch (e) {}
});

document.querySelectorAll('[data-deactivate]').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-deactivate');
        const name = btn.getAttribute('data-name');
        const activate = btn.textContent.trim() === 'Activate';
        appConfirm(activate ? 'Activate Product?' : 'Deactivate Product?', name, () => {
            appJson('api/products/delete.php', { id: Number(id), activate: activate ? 1 : 0 });
        });
    });
});
