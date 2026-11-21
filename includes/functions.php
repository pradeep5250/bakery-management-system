<?php
declare(strict_types=1);

function app_log(string $message): void
{
    $file = (defined('LOGS_PATH') ? LOGS_PATH : dirname(__DIR__) . '/logs') . '/php-errors.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

function secure_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-XSS-Protection: 1; mode=block');
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        enforce_session_timeout();
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('BAKERYSESSID');
    session_start();
    enforce_session_timeout();
}

function enforce_session_timeout(): void
{
    $timeout = (int) (get_setting('session_timeout', (string) SESSION_TIMEOUT) ?: SESSION_TIMEOUT);
    if (!empty($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $timeout) {
        $_SESSION = [];
        session_destroy();
        start_app_session();
        return;
    }
    $_SESSION['last_activity'] = time();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if (!verify_csrf_token()) {
        json_response(false, 'Invalid security token. Please refresh and try again.', null, 403);
    }
}

function json_response(bool $success, string $message, mixed $data = null, int $code = 200): void
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $path): void
{
    if (!preg_match('#^https?://#i', $path)) {
        $path = BASE_URL . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function user_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

function detect_device(?string $ua = null): string
{
    $ua = strtolower($ua ?? user_agent());
    if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
        return 'Mobile';
    }
    if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
        return 'Tablet';
    }
    if (str_contains($ua, 'chrome')) {
        return 'Chrome Desktop';
    }
    if (str_contains($ua, 'firefox')) {
        return 'Firefox Desktop';
    }
    if (str_contains($ua, 'edg')) {
        return 'Edge Desktop';
    }
    if (str_contains($ua, 'safari')) {
        return 'Safari Desktop';
    }
    return 'Desktop';
}

function money_inr(float|int|string $amount): string
{
    $n = (float) $amount;
    $formatted = number_format($n, 2, '.', '');
    [$int, $dec] = explode('.', $formatted);
    $len = strlen($int);
    if ($len > 3) {
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $int = $rest . ',' . $last3;
    }
    return '₹' . $int . '.' . $dec;
}

function format_date(?string $datetime, string $withTime = 'datetime'): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '—';
    }
    if ($withTime === 'date') {
        return date('d-m-Y', $ts);
    }
    if ($withTime === 'time') {
        return date('h:i A', $ts);
    }
    return date('d-m-Y h:i A', $ts);
}

function get_setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            if (function_exists('db')) {
                $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
                foreach ($rows as $row) {
                    $cache[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function bakery_name(): string
{
    return get_setting('bakery_name', 'Golden Crust Bakery') ?: 'Golden Crust Bakery';
}

function current_user(bool $refresh = false): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = false;
    if ($refresh) {
        $user = false;
    }
    if ($user !== false) {
        return $user;
    }
    try {
        $stmt = db()->prepare(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        $user = null;
    }
    return $user;
}

function log_activity(string $action, string $module, ?int $referenceId = null, string $description = ''): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO activity_logs (user_id, role, action, module, reference_id, description, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? null,
            $action,
            $module,
            $referenceId,
            $description,
            client_ip(),
            user_agent(),
        ]);
    } catch (Throwable $e) {
        app_log('Activity log failed: ' . $e->getMessage());
    }
}

function notify(?int $userId, string $title, string $message, string $type = 'info'): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $message, $type]);
    } catch (Throwable $e) {
        app_log('Notification failed: ' . $e->getMessage());
    }
}

function notify_role(string $roleName, string $title, string $message, string $type = 'info'): void
{
    try {
        $stmt = db()->prepare(
            'SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE r.name = ? AND u.status = "active"'
        );
        $stmt->execute([$roleName]);
        foreach ($stmt->fetchAll() as $row) {
            notify((int) $row['id'], $title, $message, $type);
        }
    } catch (Throwable $e) {
        app_log('Role notify failed: ' . $e->getMessage());
    }
}

function unread_notification_count(): int
{
    $user = current_user();
    if (!$user) {
        return 0;
    }
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (user_id = ? OR user_id IS NULL)'
    );
    $stmt->execute([(int) $user['id']]);
    return (int) $stmt->fetchColumn();
}

function generate_invoice_number(PDO $pdo, string $prefix = 'INV'): string
{
    $prefix = get_setting('invoice_prefix', $prefix) ?: $prefix;
    $date = date('Ymd');
    $like = $prefix . '-' . $date . '-%';
    $stmt = $pdo->prepare(
        'SELECT invoice_number FROM sales WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();
    $seq = 1;
    if ($last && preg_match('/-(\d+)$/', (string) $last, $m)) {
        $seq = ((int) $m[1]) + 1;
    }
    return sprintf('%s-%s-%04d', $prefix, $date, $seq);
}

function generate_bd_number(PDO $pdo): string
{
    $date = date('Ymd');
    $like = 'BD-' . $date . '-%';
    $stmt = $pdo->prepare(
        'SELECT reference_number FROM bill_determinations WHERE reference_number LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();
    $seq = 1;
    if ($last && preg_match('/-(\d+)$/', (string) $last, $m)) {
        $seq = ((int) $m[1]) + 1;
    }
    return sprintf('BD-%s-%04d', $date, $seq);
}

function calc_item_totals(float $unitPrice, int $qty, float $gstPercent, float $discount = 0.0): array
{
    $itemTotal = round($unitPrice * $qty, 2);
    $discount = min($discount, $itemTotal);
    $taxable = $itemTotal - $discount;
    $gst = round($taxable * ($gstPercent / 100), 2);
    $total = round($taxable + $gst, 2);
    return [
        'item_total' => $itemTotal,
        'discount' => $discount,
        'gst' => $gst,
        'total' => $total,
    ];
}

function stock_status(int $qty, int $min): string
{
    if ($qty <= 0) {
        return 'OUT OF STOCK';
    }
    if ($qty <= $min) {
        return 'LOW STOCK';
    }
    return 'IN STOCK';
}

function stock_status_class(string $status): string
{
    return match ($status) {
        'OUT OF STOCK' => 'badge-out',
        'LOW STOCK' => 'badge-low',
        default => 'badge-ok',
    };
}

function safe_upload_image(array $file, string $subdir = 'products'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }
    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Image must be 2 MB or smaller.');
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) {
        throw new RuntimeException('Only JPG, PNG and WEBP images are allowed.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_MIME, true)) {
        throw new RuntimeException('Invalid image file type.');
    }

    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $dir = UPLOADS_PATH . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Unable to save uploaded image.');
    }
    return $subdir . '/' . $name;
}

function pagination(int $total, int $page, int $perPage = 15): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'pages' => $pages,
        'offset' => ($page - 1) * $perPage,
    ];
}

function date_range_from_filter(string $filter, ?string $from = null, ?string $to = null): array
{
    $today = date('Y-m-d');
    return match ($filter) {
        'yesterday' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
        'week', 'last7' => [date('Y-m-d', strtotime('-6 days')), $today],
        'month' => [date('Y-m-01'), $today],
        'last_month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        'custom' => [
            $from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : $today,
            $to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : $today,
        ],
        default => [$today, $today],
    };
}

function login_rate_limited(string $username): bool
{
    $key = 'login_attempts_' . md5($username . client_ip());
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'until' => 0];
    }
    if ($_SESSION[$key]['until'] > $now) {
        return true;
    }
    return false;
}

function record_login_attempt(string $username, bool $success): void
{
    $key = 'login_attempts_' . md5($username . client_ip());
    if ($success) {
        unset($_SESSION[$key]);
        return;
    }
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'until' => 0];
    }
    $_SESSION[$key]['count']++;
    if ($_SESSION[$key]['count'] >= 5) {
        $_SESSION[$key]['until'] = time() + 300;
        $_SESSION[$key]['count'] = 0;
    }
}

function asset(string $path): string
{
    return ASSETS_URL . ltrim($path, '/');
}

function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function active_nav(string $needle): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, $needle) ? 'active' : '';
}

function role_home(string $role): string
{
    return match ($role) {
        ROLE_SUPER_ADMIN => 'super-admin/dashboard.php',
        ROLE_ADMIN => 'admin/dashboard.php',
        ROLE_BILLER => 'biller/pos.php',
        default => 'index.php',
    };
}

function ensure_billing_history_controls(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo = db();
        $pdo->exec(
            "INSERT IGNORE INTO permissions (permission_name, description, module)
             VALUES ('BILL_HISTORY_VIEW', 'View own billing history (limited invoices)', 'billing')"
        );
        $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_key = setting_key'
        )->execute(['biller_history_limit', '5']);
    } catch (Throwable $e) {
        app_log('ensure_billing_history_controls: ' . $e->getMessage());
    }
}

function biller_history_limit(): int
{
    $limit = (int) (get_setting('biller_history_limit', '5') ?: '5');
    return max(1, min(100, $limit));
}

function save_app_setting(string $key, string $value): void
{
    db()->prepare(
        'INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
    )->execute([$key, $value, (int) ($_SESSION['user_id'] ?? 0)]);
}

function biller_recent_sale_ids(int $billerId, ?int $limit = null): array
{
    $limit = $limit ?? biller_history_limit();
    $stmt = db()->prepare('SELECT id FROM sales WHERE biller_id = ? ORDER BY id DESC LIMIT ' . (int) $limit);
    $stmt->execute([$billerId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function biller_can_open_sale(int $saleId, int $billerId): bool
{
    $ids = biller_recent_sale_ids($billerId, biller_history_limit());
    if ($ids === []) {
        return false;
    }
    if (!in_array($saleId, $ids, true)) {
        return false;
    }
    if (has_permission(PERM_BILL_HISTORY_VIEW)) {
        return true;
    }
    return $saleId === $ids[0];
}

function permission_label(string $name, ?string $fallback = null): string
{
    $labels = [
        'PRODUCT_VIEW' => 'View products',
        'PRODUCT_CREATE' => 'Add products',
        'PRODUCT_EDIT' => 'Edit products',
        'PRODUCT_DELETE' => 'Remove products',
        'CATEGORY_VIEW' => 'View categories',
        'CATEGORY_CREATE' => 'Add categories',
        'CATEGORY_EDIT' => 'Edit categories',
        'CATEGORY_DELETE' => 'Delete categories',
        'STOCK_VIEW' => 'View stock',
        'STOCK_CREATE' => 'Add stock',
        'STOCK_EDIT' => 'Edit stock',
        'STOCK_ADJUST' => 'Adjust stock',
        'BARCODE_VIEW' => 'View barcodes',
        'BARCODE_GENERATE' => 'Generate barcodes',
        'BARCODE_PRINT' => 'Print barcodes',
        'BILL_CREATE' => 'Create bills',
        'BILL_PRINT' => 'Print bills',
        'BILL_REPRINT' => 'Reprint bills',
        'BILL_CANCEL' => 'Cancel bills',
        'BILL_HISTORY_VIEW' => 'View billing history',
        'SALES_VIEW' => 'View sales',
        'SALES_EXPORT' => 'Export sales',
        'PAYMENT_VIEW' => 'View payments',
        'PAYMENT_CONFIG' => 'Set up payment methods',
        'CUSTOMER_VIEW' => 'View customers',
        'CUSTOMER_CREATE' => 'Add customers',
        'CUSTOMER_EDIT' => 'Edit customers',
        'REPORT_VIEW' => 'View reports',
        'REPORT_EXPORT' => 'Export reports',
        'USER_VIEW' => 'View users',
        'USER_CREATE' => 'Add users',
        'USER_EDIT' => 'Edit users',
        'USER_DELETE' => 'Deactivate users',
        'HISTORY_VIEW' => 'View activity logs',
        'LOGIN_HISTORY_VIEW' => 'View login history',
        'PRINT_CONTROL' => 'Control printing',
        'SETTINGS_VIEW' => 'View settings',
        'SETTINGS_EDIT' => 'Edit settings',
    ];
    if (isset($labels[$name])) {
        return $labels[$name];
    }
    if ($fallback !== null && $fallback !== '') {
        return $fallback;
    }
    return ucwords(strtolower(str_replace('_', ' ', $name)));
}

function permission_module_label(string $module): string
{
    return match ($module) {
        'products' => 'Products',
        'categories' => 'Categories',
        'stock' => 'Stock',
        'barcode' => 'Barcode',
        'billing' => 'Billing',
        'sales' => 'Sales',
        'payments' => 'Payments',
        'customers' => 'Customers',
        'reports' => 'Reports',
        'users' => 'Users',
        'history' => 'History',
        'print' => 'Print',
        'settings' => 'Settings',
        default => ucfirst($module !== '' ? $module : 'Other'),
    };
}

function grouped_permissions(array $perms, array $hideNames = []): array
{
    $order = ['products','categories','stock','barcode','billing','sales','payments','customers','reports','users','history','print','settings'];
    $groups = [];
    foreach ($perms as $perm) {
        $name = (string) ($perm['permission_name'] ?? '');
        if (in_array($name, $hideNames, true)) {
            continue;
        }
        $module = (string) ($perm['module'] ?? 'other');
        $groups[$module][] = $perm;
    }
    $sorted = [];
    foreach ($order as $module) {
        if (!empty($groups[$module])) {
            $sorted[$module] = $groups[$module];
            unset($groups[$module]);
        }
    }
    foreach ($groups as $module => $items) {
        $sorted[$module] = $items;
    }
    return $sorted;
}

function product_image_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path !== '' && is_file(UPLOADS_PATH . '/' . $path)) {
        return UPLOADS_URL . $path;
    }
    return BASE_URL . 'assets/img/product-placeholder.svg';
}

function ensure_sample_product_images(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $dir = UPLOADS_PATH . '/products';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $palette = [
        'Chocolate Cake' => ['#4A2C1A', '#C4A35A', 'Cake'],
        'Vanilla Cake' => ['#F3E2B8', '#8A5436', 'Cake'],
        'Black Forest Cake' => ['#3B1C1C', '#E8C9C4', 'Cake'],
        'Bread' => ['#D9B483', '#6B3E26', 'Bread'],
        'Veg Puff' => ['#E8C36A', '#3D6B4F', 'Puff'],
        'Egg Puff' => ['#E7A45A', '#6B3E26', 'Puff'],
        'Chocolate Cookie' => ['#6B3E26', '#F3E2B8', 'Cookie'],
        'Butter Cookie' => ['#E8C36A', '#6B3E26', 'Cookie'],
        'Donut' => ['#E8A0B0', '#6B3E26', 'Donut'],
        'Croissant' => ['#E0A45A', '#4A2C1A', 'Pastry'],
        'Tea' => ['#7A8F4A', '#F6F0E6', 'Tea'],
        'Coffee' => ['#3D2A22', '#C4A35A', 'Coffee'],
        "Lay's Classic Salted" => ['#E0A800', '#C41E3A', 'Chips'],
        "Lay's Magic Masala" => ['#C41E3A', '#F3E2B8', 'Chips'],
        'Kurkure Masala Munch' => ['#E85D04', '#FFD166', 'Kurkure'],
        'Bingo Mad Angles' => ['#2D6A4F', '#F4A261', 'Bingo'],
        'Balaji Wafers' => ['#1D3557', '#F4A261', 'Balaji'],
        'Bikaji Bhujia' => ['#9B2226', '#EE9B00', 'Bikaji'],
        'Uncle Chipps' => ['#40916C', '#D8F3DC', 'Chips'],
        "Haldiram's Aloo Bhujia" => ['#BC4749', '#F2E8CF', 'Namkeen'],
        'Too Yumm Karare' => ['#0077B6', '#90E0EF', 'Snack'],
        'Cornitos Nacho Chips' => ['#E9C46A', '#264653', 'Nachos'],
        'Parle Monaco' => ['#264653', '#E9C46A', 'Biscuit'],
        'Cheetos Puffs' => ['#E63900', '#FFD166', 'Cheetos'],
        'Act II Butter Popcorn' => ['#D62828', '#F4A261', 'Popcorn'],
        'Yellow Diamond Chips' => ['#FFD60A', '#3D2B1F', 'Chips'],
    ];
    try {
        $rows = db()->query('SELECT id, product_name, product_image FROM products')->fetchAll();
    } catch (Throwable $e) {
        return;
    }
    $upd = db()->prepare('UPDATE products SET product_image = ? WHERE id = ?');
    foreach ($rows as $row) {
        $existing = trim((string) ($row['product_image'] ?? ''));
        if ($existing !== '' && is_file(UPLOADS_PATH . '/' . $existing)) {
            continue;
        }
        $name = (string) $row['product_name'];
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?: 'product');
        $rel = 'products/' . $slug . '.svg';
        $abs = UPLOADS_PATH . '/' . $rel;
        $style = $palette[$name] ?? ['#8A5436', '#F3E2B8', 'Item'];
        if (!is_file($abs)) {
            create_product_svg($abs, $name, $style[0], $style[1], $style[2]);
        }
        if (is_file($abs)) {
            $upd->execute([$rel, (int) $row['id']]);
        }
    }
}

function create_product_svg(string $path, string $title, string $bg, string $fg, string $tag): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $safeTag = htmlspecialchars($tag, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480">
  <rect width="640" height="480" rx="36" fill="{$bg}"/>
  <ellipse cx="320" cy="210" rx="150" ry="110" fill="{$fg}"/>
  <ellipse cx="320" cy="198" rx="108" ry="78" fill="#FFF8F0"/>
  <text x="320" y="208" text-anchor="middle" font-size="28" font-family="Georgia, serif" fill="{$bg}">{$safeTag}</text>
  <text x="320" y="400" text-anchor="middle" font-size="28" font-family="Georgia, serif" fill="#FFF8F0">{$safeTitle}</text>
</svg>
SVG;
    file_put_contents($path, $svg);
}

function ean13_barcode(string $digits12): string
{
    $digits12 = preg_replace('/\D/', '', $digits12) ?? '';
    $digits12 = str_pad(substr($digits12, 0, 12), 12, '0', STR_PAD_LEFT);
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $n = (int) $digits12[$i];
        $sum += ($i % 2 === 0) ? $n : $n * 3;
    }
    $check = (10 - ($sum % 10)) % 10;
    return $digits12 . (string) $check;
}

function ensure_indian_snack_products(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo = db();
        $catId = (int) $pdo->query("SELECT id FROM categories WHERE name = 'Snacks' LIMIT 1")->fetchColumn();
        if ($catId <= 0) {
            $pdo->prepare('INSERT INTO categories (name, description, status) VALUES ("Snacks", "Packaged Indian snack brands", "active")')->execute();
            $catId = (int) $pdo->lastInsertId();
        } else {
            $pdo->prepare('UPDATE categories SET description = ? WHERE id = ?')->execute(['Packaged Indian snack brands', $catId]);
        }

        $snacks = [
            ["Lay's Classic Salted", '890251000001', 'Classic salted potato chips', 12.00, 20.00],
            ["Lay's Magic Masala", '890251000002', 'Masala flavoured potato chips', 12.00, 20.00],
            ['Kurkure Masala Munch', '890251000003', 'Crunchy corn snack', 12.00, 20.00],
            ['Bingo Mad Angles', '890251000004', 'Masala triangle chips', 12.00, 20.00],
            ['Balaji Wafers', '890251000005', 'Balaji potato wafers', 8.00, 10.00],
            ['Bikaji Bhujia', '890251000006', 'Bikaner bhujia namkeen', 18.00, 30.00],
            ['Uncle Chipps', '890251000007', 'Spicy potato chips', 12.00, 20.00],
            ["Haldiram's Aloo Bhujia", '890251000008', 'Aloo bhujia namkeen', 22.00, 40.00],
            ['Too Yumm Karare', '890251000009', 'Baked karare snack', 12.00, 20.00],
            ['Cornitos Nacho Chips', '890251000010', 'Cheese nacho chips', 28.00, 45.00],
            ['Parle Monaco', '890251000011', 'Salty snack biscuit', 8.00, 10.00],
            ['Cheetos Puffs', '890251000012', 'Cheese puffs', 12.00, 20.00],
            ['Act II Butter Popcorn', '890251000013', 'Instant butter popcorn', 18.00, 30.00],
            ['Yellow Diamond Chips', '890251000014', 'Yellow diamond potato chips', 8.00, 10.00],
        ];

        $exists = $pdo->prepare('SELECT id FROM products WHERE barcode = ? OR product_name = ? LIMIT 1');
        $ins = $pdo->prepare(
            'INSERT INTO products (product_name, barcode, category_id, description, purchase_price, selling_price, gst, unit, stock_quantity, minimum_stock, status)
             VALUES (?, ?, ?, ?, ?, ?, 12.00, "pkt", 40, 10, "active")'
        );
        $bar = $pdo->prepare(
            'INSERT IGNORE INTO product_barcodes (product_id, barcode, barcode_type, is_primary) VALUES (?, ?, "EAN-13", 1)'
        );

        foreach ($snacks as $item) {
            [$name, $base, $desc, $buy, $sell] = $item;
            $code = ean13_barcode($base);
            $exists->execute([$code, $name]);
            $id = (int) $exists->fetchColumn();
            if ($id <= 0) {
                $ins->execute([$name, $code, $catId, $desc, $buy, $sell]);
                $id = (int) $pdo->lastInsertId();
            }
            if ($id > 0) {
                $bar->execute([$id, $code]);
            }
        }
    } catch (Throwable $e) {
        app_log('ensure_indian_snack_products: ' . $e->getMessage());
    }
}

function csv_download(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
