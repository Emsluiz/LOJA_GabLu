<?php

require_once __DIR__ . "/../config/database.php";

/* =====================================
BUSCAR CLIENTES E PRODUTOS
===================================== */

$clientes = $pdo->query("
    SELECT id, nome, telefone
    FROM clientes
")->fetchAll(PDO::FETCH_ASSOC);

$produtos = $pdo->query("
    SELECT id, nome
    FROM produtos
")->fetchAll(PDO::FETCH_ASSOC);

$msg = "";
$msgBonus = "";

/* =====================================
CRIAR PEDIDO
===================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $cliente_id = (int) ($_POST["cliente_id"] ?? 0);
    $produto_id = (int) ($_POST["produto_id"] ?? 0);
    $quantidade = (int) ($_POST["quantidade"] ?? 0);

    if ($cliente_id <= 0 || $produto_id <= 0 || $quantidade <= 0) {
        $msg = "Dados inválidos.";
    } else {

        try {
            $pdo->beginTransaction();

            /* =====================================
            1️⃣ CRIAR PEDIDO
            ===================================== */
            $stmt = $pdo->prepare("
                INSERT INTO pedidos (cliente_id)
                VALUES (?)
            ");
            $stmt->execute([$cliente_id]);
            $pedido_id = $pdo->lastInsertId();

            /* =====================================
            2️⃣ INSERIR ITEM
            ===================================== */
            $stmt = $pdo->prepare("
                INSERT INTO pedido_itens (pedido_id, produto_id, quantidade)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pedido_id, $produto_id, $quantidade]);

            /* =====================================
            3️⃣ BUSCAR SALDO
            ===================================== */
            $stmt = $pdo->prepare("
                SELECT saldo_itens
                FROM clientes
                WHERE id = ?
            ");
            $stmt->execute([$cliente_id]);
            $saldoAtual = (int) $stmt->fetchColumn();

            /* =====================================
            4️⃣ CALCULAR BÔNUS
            ===================================== */
            $totalAgora = $saldoAtual + $quantidade;
            $bonusGerados = intdiv($totalAgora, 10);
            $novoSaldo = $totalAgora % 10;

            /* =====================================
            5️⃣ ATUALIZAR SALDO
            ===================================== */
            $stmt = $pdo->prepare("
                UPDATE clientes
                SET saldo_itens = ?
                WHERE id = ?
            ");
            $stmt->execute([$novoSaldo, $cliente_id]);

            /* =====================================
            6️⃣ GERAR BÔNUS
            ===================================== */
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

            /* =====================================
            FINALIZAR TRANSAÇÃO
            ===================================== */
            $pdo->commit();

            /* =====================================
            BUSCAR DADOS DO CLIENTE
            ===================================== */
            $stmt = $pdo->prepare("
                SELECT nome, telefone
                FROM clientes
                WHERE id = ?
            ");
            $stmt->execute([$cliente_id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            /* =====================================
            FORMATAR TELEFONE
            ===================================== */
            $telefone = preg_replace('/[^0-9]/', '', $cliente["telefone"]);

            /* =====================================
            CRIAR MENSAGEM
            ===================================== */
            $faltam = 10 - $novoSaldo;

            $mensagemWhatsapp = "Olá {$cliente["nome"]}!\n\n";
            $mensagemWhatsapp .= "Seu pedido foi registrado com sucesso.\n";
            $mensagemWhatsapp .= "Quantidade do pedido: {$quantidade}\n";
            $mensagemWhatsapp .= "Saldo atual: {$novoSaldo} produtos.\n\n";

            if ($bonusGerados > 0) {
                $mensagemWhatsapp .= "Parabéns!\nVocê ganhou {$bonusGerados} bônus!\n";
            } else {
                $mensagemWhatsapp .= "Faltam apenas {$faltam} produtos para ganhar seu bônus\n";
            }

            $mensagemWhatsapp .= "\nObrigado pela preferência!";
            $mensagemWhatsapp = urlencode($mensagemWhatsapp);

            /* =====================================
            LINK WHATSAPP CORRIGIDO
            ===================================== */
            $linkWhatsapp = "https://whatsapp.com" . $telefone . "&text=" . $mensagemWhatsapp;

            /* =====================================
            REDIRECIONAR
            ===================================== */
            if (!headers_sent()) {
                header("Location: " . $linkWhatsapp);
                exit;
            } else {
                echo "<script>window.location.href='" . $linkWhatsapp . "';</script>";
                exit;
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = $e->getMessage();
        }

    } // <-- CHAVE ADICIONADA: Fecha o 'else' da validação inicial
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Pedido - Sistema Loja</title>
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
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        
        /* Seletores e inputs unificados com o seu design de Clientes */
        input, select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; transition: 0.2s; background-color: #fff; }
        input:focus, select:focus { border-color: #3498db; outline: none; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }
        
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 15px; font-weight: 600; transition: 0.3s; display: inline-block; text-decoration: none; text-align: center; }
        .btn-search { background: #34495e; color: white; padding: 12px 20px; }
        .btn-search:hover { background: #2c3e50; }
        
        /* Mensagens de Feedback */
        .mensagem { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 25px; border-left: 5px solid #2ecc71; font-weight: 500; }
        
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>

    <!-- Menu Lateral de Navegação Unificado -->
    <nav class="sidebar">
        <h2>Gerenciamento</h2>
        <ul>
            <li><a href="/">🏠 Início</a></li>
            <li><a href="clientes.php">👥 Clientes</a></li>
            <li><a href="historico_cliente.php">📜 Histórico de Clientes</a></li>
            <li><a href="cadastrar_produto.php">📦 Cadastrar Produto</a></li>
            <li><a href="visualizar_produtos.php">👁️ Visualizar Produtos</a></li>
            <li><a href="criar_pedido.php">🛒 Criar Pedido</a></li>
        </ul>
    </nav>

    <!-- Conteúdo da Página -->
    <main class="main-content">
        <div class="header">
            <h1>Gerenciamento de Pedidos</h1>
            <p>Consulte, cadastre e altere os dados dos pedidos da loja.</p>
        </div>

        <!-- Exibição de Erros / Sucesso com base no seu padrão CSS -->
        <?php if (!empty($msg)): ?>
            <div class="mensagem" style="background: #f8d7da; color: #721c24; border-left-color: #dc3545;">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($msgBonus)): ?>
            <div class="mensagem">
                <?= htmlspecialchars($msgBonus) ?>
            </div>
        <?php endif; ?>

        <!-- Bloco do Formulário Padronizado -->
        <div class="card-panel">
            <h3>🛒 Novo Pedido</h3>
            
            <form method="POST">
                <div class="form-row">
                    <select name="cliente_id" required>
                        <option value="">Selecione o cliente</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="produto_id" required>
                        <option value="">Selecione o produto</option>
                        <?php foreach ($produtos as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <input type="number" name="quantidade" placeholder="Quantidade" min="1" required>
                </div>

                <button type="submit" class="btn btn-search">Finalizar Pedido</button>
            </form>
        </div>
    </main>

</body>
</html>
