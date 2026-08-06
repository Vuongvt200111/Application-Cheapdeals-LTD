<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Ticket.php';

class SupportController extends BaseController {
    public function chat() {
        $inquiryPackage = trim($_GET['inquire_pkg'] ?? $_GET['inquiry_code'] ?? $_GET['inquiry_pkg'] ?? '');
        if (!$this->me) {
            redirect('login.php');
        }

        // Handle AJAX action
        if (isset($_GET['action'])) {
            header('Content-Type: application/json');
            if ($_GET['action'] === 'get_messages') {
                $msgs = Ticket::getForUser($this->me['id']);
                echo json_encode(['messages' => $msgs]);
                exit;
            }
            if ($_GET['action'] === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $msg = trim($_POST['message'] ?? '');
                if (!empty($_POST['attached_package'])) {
                    $pkgInfo = trim($_POST['attached_package']);
                    $msg = "[Product Link]: " . $pkgInfo . "\n" . $msg;
                }
                if ($msg !== '') {
                    Ticket::reply($this->me['id'], 'user', $msg);
                    echo json_encode(['success' => true]);
                    exit;
                }
                echo json_encode(['error' => 'Empty message.']);
                exit;
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $msg = trim($_POST['message'] ?? '');
            if (!empty($_POST['attached_package'])) {
                $pkgInfo = trim($_POST['attached_package']);
                $msg = "[Product Link]: " . $pkgInfo . "\n" . $msg;
            }
            if ($msg !== '') {
                Ticket::reply($this->me['id'], 'user', $msg);
            }
            redirect('support.php');
        }

        $inquiryPackage = null;
        if (!empty($_GET['inquiry_code'])) {
            $stmt = $this->pdo->prepare("SELECT name, price, category FROM packages WHERE code = ? AND active = 1");
            $stmt->execute([trim($_GET['inquiry_code'])]);
            $inquiryPackage = $stmt->fetch();
        }

        $msgs = Ticket::getForUser($this->me['id']);
        
        $this->render('support/chat', [
            'msgs' => $msgs,
            'inquiryPackage' => $inquiryPackage
        ]);
    }
}
