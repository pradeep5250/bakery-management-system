window.POSPay = (function () {
    let method = '';
    document.querySelectorAll('.pay-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            method = btn.dataset.code;
            document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('btn-primary'));
            btn.classList.remove('btn-outline-dark');
            btn.classList.add('btn-primary');
            document.getElementById('payLabel').textContent = btn.textContent.trim();
        });
    });
    async function generate(extra) {
        if (!navigator.onLine) {
            toast('Server connection unavailable. Please reconnect before completing the bill.', 'error');
            return;
        }
        if (!POSCart.items.length) { toast('Cart is empty.', 'error'); return; }
        if (!method) { toast('Select a payment method.', 'error'); return; }
        const t = POSCart.totals();
        const payload = Object.assign({
            csrf_token: APP.csrf,
            items: POSCart.items.map(i => ({ product_id: i.id, quantity: i.qty, discount: i.discount || 0 })),
            customer_id: Number(document.getElementById('customerId')?.value || 0),
            discount: t.billDisc,
            payment_method: method
        }, extra || {});
        document.getElementById('checkoutBtn').disabled = true;
        try {
            const res = await fetch(APP.baseUrl + 'api/sales/create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': APP.csrf },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (!json.success) { toast(json.message || 'Unable to generate bill.', 'error'); return; }
            toast('Bill generated: ' + json.data.invoice_number);
            location.href = APP.baseUrl + 'biller/invoice.php?id=' + json.data.sale_id;
        } catch (e) {
            toast('Server connection unavailable. Please reconnect before completing the bill.', 'error');
        } finally {
            document.getElementById('checkoutBtn').disabled = false;
        }
    }
    function openCheckout() {
        if (!POSCart.items.length) { toast('Cart is empty.', 'error'); return; }
        if (!method) { toast('Select a payment method.', 'error'); return; }
        const t = POSCart.totals();
        if (method === 'CASH') {
            document.getElementById('cashTotal').textContent = POSCart.money(t.grand);
            document.getElementById('cashReceived').value = t.grand.toFixed(2);
            document.getElementById('cashChange').textContent = POSCart.money(0);
            document.getElementById('cashErr').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('cashModal')).show();
        } else {
            document.getElementById('upiAmount').textContent = POSCart.money(t.grand);
            document.getElementById('upiStatus').textContent = 'PENDING';
            document.getElementById('upiRef').value = '';
            new bootstrap.Modal(document.getElementById('upiModal')).show();
        }
    }
    document.getElementById('cashReceived')?.addEventListener('input', () => {
        const t = POSCart.totals().grand;
        const rec = Number(document.getElementById('cashReceived').value || 0);
        const change = rec - t;
        document.getElementById('cashChange').textContent = POSCart.money(Math.max(0, change));
        document.getElementById('cashErr').classList.toggle('d-none', rec >= t);
    });
    document.getElementById('cashComplete')?.addEventListener('click', () => {
        const t = POSCart.totals().grand;
        const rec = Number(document.getElementById('cashReceived').value || 0);
        if (rec < t) { document.getElementById('cashErr').classList.remove('d-none'); return; }
        generate({ cash_received: rec });
    });
    document.getElementById('upiPaid')?.addEventListener('click', () => {
        const ref = document.getElementById('upiRef').value.trim();
        if (!ref) { toast('Transaction reference is required.', 'error'); return; }
        document.getElementById('upiStatus').textContent = 'PAID';
        generate({ upi_status: 'PAID', transaction_reference: ref });
    });
    document.getElementById('upiFail')?.addEventListener('click', () => {
        document.getElementById('upiStatus').textContent = 'FAILED';
        toast('Payment failed. The bill was not generated.', 'error');
    });
    document.getElementById('checkoutBtn')?.addEventListener('click', openCheckout);
    document.getElementById('checkoutBtnMobile')?.addEventListener('click', openCheckout);
    return { generate, getMethod: () => method };
})();
