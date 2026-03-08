<?php
require_once __DIR__ . "/../config/database.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome  = trim($_POST["nome"] ?? "");
    $preco = $_POST["preco"] ?? "";

    if ($nome === "" || !is_numeric($preco) || $preco <= 0) {
        $mensagem = "Informe um nome válido e um preço maior que zero.";
    } else {
        $preco = (float) $preco;

        $sql = "INSERT INTO produtos (nome, preco) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $preco]);

        $mensagem = "Produto cadastrado com sucesso!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Produto</title>
</head>
<body>

<h2>Cadastrar Produto</h2>

<?php if ($mensagem): ?>
    <p><?= htmlspecialchars($mensagem) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nome do produto:</label><br>
    <input type="text" name="nome" required><br><br>

    <label>Preço:</label><br>
    <input type="number" name="preco" step="0.01" min="0.01" required><br><br>

    <button type="submit">Cadastrar</button>
</form>

</body>
</html>
