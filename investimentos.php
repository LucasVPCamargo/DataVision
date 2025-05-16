<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configurações do banco de dados
define('DB_HOST', '192.168.15.35');
define('DB_PORT', '5999');
define('DB_NAME', 'dadosmkt');
define('DB_USER', 'mkt');
define('DB_PASS', 'mkt2025dados!@#');

try {
    if (!isset($_GET['mes'])) {
        throw new Exception("Parâmetro 'mes' é obrigatório", 400);
    }

    $mes = trim($_GET['mes']);

    if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
        throw new Exception("Formato inválido para 'mes'. Use YYYY-MM", 400);
    }

    // Limites do período (dia 5 até o fim do mês)
    $inicio = $mes . '-05';
    $partes = explode('-', $mes);
    $ano = (int)$partes[0];
    $mes_num = (int)$partes[1];
    $mes_seguinte = $mes_num == 12 ? '01' : str_pad($mes_num + 1, 2, '0', STR_PAD_LEFT);
    $ano_fim = $mes_num == 12 ? $ano + 1 : $ano;
    $fim = $ano_fim . '-' . $mes_seguinte . '-01';

    $conn = pg_connect("host=" . DB_HOST . " port=" . DB_PORT . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);
    if (!$conn) {
        throw new Exception("Erro na conexão com o banco de dados");
    }

    // SQL ajustado para considerar o intervalo e garantir o mês de maio
    $sql = "
        SELECT TO_CHAR(data, 'YYYY-MM-DD') AS data, COALESCE(SUM(custo), 0) AS total_investido
        FROM custo_campanhas
        WHERE data >= $1
          AND data < $2
          AND EXTRACT(MONTH FROM data) = 5
          AND EXTRACT(YEAR FROM data) = $3
        GROUP BY data
        ORDER BY data
    ";

    $result = pg_query_params($conn, $sql, [$inicio, $fim, $ano]);
    if (!$result) {
        throw new Exception("Erro na consulta SQL: " . pg_last_error($conn));
    }

    $dados = [];
    while ($row = pg_fetch_assoc($result)) {
        $dados[$row['data']] = (float)$row['total_investido'];
    }

    echo json_encode([
        'success' => true,
        'investimentos' => $dados,
        'mes' => $mes,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'mes' => $mes ?? null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
