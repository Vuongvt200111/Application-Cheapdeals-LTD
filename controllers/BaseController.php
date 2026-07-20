<?php
class BaseController {
    protected $pdo;
    protected $me;
    
    public function __construct($pdo, $me) {
        $this->pdo = $pdo;
        $this->me = $me;
    }
    
    protected function render($viewPath, $data = []) {
        extract($data);
        global $pdo, $CFG, $ME, $cur, $pageTitle, $pageScripts;
        require __DIR__ . '/../views/' . $viewPath . '.php';
    }
}
