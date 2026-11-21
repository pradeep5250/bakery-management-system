<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role(ROLE_SUPER_ADMIN);
$page_title = 'Dashboard';
$extra_js = [asset('js/dashboard.js')];
require INCLUDES_PATH . '/header.php';
?>
<main class="app-content">
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p><?= e(bakery_name()) ?> · <?= e(date('d-m-Y h:i A')) ?></p>
        </div>
    </div>
    <div class="stat-grid" id="statGrid"></div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-block chart-card"><h2 class="h5">Sales (last 7 days)</h2><canvas id="salesChart"></canvas></div>
        </div>
        <div class="col-lg-4">
            <div class="card-block chart-card"><h2 class="h5">Payment methods</h2><canvas id="payChart"></canvas></div>
        </div>
        <div class="col-lg-6"><div class="card-block"><h2 class="h5">Recent Bills</h2><ul class="list-compact" id="recentBills"></ul></div></div>
        <div class="col-lg-6"><div class="card-block"><h2 class="h5">Recent Activities</h2><ul class="list-compact" id="recentAct"></ul></div></div>
        <div class="col-lg-4"><div class="card-block"><h2 class="h5">Top Selling</h2><ul class="list-compact" id="topProd"></ul></div></div>
        <div class="col-lg-4"><div class="card-block"><h2 class="h5">Low Stock</h2><ul class="list-compact" id="lowStock"></ul></div></div>
        <div class="col-lg-4"><div class="card-block"><h2 class="h5">Out of Stock</h2><ul class="list-compact" id="outStock"></ul></div></div>
    </div>
</main>
<?php require INCLUDES_PATH . '/footer.php'; ?>
