<?php
require_once __DIR__ . "/../config/database.php";

/* Buscar clientes e produtos */
$clientes = $pdo->query("SELECT id, nome FROM clientes")->fetchAll(PDO::FETCH_ASSOC);
$produtos = $pdo->query("SELECT id, nome FROM produtos")->fetchAll(PDO::FETCH_ASSOC);

$msg = "";
$msgBonus = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $cliente_id = (int) ($_POST["cliente_id"] ?? 0);
    $produto_id = (int) ($_POST["produto_id"] ?? 0);
    $quantidade = (int) ($_POST["quantidade"] ?? 0);

    if ($cliente_id <= 0 || $produto_id <= 0 || $quantidade <= 0) {
        $msg = "Dados inválidos.";
    } else {
        try {
            $pdo->beginTransaction();

            /* 1️⃣ Criar pedido */
            $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id) VALUES (?)");
            $stmt->execute([$cliente_id]);
            $pedido_id = $pdo->lastInsertId();

            /* 2️⃣ Inserir item */
            $stmt = $pdo->prepare("
                INSERT INTO pedido_itens (pedido_id, produto_id, quantidade)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pedido_id, $produto_id, $quantidade]);

            /* 3️⃣ Buscar saldo atual */
            $stmt = $pdo->prepare("SELECT saldo_itens FROM clientes WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $saldoAtual = (int) $stmt->fetchColumn();

            /* 4️⃣ Calcular bônus */
            $totalAgora = $saldoAtual + $quantidade;
            $bonusGerados = intdiv($totalAgora, 10);
            $novoSaldo = $totalAgora % 10;

            /* 5️⃣ Atualizar saldo */
            $stmt = $pdo->prepare("UPDATE clientes SET saldo_itens = ? WHERE id = ?");
            $stmt->execute([$novoSaldo, $cliente_id]);

            /* 6️⃣ Criar bônus */
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

                $msgBonus = "🎉 Cliente ganhou {$bonusGerados} bônus!";
            }

            $pdo->commit();
            $msg = "Pedido registrado com sucesso!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Erro ao registrar pedido.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar Pedido</title>
</head>
<body>

<h2>Criar Pedido</h2>

<?php if (!empty($msg)): ?>
    <p><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<?php if (!empty($msgBonus)): ?>
    <p style="color: green; font-weight: bold;">
        <?= htmlspecialchars($msgBonus) ?>
    </p>
<?php endif; ?>

<form method="POST">

    <select name="cliente_id" required>
        <option value="">Cliente</option>
        <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['id'] ?>">
                <?= htmlspecialchars($c['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <select name="produto_id" required>
        <option value="">Produto</option>
        <?php foreach ($produtos as $p): ?>
            <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <input
        type="number"
        name="quantidade"
        min="1"
        required
    >

    <br><br>

    <button type="submit">Finalizar Pedido</button>

</form>

</body>
</html>

