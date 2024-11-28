<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verifica o método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido. Apenas POST é permitido.']);
    exit;
}

try {
    // Obtém o corpo da requisição
    $input = json_decode(file_get_contents('php://input'), true);

    // Valida os dados recebidos
    if (!isset($input['id'], $input['feito'])) {
        echo json_encode(['success' => false, 'message' => 'Parâmetros incompletos.']);
        exit;
    }

    $id = intval($input['id']); // Converte o ID para inteiro
    $feito = filter_var($input['feito'], FILTER_SANITIZE_STRING);

    // Verifica se o campo "feito" tem um valor válido
    if ($feito !== 'Sim' && $feito !== 'Não') {
        error_log("Nenhum registro encontrado com ID: $id");
        echo json_encode(['success' => false, 'message' => 'O campo "feito" deve ser "Sim" ou "Não".']);
        exit;
    }

    // Conexão com o banco de dados
    $host = '127.0.0.1:3306';
    $dbname = 'moodle'; 
    $username = 'root'; 
    $password = ''; 

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $sql = 'UPDATE mdl_data_content SET content = :feito WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':feito' => $feito,
        ':id' => $id
    ]);

    // Verifica se alguma linha foi atualizada
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Tarefa finalizada com sucesso.']);
    } else {
        echo json_encode(['message' => 'Para finalizar esta tarefa, selecione o "SIM" antes.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}