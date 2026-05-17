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

<title>Criar Pedido</title>

<style>

body {

    font-family: Arial;

    background: #f4f4f4;

    padding: 40px;
}

.container {

    max-width: 700px;

    margin: auto;

    background: white;

    padding: 30px;

    border-radius: 10px;

    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

h2 {

    text-align: center;

    margin-bottom: 30px;
}

select,
input {

    width: 100%;

    padding: 12px;

    margin-bottom: 15px;

    border: 1px solid #ccc;

    border-radius: 5px;

    box-sizing: border-box;
}

button {

    width: 100%;

    padding: 15px;

    background: #28a745;

    color: white;

    border: none;

    border-radius: 5px;

    font-size: 16px;

    cursor: pointer;
}

button:hover {

    background: #218838;
}

.msg {

    background: #d4edda;

    color: #155724;

    padding: 10px;

    border-radius: 5px;

    margin-bottom: 20px;
}

.erro {

    background: #f8d7da;

    color: #721c24;

    padding: 10px;

    border-radius: 5px;

    margin-bottom: 20px;
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