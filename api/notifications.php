<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db = pdo();

// ── GET — recent notifications ────────────────────────────────────────────
if (method('GET')) {
    $rows = $db->query(
        'SELECT id, `text` AS txt, created_at, is_read
         FROM notifications ORDER BY created_at DESC LIMIT 20'
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['t']       = relative_time($r['created_at']);
        $r['is_read'] = (bool)(int)$r['is_read'];
        unset($r['created_at']);
    }
    json_ok($rows);
}

// ── POST ──────────────────────────────────────────────────────────────────
if (method('POST')) {
    $b      = body();
    $action = $b['action'] ?? '';

    if ($action === 'mark_read') {
        $db->query('UPDATE notifications SET is_read = 1');
        json_ok(['ok' => true]);
    }

    json_err('Unknown action');
}

json_err('Method not allowed', 405);
