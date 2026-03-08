<?php

header("Content-Type: application/json");

require_once __DIR__ . "/middleware/AuthMiddleware.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/controllers/AuthController.php";
require_once __DIR__ . "/controllers/PedidoController.php";

$rota = trim($_GET['rota'] ?? '', '/');

switch ($rota) {

    case 'login':
        AuthController::login($pdo);
        break;

    case 'criar-pedido':
        AuthMiddleware::verificar();
        PedidoController::criar($pdo);
        break;

    case 'historico':
        AuthMiddleware::verificar();
        PedidoController::historico($pdo);
        break;

    default:
        echo json_encode(["erro" => "Rota não encontrada"]);
}

