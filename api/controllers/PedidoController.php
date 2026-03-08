<?php

class PedidoController {

    /* ================================
       🔐 CRIAR PEDIDO
    ================================== */
    public static function criar($pdo) {

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode(["erro" => "Método não permitido"]);
            return;
        }

        $dados = json_decode(file_get_contents("php://input"), true);

        if (!isset($dados["cliente_id"], $dados["produto_id"], $dados["quantidade"])) {
            http_response_code(400);
            echo json_encode(["erro" => "Dados incompletos"]);
            return;
        }

        $cliente_id = (int) $dados["cliente_id"];
        $produto_id = (int) $dados["produto_id"];
        $quantidade = (int) $dados["quantidade"];

        if ($quantidade <= 0) {
            http_response_code(400);
            echo json_encode(["erro" => "Quantidade inválida"]);
            return;
        }

        try {

            $pdo->beginTransaction();

            /* 1️⃣ Criar pedido */
            $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id) VALUES (?)");
            $stmt->execute([$cliente_id]);
            $pedido_id = $pdo->lastInsertId();

            /* 2️⃣ Inserir item */
            $stmt = $pdo->prepare("
                INSERT INTO pedido_itens (pedido_id, produto_id, quantidade)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pedido_id, $produto_id, $quantidade]);

            /* 3️⃣ Buscar saldo atual */
            $stmt = $pdo->prepare("SELECT saldo_itens FROM clientes WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $saldoAtual = (int) $stmt->fetchColumn();

            /* 4️⃣ Calcular bônus */
            $totalAgora = $saldoAtual + $quantidade;
            $bonusGerados = intdiv($totalAgora, 10);
            $novoSaldo = $totalAgora % 10;

            /* 5️⃣ Atualizar saldo */
            $stmt = $pdo->prepare("UPDATE clientes SET saldo_itens = ? WHERE id = ?");
            $stmt->execute([$novoSaldo, $cliente_id]);

            /* 6️⃣ Criar bônus se houver */
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
            }

            $pdo->commit();

            http_response_code(201);
            echo json_encode([
                "sucesso" => true,
                "pedido_id" => $pedido_id,
                "bonus_gerados" => $bonusGerados,
                "saldo_atual" => $novoSaldo
            ]);

        } catch (Exception $e) {

            $pdo->rollBack();
            http_response_code(500);

            echo json_encode([
                "erro" => "Erro interno",
                "detalhe" => $e->getMessage()
            ]);
        }
    }

    /* ================================
       📜 HISTÓRICO
    ================================== */
    public static function historico($pdo) {

        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            http_response_code(405);
            echo json_encode(["erro" => "Método não permitido"]);
            return;
        }

        $cliente_id = $_GET["cliente_id"] ?? null;

        if (!$cliente_id) {
            http_response_code(400);
            echo json_encode(["erro" => "cliente_id obrigatório"]);
            return;
        }

        try {

            $stmt = $pdo->prepare("
                SELECT 
                    p.id AS pedido_id,
                    pr.nome AS produto,
                    pi.quantidade,
                    p.data AS data_pedido
                FROM pedidos p
                JOIN pedido_itens pi ON p.id = pi.pedido_id
                JOIN produtos pr ON pi.produto_id = pr.id
                WHERE p.cliente_id = ?
                ORDER BY p.data DESC
            ");

            $stmt->execute([$cliente_id]);

            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($resultado);

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                "erro" => "Erro interno",
                "detalhe" => $e->getMessage()
            ]);
        }
    }
}
