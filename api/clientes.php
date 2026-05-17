<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {

    $sql = "SELECT * FROM clientes";

    $stmt = $pdo->query($sql);

    echo json_encode(
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
}

elseif ($method === "POST") {

    $dados = json_decode(
        file_get_contents("php://input"),
        true
    );

    $nome   = trim($dados["nome"] ?? "");
    $numero = trim($dados["numero"] ?? "");
    $cidade = trim($dados["cidade"] ?? "");

    if (
        $nome === "" ||
        $numero === "" ||
        $cidade === ""
    ) {

        http_response_code(400);

        echo json_encode([
            "erro" => "Preencha todos os campos"
        ]);

        exit;
    }

    $sql = "
        INSERT INTO clientes
        (nome, numero, cidade)
        VALUES (?, ?, ?)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $nome,
        $numero,
        $cidade
    ]);

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Cliente cadastrado com sucesso"
    ]);
}

else {

    http_response_code(405);

    echo json_encode([
        "erro" => "Método não permitido"
    ]);
}