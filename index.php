<?php
session_start();

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    // LOGIN FIXO BLINDADO: Não usa o banco de dados, entra direto!
    if ($email === "admin@loja.com" && $senha === "123") {
        
        // Cria as credenciais na memória para a trava de segurança aceitar
        $_SESSION["usuario_id"] = 1;
        $_SESSION["usuario_email"] = "admin@loja.com";

        // Redireciona na hora para a tela de clientes
        header("Location: public/clientes.php");
        exit;
    } else {
        $erro = "E-mail ou senha inválidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Loja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f4f6f9; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .login-container { background: white; padding: 40px; width: 100%; max-width: 420px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-top: 5px solid #2c3e50; }
        .header { margin-bottom: 25px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; text-align: center; }
        .header h1 { color: #2c3e50; font-size: 26px; }
        .header p { color: #7f8c8d; margin-top: 5px; font-size: 14px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        label { margin-bottom: 8px; font-weight: 600; color: #34495e; font-size: 14px; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; background: white; }
        input:focus { border-color: #2ecc71; outline: none; box-shadow: 0 0 5px rgba(46,204,113,.3); }
        .btn { width: 100%; padding: 14px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600; transition: 0.3s; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; }
        .mensagem { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; font-weight: 500; background: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; }
    </style>
</head>
<body>

    <!-- Container do Cartão de Login Centralizado -->
    <div class="login-container">
        
        <div class="header">
            <h1>Login</h1>
            <p>Entre com suas credenciais para acessar o sistema.</p>
        </div>

        <!-- Alerta se houver erro de autenticação -->
        <?php if (!empty($erro)): ?>
            <div class="mensagem">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label>E-mail</label>
                <input
                    type="email"
                    name="email"
                    placeholder="Digite seu e-mail"
                    required
                >
            </div>

            <div class="form-group">
                <label>Senha</label>
                <input
                    type="password"
                    name="senha"
                    placeholder="Digite sua senha"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn btn-success"
            >
                Entrar
            </button>

        </form>

    </div>

</body>
</html>
