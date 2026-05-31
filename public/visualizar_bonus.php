<?php
// Inclui a trava de segurança. Quem não tiver e-mail e senha é redirecionado na hora
require_once __DIR__ . "/../config/verificar_login.php";

require_once __DIR__ . "/../config/database.php";

$mensagem = "";
$tipoMensagem = "sucesso";

# =====================================
# DELETAR / MARCAR BÔNUS COMO CONCLUÍDO
# =====================================
if (isset($_GET["resgatar_bonus"]) && isset($_GET["cliente_id"])) {
    $bonus_id = (int)$_GET["resgatar_bonus"];
    $cliente_id = (int)$_GET["cliente_id"];

    if ($bonus_id > 0 && $cliente_id > 0) {
        try {
            $sql = "DELETE FROM bonus WHERE id = ? AND cliente_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$bonus_id, $cliente_id]);
            
            header("Location: visualizar_bonus.php?sucesso=resgatado");
            exit;
        } catch (PDOException $e) {
            header("Location: visualizar_bonus.php?erro=sistema");
            exit;
        }
    }
}

# =====================================
# MENSAGENS (Idêntico ao fluxo de clientes)
# =====================================
if (isset($_GET["sucesso"])) {
    if ($_GET["sucesso"] === "resgatado") {
        $mensagem = "Bônus marcado como concluído com sucesso.";
    }
} elseif (isset($_GET["erro"])) {
    $tipoMensagem = "erro";
    if ($_GET["erro"] === "sistema") {
        $mensagem = "Erro interno ao processar a baixa do bônus.";
    }
}

# =====================================
# BUSCAR LISTA DE BÔNUS ATIVOS
# =====================================
$sql = "
    SELECT b.id AS bonus_id, b.descricao, b.data AS data_gerado, c.nome AS nome_cliente, c.id AS cliente_id, c.telefone 
    FROM bonus b 
    JOIN clientes c ON b.cliente_id = c.id 
    ORDER BY b.id DESC
";
$listaBonus = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Bônus - Sistema Loja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; overflow-x: hidden; max-width: 100%; }
        
        .sidebar { width: 250px; height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 22px; letter-spacing: 1px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin-bottom: 15px; }
        .sidebar ul li a { color: #ecf0f1; text-decoration: none; display: block; padding: 12px; border-radius: 5px; transition: 0.3s; }
        .sidebar ul li a:hover { background-color: #34495e; padding-left: 20px; }
        
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); min-height: 100vh; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; }
        .header h1 { color: #2c3e50; }
        
        .card-panel { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card-panel h3 { color: #2c3e50; margin-bottom: 20px; font-size: 20px; border-left: 4px solid #f97316; padding-left: 10px; }
        
        .bonus-box { display: flex; justify-content: space-between; align-items: center; background: #fff7ed; padding: 15px 20px; border-radius: 6px; margin-bottom: 12px; border: 1px solid #ffedd5; border-left: 5px solid #f97316; }
        .bonus-info strong { font-size: 16px; color: #2c3e50; display: block; margin-bottom: 2px; }
        .bonus-info span { font-size: 14px; color: #e67e22; font-weight: 600; display: block; margin-bottom: 2px; }
        .bonus-info small { color: #95a5a6; display: block; }
        
        .btn-resgate { background: #2ecc71; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 14px; font-weight: bold; transition: 0.3s; display: inline-block; border: none; cursor: pointer; }
        .btn-resgate:hover { background: #27ae60; }
        
        .sem-dados { text-align: center; padding: 40px; color: #7f8c8d; font-style: italic; background: #f8f9fa; border-radius: 6px; border: 1px dashed #ccc; font-size: 15px; }
        .mensagem { padding: 15px; border-radius: 5px; margin-bottom: 25px; border-left: 5px solid #2ecc71; font-weight: 500; background: #d4edda; color: #155724; }
        
        @media (max-width: 768px) { body { flex-direction: column; } .sidebar { width: 100%; height: auto; position: relative; } .main-content { margin-left: 0; width: 100%; padding: 20px; } }
    </style>
</head>
<body>

    <!-- Menu Lateral de Navegação Unificado -->
    <nav class="sidebar">
        <h2>Gerenciamento</h2>
        <ul>
            <!-- Modulo de Clientes -->
            <li style="padding-top: 10px; font-weight: bold; color: #a6b8c7; font-size: 12px; text-transform: uppercase; list-style: none; margin-bottom: 5px;">Clientes</li>
            <li><a href="http://localhost:8000/public/clientes.php">Gerenciar Clientes</a></li>
            <li><a href="http://localhost:8000/public/historico_cliente.php">Historico de Clientes</a></li>
            
            <!-- Modulo de Produtos -->
            <li style="padding-top: 10px; font-weight: bold; color: #a6b8c7; font-size: 12px; text-transform: uppercase; list-style: none; margin-bottom: 5px;">Produtos</li>
            <li><a href="http://localhost:8000/public/cadastrar_produto.php">Cadastrar Produto</a></li>
            <li><a href="http://localhost:8000/public/visualizar_produtos.php">Visualizar Produtos</a></li>
            
            <!-- Modulo de Pedidos e Vendas -->
            <li style="padding-top: 10px; font-weight: bold; color: #a6b8c7; font-size: 12px; text-transform: uppercase; list-style: none; margin-bottom: 5px;">Pedidos</li>
            <li><a href="http://localhost:8000/public/criar_pedido.php">Criar Pedido</a></li>
            <li><a href="http://localhost:8000/public/visualizar_pedidos.php">Visualizar Pedidos</a></li>
            <li><a href="http://localhost:8000/public/visualizar_bonus.php">Visualizar Bônus</a></li>
        </ul>
    </nav>

     <!-- Area de Conteudo Principal -->
    <div class="main-content">
        <div class="header">
            <h1>Gerenciamento de Bônus</h1>
        </div>

        <!-- Alerta de Feedback (Idêntico ao estilo do gerenciar clientes) -->
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem" style="margin-bottom: 30px; <?= $tipoMensagem === 'erro' ? 'background: #f8d7da; color: #721c24; border-left-color: #dc3545;' : '' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="card-panel">
            <h3>Bônus Pendentes de Entrega</h3>

            <?php if (count($listaBonus) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($listaBonus as $bonus): ?>
                        <?php 
                            // MONTAGEM BLINDADA: Junta o endereço oficial com a barra e o número limpo
                            $telefoneLimpo = preg_replace('/[^0-9]/', '', $bonus['telefone'] ?? '');
                            
                            // Texto oficial, atraente e personalizado para o Bônus Fidelidade do cliente
                            $msgWhats = "🎉 *BÔNUS FIDELIDADE DISPONÍVEL!* 🎉\n\nOlá " . trim($bonus['nome_cliente']) . "!\n\nÓtima notícia: você completou a sua meta de compras e acaba de ganhar um prêmio exclusivo do nosso sistema de fidelidade! 🎁\n\nO seu cupom (*" . trim($bonus['descricao']) . "*) já está liberado. Pode passar aqui para retirar o seu prêmio quando quiser!\n\nTe aguardamos com muito carinho! 🥖✨";
                            
                            $urlWhatsFixa = "https://wa.me/" . $telefoneLimpo . "?text=" . urlencode($msgWhats);
                        ?>
                        <div class="bonus-box">
                            <div class="bonus-info">
                                <strong>🎁 <?= htmlspecialchars($bonus['descricao']) ?></strong>
                                <span>Cliente: <?= htmlspecialchars($bonus['nome_cliente']) ?></span>
                                <small>Código do Cupom: #<?= $bonus['bonus_id'] ?> | Gerado em: <?= date("d/m/Y H:i", strtotime($bonus['data_gerado'])) ?></small>
                            </div>
                            
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <!-- Botão do WhatsApp corrigido puxando a URL com a barra explícita -->
                                <a class="btn-resgate" style="background-color: #3498db; display: inline-block;" target="_blank" href="<?= $urlWhatsFixa ?>">WhatsApp</a>
                                
                                <!-- Botão Verde aplicando o redirecionamento com arquivo explícito -->
                                <a href="visualizar_bonus.php?cliente_id=<?= $bonus['cliente_id'] ?>&resgatar_bonus=<?= $bonus['bonus_id'] ?>" 
                                   class="btn-resgate" 
                                   onclick="return confirm('Confirmar a entrega do prêmio para <?= htmlspecialchars($bonus['nome_cliente']) ?> e dar baixa definitiva neste cupom de bônus?')">
                                    Marcar Concluído
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Estado Vazio Inteligente -->
                <div class="sem-dados">
                    <strong>Nenhum bônus pendente de entrega.</strong>
                    <p style="font-size: 14px; margin-top: 5px; color: #95a5a6; font-style: normal;">Todos os cupons gerados automaticamente pelo sistema de fidelidade já foram devidamente resgatados e entregues aos clientes.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

