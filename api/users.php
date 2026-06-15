<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
$db = pdo();

// ── GET — list users ──────────────────────────────────────────────────────
if (method('GET')) {
    $role   = $_GET['role']   ?? '';
    $search = $_GET['search'] ?? '';

    $sql    = 'SELECT u.id, u.role, u.name, u.institution,
                      u.username, u.student_code,
                      COALESCE(c.name, u.institution) AS college_name,
                      u.created_at,
                      IF(u.username IS NOT NULL OR u.student_code IS NOT NULL, 1, 0) AS active
               FROM users u
               LEFT JOIN colleges c ON u.college_id = c.id
               WHERE 1=1';
    $params = [];

    if ($role) {
        $sql     .= ' AND u.role = ?';
        $params[] = $role;
    }
    if ($search) {
        $sql     .= ' AND (u.name LIKE ? OR u.username LIKE ? OR u.institution LIKE ?)';
        $s        = "%$search%";
        $params   = array_merge($params, [$s, $s, $s]);
    }
    $sql .= ' ORDER BY u.role, u.name';

    $st = $db->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    foreach ($rows as &$r) {
        $r['active']     = (bool)(int)$r['active'];
        $r['created_at'] = substr($r['created_at'], 0, 10);
    }
    json_ok($rows);
}

// ── POST — create user ────────────────────────────────────────────────────
if (method('POST')) {
    $b    = body();
    $role = $b['role'] ?? '';

    $allowed = ['student', 'teacher', 'officer', 'soj', 'center', 'admin'];
    if (!in_array($role, $allowed, true)) json_err('Invalid role');

    // check duplicate username
    if (!empty($b['username'])) {
        $exists = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $exists->execute([$b['username']]);
        if ($exists->fetch()) json_err('ชื่อผู้ใช้นี้มีอยู่แล้ว');
    }

    $passwordHash = !empty($b['password'])
        ? password_hash($b['password'], PASSWORD_BCRYPT)
        : null;

    $nationalIdHash = !empty($b['national_id'])
        ? password_hash($b['national_id'], PASSWORD_BCRYPT)
        : null;

    if (empty(trim($b['name'] ?? ''))) json_err('กรุณาระบุชื่อ-นามสกุล');

    try {
        $st = $db->prepare(
            'INSERT INTO users (role, name, institution, username, password_hash, student_code, national_id_hash)
             VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute([
            $role,
            trim($b['name']         ?? ''),
            trim($b['institution']  ?? ''),
            !empty(trim($b['username'] ?? ''))     ? trim($b['username'])    : null,
            $passwordHash,
            !empty(trim($b['student_code'] ?? '')) ? trim($b['student_code']): null,
            $nationalIdHash,
        ]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            json_err('ชื่อผู้ใช้นี้มีอยู่แล้ว');
        }
        json_err('ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage());
    }

    json_ok(['id' => $db->lastInsertId()]);
}

// ── PATCH — update user ───────────────────────────────────────────────────
if (method('PATCH')) {
    $b  = body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_err('Missing id');

    $fields = [];
    $params = [];

    if (isset($b['name']))        { $fields[] = 'name = ?';        $params[] = trim($b['name']); }
    if (isset($b['institution'])) { $fields[] = 'institution = ?'; $params[] = trim($b['institution']); }
    if (isset($b['role']))        {
        $allowed = ['student','teacher','officer','soj','center','admin'];
        if (!in_array($b['role'], $allowed, true)) json_err('Invalid role');
        $fields[] = 'role = ?'; $params[] = $b['role'];
    }
    if (isset($b['username']))    { $fields[] = 'username = ?';    $params[] = trim($b['username']) ?: null; }
    if (!empty($b['password']))   {
        $fields[] = 'password_hash = ?';
        $params[] = password_hash($b['password'], PASSWORD_BCRYPT);
    }

    if (!$fields) json_err('Nothing to update');

    $params[] = $id;
    $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')
       ->execute($params);

    json_ok(['ok' => true]);
}

// ── DELETE — remove user ──────────────────────────────────────────────────
if (method('DELETE')) {
    $b  = body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_err('Missing id');

    // soft-delete: clear credentials so they cannot log in
    $db->prepare('UPDATE users SET username = NULL, password_hash = NULL, student_code = NULL, national_id_hash = NULL WHERE id = ?')
       ->execute([$id]);

    json_ok(['ok' => true]);
}

json_err('Method not allowed', 405);
