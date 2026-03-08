<?php
echo password_hash("123456", PASSWORD_DEFAULT);
exit;


require_once __DIR__ . "/../config/database.php";


header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["erro" => "Método não permitido"]);
    exit;
}

$dados = json_decode(file_get_contents("php://input"), true);

$email = $dados["email"] ?? '';
$senha = $dados["senha"] ?? '';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($senha, $usuario["senha"])) {
    echo json_encode(["erro" => "Login inválido"]);
    exit;
}

// Token simples (base64)
$token = base64_encode($usuario["id"] . "|" . time());

echo json_encode([
    "sucesso" => true,
    "token" => $token
]);
