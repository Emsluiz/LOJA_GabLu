<?php

class AuthMiddleware {

    public static function verificar() {

        $headers = getallheaders();

        if (!isset($headers["Authorization"])) {
            http_response_code(401);
            echo json_encode(["erro" => "Token não enviado"]);
            exit;
        }

        $token = $headers["Authorization"];

        $dados = explode("|", base64_decode($token));

        if (count($dados) !== 2) {
            http_response_code(401);
            echo json_encode(["erro" => "Token inválido"]);
            exit;
        }

        $usuario_id = $dados[0];
        $timestamp = $dados[1];

        // Expira em 2 horas
        if (time() - $timestamp > 7200) {
            http_response_code(401);
            echo json_encode(["erro" => "Token expirado"]);
            exit;
        }

        return $usuario_id;
    }
}
