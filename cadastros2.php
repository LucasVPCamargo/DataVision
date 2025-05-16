<?php
header('Content-Type: application/json');

$mes = $_GET['mes'] ?? null;
if (!$mes || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Formato inválido. Use YYYY-MM']);
    exit;
}

$inicio = $mes . '-05';
$partes = explode('-', $mes);
$ano = (int)$partes[0];
$mes_num = (int)$partes[1];
$mes_seguinte = $mes_num == 12 ? '01' : str_pad($mes_num + 1, 2, '0', STR_PAD_LEFT);
$ano_fim = $mes_num == 12 ? $ano + 1 : $ano;
$fim = $ano_fim . '-' . $mes_seguinte . '-01';

$conn = pg_connect("host=192.168.15.35 port=5999 dbname=dadosmkt user=mkt password=mkt2025dados!@#");
if (!$conn) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar ao banco']);
    exit;
}

// Consulta para obter cadastros por dia
$sqlCadastros = "
    SELECT
        DATE(a.data_e_hora) AS data,
        COUNT(DISTINCT a.external_id) AS cadastros,
        ARRAY_AGG(DISTINCT a.external_id) AS external_ids
    FROM acessos_diarios a
    JOIN cadastros_smartico c ON a.external_id = c.external_id
    WHERE DATE(a.data_e_hora) = DATE(c.registration_date)
      AND a.data_e_hora >= $1
      AND a.data_e_hora < $2
      AND EXTRACT(MONTH FROM a.data_e_hora) = 5
      AND EXTRACT(YEAR FROM a.data_e_hora) = $3
    GROUP BY DATE(a.data_e_hora)
    ORDER BY DATE(a.data_e_hora);
";



$resultCadastros = pg_query_params($conn, $sqlCadastros, [$inicio, $fim, $ano]);

if (!$resultCadastros) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro na consulta de cadastros']);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($resultCadastros)) {
    $data[] = $row;
}

echo json_encode($data);