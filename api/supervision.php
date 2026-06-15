<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db = pdo();

// ── GET — list supervision records ───────────────────────────────────────
if (method('GET')) {
    $rows = $db->query(
        'SELECT id, code, student_name AS st, college_name AS col,
                enterprise_name AS ent, supervisor_name AS sv,
                DATE_FORMAT(next_visit_date, "%d/%m/") AS nv_d,
                YEAR(next_visit_date) AS nv_y,
                visit_count AS vis, status AS sta
         FROM supervision ORDER BY next_visit_date ASC'
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['vis'] = (int)$r['vis'];
        if ($r['nv_d'] && $r['nv_y']) {
            $r['nv'] = $r['nv_d'] . ((int)$r['nv_y'] + 543);
        } else {
            $r['nv'] = '-';
        }
        unset($r['nv_d'], $r['nv_y']);
    }
    json_ok($rows);
}

// ── POST ──────────────────────────────────────────────────────────────────
if (method('POST')) {
    $b      = body();
    $action = $b['action'] ?? 'visit';

    // assign supervisor to a student
    if ($action === 'assign') {
        $st = $db->prepare(
            'INSERT INTO supervision
               (student_name, college_name, enterprise_name, supervisor_name, next_visit_date, status)
             VALUES (?,?,?,?,?,?)'
        );
        $st->execute([
            $b['student_name']    ?? '',
            $b['college_name']    ?? '',
            $b['enterprise_name'] ?? '',
            $b['supervisor_name'] ?? '',
            $b['next_visit_date'] ?? null,
            'ปกติ',
        ]);
        json_ok(['id' => $db->lastInsertId()], 201);
    }

    // record a supervision visit
    $supId = (int)($b['supervision_id'] ?? 0);
    if (!$supId) json_err('Missing supervision_id');

    $visitNum = (int)$db->query(
        "SELECT COUNT(*)+1 FROM supervision_visits WHERE supervision_id = $supId"
    )->fetchColumn();

    $st = $db->prepare(
        'INSERT INTO supervision_visits
           (supervision_id, visit_date, visit_number, notes, student_status)
         VALUES (?,?,?,?,?)'
    );
    $studentStatus = $b['student_status'] ?? 'ปกติ';
    $visitDate     = $b['visit_date'] ?? date('Y-m-d');
    $st->execute([$supId, $visitDate, $visitNum, $b['notes'] ?? '', $studentStatus]);

    // update supervision row
    $nextVisit = date('Y-m-d', strtotime('+14 days'));
    $db->prepare(
        'UPDATE supervision SET visit_count = ?, status = ?, next_visit_date = ? WHERE id = ?'
    )->execute([$visitNum, $studentStatus, $nextVisit, $supId]);

    // notification
    try {
        $name = $b['student_name'] ?? '';
        if (!$name) {
            $row  = $db->query("SELECT student_name FROM supervision WHERE id = $supId")->fetch();
            $name = $row['student_name'] ?? '';
        }
        $db->prepare("INSERT INTO notifications (`text`) VALUES (?)")
           ->execute(['บันทึกผลนิเทศ ' . $name . ' ครั้งที่ ' . $visitNum]);
    } catch (PDOException) {}

    json_ok(['ok' => true], 201);
}

json_err('Method not allowed', 405);
