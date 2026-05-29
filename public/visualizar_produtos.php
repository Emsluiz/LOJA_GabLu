<?php
require_once __DIR__ . "/../config/database.php";

$mensagem = "";
$tipoMensagem = "sucesso";

# =====================================
# DELETAR PRODUTO (TRATADO CONTRA ERROS)
# =====================================
if (isset($_GET["excluir"])) {
    $id = (int) $_GET["excluir"];
    if ($id > 0) {
        try {
            $sql = "DELETE FROM produtos WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            
            header("Location: visualizar_produtos.php?sucesso=excluido");
            exit;
        } catch (PDOException $e) {
            // Código 23000 indica violação de chave estrangeira (produto está em algum pedido)
            if ($e->getCode() == '23000') {
                header("Location: visualizar_produtos.php?erro=vinculado");
                exit;
            } else {
                header("Location: visualizar_produtos.php?erro=sistema");
                exit;
            }
        }
    }
}

# =====================================
# PROCESSAMENTO DE MENSAGENS
# =====================================
if (isset($_GET["sucesso"])) {
    $tipoMensagem = "sucesso";
    if ($_GET["sucesso"] === "excluido") {
        $mensagem = "Produto excluído com sucesso do catálogo!";
    }
} elseif (isset($_GET["erro"])) {
    $tipoMensagem = "erro";
    if ($_GET["erro"] === "vinculado") {
        $mensagem = "⚠️ Não é possível excluir este produto pois ele já foi vendido em pedidos ativos do histórico!";
    } elseif ($_GET["erro"] === "sistema") {
        $mensagem = "Erro interno: Ocorreu um problema ao tentar processar a exclusão no banco.";
    }
}

# =====================================
# BUSCA E LISTAGEM DE PRODUTOS
# =====================================
$busca = trim($_GET["buscar"] ?? "");
if ($busca !== "") {
    $sql = "
        SELECT *
        FROM produtos
        WHERE
            id = ?
            OR nome LIKE ?
        ORDER BY id DESC
    ";
    $stmt = $pdo->prepare($sql);
    // Permite buscar digitando o ID exato ou parte do nome
    $pesquisaNome = "%{$busca}%";
    $stmt->execute([$busca, $pesquisaNome]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT * FROM produtos ORDER BY id DESC";
    $produtos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Produtos - Sistema Loja</title>
    <style>
        /* CSS Unificado do Painel */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; overflow-x: hidden; max-width: 100%; }
        
        /* Menu Lateral (Sidebar) */
        .sidebar { width: 250px; height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 22px; letter-spacing: 1px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin-bottom: 15px; }
        .sidebar ul li a { color: #ecf0f1; text-decoration: none; display: block; padding: 12px; border-radius: 5px; transition: 0.3s; }
        .sidebar ul li a:hover { background-color: #34495e; padding-left: 20px; }
        
        /* Área de Conteúdo */
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .header { margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; }
        .header h1 { color: #2c3e50; }
        
        /* Blocos Card Panel */
        .card-panel { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card-panel h3 { color: #2c3e50; margin-bottom: 20px; font-size: 20px; border-left: 4px solid #3498db; padding-left: 10px; }
        
        /* Barra de Busca */
        .busca-container { display: flex; margin-bottom: 25px; }
        .busca-container input { flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 15px; font-weight: 600; transition: 0.3s; display: inline-block; text-decoration: none; text-align: center; }
        .btn-search { background: #34495e; color: white; margin-left: 10px; }
        .btn-search:hover { background: #2c3e50; }
        .btn-secondary { background: #95a5a6; color: white; margin-left: 5px; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        /* Listagem de Tabela */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        table th, table td { padding: 14px; text-align: left; border-bottom: 1px solid #e0e0e0; font-size: 15px; }
        table th { background: #f8f9fa; color: #34495e; font-weight: 600; }
        table tr:hover { background-color: #fcfcfc; }
        
        /* Links de Ações */
        .actions-links a { text-decoration: none; font-weight: 600; font-size: 14px; padding: 4px 8px; border-radius: 4px; transition: 0.2s; }
        .actions-links .excluir { color: #e74c3c; }
        .actions-links .excluir:hover { background: rgba(231,76,60,0.1); }
        
        /* Banner de Alertas */
        .mensagem { padding: 15px; border-radius: 5px; margin-bottom: 25px; border-left: 5px solid #2ecc71; font-weight: 500; background: #d4edda; color: #155724; }
        .sem-dados { text-align: center; padding: 30px; color: #7f8c8d; font-style: italic; }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>

    <!-- Menu Lateral de Navegação Unificado -->
    <nav class="sidebar">
        <h2>Gerenciamento</h2>
        <ul>
            <li><a href="http://localhost:8000/index.php">Início</a></li>
            <li><a href="http://localhost:8000/public/clientes.php">Clientes</a></li>
            <li><a href="http://localhost:8000/public/historico_cliente.php">Histórico de Clientes</a></li>
            <li><a href="http://localhost:8000/public/cadastrar_produto.php">Cadastrar Produto</a></li>
            <li><a href="http://localhost:8000/public/visualizar_produtos.php">Visualizar Produtos</a></li>
            <li><a href="http://localhost:8000/public/cadastrar_status.php">Gerenciar Status</a></li>
            <li><a href="http://localhost:8000/public/visualizar_pedidos.php">Visualizar Pedidos</a></li>
            <li><a href="http://localhost:8000/public/criar_pedido.php">Criar Pedido</a></li>
        </ul>


    </nav>

    <!-- Área de Conteúdo Principal -->
    <main class="main-content">
        <div class="header">
            <h1>Visualizar Produtos</h1>
            <p>Gerencie o catálogo de produtos e os valores cadastrados no estoque.</p>
        </div>

        <!-- Exibição do Alerta Alinhado Centralizado -->
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem" style="margin-bottom: 30px; <?= $tipoMensagem === 'erro' ? 'background: #f8d7da; color: #721c24; border-left-color: #dc3545;' : '' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Bloco de Listagem e Busca de Itens -->
        <div class="card-panel">
            <h3>👁️ Lista de Itens Cadastrados</h3>
            
            <form method="GET" class="busca-container">
                <input type="text" name="buscar" placeholder="🔍 Buscar produtos por código ID ou nome..." value="<?= htmlspecialchars($_GET["buscar"] ?? "") ?>">
                <button type="submit" class="btn btn-search">Buscar</button>
                <?php if ($busca !== ""): ?>
                    <a href="visualizar_produtos.php" class="btn btn-secondary">Limpar</a>
                <?php endif; ?>
            </form>

            <table>
                <thead>
                    <tr>
                        <th width="15%">Código ID</th>
                        <th width="50%">Nome do Produto</th>
                        <th width="20%">Preço Unitário</th>
                        <th width="15%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($produtos) > 0): ?>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><strong>#<?= $produto["id"] ?></strong></td>
                                <td><?= htmlspecialchars($produto["nome"] ?? "") ?></td>
                                <td>R$ <?= number_format((float)$produto["preco"], 2, ',', '.') ?></td>
                                <td class="actions-links">
                                    <a class="excluir" href="?excluir=<?= $produto["id"] ?>" onclick="return confirm('Tem certeza que deseja remover este produto do catálogo?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="sem-dados">Nenhum produto foi cadastrado ou localizado na busca.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
