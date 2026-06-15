<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

cors();
if (session_status() === PHP_SESSION_NONE) session_start();

// GET /api/auth.php — return current session user
if (method('GET')) {
    if (empty($_SESSION['user_id'])) {
        json_err('Not logged in', 401);
    }
    json_ok([
        'role'        => $_SESSION['role'],
        'name'        => $_SESSION['name'],
        'institution' => $_SESSION['inst'],
    ]);
}

// POST /api/auth.php
if (method('POST')) {
    $b      = body();
    $action = $b['action'] ?? 'login';

    // ── logout ──
    if ($action === 'logout') {
        session_destroy();
        json_ok(['ok' => true]);
    }

    // ── login ──
    $role = $b['role'] ?? 'student';
    $user = null;

    if ($role === 'student') {
        $code = trim($b['student_code'] ?? '');
        $nid  = trim($b['national_id'] ?? '');
        if ($code) {
            try {
                $st = pdo()->prepare(
                    'SELECT u.*, c.name AS college_name FROM users u
                     LEFT JOIN colleges c ON u.college_id = c.id
                     WHERE u.role = "student" AND u.student_code = ? LIMIT 1'
                );
                $st->execute([$code]);
                $row = $st->fetch();
                if ($row && $nid && password_verify($nid, $row['national_id_hash'])) {
                    $user = $row;
                }
            } catch (PDOException) { /* fall through to demo */ }
        }
        // demo fallback
        if (!$user) {
            $user = [
                'id'          => 0,
                'role'        => 'student',
                'name'        => 'นายสมชาย ใจดี',
                'institution' => 'วิทยาลัยเทคนิคกรุงเทพ',
            ];
        }
    } else {
        $username = trim($b['username'] ?? '');
        $password = trim($b['password'] ?? '');

        if ($username && $password) {
            try {
                $st = pdo()->prepare(
                    'SELECT u.*, c.name AS college_name FROM users u
                     LEFT JOIN colleges c ON u.college_id = c.id
                     WHERE u.role = ? AND u.username = ? LIMIT 1'
                );
                $st->execute([$role, $username]);
                $row = $st->fetch();
                if ($row && password_verify($password, $row['password_hash'])) {
                    $user = $row;
                }
            } catch (PDOException) { /* fall through */ }

            if (!$user) {
                json_err('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง', 401);
            }
        }

        // demo fallback (empty credentials → auto login as that role)
        if (!$user) {
            $names = [
                'teacher' => 'อ.วิเชียร สุขสันต์',
                'officer' => 'น.ส.สุภา มานะดี',
                'soj'     => 'ผอ.ประกิต เจริญกิจ',
                'admin'   => 'นายณรงค์ รักษ์ชาติ',
            ];
            $insts = [
                'teacher' => 'วิทยาลัยเทคนิคกรุงเทพ',
                'officer' => 'วิทยาลัยเทคนิคกรุงเทพ',
                'soj'     => 'สำนักงานอาชีวศึกษาจังหวัดชลบุรี',
                'admin'   => 'ศูนย์ทวิภาคี สอศ.',
            ];
            $user = [
                'id'          => 0,
                'role'        => $role,
                'name'        => $names[$role] ?? 'ผู้ใช้งาน',
                'institution' => $insts[$role] ?? 'สถานศึกษา',
            ];
        }
    }

    $_SESSION['user_id'] = $user['id'] ?? 0;
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['inst']    = $user['institution'] ?? $user['college_name'] ?? 'สถานศึกษา';

    json_ok([
        'role'        => $_SESSION['role'],
        'name'        => $_SESSION['name'],
        'institution' => $_SESSION['inst'],
    ]);
}

json_err('Method not allowed', 405);
