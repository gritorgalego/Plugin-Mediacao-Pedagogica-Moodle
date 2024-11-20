<?php
require_once(__DIR__ . '/../../config.php');

// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json');

try {
    // Obter os dados enviados via POST
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['recordid']) || empty($data['recordid'])) {
        throw new Exception('ID do registro não fornecido ou inválido.');
    }

    $recordid = intval($data['recordid']);
    global $DB;

    // Log para verificar o recordid
    error_log("Record ID recebido: " . $recordid);

    // Identificar o campo "Feito" no banco de dados
    $field_feito_id = $DB->get_field('data_fields', 'id', ['name' => 'Feito']);

    if (!$field_feito_id) {
        throw new Exception('Campo "Feito" não encontrado.');
    }

    // Atualizar o registro para "Sim"
    $DB->execute("
        UPDATE {data_content}
        SET content = 'Sim'
        WHERE recordid = ? AND fieldid = ?
    ", [$recordid, $field_feito_id]);

    // Log após a atualização
    error_log("Registro atualizado com sucesso para recordid: " . $recordid);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Log do erro
    error_log("Erro ao finalizar tarefa: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
