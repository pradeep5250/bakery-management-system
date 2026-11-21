<?php
declare(strict_types=1);

function render_barcode_page(): void
{
    require_permission(PERM_BARCODE_VIEW);
    $products = db()->query('SELECT id, product_name, barcode, selling_price FROM products WHERE status="active" ORDER BY product_name')->fetchAll();
    $codes = db()->query(
        'SELECT pb.*, p.product_name, p.selling_price FROM product_barcodes pb INNER JOIN products p ON p.id = pb.product_id ORDER BY pb.id DESC LIMIT 100'
    )->fetchAll();
    $page_title = 'Barcode';
    $extra_js = [asset('js/barcode.js')];
    require INCLUDES_PATH . '/header.php';
    ?>
    <main class="app-content barcode-page">
        <div class="page-head no-print">
            <div>
                <h1>Barcode Labels</h1>
                <p>Generate a sticker, then print only the label.</p>
            </div>
        </div>
        <div class="row g-4 no-print">
            <div class="col-lg-5">
                <form class="card-block" id="barcodeForm">
                    <?= csrf_field() ?>
                    <label class="form-label">Product</label>
                    <select class="form-select mb-3" name="product_id" id="bcProduct" required>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"
                                data-code="<?= e((string) $p['barcode']) ?>"
                                data-name="<?= e($p['product_name']) ?>"
                                data-price="<?= e((string) $p['selling_price']) ?>">
                                <?= e($p['product_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label">Type</label>
                    <select class="form-select mb-3" name="barcode_type" id="bcType">
                        <option value="CODE128">CODE128</option>
                        <option value="EAN-13">EAN-13</option>
                        <option value="EAN-8">EAN-8</option>
                        <option value="UPC-A">UPC-A</option>
                        <option value="CODE39">CODE39</option>
                    </select>
                    <label class="form-label">Barcode value</label>
                    <input class="form-control mb-3" name="barcode" id="bcValue">
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_primary" value="1" checked>
                        Primary barcode
                    </label>
                    <label class="form-label">Copies</label>
                    <input class="form-control mb-3" type="number" id="labelCopies" min="1" max="40" value="1">
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (has_permission(PERM_BARCODE_GENERATE)): ?>
                            <button class="btn btn-primary" type="submit">Generate</button>
                        <?php endif; ?>
                        <a class="btn btn-gold" id="printLabelBtn" href="<?= e(url('barcode-print.php')) ?>" target="_blank" rel="noopener">Print Label</a>
                        <button class="btn btn-outline-dark" type="button" id="printSelectedBtn">Print Selected</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-7">
                <div class="card-block">
                    <p class="eyebrow mb-3">Label preview</p>
                    <div class="label-preview-wrap">
                        <article class="barcode-label" id="labelPreview" data-shop="<?= e(bakery_name()) ?>">
                            <div class="bl-shop" id="labelShop"><?= e(bakery_name()) ?></div>
                            <div class="bl-name" id="labelName"></div>
                            <svg id="barcodeSvg"></svg>
                            <div class="bl-code" id="labelCode"></div>
                            <div class="bl-price" id="labelPrice"></div>
                        </article>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-wrap mt-4 no-print">
            <table class="table table-app" id="barcodeTable">
                <thead>
                <tr>
                    <th></th>
                    <th>Product</th>
                    <th>Barcode</th>
                    <th>Type</th>
                    <th>Primary</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($codes as $c): ?>
                    <tr>
                        <td><input type="checkbox" class="form-check-input label-pick"
                                   data-name="<?= e($c['product_name']) ?>"
                                   data-code="<?= e($c['barcode']) ?>"
                                   data-price="<?= e((string) $c['selling_price']) ?>"
                                   data-type="<?= e($c['barcode_type']) ?>"></td>
                        <td><?= e($c['product_name']) ?></td>
                        <td><?= e($c['barcode']) ?></td>
                        <td><?= e($c['barcode_type']) ?></td>
                        <td><?= $c['is_primary'] ? 'Yes' : 'No' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="printLabels" class="label-print-area" aria-hidden="true"></div>
    </main>
    <?php
    require INCLUDES_PATH . '/footer.php';
}
