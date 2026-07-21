<?php
require_once __DIR__ . '/BaseController.php';

class AdminController extends BaseController {
    public function dashboard() {
        if (!$this->me || $this->me['role'] !== 'admin') {
            redirect('index.php');
        }

        $tab = $_GET['tab'] ?? 'overview';
        $msg = $_GET['msg'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['act'] ?? '';
            $flash = '';
            $back_tab = $_POST['back'] ?? 'overview';

            if ($act === 'setRole') {
                $id = (int)($_POST['id'] ?? 0);
                $role = $_POST['role'] ?? '';
                
                if (!in_array($role, ['admin', 'staff', 'user'], true)) {
                    $flash = 'Invalid role.';
                } else {
                    $s = $this->pdo->prepare('SELECT email FROM users WHERE id=?');
                    $s->execute([$id]);
                    $t = $s->fetch();
                    
                    if (!$t) {
                        $flash = 'User not found.';
                    } elseif (isPrimaryAdmin($t['email'])) {
                        $flash = 'The primary admin is fixed and cannot be changed.';
                    } elseif ($id === (int)$this->me['id']) {
                        $flash = 'You cannot modify your own role.';
                    } else {
                        $this->pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $id]);
                        
                        // Log audit trail
                        $this->pdo->prepare('INSERT INTO audit_logs (user_id, user_email, action, detail) VALUES (?,?,?,?)')
                            ->execute([$this->me['id'], $this->me['email'], 'Change User Role', 'Changed role of ' . $t['email'] . ' to ' . $role]);
                            
                        $flash = 'Role updated successfully.';
                        $back_tab = 'users';
                    }
                }
            }
            elseif ($act === 'addCampaign') {
                $name = trim($_POST['name'] ?? '');
                $code = trim(strtoupper($_POST['code'] ?? ''));
                $pct = (float)($_POST['discount_pct'] ?? 0);
                $abs = (float)($_POST['discount_abs'] ?? 0);
                $seg = $_POST['target_segment'] ?? 'All';
                $desc = trim($_POST['description'] ?? '');

                // Check duplicate code
                $chk = $this->pdo->prepare('SELECT id FROM campaigns WHERE code=?');
                $chk->execute([$code]);
                if ($chk->fetch()) {
                    $flash = 'Error: Campaign promo code already exists.';
                    $back_tab = 'campaigns';
                } else {
                    $this->pdo->prepare('INSERT INTO campaigns(name, code, discount_pct, discount_abs, target_segment, active, description) VALUES (?,?,?,?,?,1,?)')
                        ->execute([$name, $code, $pct, $abs, $seg, $desc]);
                    
                    $this->pdo->prepare('INSERT INTO audit_logs (user_id, user_email, action, detail) VALUES (?,?,?,?)')
                        ->execute([$this->me['id'], $this->me['email'], 'Create Promo Campaign', 'Created promotion ' . $code . ' (' . $pct . '% off / £' . $abs . ' off) for segment: ' . $seg]);

                    $flash = 'Campaign promotion created.';
                    $back_tab = 'campaigns';
                }
            }
            elseif ($act === 'deleteCampaign') {
                $id = (int)$_POST['id'];
                $s_code = $this->pdo->prepare('SELECT code FROM campaigns WHERE id=?');
                $s_code->execute([$id]);
                $c_row = $s_code->fetch();
                $code = $c_row ? $c_row['code'] : 'Unknown';
                
                $this->pdo->prepare('DELETE FROM campaigns WHERE id=?')->execute([$id]);
                
                $this->pdo->prepare('INSERT INTO audit_logs (user_id, user_email, action, detail) VALUES (?,?,?,?)')
                    ->execute([$this->me['id'], $this->me['email'], 'Delete Promo Campaign', 'Deleted promotion ' . $code]);
                    
                $flash = 'Campaign promotion deleted.';
                $back_tab = 'campaigns';
            }

            redirect('admin.php?tab=' . $back_tab . '&msg=' . urlencode($flash));
            return;
        }

        // Fetch data based on active tab
        $s = [];
        $users = [];
        $campaigns = [];
        $logs = [];

        if ($tab === 'overview') {
            $byRole = [];
            foreach ($this->pdo->query('SELECT role,COUNT(*) c FROM users GROUP BY role') as $r) {
                $byRole[$r['role']] = (int)$r['c'];
            }
            $s = [
                'users'    => (int)$this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'customers'=> $byRole['user'] ?? 0,
                'staff'    => $byRole['staff'] ?? 0,
                'orders'   => (int)$this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
                'revenue'  => (float)$this->pdo->query('SELECT COALESCE(SUM(total),0) FROM orders')->fetchColumn(),
                'feedback' => (int)$this->pdo->query('SELECT COUNT(*) FROM feedback')->fetchColumn(),
                'packages' => (int)$this->pdo->query('SELECT (SELECT COUNT(*) FROM packages WHERE active=1) + (SELECT COUNT(*) FROM products WHERE active=1)')->fetchColumn(),
                'campaigns'=> (int)$this->pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn(),
            ];
        }
        elseif ($tab === 'users') {
            $users = $this->pdo->query('SELECT id,name,email,role,phone FROM users ORDER BY id')->fetchAll();
        }
        elseif ($tab === 'campaigns') {
            $campaigns = $this->pdo->query('SELECT * FROM campaigns ORDER BY id DESC')->fetchAll();
        }
        elseif ($tab === 'audit') {
            $logs = $this->pdo->query('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 100')->fetchAll();
        }

        $this->render('admin/dashboard', [
            'tab' => $tab,
            'msg' => $msg,
            's' => $s,
            'users' => $users,
            'campaigns' => $campaigns,
            'logs' => $logs
        ]);
    }
}
