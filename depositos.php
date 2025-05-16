<?php
header('Content-Type: application/json');

try {
    // Obter e validar dados do POST
    $jsonInput = file_get_contents('php://input');
    $input = json_decode($jsonInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    if (!isset($input['external_ids']) || !is_array($input['external_ids'])) {
        throw new Exception('external_ids deve ser um array');
    }

    $externalIds = $input['external_ids'];
    $data = $input['data'] ?? null;

    if (empty($externalIds)) {
        throw new Exception('Nenhum external_id fornecido');
    }

    if (!$data) {
        throw new Exception('Data não fornecida');
    }

    // Conexão com o banco de dados
    $conn = pg_connect("host=192.168.15.35 port=5999 dbname=dadosmkt user=mkt password=mkt2025dados!@#");
    if (!$conn) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }

    // Garantir que external_ids é um array
    if (!is_array($externalIds)) {
        $externalIds = [$externalIds]; // Converte string única em array
    }

    // Preparar array de IDs para a consulta SQL
    $escapedIds = array_map(function($id) use ($conn) {
        return pg_escape_string($conn, $id);
    }, $externalIds);
    
    $externalIdsStr = '{' . implode(',', $escapedIds) . '}';

    // Consulta SQL
    $sql = "SELECT COALESCE(SUM(valor), 0) AS total 
            FROM depositos_totais 
            WHERE external_id = ANY($1)
            AND DATE(data_e_hora) = $2";

    $result = pg_query_params($conn, $sql, [$externalIdsStr, $data]);
    if (!$result) {
        throw new Exception('Erro na consulta SQL: ' . pg_last_error($conn));
    }

    $row = pg_fetch_assoc($result);
    echo json_encode(['total' => (float)($row['total'] ?? 0)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}