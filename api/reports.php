<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db   = pdo();
$type = $_GET['type'] ?? 'internship';

if ($type === 'internship') {
    $kpi     = $db->query('SELECT `key`, `value` FROM kpi')->fetchAll(PDO::FETCH_KEY_PAIR);
    $trend   = $db->query(
        'SELECT year_be AS y, student_count AS v, CONCAT(ROUND(student_count/1000,1),"K") AS vl
         FROM kpi_trend ORDER BY year_be'
    )->fetchAll();
    $fields  = $db->query(
        'SELECT field_name AS label, percentage AS v, color FROM kpi_field ORDER BY percentage DESC'
    )->fetchAll();
    $prov    = $db->query(
        'SELECT province AS label, short_name AS short, student_count AS v
         FROM kpi_province ORDER BY student_count DESC LIMIT 10'
    )->fetchAll();
    $approved = (int)$db->query(
        "SELECT COUNT(*) FROM internship_requests WHERE status = 'อนุมัติแล้ว'"
    )->fetchColumn();
    $lettered = (int)$db->query(
        "SELECT COUNT(*) FROM internship_requests WHERE status = 'ออกหนังสือแล้ว'"
    )->fetchColumn();

    foreach ($trend  as &$r) { $r['y'] = (string)$r['y']; $r['v'] = (int)$r['v']; }
    foreach ($fields as &$r) { $r['v'] = (int)$r['v']; }
    foreach ($prov   as &$r) { $r['v'] = (int)$r['v']; }

    json_ok(compact('kpi', 'trend', 'fields', 'prov', 'approved', 'lettered'));
}

if ($type === 'ppp') {
    $rows = $db->query(
        'SELECT province AS label, SUBSTR(province,1,8) AS short, hr_demand AS v
         FROM ppp_estates ORDER BY id'
    )->fetchAll();
    foreach ($rows as &$r) { $r['v'] = (int)$r['v']; }
    json_ok(['bars' => $rows]);
}

if ($type === 'supervision') {
    $rows = $db->query(
        'SELECT REPLACE(REPLACE(supervisor_name,"ว่าที่ร.ต.","อ."),"ว่าที่ร.ต.","") AS label,
                visit_count+1 AS v
         FROM supervision ORDER BY visit_count DESC'
    )->fetchAll();
    foreach ($rows as &$r) { $r['v'] = (int)$r['v']; $r['short'] = (string)$r['v']; }
    json_ok(['bars' => $rows]);
}

if ($type === 'finance') {
    $rows = $db->query(
        'SELECT province AS label, ROUND(amount/1000000,1) AS v, amount AS raw
         FROM finance_allocations ORDER BY id'
    )->fetchAll();
    foreach ($rows as &$r) {
        $r['v']     = (float)$r['v'];
        $r['short'] = $r['v'] . 'M';
    }
    json_ok(['bars' => $rows]);
}

json_ok([]);
