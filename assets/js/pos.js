(function () {
    const search = document.getElementById('productSearch');
    const results = document.getElementById('productResults');
    const status = document.getElementById('searchStatus');
    let t = null;

    function imgSrc(p) {
        return p.product_image
            ? (APP.baseUrl + 'uploads/' + p.product_image)
            : (APP.baseUrl + 'assets/img/product-placeholder.svg');
    }

    function renderProducts(rows) {
        if (!results) return;
        if (!rows.length) {
            results.innerHTML = '<p class="text-muted mb-0">No products found.</p>';
            return;
        }
        results.innerHTML = rows.map((p, i) => {
            const out = Number(p.stock_quantity) <= 0;
            return `<div class="product-hit${out ? ' is-out' : ''}">
                <img class="product-thumb" src="${imgSrc(p)}" alt="">
                <div>
                    <strong>${p.product_name}</strong>
                    <small class="d-block">${p.barcode || ''} · Stock ${p.stock_quantity}</small>
                    <div class="product-price">₹${Number(p.selling_price).toFixed(2)}</div>
                </div>
                <button type="button" class="btn btn-primary btn-select" data-idx="${i}" ${out ? 'disabled' : ''}>
                    ${out ? 'Out of stock' : 'Select'}
                </button>
            </div>`;
        }).join('');
        results.querySelectorAll('.btn-select').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const idx = Number(btn.dataset.idx);
                if (POSCart.add(rows[idx])) {
                    toast(rows[idx].product_name + ' added to cart');
                }
            });
        });
    }

    async function loadProducts(q) {
        if (!results) return;
        status.textContent = 'Loading products...';
        try {
            const res = await fetch(APP.baseUrl + 'api/products/search.php?q=' + encodeURIComponent(q || ''));
            const json = await res.json();
            status.textContent = json.success ? 'Select a product to add it to the cart.' : (json.message || '');
            renderProducts(json.data || []);
        } catch (e) {
            status.textContent = 'Server unavailable.';
        }
    }

    search?.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => loadProducts(search.value.trim()), 200);
    });

    loadProducts('');

    setInterval(() => {
        const el = document.getElementById('clock');
        if (el) el.textContent = new Date().toLocaleString('en-IN');
    }, 1000);
    const banner = document.getElementById('offlineBanner');
    function sync() { banner?.classList.toggle('d-none', navigator.onLine); }
    window.addEventListener('online', sync);
    window.addEventListener('offline', sync);
    sync();
})();
