<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Package.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Wishlist.php';

class HomeController extends BaseController {
    public function index() {
        if (isset($_GET['action'])) {
            $this->handleAjax($_GET['action']);
            return;
        }

        if (isset($_GET['view_code'])) {
            $code = $_GET['view_code'];
            if (!isset($_SESSION['view_history'])) {
                $_SESSION['view_history'] = [];
            }
            if (!in_array($code, $_SESSION['view_history'], true)) {
                array_unshift($_SESSION['view_history'], $code);
                $_SESSION['view_history'] = array_slice($_SESSION['view_history'], 0, 5);
            }
            echo json_encode(['ok' => true]);
            exit;
        }

        $pkgs = Package::getAll();
        $prods = Product::getAll();
        
        $wishlist = [];
        if ($this->me) {
            $wishlist = Wishlist::getForUser($this->me['id']);
        }

        $recs = [];
        if (!empty($_SESSION['view_history'])) {
            foreach ($_SESSION['view_history'] as $code) {
                $p = Package::getByCode($code) ?: Product::getByCode($code);
                if ($p) {
                    $recs[] = $p;
                }
            }
        }

        $this->render('home/index', [
            'pkgs' => $pkgs,
            'prods' => $prods,
            'wishlist' => $wishlist,
            'recs' => $recs
        ]);
    }

    private function handleAjax($action) {
        header('Content-Type: application/json');
        if ($action === 'toggle_wishlist') {
            if (!$this->me) {
                echo json_encode(['error' => 'Please log in to save deals.']);
                exit;
            }
            $code = trim($_POST['code'] ?? '');
            if (!$code) {
                echo json_encode(['error' => 'Invalid product code.']);
                exit;
            }
            $status = Wishlist::toggle($this->me['id'], $code);
            echo json_encode(['status' => $status]);
            exit;
        }
        if ($action === 'clear_history') {
            unset($_SESSION['view_history']);
            echo json_encode(['ok' => true]);
            exit;
        }
        echo json_encode(['error' => 'Unknown action.']);
        exit;
    }
}
