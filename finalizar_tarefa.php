<?php
require_once(__DIR__ . '/../../config.php');

// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json');

try {
    // Verifica o método HTTP usado na requisição
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método HTTP não permitido. Utilize POST.');
    }

    // Obtém o corpo da requisição
    $input = json_decode(file_get_contents('php://input'), true);

    // Verifica se o 'recordid' foi enviado no corpo da requisição
    if (isset($input['recordid'])) {
        $recordid = intval($input['recordid']); // Converte para inteiro

        global $DB;

        // Obtém o campo 'Feito' na tabela de dados
        $field_feito_id = $DB->get_field('data_fields', 'id', ['name' => 'Feito']);
        if (!$field_feito_id) {
            throw new Exception('Campo "Feito" não encontrado.');
        }

        // Atualiza o registro no banco de dados
        $result = $DB->execute(
            "UPDATE {data_content} SET content = 'Sim' WHERE recordid = ? AND fieldid = ?",
            [$recordid, $field_feito_id]
        );

        // Verifica se a atualização foi bem-sucedida
        if (!$result) {
            throw new Exception('Falha ao atualizar o registro no banco de dados.');
        }

        // Resposta de sucesso
        echo json_encode(['success' => true, 'message' => 'Tarefa finalizada.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID da tarefa não enviado.']);
    }
} catch (Exception $e) {
    // Log do erro e resposta de erro
    error_log("Erro: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
