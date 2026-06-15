<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db = pdo();

// ── GET — list requests ───────────────────────────────────────────────────
if (method('GET')) {
    $tab    = $_GET['tab']    ?? 'all';
    $search = $_GET['search'] ?? '';

    $statusMap = [
        'pending'  => 'รออนุมัติ',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ปฏิเสธ',
    ];

    $sql    = 'SELECT req_code AS id, student_name AS name, college_name AS col,
                      department AS dept, enterprise_name AS ent, status AS sta,
                      DATE_FORMAT(date_submitted, "%d/%m/") AS dt_d,
                      YEAR(date_submitted) AS dt_y,
                      type, period_display AS period
               FROM internship_requests WHERE 1=1';
    $params = [];

    if (isset($statusMap[$tab])) {
        $sql     .= ' AND status = ?';
        $params[] = $statusMap[$tab];
    }
    if ($search) {
        $sql     .= ' AND (student_name LIKE ? OR req_code LIKE ? OR enterprise_name LIKE ?)';
        $s        = "%$search%";
        $params   = array_merge($params, [$s, $s, $s]);
    }

    $sql .= ' ORDER BY date_submitted DESC, id DESC';
    $st   = $db->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    foreach ($rows as &$r) {
        $r['dt'] = $r['dt_d'] . ((int)$r['dt_y'] + 543);
        unset($r['dt_d'], $r['dt_y']);
    }
    json_ok($rows);
}

// ── POST — create request ─────────────────────────────────────────────────
if (method('POST')) {
    $b = body();

    $year = (int)date('Y') + 543;
    $cnt  = (int)$db->query(
        'SELECT COUNT(*)+1 FROM internship_requests WHERE YEAR(date_submitted) = YEAR(CURDATE())'
    )->fetchColumn();
    $code = 'REQ' . $year . '-' . str_pad($cnt, 3, '0', STR_PAD_LEFT);

    $pStart = $b['period_start'] ?? null;
    $pEnd   = $b['period_end']   ?? null;
    $period = '';
    if ($pStart && $pEnd) {
        [$sy, $sm, $sd] = explode('-', $pStart);
        [$ey, $em, $ed] = explode('-', $pEnd);
        $period = "$sd/$sm–$ed/$em/" . ((int)$ey + 543);
    }

    $st = $db->prepare(
        'INSERT INTO internship_requests
           (req_code, student_name, college_name, department, enterprise_name,
            type, status, date_submitted, period_start, period_end, period_display)
         VALUES (?,?,?,?,?,?,?,CURDATE(),?,?,?)'
    );
    $st->execute([
        $code,
        $b['student_name']  ?? 'ไม่ระบุ',
        $b['college_name']  ?? '',
        $b['department']    ?? '',
        $b['enterprise_name'] ?? '',
        $b['type']          ?? 'ฝึกงาน',
        'รออนุมัติ',
        $pStart,
        $pEnd,
        $period,
    ]);

    // notification
    try {
        $db->prepare("INSERT INTO notifications (`text`) VALUES (?)")
           ->execute(['คำร้องใหม่จาก ' . ($b['student_name'] ?? 'ไม่ระบุ')]);
    } catch (PDOException) {}

    json_ok(['req_code' => $code], 201);
}

// ── PATCH — update status ─────────────────────────────────────────────────
if (method('PATCH')) {
    $b      = body();
    $id     = $b['id']     ?? null;
    $status = $b['status'] ?? null;

    if (!$id || !$status) json_err('Missing id or status');

    $allowed = ['รออนุมัติ','อนุมัติแล้ว','ปฏิเสธ','ออกหนังสือแล้ว','อยู่ระหว่างฝึก','เสร็จสิ้น'];
    if (!in_array($status, $allowed, true)) json_err('Invalid status');

    $st = $db->prepare('UPDATE internship_requests SET status = ? WHERE req_code = ?');
    $st->execute([$status, $id]);

    if ($st->rowCount() === 0) json_err('Request not found', 404);

    // notification
    try {
        $db->prepare("INSERT INTO notifications (`text`) VALUES (?)")
           ->execute([$status . ' ' . $id . ' แล้ว']);
    } catch (PDOException) {}

    json_ok(['ok' => true]);
}

json_err('Method not allowed', 405);
