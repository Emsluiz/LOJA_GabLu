<?php
require_once __DIR__ . "/../config/database.php";

/* Buscar clientes */
$clientes = $pdo->query("SELECT id, nome FROM clientes")->fetchAll(PDO::FETCH_ASSOC);

$clienteSelecionado = $_GET['cliente_id'] ?? null;
$pedidos = [];
$bonus = [];
$totalProdutos = 0;
$faltamProdutos = 0;

if ($clienteSelecionado) {

    /* Buscar pedidos do cliente */
    $sqlPedidos = "
        SELECT 
            p.id AS pedido_id,
            SUM(pi.quantidade) AS total_produtos
        FROM pedidos p
        JOIN pedido_itens pi ON pi.pedido_id = p.id
        WHERE p.cliente_id = ?
        GROUP BY p.id
        ORDER BY p.id DESC
    ";
    $stmtPedidos = $pdo->prepare($sqlPedidos);
    $stmtPedidos->execute([$clienteSelecionado]);
    $pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

    /* Calcular total de produtos comprados */
    $sqlTotalProdutos = "
        SELECT SUM(pi.quantidade) AS total
        FROM pedidos p
        JOIN pedido_itens pi ON pi.pedido_id = p.id
        WHERE p.cliente_id = ?
    ";
    $stmtTotal = $pdo->prepare($sqlTotalProdutos);
    $stmtTotal->execute([$clienteSelecionado]);
    $totalProdutos = (int) $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

    /* Calcula bônus esperados */
    $bonusEsperados = floor($totalProdutos / 10);

    /* 4️⃣ Ver bônus já existentes */
    $sqlBonusAtuais = "SELECT COUNT(*) AS total FROM bonus WHERE cliente_id = ?";
    $stmtBonusAtuais = $pdo->prepare($sqlBonusAtuais);
    $stmtBonusAtuais->execute([$clienteSelecionado]);
    $bonusAtuais = (int) $stmtBonusAtuais->fetch(PDO::FETCH_ASSOC)['total'];

    /* 5️⃣ Criar bônus faltantes (REGRA GARANTIDA) */
    if ($bonusEsperados > $bonusAtuais) {
        $sqlInsertBonus = "
            INSERT INTO bonus (cliente_id, descricao)
            VALUES (?, ?)
        ";
        $stmtInsert = $pdo->prepare($sqlInsertBonus);

        for ($i = $bonusAtuais; $i < $bonusEsperados; $i++) {
            $stmtInsert->execute([
                $clienteSelecionado,
                "Bônus por completar 10 produtos comprados"
            ]);
        }
    }

    /* 6️⃣ Buscar bônus atualizados */
    $sqlBonus = "
        SELECT descricao, data
        FROM bonus
        WHERE cliente_id = ?
        ORDER BY data DESC
    ";
    $stmtBonus = $pdo->prepare($sqlBonus);
    $stmtBonus->execute([$clienteSelecionado]);
    $bonus = $stmtBonus->fetchAll(PDO::FETCH_ASSOC);

    /* 7️⃣ Calcular quanto falta para o próximo bônus */
    $resto = $totalProdutos % 10;
    $faltamProdutos = ($resto === 0) ? 0 : (10 - $resto);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico do Cliente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }
        .container {
            width: 750px;
            margin: 40px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
        }
        h2 {
            text-align: center;
        }
        select, button {
            width: 100%;
            padding: 8px;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background: #f0f0f0;
        }
        .bonus-box {
            background: #fff7ed;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .info {
            background: #e6fffa;
            padding: 10px;
            border-radius: 6px;
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Histórico do Cliente</h2>

    <form method="GET">
        <label>Selecione o cliente</label>
        <select name="cliente_id" required>
            <option value="">Selecione</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($clienteSelecionado == $c['id']) ? 'selected' : '' ?>>
                    <?= $c['nome'] ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Ver Histórico</button>
    </form>

    <?php if ($clienteSelecionado): ?>

        <div class="info">
            Total de produtos comprados: <?= $totalProdutos ?><br>
            <?php if ($faltamProdutos > 0): ?>
                Faltam <?= $faltamProdutos ?> produtos para o próximo bônus 🎯
            <?php else: ?>
                🎉 Próximo pedido já conta para um novo bônus!
            <?php endif; ?>
        </div>

        <h3>Pedidos</h3>

        <?php if ($pedidos): ?>
            <table>
                <tr>
                    <th>Pedido</th>
                    <th>Total de Produtos</th>
                </tr>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td>#<?= $p['pedido_id'] ?></td>
                        <td><?= $p['total_produtos'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Este cliente ainda não possui pedidos.</p>
        <?php endif; ?>

        <h3>Bônus</h3>

        <?php if ($bonus): ?>
            <?php foreach ($bonus as $b): ?>
                <div class="bonus-box">
                    <?= $b['descricao'] ?><br>
                    <small><?= date('d/m/Y H:i', strtotime($b['data'])) ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Este cliente ainda não possui bônus.</p>
        <?php endif; ?>

    <?php endif; ?>
</div>

</body>
</html>
