<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_PRODUCT_CREATE);
$cats = db()->query('SELECT id, name FROM categories WHERE status="active" ORDER BY name')->fetchAll();
$error = $success = '';
if (is_post()) {
    if (!verify_csrf_token()) {
        $error = 'Invalid security token.';
    } else {
        try {
            $name = trim((string) $_POST['product_name']);
            $barcode = trim((string) ($_POST['barcode'] ?? ''));
            $categoryId = (int) $_POST['category_id'];
            $selling = (float) $_POST['selling_price'];
            if ($name === '' || $selling <= 0 || $categoryId <= 0) {
                throw new RuntimeException('Product name, category and selling price are required.');
            }
            $image = !empty($_FILES['product_image']['name']) ? safe_upload_image($_FILES['product_image']) : null;
            $stock = max(0, (int) ($_POST['stock_quantity'] ?? 0));
            $pdo = db();
            $pdo->beginTransaction();
            $pdo->prepare(
                'INSERT INTO products (product_name, barcode, category_id, description, purchase_price, selling_price, gst, unit, stock_quantity, minimum_stock, product_image, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active")'
            )->execute([
                $name,
                $barcode !== '' ? $barcode : null,
                $categoryId,
                trim((string) ($_POST['description'] ?? '')),
                (float) ($_POST['purchase_price'] ?? 0),
                $selling,
                (float) ($_POST['gst'] ?? 0),
                trim((string) ($_POST['unit'] ?? 'pcs')) ?: 'pcs',
                $stock,
                max(0, (int) ($_POST['minimum_stock'] ?? 0)),
                $image,
            ]);
            $id = (int) $pdo->lastInsertId();
            if ($barcode !== '') {
                $pdo->prepare('INSERT INTO product_barcodes (product_id, barcode, barcode_type, is_primary, created_by) VALUES (?,?,"CODE128",1,?)')
                    ->execute([$id, $barcode, (int) $_SESSION['user_id']]);
            }
            if ($stock > 0) {
                $pdo->prepare('INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, new_stock, reference_type, reference_id, reason, created_by) VALUES (?,"PURCHASE",?,0,?, "product", ?, "Initial stock", ?)')
                    ->execute([$id, $stock, $stock, $id, (int) $_SESSION['user_id']]);
            }
            $pdo->commit();
            log_activity('PRODUCT_CREATE', 'products', $id, 'Created product ' . $name);
            notify_role('super_admin', 'Product Added', $name . ' was added.', 'success');
            $success = 'Product added successfully.';
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = str_contains($e->getMessage(), 'Duplicate') ? 'Barcode already exists.' : $e->getMessage();
        }
    }
}
$page_title = 'Add Product';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head">
        <div><h1>Add Product</h1></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-dark" href="<?= e(url('super-admin/products.php')) ?>">Back</a>
            <button class="btn btn-primary" type="submit" form="productAddForm">Save Product</button>
        </div>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <form class="card-block" id="productAddForm" method="post" enctype="multipart/form-data" style="max-width:760px">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Product name</label><input class="form-control" name="product_name" required></div>
            <div class="col-md-6"><label class="form-label">Barcode</label><input class="form-control" name="barcode"></div>
            <div class="col-md-6"><label class="form-label">Category</label>
                <select class="form-select" name="category_id" required>
                    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Unit</label><input class="form-control" name="unit" value="pcs"></div>
            <div class="col-md-4"><label class="form-label">Purchase price</label><input class="form-control" type="number" step="0.01" name="purchase_price" required></div>
            <div class="col-md-4"><label class="form-label">Selling price</label><input class="form-control" type="number" step="0.01" name="selling_price" required></div>
            <div class="col-md-4"><label class="form-label">GST %</label><input class="form-control" type="number" step="0.01" name="gst" value="0"></div>
            <div class="col-md-6"><label class="form-label">Stock</label><input class="form-control" type="number" name="stock_quantity" value="0" min="0"></div>
            <div class="col-md-6"><label class="form-label">Minimum stock</label><input class="form-control" type="number" name="minimum_stock" value="5" min="0"></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
            <div class="col-12">
                <label class="form-label">Product image</label>
                <div class="image-edit">
                    <img class="product-photo" id="imagePreview" src="<?= e(product_image_url(null)) ?>" alt="Preview">
                    <div>
                        <label class="btn btn-primary mb-2" for="productImageInput">Choose image</label>
                        <input class="form-control" type="file" name="product_image" id="productImageInput" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <div class="form-text">Choose a JPG, PNG or WEBP (max 2 MB), then click Save Product.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
            <button class="btn btn-primary" type="submit">Save Product</button>
            <a class="btn btn-outline-dark" href="<?= e(url('super-admin/products.php')) ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
