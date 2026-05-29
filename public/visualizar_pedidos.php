<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../api/services/WhatsappService.php";

$mensagem = "";
$tipoMensagem = "sucesso";

# =====================================
# ATUALIZAÇÃO DE STATUS E NOTIFICAÇÃO AUTOMÁTICA
# =====================================
if (isset($_GET["alterar_status"]) && isset($_GET["novo_status"])) {
    $pedido_id = (int)$_GET["alterar_status"];
    $novo_status = trim($_GET["novo_status"]);
    $filtro_atual = trim($_GET["filtro_status"] ?? "");

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM status_pedido WHERE nome = ?");
    $stmtCheck->execute([$novo_status]);
    $statusExiste = (int)$stmtCheck->fetchColumn();

    if ($pedido_id > 0 && $statusExiste > 0) {
        try {
            $pdo->beginTransaction();

            $stmtPedido = $pdo->prepare("SELECT cliente_id, status FROM pedidos WHERE id = ?");
            $stmtPedido->execute([$pedido_id]);
            $pedidoDados = $stmtPedido->fetch(PDO::FETCH_ASSOC);

            if ($pedidoDados) {
                $cliente_id = (int)$pedidoDados["cliente_id"];
                $statusAntigo = $pedidoDados["status"];

                // Se o status mudou, buscamos os dados do cliente para notificar
                if ($statusAntigo !== $novo_status) {
                    
                    $stmtCliente = $pdo->prepare("SELECT nome, telefone, saldo_itens FROM clientes WHERE id = ?");
                    $stmtCliente->execute([$cliente_id]);
                    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);
                    $novoSaldo = (int)$cliente["saldo_itens"];
                    $bonusGerados = 0;

                    // Regra específica caso mude para Finalizado (Calcula Bônus)
                    if ($novo_status === "Finalizado") {
                        $stmtQtd = $pdo->prepare("SELECT SUM(quantidade) FROM pedido_itens WHERE pedido_id = ?");
                        $stmtQtd->execute([$pedido_id]);
                        $quantidadePedida = (int)$stmtQtd->fetchColumn();

                        $totalAgora = $novoSaldo + $quantidadePedida;
                        $bonusGerados = intdiv($totalAgora, 10);
                        $novoSaldo = $totalAgora % 10;

                        $stmtUpdateCliente = $pdo->prepare("UPDATE clientes SET saldo_itens = ? WHERE id = ?");
                        $stmtUpdateCliente->execute([$novoSaldo, $cliente_id]);

                        if ($bonusGerados > 0) {
                            $stmtInsertBonus = $pdo->prepare("INSERT INTO bonus (cliente_id, descricao) VALUES (?, ?)");
                            for ($i = 0; $i < $bonusGerados; $i++) {
                                $stmtInsertBonus->execute([$cliente_id, "Bônus por completar 10 produtos comprados"]);
                            }
                        }
                    }

                    // Montagem do texto de notificação de alteração de status
                    $textoWhats = "Olá " . $cliente["nome"] . "!\n\nA situação da sua encomenda de pudim mudou.\nNovo Status: " . $novo_status . "\nSaldo de fidelidade: " . $novoSaldo . " produtos.\n\n";
                    
                    if ($novo_status === "Finalizado" && $bonusGerados > 0) {
                        $textoWhats .= "Parabéns! Você acumulou pontos e ganhou " . $bonusGerados . " bônus! 🎁\n";
                    } elseif ($novo_status !== "Cancelado") {
                        $faltam = 10 - $novoSaldo;
                        $textoWhats .= "Faltam " . $faltam . " pudins para seu próximo prêmio grátis. 🎯\n";
                    }
                    $textoWhats .= "\nObrigado pela preferência!";

                    // Disparo silencioso via Evolution API nos bastidores
                    WhatsappService::enviar($cliente["telefone"], $textoWhats);
                }
            }

            $sql = "UPDATE pedidos SET status = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$novo_status, $pedido_id]);

            $pdo->commit();
            header("Location: visualizar_pedidos.php?sucesso=atualizado&filtro_status=" . urlencode($filtro_atual));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            header("Location: visualizar_pedidos.php?erro=sistema");
            exit;
        }
    }
}

if (isset($_GET["sucesso"]) && $_GET["sucesso"] === "atualizado") {
    $mensagem = "Situação da encomenda alterada e cliente notificado automaticamente.";
    $tipoMensagem = "sucesso";
} elseif (isset($_GET["erro"])) {
    $tipoMensagem = "erro";
    $mensagem = "Erro interno: Não foi possível processar a alteração no banco de dados.";
}

$filtroStatus = trim($_GET["filtro_status"] ?? "");
$statusDisponiveis = $pdo->query("SELECT nome FROM status_pedido ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

$sqlPedidos = "
    SELECT p.id AS pedido_id, p.data AS data_pedido, p.status AS status_pedido, c.nome AS nome_cliente, IFNULL(SUM(pi.quantidade), 0) AS total_produtos
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    LEFT JOIN pedido_itens pi ON p.id = pi.pedido_id
";
if (!empty($filtroStatus)) {
    $sqlPedidos .= " WHERE p.status = ? ";
}
$sqlPedidos .= " GROUP BY p.id ORDER BY p.id DESC ";

$stmtPedidos = $pdo->prepare($sqlPedidos);
if (!empty($filtroStatus)) {
    $stmtPedidos->execute([$filtroStatus]);
} else {
    $stmtPedidos->execute();
}
$listaPedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Pedidos - Sistema Loja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; overflow-x: hidden; max-width: 100%; }
        
        .sidebar { width: 250px; height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 22px; letter-spacing: 1px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin-bottom: 15px; }
        .sidebar ul li a { color: #ecf0f1; text-decoration: none; display: block; padding: 12px; border-radius: 5px; transition: 0.3s; }
        .sidebar ul li a:hover { background-color: #34495e; padding-left: 20px; }
        
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .header { margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; }
        .header h1 { color: #2c3e50; }
        
        .card-panel { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card-panel h3 { color: #2c3e50; margin-bottom: 20px; font-size: 20px; border-left: 4px solid #3498db; padding-left: 10px; }
        
        .filtro-container { display: flex; gap: 10px; margin-bottom: 25px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; flex: 1; }
        label { margin-bottom: 8px; font-weight: 600; color: #34495e; font-size: 15px; }
        select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; background: white; }
        
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 15px; font-weight: 600; transition: 0.3s; display: inline-block; text-decoration: none; text-align: center; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        
        .badge-status { font-weight: bold; padding: 6px 12px; border-radius: 4px; font-size: 13px; display: inline-block; background: #e0e0e0; color: #333; }
        .select-acao { padding: 6px; font-size: 13px; border: 1px solid #ced4da; border-radius: 4px; background-color: #fff; cursor: pointer; width: 100%; }
        
        .status-Finalizado { background: #e2fbe8; color: #1e7e34; }
        .status-Cancelado { background: #f8d7da; color: #721c24; }
        .status-Em-Preparo { background: #fff3cd; color: #856404; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        table th, table td { padding: 14px; text-align: center; border-bottom: 1px solid #e0e0e0; font-size: 15px; }
        table th { background: #f8f9fa; color: #34495e; font-weight: 600; }
        table tr:hover { background-color: #fcfcfc; }
        
        .mensagem { padding: 15px; border-radius: 5px; margin-bottom: 25px; border-left: 5px solid #2ecc71; font-weight: 500; background: #d4edda; color: #155724; }
        .sem-dados { text-align: center; padding: 30px; color: #7f8c8d; font-style: italic; }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Gerenciamento</h2>
        <ul>
            <li><a href="http://localhost:8000/index.php">Início</a></li>
            <li><a href="http://localhost:8000/public/clientes.php">Clientes</a></li>
            <li><a href="http://localhost:8000/public/historico_cliente.php">Histórico de Clientes</a></li>
            <li><a href="http://localhost:8000/public/cadastrar_produto.php">Cadastrar Produto</a></li>
            <li><a href="http://localhost:8000/public/visualizar_produtos.php">Visualizar Produtos</a></li>
            <li><a href="http://localhost:8000/public/cadastrar_status.php">Gerenciar Status</a></li>
            <li><a href="http://localhost:8000/public/visualizar_pedidos.php">Visualizar Pedidos</a></li>
            <li><a href="http://localhost:8000/public/criar_pedido.php">Criar Pedido</a></li>
        </ul>


    </nav>

    <main class="main-content">
        <div class="header">
            <h1>Controle de Encomendas</h1>
            <p>Monitore os pedidos de pudim e faça a gestão do fluxo de entrega de forma dinâmica.</p>
        </div>

        <div class="card-panel">
            <h3>Situação das Encomendas</h3>
            
            <form method="GET" class="filtro-container">
                <div class="form-group">
                    <label for="filtro_status">Filtrar por Situação:</label>
                    <select name="filtro_status" id="filtro_status">
                        <option value="">-- Todos os pedidos registrados --</option>
                        <?php foreach ($statusDisponiveis as $stNome): ?>
                            <option value="<?= htmlspecialchars($stNome) ?>" <?= $filtroStatus === $stNome ? 'selected' : '' ?>>
                                <?= htmlspecialchars($stNome) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>

            <?php if (!empty($mensagem)): ?>
                <div class="mensagem" style="margin-bottom: 25px; <?= $tipoMensagem === 'erro' ? 'background: #f8d7da; color: #721c24; border-left-color: #dc3545;' : '' ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th width="10%">Pedido</th>
                        <th width="25%">Cliente / Comprador</th>
                        <th width="20%">Data do Pedido</th>
                        <th width="12%">Qtd. Pudins</th>
                        <th width="15%">Situação Atual</th>
                        <th width="18%">Alterar Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($listaPedidos) > 0): ?>
                        <?php foreach ($listaPedidos as $p): ?>
                            <tr>
                                <td><strong>#<?= $p["pedido_id"] ?></strong></td>
                                <td><?= htmlspecialchars($p["nome_cliente"] ?? "") ?></td>
                                <td><?= !empty($p["data_pedido"]) ? date('d/m/Y H:i', strtotime($p["data_pedido"])) : '<span style="color:#bbb;">Não salva</span>' ?></td>
                                <td><?= (int)$p["total_produtos"] ?> un.</td>
                                <td>
                                    <?php $classeStatus = str_replace(' ', '-', $p["status_pedido"]); ?>
                                    <span class="badge-status status-<?= $classeStatus ?>">
                                        <?= htmlspecialchars($p["status_pedido"]) ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="select-acao" onchange="location.href='?alterar_status=<?= $p['pedido_id'] ?>&filtro_status=<?= urlencode($filtroStatus) ?>&novo_status=' + encodeURIComponent(this.value)">
                                        <?php foreach ($statusDisponiveis as $stOpcao): ?>
                                            <option value="<?= htmlspecialchars($stOpcao) ?>" <?= $p["status_pedido"] === $stOpcao ? 'selected' : '' ?>>
                                                Mudar para: <?= htmlspecialchars($stOpcao) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="sem-dados">Nenhuma encomenda registrada ou encontrada para este filtro.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
