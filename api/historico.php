<?php
require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json");

// Verifica se foi enviado cliente_id
if (!isset($_GET['cliente_id'])) {
    echo json_encode(["erro" => "cliente_id é obrigatório"]);
    exit;
}

$cliente_id = (int) $_GET['cliente_id'];

$stmt = $pdo->prepare("
    SELECT 
        p.id AS pedido_id,
        pr.nome AS produto,
        pi.quantidade,
        p.data AS data_pedido
    FROM pedidos p
    JOIN pedido_itens pi ON p.id = pi.pedido_id
    JOIN produtos pr ON pi.produto_id = pr.id
    WHERE p.cliente_id = ?
    ORDER BY p.data DESC
");

$stmt->execute([$cliente_id]);

$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($historico);
