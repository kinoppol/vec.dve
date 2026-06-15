<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db = pdo();

if (method('GET')) {
    $q = trim($_GET['q'] ?? '');

    if (mb_strlen($q) < 1) {
        json_ok([]);
    }

    $like = '%' . $q . '%';
    $st   = $db->prepare(
        'SELECT id, name, province, type
         FROM colleges
         WHERE name LIKE ? OR province LIKE ?
         ORDER BY
           CASE WHEN name LIKE ? THEN 0 ELSE 1 END,
           name
         LIMIT 15'
    );
    $st->execute([$like, $like, $q . '%']);
    json_ok($st->fetchAll());
}

json_err('Method not allowed', 405);
