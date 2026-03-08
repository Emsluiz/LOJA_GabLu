<?php
require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json");

// Só aceita POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["erro" => "Método não permitido"]);
    exit;
}

// Recebe JSON
$dados = json_decode(file_get_contents("php://input"), true);

if (!isset($dados["cliente_id"], $dados["produto_id"], $dados["quantidade"])) {
    echo json_encode(["erro" => "Dados incompletos"]);
    exit;
}

$cliente_id = (int) $dados["cliente_id"];
$produto_id = (int) $dados["produto_id"];
$quantidade = (int) $dados["quantidade"];

try {

    $pdo->beginTransaction();

    // 1️⃣ Criar pedido
    $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id) VALUES (?)");
    $stmt->execute([$cliente_id]);
    $pedido_id = $pdo->lastInsertId();

    // 2️⃣ Inserir item
    $stmt = $pdo->prepare("
        INSERT INTO pedido_itens (pedido_id, produto_id, quantidade)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$pedido_id, $produto_id, $quantidade]);

    // 3️⃣ Buscar saldo atual
    $stmt = $pdo->prepare("SELECT saldo_itens FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $saldoAtual = (int) $stmt->fetchColumn();

    // 4️⃣ Calcular bônus
    $totalAgora = $saldoAtual + $quantidade;
    $bonusGerados = intdiv($totalAgora, 10);
    $novoSaldo = $totalAgora % 10;

    // 5️⃣ Atualizar saldo
    $stmt = $pdo->prepare("UPDATE clientes SET saldo_itens = ? WHERE id = ?");
    $stmt->execute([$novoSaldo, $cliente_id]);

    // 6️⃣ Criar bônus se houver
    if ($bonusGerados > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO bonus (cliente_id, descricao)
            VALUES (?, ?)
        ");

        for ($i = 0; $i < $bonusGerados; $i++) {
            $stmt->execute([
                $cliente_id,
                "Bônus por completar 10 produtos"
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        "sucesso" => true,
        "pedido_id" => $pedido_id,
        "bonus_gerados" => $bonusGerados,
        "saldo_atual" => $novoSaldo
    ]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        "erro" => $e->getMessage()
    ]);
}
