<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Controllers\RequestController;
use App\Services\AuthService;
use App\Services\LibraryManager;
use App\Services\OopInspectorService;

session_start();

$manager = new LibraryManager();
$inspector = new OopInspectorService();
$auth = new AuthService($manager);
$controller = new RequestController($manager, $auth, $inspector);

// 1. Endpoint API JSON (AJAX fetch)
if (isset($_GET['api'])) {
    $controller->handleApiRequest((string) $_GET['api'], $_GET);
}

// 2. Handler Aksi Form POST (pola PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->handlePostRequest($_POST);
}

// 3. Susun Data & Render Tampilan Modular (views/)
extract($controller->buildViewData($_GET), EXTR_SKIP);

require __DIR__ . '/views/layout/header.php';
require __DIR__ . '/views/pages/home.php';
require __DIR__ . '/views/pages/catalog.php';
require __DIR__ . '/views/pages/loans.php';
require __DIR__ . '/views/pages/members.php';
require __DIR__ . '/views/pages/manage.php';
require __DIR__ . '/views/pages/system.php';
require __DIR__ . '/views/layout/footer.php';
