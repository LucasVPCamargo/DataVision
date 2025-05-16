<?php
header('Content-Type: application/json');

$mes = $_GET['mes'] ?? null;
if (!$mes || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Formato inválido. Use YYYY-MM']);
    exit;
}

$inicio = $mes . '-01';
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

$sql = "
    SELECT
        DATE(registration_date) AS data,
        COUNT(DISTINCT external_id) AS cadastros
    FROM cadastros_smartico
    WHERE registration_date >= $1
      AND registration_date < $2
    GROUP BY DATE(registration_date)
    ORDER BY DATE(registration_date);
";

$result = pg_query_params($conn, $sql, [$inicio, $fim]);
if (!$result) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro na consulta']);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
