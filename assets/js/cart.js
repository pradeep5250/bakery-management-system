window.POSCart = (function () {
    const items = [];
    function money(n) { return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function totals() {
        const billDisc = Number(document.getElementById('billDiscount')?.value || 0);
        let subtotal = 0, gst = 0, discount = 0;
        items.forEach(i => {
            const line = i.qty * i.price;
            const d = Math.min(i.discount || 0, line);
            gst += (line - d) * (i.gst / 100);
            subtotal += line;
            discount += d;
        });
        discount += billDisc;
        const grand = Math.max(0, subtotal - billDisc + gst);
        return { subtotal, discount, gst, grand, billDisc };
    }
    function render() {
        const box = document.getElementById('cartItems');
        if (!box) return;
        box.innerHTML = items.map((i, idx) => {
            const line = i.qty * i.price;
            const g = (line - (i.discount || 0)) * (i.gst / 100);
            return `<div class="cart-item">
                <div>
                    <strong>${i.name}</strong>
                    <div class="small text-muted">${i.barcode || ''} · ${money(i.price)}</div>
                    <div class="qty-ctrl mt-2">
                        <button type="button" class="qty-btn" data-chg="${idx}" data-d="-1" aria-label="Decrease">−</button>
                        <span class="qty-num">${i.qty}</span>
                        <button type="button" class="qty-btn" data-chg="${idx}" data-d="1" aria-label="Increase">+</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-del="${idx}">Remove</button>
                    </div>
                </div>
                <div class="cart-line">${money(line - (i.discount || 0) + g)}</div>
            </div>`;
        }).join('') || '<p class="text-muted">Cart is empty.</p>';
        const t = totals();
        ['tSub'].forEach(() => {});
        const sub = document.getElementById('tSub');
        const gstEl = document.getElementById('tGst');
        const grand = document.getElementById('tGrand');
        const sticky = document.getElementById('tGrandSticky');
        if (sub) sub.textContent = money(t.subtotal);
        if (gstEl) gstEl.textContent = money(t.gst);
        if (grand) grand.textContent = money(t.grand);
        if (sticky) sticky.textContent = money(t.grand);
    }
    function add(product) {
        const stock = Number(product.stock_quantity);
        const existing = items.find(i => i.id === Number(product.id));
        if (existing) {
            if (existing.qty + 1 > stock) { toast('Only ' + stock + ' units available.', 'error'); return false; }
            existing.qty += 1;
        } else {
            if (stock <= 0) { toast('Product is out of stock.', 'error'); return false; }
            items.push({
                id: Number(product.id),
                name: product.product_name,
                barcode: product.barcode,
                qty: 1,
                price: Number(product.selling_price),
                gst: Number(product.gst || 0),
                discount: 0,
                stock
            });
        }
        render();
        return true;
    }
    function change(idx, d) {
        const item = items[idx];
        if (!item) return;
        const next = item.qty + d;
        if (next <= 0) { items.splice(idx, 1); render(); return; }
        if (next > item.stock) { toast('Only ' + item.stock + ' units available.', 'error'); return; }
        item.qty = next;
        render();
    }
    document.addEventListener('click', (e) => {
        const chg = e.target.closest('[data-chg]');
        if (chg) change(Number(chg.dataset.chg), Number(chg.dataset.d));
        const del = e.target.closest('[data-del]');
        if (del) { items.splice(Number(del.dataset.del), 1); render(); }
    });
    document.getElementById('clearCart')?.addEventListener('click', () => { items.splice(0, items.length); render(); });
    document.getElementById('billDiscount')?.addEventListener('input', render);
    render();
    return { items, add, render, totals, money };
})();
