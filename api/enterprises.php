<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db   = pdo();
$type = $_GET['type'] ?? 'list';

// ── GET ───────────────────────────────────────────────────────────────────
if (method('GET')) {

    if ($type === 'duplicates') {
        $groups = $db->query(
            'SELECT id, code, canonical_name AS can FROM duplicate_groups WHERE resolved = 0'
        )->fetchAll();

        foreach ($groups as &$g) {
            $st = $db->prepare(
                'SELECT id, enterprise_name AS n, usage_count AS cnt, similarity_score AS score
                 FROM duplicate_group_members WHERE group_id = ?
                 ORDER BY is_canonical DESC, usage_count DESC'
            );
            $st->execute([$g['id']]);
            $members = $st->fetchAll();
            foreach ($members as &$m) {
                $m['cnt']   = (int)$m['cnt'];
                $m['score'] = (int)$m['score'];
            }
            $g['ent'] = $members;
        }
        json_ok($groups);
    }

    // list
    $search = $_GET['search'] ?? '';
    $sql    = 'SELECT e.id, e.code, e.name, e.type, e.province AS prov,
                      (SELECT COUNT(*) FROM internship_requests ir
                       WHERE ir.enterprise_id = e.id OR ir.enterprise_name = e.name) AS cnt
               FROM enterprises e WHERE e.canonical_id IS NULL';
    $params = [];

    if ($search) {
        $sql    .= ' AND (e.name LIKE ? OR e.type LIKE ? OR e.province LIKE ?)';
        $s       = "%$search%";
        $params  = [$s, $s, $s];
    }
    $sql .= ' ORDER BY cnt DESC, e.name';
    $st   = $db->prepare($sql);
    $st->execute($params);
    $ents = $st->fetchAll();

    foreach ($ents as &$e) {
        $e['cnt'] = (int)$e['cnt'];
        $st2 = $db->prepare('SELECT alias FROM enterprise_aliases WHERE enterprise_id = ?');
        $st2->execute([$e['id']]);
        $e['al'] = $st2->fetchAll(PDO::FETCH_COLUMN);
    }
    json_ok($ents);
}

// ── POST ──────────────────────────────────────────────────────────────────
if (method('POST')) {
    $b      = body();
    $action = $b['action'] ?? 'create';

    if ($action === 'merge') {
        $gid = $b['group_id'] ?? null;
        if (!$gid) json_err('Missing group_id');

        $db->prepare('UPDATE duplicate_groups SET resolved = 1 WHERE id = ?')->execute([$gid]);

        // notification
        try {
            $db->prepare("INSERT INTO notifications (`text`) VALUES (?)")
               ->execute(['รวมสถานประกอบการซ้ำเรียบร้อยแล้ว (กลุ่ม ' . $gid . ')']);
        } catch (PDOException) {}

        json_ok(['ok' => true]);
    }

    // create new enterprise
    $cnt  = (int)$db->query('SELECT COUNT(*)+1 FROM enterprises')->fetchColumn();
    $code = 'E' . str_pad($cnt, 3, '0', STR_PAD_LEFT);
    $st   = $db->prepare(
        'INSERT INTO enterprises (code, name, type, province) VALUES (?,?,?,?)'
    );
    $st->execute([$code, $b['name'] ?? '', $b['type'] ?? '', $b['province'] ?? '']);
    json_ok(['id' => $db->lastInsertId()], 201);
}

json_err('Method not allowed', 405);
