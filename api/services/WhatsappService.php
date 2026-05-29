<?php

class WhatsappService {
    public static function enviar($telefone, $mensagem) {
        $evolution_url = "http://localhost:8080"; 
        $instance_name = "SuaInstancia";          
        $apikey        = "SuaApiKeyAqui";         

        $url_envio = $evolution_url . "/message/sendText/" . $instance_name;
        
        $payload = json_encode([
            "number" => "55" . preg_replace('/[^0-9]/', '', $telefone),
            "text" => $mensagem,
            "delay" => 1200, 
            "linkPreview" => false
        ]);

        $ch = curl_init($url_envio);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "apikey: " . $apikey
        ]);

        // Executa sem travar o sistema caso o servidor do whats esteja offline
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
}
