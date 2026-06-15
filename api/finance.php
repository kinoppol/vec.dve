<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db = pdo();

// ── GET — list allocations ────────────────────────────────────────────────
if (method('GET')) {
    $year = (int)($_GET['year'] ?? 2567);
    $st   = $db->prepare(
        'SELECT id, code, college_name AS col, province AS prov,
                usage_pct AS `usage`, perf_pct AS perf, zone,
                amount AS alloc, is_pending AS pend, fiscal_year
         FROM finance_allocations WHERE fiscal_year = ? ORDER BY id'
    );
    $st->execute([$year]);
    $rows = $st->fetchAll();

    foreach ($rows as &$r) {
        $r['usage'] = (int)$r['usage'];
        $r['perf']  = (int)$r['perf'];
        $r['alloc'] = (int)$r['alloc'];
        $r['pend']  = (bool)(int)$r['pend'];
    }
    json_ok($rows);
}

// ── PATCH — approve / update transfer ────────────────────────────────────
if (method('PATCH')) {
    $b      = body();
    $id     = $b['id']     ?? null;
    $action = $b['action'] ?? 'approve';

    if (!$id) json_err('Missing id');

    if ($action === 'approve') {
        $st = $db->prepare('UPDATE finance_allocations SET is_pending = 0 WHERE id = ?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) json_err('Record not found', 404);

        // notification
        try {
            $row = $db->query("SELECT college_name FROM finance_allocations WHERE id = $id")->fetch();
            $db->prepare("INSERT INTO notifications (`text`) VALUES (?)")
               ->execute(['อนุมัติโอนเงินให้ ' . ($row['college_name'] ?? '') . ' แล้ว']);
        } catch (PDOException) {}

        json_ok(['ok' => true]);
    }

    json_err('Unknown action');
}

json_err('Method not allowed', 405);
