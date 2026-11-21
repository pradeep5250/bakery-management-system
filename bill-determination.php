<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
$page_title = 'Bill Determination';
$guest = true;
$extra_css = [asset('css/landing.css'), asset('css/pos.css')];
$extra_js = [asset('js/app.js')];
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content" style="padding-left:16px">
    <div class="page-head">
        <div>
            <a class="back-link" href="<?= e(url('index.php')) ?>">&larr; Back to Main</a>
            <h1 class="mt-2">Bill Determination</h1>
            <p>Independent billing calculation, preview and print.</p>
        </div>
    </div>
    <div class="pos-layout">
        <section class="pos-left">
            <label class="form-label">Search product</label>
            <input class="form-control mb-3" id="bdSearch" placeholder="Name or barcode">
            <div id="bdResults" class="product-results mb-3"></div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <input class="form-control" id="bdCustomer">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment</label>
                    <select class="form-select" id="bdPay">
                        <option value="CASH">Cash</option>
                        <option value="GPAY">GPay / UPI</option>
                    </select>
                </div>
            </div>
        </section>
        <aside class="pos-right">
            <h2 class="h5">Bill information</h2>
            <div id="bdCart"></div>
            <div class="pos-summary mt-3">
                <div class="pos-totals">
                    <div><span>Subtotal</span><strong id="bdSub">₹0.00</strong></div>
                    <div><span>Discount</span><strong id="bdDisc">₹0.00</strong></div>
                    <div><span>GST</span><strong id="bdGst">₹0.00</strong></div>
                    <div class="grand-total"><span>Grand Total</span><span id="bdGrand">₹0.00</span></div>
                </div>
                <button class="btn btn-primary w-100 mt-3" id="bdSave">Save &amp; Preview</button>
                <button class="btn btn-outline-dark w-100 mt-2" onclick="window.print()">Print</button>
            </div>
        </aside>
    </div>
    <div id="bdPreview" class="card-block mt-4 print-a4 d-none"></div>
</main>
<script>
const cart = [];
const money = n => '₹' + Number(n).toFixed(2);
function render() {
    let sub=0, disc=0, gst=0;
    document.getElementById('bdCart').innerHTML = cart.map((i,idx) => {
        const line = i.qty * i.price;
        const g = (line - i.discount) * (i.gst/100);
        sub += line; disc += i.discount; gst += g;
        return `<div class="cart-item"><div><strong>${i.name}</strong><div class="qty-ctrl mt-1">
            <button type="button" onclick="chg(${idx},-1)">-</button><span>${i.qty}</span>
            <button type="button" onclick="chg(${idx},1)">+</button>
            <button class="btn btn-sm btn-outline-danger" onclick="cart.splice(${idx},1);render()">Remove</button>
        </div></div><div>${money(line - i.discount + g)}</div></div>`;
    }).join('') || '<p class="text-muted">No items yet.</p>';
    document.getElementById('bdSub').textContent = money(sub);
    document.getElementById('bdDisc').textContent = money(disc);
    document.getElementById('bdGst').textContent = money(gst);
    document.getElementById('bdGrand').textContent = money(sub - disc + gst);
}
function chg(i,d){ cart[i].qty = Math.max(1, cart[i].qty + d); render(); }
document.getElementById('bdSearch').addEventListener('input', async e => {
    const q = e.target.value.trim();
    if (q.length < 2) return;
    const res = await fetch(APP.baseUrl + 'api/bill-determination/search.php?q=' + encodeURIComponent(q));
    const json = await res.json();
    document.getElementById('bdResults').innerHTML = (json.data||[]).map(p =>
        `<div class="product-hit" data-p='${JSON.stringify(p).replace(/'/g,'&#39;')}'>
            <div></div><div><strong>${p.product_name}</strong><small class="d-block">${money(p.selling_price)}</small></div>
            <button class="btn btn-sm btn-primary">Add</button></div>`
    ).join('');
});
document.getElementById('bdResults').addEventListener('click', e => {
    const hit = e.target.closest('.product-hit');
    if (!hit) return;
    const p = JSON.parse(hit.dataset.p);
    cart.push({name:p.product_name, qty:1, price:Number(p.selling_price), gst:Number(p.gst), discount:0});
    render();
});
document.getElementById('bdSave').addEventListener('click', async () => {
    const payload = {
        csrf_token: APP.csrf,
        customer_name: document.getElementById('bdCustomer').value,
        payment_method: document.getElementById('bdPay').value,
        items: cart.map(i => ({product_name:i.name, quantity:i.qty, unit_price:i.price, gst:i.gst, discount:i.discount}))
    };
    const res = await fetch(APP.baseUrl + 'api/bill-determination/save.php', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':APP.csrf},
        body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (!json.success) { alert(json.message); return; }
    const prev = document.getElementById('bdPreview');
    prev.classList.remove('d-none');
    prev.innerHTML = `<h3><?= e(bakery_name()) ?></h3><p>Bill Determination ${json.data.reference_number}</p>
        <p>Grand Total <strong>${money(json.data.total)}</strong></p><p><?= e(get_setting('thank_you_message','Thank You. Visit Again.')) ?></p>`;
});
render();
</script>
<?php require INCLUDES_PATH . '/footer.php'; ?>
