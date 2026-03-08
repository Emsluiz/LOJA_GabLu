<?php

class AuthController {

    public static function login($pdo) {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(["erro" => "Método não permitido"]);
            return;
        }

        $dados = json_decode(file_get_contents("php://input"), true);

        $email = $dados["email"] ?? '';
        $senha = $dados["senha"] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario || !password_verify($senha, $usuario["senha"])) {
            echo json_encode(["erro" => "Login inválido"]);
            return;
        }

        $token = base64_encode($usuario["id"] . "|" . time());

        echo json_encode([
            "sucesso" => true,
            "token" => $token
        ]);
    }
}
