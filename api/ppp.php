<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db = pdo();

// ── GET ───────────────────────────────────────────────────────────────────
if (method('GET')) {
    $type = $_GET['type'] ?? 'estates';

    if ($type === 'demand') {
        $rows = $db->query(
            'SELECT field_name, SUM(count_needed) AS total, SUM(count_filled) AS filled
             FROM ppp_hr_demand GROUP BY field_name ORDER BY total DESC'
        )->fetchAll();
        foreach ($rows as &$r) {
            $r['total']  = (int)$r['total'];
            $r['filled'] = (int)$r['filled'];
        }
        json_ok($rows);
    }

    $rows = $db->query(
        'SELECT id, code, name, province AS prov, soj_name AS soj,
                company_count AS co, hr_demand AS dem, hr_filled AS fill, status AS sta
         FROM ppp_estates ORDER BY id'
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['co']   = (int)$r['co'];
        $r['dem']  = (int)$r['dem'];
        $r['fill'] = (int)$r['fill'];
    }
    json_ok($rows);
}

// ── POST — add HR demand ──────────────────────────────────────────────────
if (method('POST')) {
    $b       = body();
    $estId   = (int)($b['estate_id']   ?? 0);
    $field   = $b['field_name']         ?? '';
    $needed  = (int)($b['count_needed'] ?? 0);
    $yearBe  = (int)($b['year_be']      ?? 2568);
    $notes   = $b['notes']              ?? '';

    if (!$estId || !$field) json_err('Missing estate_id or field_name');

    $st = $db->prepare(
        'INSERT INTO ppp_hr_demand (estate_id, field_name, count_needed, year_be, special_requirements)
         VALUES (?,?,?,?,?)'
    );
    $st->execute([$estId, $field, $needed, $yearBe, $notes]);

    // recalculate estate total demand
    $db->prepare(
        'UPDATE ppp_estates SET hr_demand =
           (SELECT SUM(count_needed) FROM ppp_hr_demand WHERE estate_id = ?)
         WHERE id = ?'
    )->execute([$estId, $estId]);

    json_ok(['ok' => true], 201);
}

json_err('Method not allowed', 405);
