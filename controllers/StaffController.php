<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Package.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Voucher.php';

class StaffController extends BaseController {
    public function dashboard() {
        if (!$this->me || !in_array($this->me['role'], ['staff', 'admin'], true)) {
            redirect('index.php');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
            return;
        }

        $tab = $_GET['tab'] ?? 'packages';
        $pkgs = Package::getAll();
        $prods = Product::getAll();
        // --- XỬ LÝ PHỄU LỌC VÀ SẮP XẾP CHO TAB ORDERS (BUG-Nam/Orders) ---
        $filterCustomer = trim($_GET['f_customer'] ?? '');
        $sortTotal = $_GET['s_total'] ?? '';
        
        $sql = 'SELECT o.*, u.name customer, u.email FROM orders o JOIN users u ON u.id=o.user_id';
        $params = [];
        if ($filterCustomer !== '') {
            $sql .= ' WHERE u.name LIKE ?';
            $params[] = '%' . $filterCustomer . '%';
        }
        if ($sortTotal === 'desc') {
            $sql .= ' ORDER BY o.total DESC';
        } elseif ($sortTotal === 'asc') {
            $sql .= ' ORDER BY o.total ASC';
        } else {
            $sql .= ' ORDER BY o.id DESC';
        }
        
        $s_ord = $this->pdo->prepare($sql);
        $s_ord->execute($params);
        $orders = $s_ord->fetchAll();
        $vouchers = Voucher::getAll();
        $threads = Ticket::getAllThreads();
        
        $escalations = $this->pdo->query("SELECT * FROM ticket_escalations")->fetchAll();
        $escMap = [];
        foreach ($escalations as $e) {
            $escMap[(int)$e['user_id']] = $e;
        }
        
        $uid = (int)($_GET['user_id'] ?? 0);
        $chat_msgs = [];
        if ($uid > 0) {
            $chat_msgs = Ticket::getForUser($uid);
        }

        // Fetch custom build requirements
        $requirements = $this->pdo->query("SELECT r.*, u.name as user_name, u.email as user_email FROM custom_requirements r JOIN users u ON r.user_id = u.id ORDER BY r.id DESC")->fetchAll();

        $this->render('staff/dashboard', [
            'tab' => $tab,
            'pkgs' => $pkgs,
            'prods' => $prods,
            'orders' => $orders,
            'vouchers' => $vouchers,
            'threads' => $threads,
            'uid' => $uid,
            'chat_msgs' => $chat_msgs,
            'requirements' => $requirements,
            'escMap' => $escMap,
            'filterCustomer' => $filterCustomer,
            'sortTotal' => $sortTotal
        ]);
    }

    private function handlePost() {
        $act = $_POST['act'] ?? '';
        $flash = '';
        $back = 'staff.php?tab=' . ($_POST['back'] ?? 'packages');

        if ($act === 'savePrice') {
            $code = $_POST['code'];
            $newPrice = (float)$_POST['price'];
            $newInventory = (int)$_POST['inventory'];
            
            $p = Package::getByCode($code);
            if ($p) {
                Package::update($code, $newPrice, $newInventory);
                if ((float)$p['price'] !== $newPrice || (int)$p['inventory'] !== $newInventory) {
                    audit($this->pdo, $this->me['email'], 'price_inventory_change', $p['name'] . ': price=' . $newPrice . ', stock=' . $newInventory);
                }
                $flash = 'Package updated.';
            }
        } elseif ($act === 'saveProdPrice') {
            $code = $_POST['code'];
            $newPrice = (float)$_POST['price'];
            $newInventory = (int)$_POST['inventory'];
            $description = trim($_POST['description'] ?? '');
            
            $p = Product::getByCode($code);
            if ($p) {
                Product::update($code, $newPrice, $newInventory, $description);
                audit($this->pdo, $this->me['email'], 'product_update', $p['name'] . ': price=' . $newPrice . ', stock=' . $newInventory);
                $flash = 'Product updated.';
            }
        } elseif ($act === 'delPkg') {
            $code = $_POST['code'];
            $p = Package::getByCode($code);
            if ($p) {
                Package::delete($code);
                audit($this->pdo, $this->me['email'], 'package_deleted', 'Deleted package: ' . $p['name']);
                $flash = 'Package deleted.';
            }
        } elseif ($act === 'delProd') {
            $code = $_POST['code'];
            $p = Product::getByCode($code);
            if ($p) {
                Product::delete($code);
                audit($this->pdo, $this->me['email'], 'product_deleted', 'Deleted product: ' . $p['name']);
                $flash = 'Product deleted.';
            }
        } elseif ($act === 'addPkg') {
            $feat = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $_POST['features'] ?? ''))));
            $code = 'pkg-' . substr(md5(($_POST['name'] ?? '') . microtime()), 0, 6);
            $unit = trim($_POST['unit'] ?? $_POST['cycle'] ?? $_POST['price_unit'] ?? 'month');
            Package::add(
                $code,
                trim($_POST['name']),
                $_POST['category'],
                $_POST['tier'],
                (float)$_POST['price'],
                json_encode($feat, JSON_UNESCAPED_UNICODE),
                (int)$_POST['inventory'],
                $unit
            );
            audit($this->pdo, $this->me['email'], 'package_added', 'Added package: ' . trim($_POST['name']));
            $flash = 'Option created.';
        } elseif ($act === 'addProd') {
            $code = 'prod-' . substr(md5(($_POST['name'] ?? '') . microtime()), 0, 6);
            Product::add(
                $code,
                trim($_POST['name']),
                trim($_POST['description'] ?? ''),
                (float)$_POST['price'],
                (int)$_POST['inventory']
            );
            audit($this->pdo, $this->me['email'], 'product_added', 'Added product: ' . trim($_POST['name']));
            $flash = 'Hardware Product created.';
        } elseif ($act === 'issueVoucher') {
            $disc = (int)($_POST['discount'] ?? 0);
            if ($disc < 1 || $disc > 50) {
                $flash = 'Voucher creation failed: discount must be between 1% and 50%.';
                $back = 'staff.php?tab=vouchers';
            } else {
                $code = 'CD' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
                Voucher::add($code, $disc, $this->me['email']);
                audit($this->pdo, $this->me['email'], 'voucher_issued', 'Issued ' . $code . ' (' . $disc . '% off)');
                $back = 'staff.php?tab=vouchers';
                $flash = 'Voucher created: ' . $code . ' (' . $disc . '% off)';
            }
        } elseif ($act === 'reply') {
            $msg = trim($_POST['message'] ?? '');
            $uid = (int)$_POST['user_id'];
            if ($msg !== '') {
                Ticket::reply($uid, 'staff', $msg);
            }
            $back = 'staff.php?tab=feedback&user_id=' . $uid;
            $flash = 'Reply sent.';
        } elseif ($act === 'escalateTicket') {
            $uid = (int)$_POST['user_id'];
            $tier = $_POST['escalate_tier'] ?? 'Tier-2';
            $comp = (float)($_POST['compensation'] ?? 0);
            
            $status = ($comp > 50.0) ? 'Pending Supervisor Approval' : 'Active';
            
            $s_esc = $this->pdo->prepare("INSERT INTO ticket_escalations (user_id, tier, compensation, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE tier=VALUES(tier), compensation=VALUES(compensation), status=VALUES(status)");
            $s_esc->execute([$uid, $tier, $comp, $status]);
            
            if ($comp > 50.0) {
                $flash = 'Escalation Alert: Compensation exceeds £50 limit. Supervisor approval required.';
            } else {
                audit($this->pdo, $this->me['email'], 'ticket_escalation', "Escalated User ID $uid to $tier. Proposed credit: £$comp");
                $flash = "Ticket successfully escalated to $tier under SLA rules.";
            }
            $back = 'staff.php?tab=feedback&user_id=' . $uid;
        } elseif ($act === 'issueCohortVoucher') {
            $disc = (int)($_POST['discount'] ?? 0);
            if ($disc < 1 || $disc > 50) {
                $flash = 'Cohort Voucher creation failed: discount must be between 1% and 50%.';
                $back = 'staff.php?tab=vouchers';
            } else {
                $code = 'CD' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
                $rule = $_POST['cohort_rule'] ?? 'data_usage_gt_20gb';
                Voucher::add($code, $disc, $this->me['email'] . " [Cohort Rule: $rule]");
                audit($this->pdo, $this->me['email'], 'cohort_voucher_issued', "Issued voucher $code with rule: $rule");
                $back = 'staff.php?tab=vouchers';
                $flash = 'Demographic Cohort Campaign Voucher created.';
            }
        } elseif ($act === 'confirmRequirement') {
            $req_id = (int)$_POST['req_id'];
            $stmt = $this->pdo->prepare("UPDATE custom_requirements SET status='Confirmed' WHERE id=?");
            $stmt->execute([$req_id]);
            audit($this->pdo, $this->me['email'], 'requirement_confirmed', "Confirmed custom build requirement ID $req_id");
            $back = 'staff.php?tab=requirements';
            $flash = 'Custom requirement confirmed successfully.';
        } elseif ($act === 'deleteRequirement') {
            $req_id = (int)$_POST['req_id'];
            $stmt = $this->pdo->prepare("DELETE FROM custom_requirements WHERE id=?");
            $stmt->execute([$req_id]);
            audit($this->pdo, $this->me['email'], 'requirement_deleted', "Deleted custom build requirement ID $req_id");
            $back = 'staff.php?tab=requirements';
            $flash = 'Custom requirement deleted successfully.';
        }
        redirect($back . '&msg=' . urlencode($flash));
    }
}
