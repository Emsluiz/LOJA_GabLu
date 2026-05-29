<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle - Sistema Loja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; }
        
        /* Menu Lateral (Sidebar) */
        .sidebar { width: 250px; height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 22px; letter-spacing: 1px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin-bottom: 15px; }
        .sidebar ul li a { color: #ecf0f1; text-decoration: none; display: block; padding: 12px; border-radius: 5px; transition: 0.3s; }
        .sidebar ul li a:hover { background-color: #34495e; padding-left: 20px; }
        
        /* Conteúdo Principal */
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .header { margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; }
        .header h1 { color: #2c3e50; }
        
        /* Cards de Atalho/Resumo */
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #3498db; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card h3 { font-size: 14px; color: #7f8c8d; text-transform: uppercase; margin-bottom: 10px; }
        .card p { font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 15px; }
        
        /* Botões de Ação */
        .btn-nav { display: inline-block; background-color: #3498db; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 14px; transition: background 0.2s; }
        .btn-nav:hover { background-color: #2980b9; }
        
        .card.produtos { border-left-color: #2ecc71; }
        .card.produtos .btn-nav { background-color: #2ecc71; }
        .card.produtos .btn-nav:hover { background-color: #27ae60; }
        
        .card.visualizar-pedidos { border-left-color: #9b59b6; }
        .card.visualizar-pedidos .btn-nav { background-color: #9b59b6; }
        .card.visualizar-pedidos .btn-nav:hover { background-color: #8e44ad; }
        
        .card.pedidos { border-left-color: #e67e22; }
        .card.pedidos .btn-nav { background-color: #e67e22; }
        .card.pedidos .btn-nav:hover { background-color: #d35400; }
    </style>
</head>
<body>

    <!-- Menu Lateral de Navegação Unificado para a Raiz -->
    <nav class="sidebar">
        <h2>Gerenciamento</h2>
        <ul>
            <li><a href="index.php">Início</a></li>
            <li><a href="public/clientes.php">Clientes</a></li>
            <li><a href="public/historico_cliente.php">Histórico de Clientes</a></li>
            <li><a href="public/cadastrar_produto.php">Cadastrar Produto</a></li>
            <li><a href="public/visualizar_produtos.php">Visualizar Produtos</a></li>
            <li><a href="public/cadastrar_status.php">Gerenciar Status</a></li>
            <li><a href="public/visualizar_pedidos.php">Visualizar Pedidos</a></li>
            <li><a href="public/criar_pedido.php">Criar Pedido</a></li>
        </ul>
    </nav>

    <!-- Conteúdo da Página -->
    <main class="main-content">
        <div class="header">
            <h1>Painel Principal</h1>
            <p>Seja bem-vindo ao sistema de controle e encomendas da Loja de Pudins.</p>
        </div>

        <!-- Cards com Botões de Navegação Direta -->
        <div class="dashboard-cards">
            
            <div class="card">
                <h3>Clientes</h3>
                <p>Módulo de Clientes</p>
                <a href="public/clientes.php" class="btn-nav">Visualizar Lista</a>
            </div>

            <div class="card produtos">
                <h3>Produtos</h3>
                <p>Catálogo Geral</p>
                <a href="public/visualizar_produtos.php" class="btn-nav">Ver Produtos</a>
            </div>

            <div class="card visualizar-pedidos">
                <h3>Encomendas</h3>
                <p>Fluxo de Status</p>
                <a href="public/visualizar_pedidos.php" class="btn-nav">Gerenciar Entregas</a>
            </div>

            <div class="card pedidos">
                <h3>Vendas / Caixa</h3>
                <p>Novo Pedido</p>
                <a href="public/criar_pedido.php" class="btn-nav">Abrir Nova Encomenda</a>
            </div>

        </div>
    </main>

</body>
</html>
