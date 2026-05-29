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
            <h1>Registrar Vendas e Pedidos</h1>
            <p>Abra uma nova encomenda no sistema vinculando o comprador aos bônus fidelidade.</p>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="mensagem" style="margin-bottom: 30px; <?= $tipoMensagem === 'erro' ? 'background: #f8d7da; color: #721c24; border-left-color: #dc3545;' : '' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($msgBonus)): ?>
            <div class="mensagem" style="margin-bottom: 30px;">
                <?= htmlspecialchars($msgBonus) ?>
            </div>
        <?php endif; ?>

        <!-- Botão de Ação Direta para Disparo Nativo (Substituindo a dependência da API) -->
        <?php if (!empty($linkWhatsFinal)): ?>
            <div style="margin-bottom: 30px;">
                <a href="<?= $linkWhatsFinal ?>" target="_blank" class="btn btn-success-whats">
                    Enviar Notificação via WhatsApp Web
                </a>
            </div>
        <?php endif; ?>

        <div class="card-panel">
            <h3>Novo Pedido por Encomenda</h3>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="cliente_id">Cliente:</label>
                        <select name="cliente_id" id="cliente_id" required>
                            <option value="" disabled selected>Selecione um cliente</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="produto_id">Produto:</label>
                        <select name="produto_id" id="produto_id" required>
                            <option value="" disabled selected>Selecione um produto</option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quantidade">Quantidade:</label>
                        <input type="number" name="quantidade" id="quantidade" placeholder="Ex: 2" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Situação Inicial:</label>
                        <select name="status" id="status" required>
                            <?php foreach ($statusDisponiveis as $nomeStatus): ?>
                                <option value="<?= htmlspecialchars($nomeStatus) ?>" <?= $nomeStatus === 'Em Preparo' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nomeStatus) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-warning">Finalizar e Registrar Pedido</button>
            </form>
        </div>
    </main>

</body>
</html>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Pedidos - Sistema Loja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; display: flex; overflow-x: hidden; max-width: 100%; }
        
        .sidebar { width: 250px; height: 100vh; background-color: #2c3e50; color: white; padding: 20px; position: fixed; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 22px; letter-spacing: 1px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin-bottom: 15px; }
        .sidebar ul li a { color: #ecf0f1; text-decoration: none; display: block; padding: 12px; border-radius: 5px; transition: 0.3s; }
        .sidebar ul li a:hover { background-color: #34495e; padding-left: 20px; }
        
        .main-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .header { margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; }
        .header h1 { color: #2c3e50; }
        
        .card-panel { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card-panel h3 { color: #2c3e50; margin-bottom: 20px; font-size: 20px; border-left: 4px solid #3498db; padding-left: 10px; }
        
        .filtro-container { display: flex; gap: 10px; margin-bottom: 25px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; flex: 1; }
        label { margin-bottom: 8px; font-weight: 600; color: #34495e; font-size: 15px; }
        select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; background: white; }
        
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 15px; font-weight: 600; transition: 0.3s; display: inline-block; text-decoration: none; text-align: center; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success-whats { background: #2ecc71; color: white; width: 100%; text-decoration: none; margin-bottom: 20px; }
        .btn-success-whats:hover { background: #27ae60; }
        
        .badge-status { font-weight: bold; padding: 6px 12px; border-radius: 4px; font-size: 13px; display: inline-block; background: #e0e0e0; color: #333; }
        .select-acao { padding: 6px; font-size: 13px; border: 1px solid #ced4da; border-radius: 4px; background-color: #fff; cursor: pointer; width: 100%; }
        
        .status-Finalizado { background: #e2fbe8; color: #1e7e34; }
        .status-Cancelado { background: #f8d7da; color: #721c24; }
        .status-Em-Preparo { background: #fff3cd; color: #856404; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        table th, table td { padding: 14px; text-align: center; border-bottom: 1px solid #e0e0e0; font-size: 15px; }
        table th { background: #f8f9fa; color: #34495e; font-weight: 600; }
        table tr:hover { background-color: #fcfcfc; }
        
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
            <h1>Controle de Encomendas</h1>
            <p>Monitore os pedidos e faça a gestão do fluxo de entrega de forma dinâmica.</p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem" style="margin-bottom: 25px; <?= $tipoMensagem === 'erro' ? 'background: #f8d7da; color: #721c24; border-left-color: #dc3545;' : '' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Botão Dinâmico para Notificação de Alteração de Status -->
        <?php if (!empty($linkWhatsAltera)): ?>
            <div style="margin-bottom: 30px;">
                <a href="<?= $linkWhatsAltera ?>" target="_blank" class="btn btn-success-whats">
                    Enviar Notificação de Alteração via WhatsApp Web
                </a>
            </div>
        <?php endif; ?>

        <div class="card-panel">
            <h3>Situação das Encomendas</h3>
            
            <form method="GET" class="filtro-container">
                <div class="form-group">
                    <label for="filtro_status">Filtrar por Situação:</label>
                    <select name="filtro_status" id="filtro_status">
                        <option value="">-- Todos os pedidos registrados --</option>
                        <?php foreach ($statusDisponiveis as $stNome): ?>
                            <option value="<?= htmlspecialchars($stNome) ?>" <?= $filtroStatus === $stNome ? 'selected' : '' ?>>
                                <?= htmlspecialchars($stNome) ?>"
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th width="10%">Pedido</th>
                        <th width="25%">Cliente / Comprador</th>
                        <th width="20%">Data do Pedido</th>
                        <th width="12%">Qtd. Itens</th>
                        <th width="15%">Situação Atual</th>
                        <th width="18%">Alterar Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($listaPedidos) > 0): ?>
                        <?php foreach ($listaPedidos as $p): ?>
                            <tr>
                                <td><strong>#<?= $p["pedido_id"] ?></strong></td>
                                <td><?= htmlspecialchars($p["nome_cliente"] ?? "") ?></td>
                                <td><?= !empty($p["data_pedido"]) ? date('d/m/Y H:i', strtotime($p["data_pedido"])) : '<span style="color:#bbb;">Não salva</span>' ?></td>
                                <td><?= (int)$p["total_produtos"] ?> un.</td>
                                <td>
                                    <?php $classeStatus = str_replace(' ', '-', $p["status_pedido"]); ?>
                                    <span class="badge-status status-<?= $classeStatus ?>">
                                        <?= htmlspecialchars($p["status_pedido"]) ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="select-acao" onchange="location.href='?alterar_status=<?= $p['pedido_id'] ?>&filtro_status=<?= urlencode($filtroStatus) ?>&novo_status=' + encodeURIComponent(this.value)">
                                        <?php foreach ($statusDisponiveis as $stOpcao): ?>
                                            <option value="<?= htmlspecialchars($stOpcao) ?>" <?= $p["status_pedido"] === $stOpcao ? 'selected' : '' ?>>
                                                Mudar para: <?= htmlspecialchars($stOpcao) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="sem-dados">Nenhuma encomenda registrada ou encontrada para este filtro.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
