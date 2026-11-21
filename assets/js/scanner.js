window.POSScan = (function () {
    let html5Qr = null;
    async function lookup(code) {
        const res = await fetch(APP.baseUrl + 'api/barcode/lookup.php?barcode=' + encodeURIComponent(code), { headers: { Accept: 'application/json' } });
        const json = await res.json();
        if (!json.success) { toast(json.message || 'Product not found.', 'error'); return; }
        POSCart.add(json.data);
        toast(json.data.product_name + ' added');
    }
    async function startCamera() {
        const modal = new bootstrap.Modal(document.getElementById('scannerModal'));
        modal.show();
        if (typeof Html5Qrcode === 'undefined') { toast('Scanner library not loaded.', 'error'); return; }
        html5Qr = new Html5Qrcode('qrReader');
        try {
            await html5Qr.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: 220 },
                async (text) => {
                    await html5Qr.stop();
                    modal.hide();
                    lookup(text);
                }
            );
        } catch (e) {
            toast('Camera permission denied or unavailable.', 'error');
        }
        document.getElementById('scannerModal').addEventListener('hidden.bs.modal', async () => {
            try { if (html5Qr) await html5Qr.stop(); } catch (e) {}
        }, { once: true });
    }
    document.getElementById('scanBtn')?.addEventListener('click', startCamera);
    const input = document.getElementById('barcodeInput');
    let timer = null;
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const code = input.value.trim();
            if (code) { lookup(code); input.value = ''; }
        }
    });
    input?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const code = input.value.trim();
            if (code.length >= 8) { lookup(code); input.value = ''; }
        }, 120);
    });
    return { lookup, startCamera };
})();
