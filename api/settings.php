<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db = pdo();

// ── GET — return all settings ──────────────────────────────────────────────
if (method('GET')) {
    $rows = $db->query('SELECT `key`, `value` FROM system_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    $out  = [];
    foreach ($rows as $k => $v) {
        $out[$k] = (bool)(int)$v;
    }
    json_ok($out);
}

// ── POST — update one setting ──────────────────────────────────────────────
if (method('POST')) {
    $b   = body();
    $key = $b['key']   ?? '';
    $val = array_key_exists('value', $b) ? (int)(bool)$b['value'] : null;

    $allowed = ['sys_requests', 'sys_ppp', 'sys_supervision', 'sys_finance'];
    if (!in_array($key, $allowed, true) || $val === null) {
        json_err('Invalid setting');
    }

    $db->prepare('UPDATE system_settings SET `value` = ? WHERE `key` = ?')
       ->execute([$val, $key]);

    json_ok(['ok' => true]);
}

json_err('Method not allowed', 405);
