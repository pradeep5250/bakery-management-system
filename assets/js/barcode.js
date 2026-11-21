(function () {
    const product = document.getElementById('bcProduct');
    const value = document.getElementById('bcValue');
    const typeEl = document.getElementById('bcType');
    const svg = document.getElementById('barcodeSvg');
    if (!product) return;

    const formatMap = {
        'CODE128': 'CODE128',
        'EAN-13': 'EAN13',
        'EAN-8': 'EAN8',
        'UPC-A': 'UPC',
        'CODE39': 'CODE39'
    };

    function money(n) {
        return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function canDraw() {
        return typeof JsBarcode === 'function';
    }

    function drawBarcode(target, code, type) {
        if (!canDraw() || !code) return;
        const format = formatMap[type] || 'CODE128';
        const options = {
            format: format,
            width: 1.6,
            height: 48,
            displayValue: false,
            margin: 4,
            background: '#ffffff',
            lineColor: '#241E1B'
        };
        try {
            JsBarcode(target, code, options);
        } catch (e) {
            try { JsBarcode(target, code, Object.assign({}, options, { format: 'CODE128' })); } catch (err) {}
        }
    }

    function currentItem() {
        const opt = product.options[product.selectedIndex];
        return {
            id: opt ? opt.value : '',
            name: opt ? opt.dataset.name : '',
            code: (value && value.value.trim()) || (opt && opt.dataset.code) || '',
            price: opt ? opt.dataset.price : '0',
            type: typeEl ? typeEl.value : 'CODE128'
        };
    }

    function printUrl(item, copies) {
        const copiesCount = Math.max(1, Math.min(40, Number(copies || 1)));
        return (window.APP && APP.baseUrl ? APP.baseUrl : './') + 'barcode-print.php'
            + '?product_id=' + encodeURIComponent(item.id || '')
            + '&barcode=' + encodeURIComponent(item.code || '')
            + '&type=' + encodeURIComponent(item.type || 'CODE128')
            + '&name=' + encodeURIComponent(item.name || '')
            + '&price=' + encodeURIComponent(item.price || '0')
            + '&copies=' + encodeURIComponent(String(copiesCount));
    }

    function preview() {
        const item = currentItem();
        const nameEl = document.getElementById('labelName');
        const codeEl = document.getElementById('labelCode');
        const priceEl = document.getElementById('labelPrice');
        const printBtn = document.getElementById('printLabelBtn');
        const copies = document.getElementById('labelCopies')?.value || 1;
        if (nameEl) nameEl.textContent = item.name;
        if (codeEl) codeEl.textContent = item.code;
        if (priceEl) priceEl.textContent = money(item.price);
        if (printBtn && printBtn.tagName === 'A') printBtn.href = printUrl(item, copies);
        if (item.code && svg) drawBarcode('#barcodeSvg', item.code, item.type);
    }

    function openPrint(items) {
        if (!items.length || !items[0].code) {
            alert('Generate or enter a barcode first.');
            return;
        }
        const first = items[0];
        const copies = items.length;
        const url = printUrl(first, copies);
        const w = window.open(url, 'barcode_label_print', 'width=720,height=640,scrollbars=yes');
        if (!w) {
            window.location.href = url;
        }
    }

    product.addEventListener('change', () => {
        const opt = product.options[product.selectedIndex];
        if (value) value.value = opt.dataset.code || '';
        preview();
    });
    value?.addEventListener('input', preview);
    typeEl?.addEventListener('change', preview);
    document.getElementById('labelCopies')?.addEventListener('input', preview);
    preview();

    document.getElementById('barcodeForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        data.is_primary = e.target.querySelector('[name=is_primary]')?.checked ? 1 : 0;
        const json = await appJson('api/barcode/generate.php', data);
        if (json.success && json.data && json.data.barcode && value) {
            value.value = json.data.barcode;
            if (typeEl && json.data.barcode_type) typeEl.value = json.data.barcode_type;
            preview();
        }
    });

    document.getElementById('printLabelBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        const item = currentItem();
        const copies = Math.max(1, Math.min(40, Number(document.getElementById('labelCopies')?.value || 1)));
        openPrint(Array.from({ length: copies }, () => item));
    });

    document.getElementById('printSelectedBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        const picked = [...document.querySelectorAll('.label-pick:checked')];
        if (!picked.length) {
            alert('Select at least one barcode from the table.');
            return;
        }
        const labels = picked.map((el) => ({
            name: el.dataset.name || '',
            code: el.dataset.code || '',
            type: el.dataset.type || 'CODE128',
            price: el.dataset.price || '0'
        }));
        const url = (window.APP && APP.baseUrl ? APP.baseUrl : './') + 'barcode-print.php'
            + '?labels=' + encodeURIComponent(JSON.stringify(labels));
        window.open(url, 'barcode_label_print', 'width=720,height=640,scrollbars=yes');
    });
})();
