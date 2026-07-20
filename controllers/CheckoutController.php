<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Package.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/User.php';

class CheckoutController extends BaseController {
    public function checkout() {
        if (!$this->me) {
            redirect('login.php');
        }
        if ($this->me['role'] !== 'user') {
            $cur = 'checkout.php'; $pageTitle = 'Checkout';
            require __DIR__ . '/../views/layout/header.php';
            echo '<div class="msg err">Staff/Admin accounts cannot place customer orders. Log in as a customer.</div>';
            require __DIR__ . '/../views/layout/footer.php';
            return;
        }

        $err = $_GET['msg'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'pay') {
            $this->processPayment();
            return;
        }

        $isCart = isset($_GET['cart']);
        $opts = [];
        $pre = '';

        if ($isCart) {
            $cart = $_SESSION['cart'] ?? [];
            if (empty($cart)) {
                redirect('cart.php?msg=' . urlencode('Your cart is empty.'));
            }
            $n = 0; foreach ($cart as $it) $n += (int)$it['qty'];
            $cartTotal = 0; foreach ($cart as $it) $cartTotal += (float)$it['price'] * (int)$it['qty'];
            $cartName = 'Cart (' . $n . ' item' . ($n === 1 ? '' : 's') . ')';
            
            $opts[] = [
                'code' => 'cart',
                'name' => $cartName,
                'price' => $cartTotal,
                'type' => 'package',
                'inventory' => 999
            ];
            $pre = 'cart';
        } else {
            $pkgs = Package::getAll();
            $prods = Product::getAll();
            
            foreach ($pkgs as $p) {
                $opts[] = [
                    'code' => $p['code'],
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'type' => 'package',
                    'inventory' => $p['inventory']
                ];
            }
            foreach ($prods as $p) {
                $opts[] = [
                    'code' => $p['code'],
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'type' => 'product',
                    'inventory' => $p['inventory']
                ];
            }

            if (isset($_GET['custom'])) {
                array_unshift($opts, [
                    'code' => 'custom',
                    'name' => $_GET['custom'],
                    'price' => (float)($_GET['cprice'] ?? 10),
                    'type' => 'package',
                    'inventory' => 999
                ]);
            }
            $pre = $_GET['code'] ?? (!empty($_GET['custom']) ? 'custom' : '');
        }

        $prefillCard = luhn(preg_replace('/\D/','', $this->me['card'])) ? $this->me['card'] : '4111 1111 1111 1111';
        $campaigns = $this->pdo->query("SELECT name, code, discount_pct, discount_abs FROM campaigns WHERE active=1")->fetchAll();
        $orders = Order::getForUser($this->me['id']);

        $this->render('checkout/form', [
            'opts' => $opts,
            'pre' => $pre,
            'prefillCard' => $prefillCard,
            'points' => $this->me['points'],
            'campaigns' => $campaigns,
            'orders' => $orders,
            'err' => $err
        ]);
    }

    private function processPayment() {
        // 1. Payment PIN check
        $payment_pin = $_POST['payment_pin'] ?? '';
        if (!paymentPinRecentlyVerified() && !verifyPaymentPin($this->me, $payment_pin)) {
            redirect('checkout.php?msg=' . urlencode('Payment blocked: invalid 6-digit payment PIN.'));
        }
        rememberPaymentPinVerified();

        // 2. VISACheck validation
        $t0 = microtime(true);
        $digits = preg_replace('/\D/', '', $_POST['co_num'] ?? '');
        $cvvOk  = preg_match('/^\d{3}$/', $_POST['co_cvv'] ?? '');
        $cardOk = luhn($digits) && $cvvOk;
        $visaMs = round((microtime(true) - $t0) * 1000, 2);

        if (!$cardOk) {
            redirect('checkout.php?msg=' . urlencode('VISACheck declined the card (failed Luhn / CVV check).'));
        }

        // Check single-use voucher validation (BUG-027)
        $voucherCode = strtoupper(trim($_POST['promo_code'] ?? ''));
        if ($voucherCode !== '') {
            $s_used = $this->pdo->prepare('SELECT id FROM user_vouchers WHERE user_id=? AND voucher_code=?');
            $s_used->execute([$this->me['id'], $voucherCode]);
            if ($s_used->fetch()) {
                redirect('checkout.php?msg=' . urlencode('You have already used this promo code.'));
            }
        }

        $code = trim($_POST['pkg_code'] ?? '');
        $name = trim($_POST['f_name'] ?? '');
        $total = (float)($_POST['f_total'] ?? 0);
        $saved = (float)($_POST['f_saved'] ?? 0);
        $redeemedPoints = (int)($_POST['points_redeemed'] ?? 0);

        if ($redeemedPoints > $this->me['points']) {
            $redeemedPoints = $this->me['points'];
        }
        $pointsAfterRedeem = $this->me['points'] - $redeemedPoints;
        $pointsEarned = (int)round($total * 0.01);
        $finalPoints = $pointsAfterRedeem + $pointsEarned;

        $items = [];
        $hasHardware = false;

        if ($code === 'cart') {
            $cart = $_SESSION['cart'] ?? [];
            if (empty($cart)) {
                redirect('cart.php?msg=' . urlencode('Your cart is empty.'));
            }
            // 1. Stock check
            foreach ($cart as $it) {
                if ($it['type'] !== 'custom') {
                    $p = Package::getByCode($it['code']) ?: Product::getByCode($it['code']);
                    if ($p && $p['inventory'] !== null && (int)$p['inventory'] < (int)$it['qty']) {
                        redirect('checkout.php?cart=1&msg=' . urlencode('Sorry, "' . $p['name'] . '" only has ' . $p['inventory'] . ' left in stock.'));
                    }
                }
            }
            // 2. Populate items list
            foreach ($cart as $it) {
                for ($i = 0; $i < (int)$it['qty']; $i++) {
                    $items[] = [
                        'code' => $it['code'],
                        'name' => $it['name'],
                        'price' => (float)$it['price'],
                        'type' => $it['type'],
                        'hw' => !empty($it['hw'])
                    ];
                }
                if (!empty($it['hw'])) {
                    $hasHardware = true;
                }
            }
            // Clear cart
            $_SESSION['cart'] = [];
        } elseif ($code === 'custom') {
            // Custom item from customizer
            $deviceVal = (int)($_GET['c-device'] ?? 0);
            $hasHardware = ($deviceVal > 0);
            $items[] = [
                'code' => 'custom',
                'name' => $name,
                'price' => $total,
                'type' => 'custom',
                'hw' => $hasHardware
            ];
        } else {
            // Single package or product
            $p = Package::getByCode($code) ?: Product::getByCode($code);
            if (!$p) {
                redirect('checkout.php?msg=' . urlencode('Item not found.'));
            }
            if ($p['inventory'] <= 0) {
                redirect('checkout.php?msg=' . urlencode('Sorry, "' . $p['name'] . '" is currently out of stock.'));
            }
            $isPkg = isset($p['features']);
            $hw = !$isPkg; // products are hardware
            if ($isPkg) {
                // Check if package features mention a hardware device
                $feats = json_decode($p['features'], true) ?: [];
                foreach ($feats as $f) {
                    if (preg_match('/phone|router|tablet|device/i', $f)) {
                        $hw = true;
                        break;
                    }
                }
            }
            if ($hw) {
                $hasHardware = true;
            }
            $items[] = [
                'code' => $code,
                'name' => $p['name'],
                'price' => (float)$p['price'],
                'type' => $isPkg ? 'package' : 'product',
                'hw' => $hw
            ];
        }

        // Deduct inventory
        foreach ($items as $it) {
            if ($it['code'] && $it['code'] !== 'custom') {
                $p = Package::getByCode($it['code']) ?: Product::getByCode($it['code']);
                if ($p) {
                    if (isset($p['features'])) {
                        Package::update($it['code'], (float)$p['price'], max(0, $p['inventory'] - 1));
                    } else {
                        Product::update($it['code'], (float)$p['price'], max(0, $p['inventory'] - 1), $p['description']);
                    }
                }
            }
        }

        User::updatePoints($this->me['id'], $finalPoints);

        // Add order with cancellation deadline and has_hardware flag
        $cancelDeadline = (new DateTimeImmutable())->modify('+' . CANCEL_GRACE_MINUTES . ' minutes')->format('Y-m-d H:i:s');
        
        $stmt = $this->pdo->prepare('INSERT INTO orders(user_id, package_name, total, saved, status, cancel_deadline, has_hardware) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$this->me['id'], $name, $total, $saved, 'Paid', $cancelDeadline, $hasHardware ? 1 : 0]);
        $order_id = $this->pdo->lastInsertId();

        // Record voucher usage (BUG-027)
        if ($voucherCode !== '') {
            $this->pdo->prepare('INSERT INTO user_vouchers (user_id, voucher_code) VALUES (?, ?)')
                     ->execute([$this->me['id'], $voucherCode]);
        }

        // Insert into order_items (FR17)
        $itemStmt = $this->pdo->prepare("INSERT INTO order_items(order_id, item_name, item_type, price) VALUES (?,?,?,?)");
        foreach ($items as $it) {
            $itemStmt->execute([$order_id, $it['name'], $it['type'], $it['price']]);
        }

        // Create shipment if has hardware (FR35)
        if ($hasHardware) {
            $this->pdo->prepare("INSERT INTO shipments(order_id, status) VALUES (?, 'Order Placed')")->execute([$order_id]);
        }

        // Log audit trail
        $this->pdo->prepare('INSERT INTO audit_log (actor, action, detail) VALUES (?,?,?)')
            ->execute([$this->me['email'], 'Order Payment', 'Order placed: ' . $name . ', Paid: ' . gbp($total) . ', Saved: ' . gbp($saved)]);

        $_SESSION['last_order'] = [
            'name' => $name,
            'total' => $total,
            'saved' => $saved,
            'email' => $this->me['email'],
            'visa_ms' => $visaMs,
            'points_earned' => $pointsEarned,
            'points_redeemed' => $redeemedPoints
        ];

        redirect('thanks.php');
    }
}
