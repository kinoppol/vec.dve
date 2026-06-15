<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();

$type = $_GET['type'] ?? 'summary';
$db   = pdo();

// trend chart
if ($type === 'trend') {
    $rows = $db->query(
        'SELECT year_be AS y, student_count AS v,
                CONCAT(ROUND(student_count/1000,1),"K") AS vl
         FROM kpi_trend ORDER BY year_be'
    )->fetchAll();
    foreach ($rows as &$r) {
        $r['y']  = (string)$r['y'];
        $r['v']  = (int)$r['v'];
    }
    json_ok($rows);
}

// province bar chart
if ($type === 'province') {
    $rows = $db->query(
        'SELECT province AS label, short_name AS short, student_count AS v
         FROM kpi_province ORDER BY student_count DESC LIMIT 10'
    )->fetchAll();
    foreach ($rows as &$r) { $r['v'] = (int)$r['v']; }
    json_ok($rows);
}

// field donut chart
if ($type === 'fields') {
    $rows = $db->query(
        'SELECT field_name AS label, percentage AS v, color
         FROM kpi_field ORDER BY percentage DESC'
    )->fetchAll();
    foreach ($rows as &$r) { $r['v'] = (int)$r['v']; }
    json_ok($rows);
}

// summary KPIs
$kpi = $db->query('SELECT `key`, `value` FROM kpi')->fetchAll(PDO::FETCH_KEY_PAIR);
json_ok([
    'stud'   => (int)($kpi['stud']   ?? 285432),
    'intern' => (int)($kpi['intern'] ?? 42187),
    'ent'    => (int)($kpi['ent']    ?? 8935),
    'col'    => (int)($kpi['col']    ?? 423),
    'sup'    => (int)($kpi['sup']    ?? 12847),
    'ppp'    => (int)($kpi['ppp']    ?? 45),
]);
