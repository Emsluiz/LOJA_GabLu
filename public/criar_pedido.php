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

    if (
        $cliente_id <= 0
        || $produto_id <= 0
        || $quantidade <= 0
    ) {

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
                INSERT INTO pedido_itens
                (
                    pedido_id,
                    produto_id,
                    quantidade
                )
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $pedido_id,
                $produto_id,
                $quantidade
            ]);

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

            $stmt->execute([
                $novoSaldo,
                $cliente_id
            ]);

            /* =====================================
            6️⃣ GERAR BÔNUS
            ===================================== */

            if ($bonusGerados > 0) {

                $stmt = $pdo->prepare("
                    INSERT INTO bonus
                    (
                        cliente_id,
                        descricao
                    )
                    VALUES (?, ?)
                ");

                for ($i = 0; $i < $bonusGerados; $i++) {

                    $stmt->execute([
                        $cliente_id,
                        "Bônus por completar 10 produtos"
                    ]);
                }

                $msgBonus =
                    "🎉 Cliente ganhou {$bonusGerados} bônus!";
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

            $telefone = preg_replace(
                '/[^0-9]/',
                '',
                $cliente["telefone"]
            );

            /* =====================================
            CRIAR MENSAGEM
            ===================================== */

            $faltam = 10 - $novoSaldo;

$mensagemWhatsapp = "
Olá {$cliente["nome"]}!

Seu pedido foi registrado com sucesso.
Quantidade do pedido: {$quantidade}
Saldo atual: {$novoSaldo} produtos.

";

if ($bonusGerados > 0) {

    $mensagemWhatsapp .=
    "Parabéns!
Você ganhou {$bonusGerados} bônus!
";

} else {

    $mensagemWhatsapp .= "
Faltam apenas {$faltam} produtos para ganhar seu bônus
";
}

$mensagemWhatsapp .= "

Obrigado pela preferência!
";
            $mensagemWhatsapp = urlencode(
                $mensagemWhatsapp
            );

            /* =====================================
            LINK WHATSAPP
            ===================================== */

            $linkWhatsapp =
                "https://wa.me/55{$telefone}?text={$mensagemWhatsapp}";

            /* =====================================
            REDIRECIONAR
            ===================================== */

            header("Location: $linkWhatsapp");

            exit;

        } catch (Exception $e) {

            $pdo->rollBack();

            $msg = $e->getMessage();

        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Pedido - Sistema Loja</title>
    <style>
        /* CSS Base do Painel Administrativo */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; overflow-x: hidden; max-width: 100%; }
        
        /* Menu Lateral (Sidebar) */
        .sidebar { width: 250px; height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 22px; letter-spacing: 1px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin-bottom: 15px; }
        .sidebar ul li a { color: #ecf0f1; text-decoration: none; display: block; padding: 12px; border-radius: 5px; transition: 0.3s; }
        .sidebar ul li a:hover { background-color: #34495e; padding-left: 20px; }
        
        /* Área do Conteúdo Principal */
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .header { margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; }
        .header h1 { color: #2c3e50; }
        
        /* Box onde fica o seu formulário atual */
        .card-panel { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
        }
    </style>
</head>

<body>

<div class="container">

<h2>Criar Pedido</h2>

<?php if (!empty($msg)): ?>

<div class="erro">
    <?= htmlspecialchars($msg) ?>
</div>

<?php endif; ?>

<?php if (!empty($msgBonus)): ?>

<div class="msg">
    <?= htmlspecialchars($msgBonus) ?>
</div>

<?php endif; ?>

<form method="POST">

<select
    name="cliente_id"
    required
>

<option value="">
    Selecione o cliente
</option>

<?php foreach ($clientes as $c): ?>

<option value="<?= $c['id'] ?>">

    <?= htmlspecialchars($c['nome']) ?>

</option>

<?php endforeach; ?>

</select>

<select
    name="produto_id"
    required
>

<option value="">
    Selecione o produto
</option>

<?php foreach ($produtos as $p): ?>

<option value="<?= $p['id'] ?>">

    <?= htmlspecialchars($p['nome']) ?>

</option>

<?php endforeach; ?>

</select>

<input
    type="number"
    name="quantidade"
    placeholder="Quantidade"
    min="1"
    required
>

<button type="submit">

    Finalizar Pedido

</button>

</form>

</div>

</body>
</html>