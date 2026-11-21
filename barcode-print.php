<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
require_login();
require_any_permission(PERM_BARCODE_PRINT, PERM_BARCODE_VIEW, PERM_PRINT_CONTROL);

$productId = (int) ($_GET['product_id'] ?? 0);
$code = trim((string) ($_GET['barcode'] ?? ''));
$type = strtoupper(trim((string) ($_GET['type'] ?? 'CODE128')));
$copies = max(1, min(40, (int) ($_GET['copies'] ?? 1)));
$name = trim((string) ($_GET['name'] ?? ''));
$price = (float) ($_GET['price'] ?? 0);
$labels = [];

$batch = $_GET['labels'] ?? '';
if (is_string($batch) && $batch !== '') {
    $decoded = json_decode($batch, true);
    if (is_array($decoded)) {
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowCode = trim((string) ($row['code'] ?? ''));
            if ($rowCode === '') {
                continue;
            }
            $labels[] = [
                'name' => (string) ($row['name'] ?? ''),
                'code' => $rowCode,
                'type' => strtoupper((string) ($row['type'] ?? 'CODE128')),
                'price' => (float) ($row['price'] ?? 0),
            ];
        }
    }
}

if ($labels === []) {
    if ($productId > 0) {
        $stmt = db()->prepare('SELECT product_name, barcode, selling_price FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $p = $stmt->fetch();
        if ($p) {
            if ($name === '') {
                $name = (string) $p['product_name'];
            }
            $price = $price > 0 ? $price : (float) $p['selling_price'];
            if ($code === '') {
                $code = (string) $p['barcode'];
            }
        }
    }

    if ($code === '') {
        http_response_code(400);
        echo 'Barcode not found. Select a product, then click Print Label again.';
        exit;
    }

    for ($i = 0; $i < $copies; $i++) {
        $labels[] = [
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'price' => $price,
        ];
    }
}

$shop = bakery_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Label</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; font-family: Arial, sans-serif; color: #241E1B; }
        .sheet { display: flex; flex-wrap: wrap; gap: 4mm; padding: 6mm; }
        .label {
            width: 58mm;
            min-height: 38mm;
            border: 1px solid #ccc;
            padding: 3.5mm;
            text-align: center;
            page-break-inside: avoid;
        }
        .shop { font-size: 9px; letter-spacing: .12em; text-transform: uppercase; font-weight: 700; color: #6B3E26; }
        .name { font-size: 13px; font-weight: 700; margin: 3px 0 4px; }
        svg { max-width: 100%; height: 46px; }
        .code { font-size: 11px; letter-spacing: .06em; margin-top: 2px; }
        .price { font-size: 14px; font-weight: 700; margin-top: 3px; }
        .toolbar { display: flex; gap: 8px; padding: 10px; background: #F6F0E6; }
        @media print {
            .toolbar { display: none !important; }
            body { margin: 0; }
            .sheet { padding: 0; gap: 3mm; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">Print</button>
    <button type="button" onclick="window.close()">Close</button>
</div>
<div class="sheet">
    <?php foreach ($labels as $i => $row): ?>
        <article class="label">
            <div class="shop"><?= e($shop) ?></div>
            <div class="name"><?= e((string) $row['name']) ?></div>
            <svg class="bc" id="bc<?= $i ?>" data-code="<?= e((string) $row['code']) ?>" data-type="<?= e((string) $row['type']) ?>"></svg>
            <div class="code"><?= e((string) $row['code']) ?></div>
            <div class="price">₹<?= e(number_format((float) $row['price'], 2)) ?></div>
        </article>
    <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
(function () {
    var map = { 'CODE128': 'CODE128', 'EAN-13': 'EAN13', 'EAN-8': 'EAN8', 'UPC-A': 'UPC', 'CODE39': 'CODE39' };
    document.querySelectorAll('.bc').forEach(function (svg) {
        var code = svg.getAttribute('data-code') || '';
        var type = svg.getAttribute('data-type') || 'CODE128';
        var format = map[type] || 'CODE128';
        if (!code || typeof JsBarcode !== 'function') return;
        try {
            JsBarcode(svg, code, { format: format, width: 1.5, height: 44, displayValue: false, margin: 2, background: '#fff', lineColor: '#241E1B' });
        } catch (e) {
            JsBarcode(svg, code, { format: 'CODE128', width: 1.5, height: 44, displayValue: false, margin: 2, background: '#fff', lineColor: '#241E1B' });
        }
    });
    setTimeout(function () { window.print(); }, 400);
})();
</script>
</body>
</html>
