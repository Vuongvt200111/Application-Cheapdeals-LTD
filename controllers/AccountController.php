<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Wishlist.php';
require_once __DIR__ . '/../models/Package.php';
require_once __DIR__ . '/../models/Product.php';

class AccountController extends BaseController {
    public function dashboard() {
        if (!$this->me) {
            redirect('login.php');
        }
        if ($this->me['role'] !== 'user') {
            redirect($this->me['role'] === 'admin' ? 'admin.php' : 'staff.php');
        }

        // Handle AJAX action
        if (isset($_GET['action'])) {
            header('Content-Type: application/json');
            if ($_GET['action'] === 'send_security_code') {
                $code = sprintf('%06d', mt_rand(100000, 999999));
                $s_save = $this->pdo->prepare('INSERT INTO email_verifications (email, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = VALUES(code)');
                $s_save->execute([$this->me['email'], $code]);
                
                $logPath = dirname(__DIR__) . '/email_codes.log';
                @file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] [" . $this->me['email'] . "] Security Code: $code\n", FILE_APPEND);
                try {
                    @mail($this->me['email'], "CheapDeals Security Verification Code", "Your verification code is: $code", "From: no-reply@cheapdeals.com");
                } catch (Throwable $e) {}
                
                echo json_encode(['success' => true, 'message' => 'Verification code sent! (Check email_codes.log in project root if local SMTP is off)']);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['act'] ?? '';
            
            if ($act === 'updateProfile') {
                $avatarPath = $this->me['avatar'];
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['avatar']['tmp_name'];
                    $name = basename($_FILES['avatar']['name']);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
                        $newName = 'avatar_' . $this->me['id'] . '_' . time() . '.' . $ext;
                        $destDir = __DIR__ . '/../uploads';
                        if (!is_dir($destDir)) {
                            mkdir($destDir, 0777, true);
                        }
                        if (move_uploaded_file($tmpName, $destDir . '/' . $newName)) {
                            $avatarPath = 'uploads/' . $newName;
                            // Sync to htdocs if running on XAMPP
                            foreach (['C:/xampp/htdocs/cheapdeals-v3/uploads', 'C:/xampp/htdocs/cheapdeals/uploads'] as $hdir) {
                                if (is_dir(dirname($hdir))) {
                                    if (!is_dir($hdir)) @mkdir($hdir, 0777, true);
                                    @copy($destDir . '/' . $newName, $hdir . '/' . $newName);
                                }
                            }
                        }
                    }
                }
                
                User::updateProfile(
                    $this->me['id'],
                    trim($_POST['name'] ?? ''),
                    trim($_POST['address'] ?? ''),
                    trim($_POST['phone'] ?? ''),
                    trim($_POST['card'] ?? ''),
                    trim($_POST['payment_pin'] ?? ''),
                    $avatarPath
                );
                
                // Refresh session ME
                $this->me = User::getById($this->me['id']);
                $_SESSION['ME'] = $this->me;
                $GLOBALS['ME'] = $this->me;
                
                redirect('account.php?tab=personal&msg=' . urlencode('Profile details updated.'));
            }
            
            if ($act === 'changePassword') {
                $new = trim($_POST['new_password'] ?? '');
                $confirm = trim($_POST['confirm_password'] ?? '');
                $code = trim($_POST['code'] ?? '');
                
                $s_code = $this->pdo->prepare('SELECT code FROM email_verifications WHERE LOWER(email)=LOWER(?)');
                $s_code->execute([$this->me['email']]);
                $saved_code = $s_code->fetchColumn();
                
                if (!$saved_code || $saved_code !== $code) {
                    redirect('account.php?tab=security&err=' . urlencode('Invalid verification code.'));
                }
                
                if ($new !== $confirm) {
                    redirect('account.php?tab=security&err=' . urlencode('Passwords do not match.'));
                }
                
                if (strlen($new) < 6) {
                    redirect('account.php?tab=security&err=' . urlencode('New password must be at least 6 characters.'));
                }
                
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $this->pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $this->me['id']]);
                // Retain audit trail in email_verifications permanently for security audits
                
                redirect('account.php?tab=security&msg=' . urlencode('Password changed successfully.'));
            }
            
            if ($act === 'submitReview') {
                $item_code = trim($_POST['item_code'] ?? '');
                $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
                $comment = trim($_POST['comment'] ?? '');
                
                $s_rev = $this->pdo->prepare('SELECT id FROM reviews WHERE user_id=? AND item_code=?');
                $s_rev->execute([$this->me['id'], $item_code]);
                $exists = $s_rev->fetch();
                
                if ($exists) {
                    $this->pdo->prepare('UPDATE reviews SET rating=?, comment=? WHERE id=?')
                             ->execute([$rating, $comment, $exists['id']]);
                } else {
                    $this->pdo->prepare('INSERT INTO reviews (user_id, item_code, rating, comment) VALUES (?, ?, ?, ?)')
                             ->execute([$this->me['id'], $item_code, $rating, $comment]);
                }
                
                $is_pkg = (Package::getByCode($item_code) !== null);
                $table = $is_pkg ? 'packages' : 'products';
                
                $s_avg = $this->pdo->prepare('SELECT AVG(rating), COUNT(*) FROM reviews WHERE item_code=?');
                $s_avg->execute([$item_code]);
                $avg = $s_avg->fetch();
                
                $avg_score = round((float)$avg[0], 2);
                $avg_count = (int)$avg[1];
                
                $this->pdo->prepare("UPDATE `$table` SET rating_score=?, rating_count=? WHERE code=?")
                         ->execute([$avg_score, $avg_count, $item_code]);
                         
                redirect('account.php?tab=reviews&msg=' . urlencode('Review submitted successfully.'));
            }
            
            if ($act === 'deleteReview') {
                $id = (int)$_POST['id'];
                
                $s_itm = $this->pdo->prepare('SELECT item_code FROM reviews WHERE id=? AND user_id=?');
                $s_itm->execute([$id, $this->me['id']]);
                $item_code = $s_itm->fetchColumn();
                
                if ($item_code) {
                    $this->pdo->prepare('DELETE FROM reviews WHERE id=? AND user_id=?')->execute([$id, $this->me['id']]);
                    
                    $is_pkg = (Package::getByCode($item_code) !== null);
                    $table = $is_pkg ? 'packages' : 'products';
                    
                    $s_avg = $this->pdo->prepare('SELECT AVG(rating), COUNT(*) FROM reviews WHERE item_code=?');
                    $s_avg->execute([$item_code]);
                    $avg = $s_avg->fetch();
                    
                    $avg_score = $avg[0] !== null ? round((float)$avg[0], 2) : 0.00;
                    $avg_count = (int)$avg[1];
                    
                    $this->pdo->prepare("UPDATE `$table` SET rating_score=?, rating_count=? WHERE code=?")
                             ->execute([$avg_score, $avg_count, $item_code]);
                }
                
                redirect('account.php?tab=reviews&msg=' . urlencode('Review deleted successfully.'));
            }
        }

        $tab = $_GET['tab'] ?? 'overview';
        $orders = Order::getForUser($this->me['id']);
        
        $wishlist_items = [];
        if ($tab === 'wishlist') {
            $wish_codes = Wishlist::getForUser($this->me['id']);
            foreach ($wish_codes as $code) {
                $p = Package::getByCode($code) ?: Product::getByCode($code);
                if ($p) {
                    $wishlist_items[] = $p;
                }
            }
            
            // Fetch confirmed custom requirements (BUG-022)
            $s_req = $this->pdo->prepare("SELECT * FROM custom_requirements WHERE user_id=? AND status='Confirmed' ORDER BY id DESC");
            $s_req->execute([$this->me['id']]);
            $confirmed_reqs = $s_req->fetchAll();
            foreach ($confirmed_reqs as $req) {
                $wishlist_items[] = [
                    'code' => 'custom',
                    'name' => $req['name'],
                    'price' => (float)$req['price'],
                    'features' => json_encode(array_values(array_filter([
                        $req['device'] !== 'No device' ? 'Device: ' . $req['device'] : null,
                        $req['minutes'] !== 'None' ? 'Minutes: ' . $req['minutes'] : null,
                        $req['data'] !== 'None' ? 'Data: ' . $req['data'] : null,
                        $req['sms'] ? 'SMS: ' . $req['sms'] : null,
                        $req['addons'] ? 'Add-ons: ' . $req['addons'] : null,
                    ]))),
                    'sales_count' => 0,
                    'rating_score' => 5.0,
                    'rating_count' => 0,
                    'inventory' => 'Unlimited',
                    'is_custom_req' => true
                ];
            }
        }

        // Fetch user reviews
        $reviews = [];
        if ($tab === 'reviews') {
            $s_rev = $this->pdo->prepare('SELECT * FROM reviews WHERE user_id=? ORDER BY created_at DESC');
            $s_rev->execute([$this->me['id']]);
            $reviews = $s_rev->fetchAll();
        }

        $this->render('account/dashboard', [
            'tab' => $tab,
            'orders' => $orders,
            'wishlist_items' => $wishlist_items,
            'reviews' => $reviews,
            'points' => $this->me['points']
        ]);
    }
}
