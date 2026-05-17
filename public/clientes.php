<?php

require_once __DIR__ . "/../config/database.php";

$mensagem = "";

# =====================================
# EDITAR CLIENTE
# =====================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["salvar_edicao"])
) {

    $id        = (int) $_POST["id"];

    $nome      = trim($_POST["nome"] ?? "");
    $telefone  = trim($_POST["telefone"] ?? "");
    $cidade    = trim($_POST["cidade"] ?? "");

    if ($nome && $telefone && $cidade) {

        $sql = "
            UPDATE clientes
            SET
                nome = ?,
                telefone = ?,
                cidade = ?
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $nome,
            $telefone,
            $cidade,
            $id
        ]);

        header("Location: clientes.php?sucesso=editado");

        exit;
    }
}

# =====================================
# CADASTRAR CLIENTE
# =====================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["cadastrar"])
) {

    $nome      = trim($_POST["nome"] ?? "");
    $telefone  = trim($_POST["telefone"] ?? "");
    $cidade    = trim($_POST["cidade"] ?? "");

    if ($nome && $telefone && $cidade) {

        $sql = "
            INSERT INTO clientes
            (nome, telefone, cidade)
            VALUES (?, ?, ?)
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $nome,
            $telefone,
            $cidade
        ]);

        header("Location: clientes.php?sucesso=cadastrado");

        exit;
    }
}

# =====================================
# EXCLUIR CLIENTE
# =====================================

if (isset($_GET["excluir"])) {

    $id = (int) $_GET["excluir"];

    $sql = "DELETE FROM clientes WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);

    header("Location: clientes.php?sucesso=excluido");

    exit;
}

# =====================================
# MENSAGENS
# =====================================

if (isset($_GET["sucesso"])) {

    if ($_GET["sucesso"] === "cadastrado") {

        $mensagem = "Cliente cadastrado com sucesso!";
    }

    elseif ($_GET["sucesso"] === "editado") {

        $mensagem = "Cliente atualizado com sucesso!";
    }

    elseif ($_GET["sucesso"] === "excluido") {

        $mensagem = "Cliente excluído com sucesso!";
    }
}

# =====================================
# BUSCAR CLIENTE PARA EDIÇÃO
# =====================================

$clienteEditar = null;

if (isset($_GET["editar"])) {

    $id = (int) $_GET["editar"];

    $sql = "SELECT * FROM clientes WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);

    $clienteEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

# =====================================
# BUSCAR CLIENTES
# =====================================

$busca = trim($_GET["buscar"] ?? "");

if ($busca !== "") {

    $sql = "
        SELECT *
        FROM clientes
        WHERE
            nome LIKE ?
            OR telefone LIKE ?
            OR cidade LIKE ?
        ORDER BY id DESC
    ";

    $stmt = $pdo->prepare($sql);

    $pesquisa = "%$busca%";

    $stmt->execute([
        $pesquisa,
        $pesquisa,
        $pesquisa
    ]);

    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {

    $sql = "SELECT * FROM clientes ORDER BY id DESC";

    $clientes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

<meta charset="UTF-8">

<title>Gerenciar Clientes</title>

<style>

body {

    font-family: Arial;

    background: #f4f4f4;

    padding: 40px;
}

.container {

    max-width: 1000px;

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

input {

    width: 100%;

    padding: 10px;

    margin-bottom: 15px;

    border: 1px solid #ccc;

    border-radius: 5px;

    box-sizing: border-box;
}

button {

    padding: 12px 20px;

    background: #007bff;

    color: white;

    border: none;

    border-radius: 5px;

    cursor: pointer;

    margin-bottom: 15px;
}

button:hover {

    background: #0056b3;
}

table {

    width: 100%;

    margin-top: 30px;

    border-collapse: collapse;
}

table th,
table td {

    border: 1px solid #ccc;

    padding: 12px;

    text-align: left;
}

table th {

    background: #f0f0f0;
}

.excluir {

    color: red;

    text-decoration: none;

    font-weight: bold;
}

.editar {

    color: blue;

    text-decoration: none;

    font-weight: bold;
}

.mensagem {

    background: #d4edda;

    color: #155724;

    padding: 12px;

    border-radius: 5px;

    margin-bottom: 20px;
}

.busca {

    margin-top: 30px;
}

.sem-clientes {

    text-align: center;

    padding: 20px;

    color: #666;
}

</style>

</head>

<body>

<div class="container">

<h2>Gerenciar Clientes</h2>

<?php if ($mensagem): ?>

<div class="mensagem">
    <?= htmlspecialchars($mensagem) ?>
</div>

<?php endif; ?>

<!-- =========================
FORMULÁRIO
========================= -->

<form method="POST">

<?php if ($clienteEditar): ?>

<input
    type="hidden"
    name="id"
    value="<?= $clienteEditar["id"] ?>"
>

<?php endif; ?>

<input
    type="text"
    name="nome"
    placeholder="Nome"
    required
    value="<?= htmlspecialchars($clienteEditar["nome"] ?? "") ?>"
>

<input
    type="text"
    name="telefone"
    placeholder="Telefone"
    required
    value="<?= htmlspecialchars($clienteEditar["telefone"] ?? "") ?>"
>

<input
    type="text"
    name="cidade"
    placeholder="Cidade"
    required
    value="<?= htmlspecialchars($clienteEditar["cidade"] ?? "") ?>"
>

<?php if ($clienteEditar): ?>

<button
    type="submit"
    name="salvar_edicao"
>
    Salvar Alterações
</button>
<button>
    <a href="clientes.php">
        Cancelar edição
</button>
<?php else: ?>

<button
    type="submit"
    name="cadastrar"
>
    Cadastrar
</button>

<?php endif; ?>

</form>

<!-- =========================
BUSCA
========================= -->

<div class="busca">

<form method="GET">

<input
    type="text"
    name="buscar"
    placeholder="Buscar por nome, telefone ou cidade"
    value="<?= htmlspecialchars($_GET["buscar"] ?? "") ?>"
>

<button type="submit">
    Buscar
</button>

</form>

</div>

<!-- =========================
TABELA
========================= -->

<table>

<tr>
    <th>ID</th>
    <th>Nome</th>
    <th>Telefone</th>
    <th>Cidade</th>
    <th>Ações</th>
</tr>

<?php if (count($clientes) > 0): ?>

<?php foreach ($clientes as $cliente): ?>

<tr>

<td><?= $cliente["id"] ?></td>

<td><?= htmlspecialchars($cliente["nome"] ?? "") ?></td>

<td><?= htmlspecialchars($cliente["telefone"] ?? "") ?></td>

<td><?= htmlspecialchars($cliente["cidade"] ?? "") ?></td>

<td>

<a
    class="editar"
    href="?editar=<?= $cliente["id"] ?>"
>
    Editar
</a>

|

<a
    target="_blank"
    href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $cliente["telefone"]) ?>?text=Olá%20<?= urlencode($cliente["nome"]) ?>"
>
    WhatsApp
</a>

|

<a
    class="excluir"
    href="?excluir=<?= $cliente["id"] ?>"
    onclick="return confirm('Excluir cliente?')"
>
    Excluir
</a>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td
    colspan="5"
    class="sem-clientes"
>
    Nenhum cliente encontrado.
</td>

</tr>

<?php endif; ?>

</table>

</div>

</body>
</html>