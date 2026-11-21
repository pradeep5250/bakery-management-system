<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_BILLER);
require_permission(PERM_BILL_CREATE);
$user = current_user();
$methods = db()->query("SELECT * FROM payment_methods WHERE status='active' ORDER BY id")->fetchAll();
$customers = db()->query("SELECT id, name, phone FROM customers ORDER BY id DESC LIMIT 40")->fetchAll();
$page_title = 'Bakery POS';
$body_class = 'app-body pos-page';
$extra_js = [
    'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
    asset('js/cart.js'),
    asset('js/scanner.js'),
    asset('js/payment.js'),
    asset('js/pos.js'),
];
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head">
        <div>
            <p class="eyebrow mb-1">BAKERY POS</p>
            <h1><?= e(bakery_name()) ?></h1>
            <p><?= e($user['name']) ?> · <span id="clock"><?= e(date('d-m-Y h:i A')) ?></span></p>
        </div>
        <a class="btn btn-outline-dark" href="<?= e(url('logout.php')) ?>">Logout</a>
    </div>
    <div id="offlineBanner" class="alert alert-danger d-none">Server connection unavailable. Please reconnect before completing the bill.</div>
    <div class="pos-layout">
        <section class="pos-left">
            <div class="pos-toolbar">
                <input class="form-control" id="productSearch" placeholder="Search product" autocomplete="off">
                <input class="form-control" id="barcodeInput" placeholder="Barcode / USB scanner" autofocus>
                <button class="btn btn-gold" type="button" id="scanBtn"><i class="bi bi-upc-scan"></i> SCAN</button>
            </div>
            <div id="searchStatus" class="small text-muted mb-2">Select a product to add it to the cart.</div>
            <div class="product-results" id="productResults"></div>
        </section>
        <aside class="pos-right">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">CART</h2>
                <button class="btn btn-sm btn-outline-danger" id="clearCart">Clear</button>
            </div>
            <label class="form-label">Customer (optional)</label>
            <select class="form-select mb-3" id="customerId">
                <option value="0">Walk-in Customer</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?><?= $c['phone'] ? ' · '.e($c['phone']) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <div id="cartItems"></div>
            <div class="pos-summary mt-3">
                <div class="pos-totals">
                    <div><span>Subtotal</span><strong id="tSub">₹0.00</strong></div>
                    <div><span>Discount</span><input class="form-control form-control-sm" style="max-width:120px" id="billDiscount" type="number" min="0" step="0.01" value="0"></div>
                    <div><span>GST</span><strong id="tGst">₹0.00</strong></div>
                    <div class="grand-total"><span>Grand Total</span><span id="tGrand">₹0.00</span></div>
                </div>
                <div class="pay-grid">
                    <?php foreach ($methods as $m): ?>
                        <button type="button" class="btn btn-outline-dark pay-btn" data-code="<?= e($m['code']) ?>"><?= e($m['name']) ?></button>
                    <?php endforeach; ?>
                </div>
                <p class="small text-muted mb-2">Selected: <strong id="payLabel">None</strong></p>
                <button class="btn btn-primary w-100" id="checkoutBtn">GENERATE BILL</button>
            </div>
        </aside>
    </div>
    <div class="sticky-checkout card-block mt-3">
        <div class="d-flex justify-content-between align-items-center">
            <div><small>TOTAL</small><div class="grand-total" id="tGrandSticky">₹0.00</div></div>
            <button class="btn btn-primary" id="checkoutBtnMobile">CHECKOUT / GENERATE BILL</button>
        </div>
    </div>
</main>

<div class="modal fade" id="cashModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Cash Payment</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <p>Total: <strong id="cashTotal">₹0.00</strong></p>
        <label class="form-label">Cash Received</label>
        <input class="form-control" id="cashReceived" type="number" min="0" step="0.01">
        <p class="mt-3 mb-0">Change: <strong id="cashChange">₹0.00</strong></p>
        <div class="alert alert-danger mt-2 d-none" id="cashErr">Insufficient payment amount.</div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary" id="cashComplete">COMPLETE PAYMENT</button></div>
</div></div></div>

<div class="modal fade" id="upiModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">GPay / UPI</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <p>Amount: <strong id="upiAmount">₹0.00</strong></p>
        <p>Payment Status: <span class="badge badge-low" id="upiStatus">PENDING</span></p>
        <label class="form-label">Transaction reference</label>
        <input class="form-control" id="upiRef" placeholder="UPI / GPay reference">
        <div class="alert alert-info mt-2">Confirm the customer payment on the UPI app, then mark as paid. Do not mark success unless payment is received.</div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-danger" id="upiFail">FAILED</button>
        <button class="btn btn-primary" id="upiPaid">MARK PAID &amp; GENERATE</button>
    </div>
</div></div></div>

<div class="modal fade" id="scannerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Scan Barcode</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div id="qrReader"></div></div>
    <div class="modal-footer"><button class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel Scanner</button></div>
</div></div></div>
<?php require INCLUDES_PATH . '/footer.php'; ?>
