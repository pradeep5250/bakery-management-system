<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
require_permission(PERM_PRODUCT_EDIT);
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) {
    http_response_code(404);
    exit('Product not found');
}
$cats = db()->query('SELECT id, name FROM categories WHERE status="active" ORDER BY name')->fetchAll();
$error = $success = '';
if (is_post()) {
    if (!verify_csrf_token()) {
        $error = 'Invalid security token.';
    } else {
        try {
            $image = $p['product_image'];
            if (!empty($_POST['remove_image'])) {
                $image = null;
            }
            if (!empty($_FILES['product_image']['name'])) {
                $image = safe_upload_image($_FILES['product_image']);
            }
            db()->prepare(
                'UPDATE products SET product_name=?, barcode=?, category_id=?, description=?, purchase_price=?, selling_price=?, gst=?, unit=?, minimum_stock=?, product_image=?, status=? WHERE id=?'
            )->execute([
                trim((string) $_POST['product_name']),
                trim((string) $_POST['barcode']) ?: null,
                (int) $_POST['category_id'],
                trim((string) $_POST['description']),
                (float) $_POST['purchase_price'],
                (float) $_POST['selling_price'],
                (float) $_POST['gst'],
                trim((string) $_POST['unit']),
                (int) $_POST['minimum_stock'],
                $image,
                $_POST['status'] === 'inactive' ? 'inactive' : 'active',
                $id,
            ]);
            log_activity('PRODUCT_UPDATE', 'products', $id, 'Updated product');
            $success = 'Product updated.';
            $stmt->execute([$id]);
            $p = $stmt->fetch();
        } catch (Throwable $e) {
            $error = str_contains($e->getMessage(), 'Duplicate') ? 'Barcode already exists.' : $e->getMessage();
        }
    }
}
$page_title = 'Edit Product';
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head">
        <div><h1>Edit Product</h1></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-dark" href="<?= e(url('super-admin/products.php')) ?>">Back</a>
            <button class="btn btn-primary" type="submit" form="productEditForm">Save Changes</button>
        </div>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <form class="card-block" id="productEditForm" method="post" enctype="multipart/form-data" style="max-width:760px">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Product image</label>
                <div class="image-edit">
                    <img class="product-photo" id="imagePreview" src="<?= e(product_image_url($p['product_image'] ?? null)) ?>" alt="<?= e($p['product_name']) ?>">
                    <div>
                        <label class="btn btn-primary mb-2" for="productImageInput">Change image</label>
                        <input class="form-control" type="file" name="product_image" id="productImageInput" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                               onchange="(function(el){var img=document.getElementById('imagePreview'); if(el.files&&el.files[0]&&img){img.src=URL.createObjectURL(el.files[0]); var r=document.getElementById('removeImage'); if(r) r.checked=false;}})(this)">
                        <label class="form-check mt-2 mb-0">
                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage">
                            Remove current image
                        </label>
                        <div class="form-text">Choose a JPG, PNG or WEBP (max 2 MB), then click Save Changes.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6"><label class="form-label">Product name</label><input class="form-control" name="product_name" value="<?= e($p['product_name']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Barcode</label><input class="form-control" name="barcode" value="<?= e($p['barcode']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Category</label>
                <select class="form-select" name="category_id">
                    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $c['id']==$p['category_id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="active" <?= $p['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $p['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Purchase price</label><input class="form-control" type="number" step="0.01" name="purchase_price" value="<?= e($p['purchase_price']) ?>"></div>
            <div class="col-md-4"><label class="form-label">Selling price</label><input class="form-control" type="number" step="0.01" name="selling_price" value="<?= e($p['selling_price']) ?>"></div>
            <div class="col-md-4"><label class="form-label">GST %</label><input class="form-control" type="number" step="0.01" name="gst" value="<?= e($p['gst']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Unit</label><input class="form-control" name="unit" value="<?= e($p['unit']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Minimum stock</label><input class="form-control" type="number" name="minimum_stock" value="<?= e((string) ($p['minimum_stock'] ?? 0)) ?>"></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e($p['description']) ?></textarea></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
            <button class="btn btn-primary" type="submit">Save Changes</button>
            <a class="btn btn-outline-dark" href="<?= e(url('super-admin/products.php')) ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
