<?php
/**
 * ChemistLab API (PHP + MySQL/MariaDB lewat PDO)
 * Dipanggil oleh index.html dengan POST JSON: { "action": "me|register|login|save|logout", ... }
 *
 * Tabel yang dipakai (database "chemistlab"):
 *   users           : id, username, password (hash), level, coins
 *   users_inventory : id, user_id, item_code   -> barang & progres pemain disimpan sebagai kode teks
 */
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Sesi login bertahan 30 hari di device yang sama
ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Throwable $e) {
    out(['ok' => false, 'error' => 'Tidak bisa terhubung ke database. Cek config.php dan pastikan MySQL menyala.'], 500);
}

$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) $in = [];
$action = $in['action'] ?? '';

// Ambil profil + isi inventory satu pemain
function profile(PDO $pdo, int $uid): array {
    $q = $pdo->prepare('SELECT id, username, level, coins FROM users WHERE id = ?');
    $q->execute([$uid]);
    $u = $q->fetch();
    if (!$u) return [];
    $i = $pdo->prepare('SELECT item_code FROM users_inventory WHERE user_id = ?');
    $i->execute([$uid]);
    return [
        'user' => ['id' => (int)$u['id'], 'username' => $u['username'], 'level' => (int)$u['level'], 'coins' => (int)$u['coins']],
        'inv'  => array_column($i->fetchAll(), 'item_code'),
    ];
}

switch ($action) {

    // Cek apakah sesi masih aktif (auto-login)
    case 'me':
        if (!empty($_SESSION['uid'])) {
            $p = profile($pdo, (int)$_SESSION['uid']);
            if ($p) out(['ok' => true] + $p);
        }
        out(['ok' => true, 'user' => null]);

    // Daftar akun baru (disimpan ke tabel users). Setelah ini pemain harus login.
    case 'register':
        $name = trim((string)($in['username'] ?? ''));
        $pass = (string)($in['password'] ?? '');
        if (mb_strlen($name) < 3 || mb_strlen($name) > 50) out(['ok' => false, 'error' => 'Username harus 3-50 karakter'], 400);
        if (strlen($pass) < 4 || strlen($pass) > 200) out(['ok' => false, 'error' => 'Kata sandi minimal 4 karakter'], 400);
        $q = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $q->execute([$name]);
        if ($q->fetch()) out(['ok' => false, 'error' => 'Username sudah dipakai'], 409);
        $pdo->prepare('INSERT INTO users (username, password, level, coins) VALUES (?, ?, 1, 10)')
            ->execute([$name, password_hash($pass, PASSWORD_DEFAULT)]);
        out(['ok' => true]);

    // Login: hanya untuk akun yang sudah terdaftar
    case 'login':
        $name = trim((string)($in['username'] ?? ''));
        $pass = (string)($in['password'] ?? '');
        if ($name === '') out(['ok' => false, 'error' => 'Isi username dulu'], 400);
        $q = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
        $q->execute([$name]);
        $u = $q->fetch();
        if (!$u) out(['ok' => false, 'error' => 'Akun belum terdaftar. Silakan daftar dulu.'], 404);
        if (!password_verify($pass, $u['password'])) out(['ok' => false, 'error' => 'Kata sandi salah'], 401);
        $uid = (int)$u['id'];
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        out(['ok' => true] + profile($pdo, $uid));

    // Simpan level, coins, dan seluruh inventory/progres
    case 'save':
        if (empty($_SESSION['uid'])) out(['ok' => false, 'error' => 'Belum login'], 401);
        $uid   = (int)$_SESSION['uid'];
        $level = max(1, min(1000, (int)($in['level'] ?? 1)));
        $coins = max(0, min(100000000, (int)($in['coins'] ?? 0)));
        $codes = [];
        foreach ((is_array($in['inv'] ?? null) ? $in['inv'] : []) as $c) {
            // contoh kode valid: gems:12  hint:3  avatar:4  disc:7:2  done:5
            if (is_string($c) && strlen($c) <= 50 && preg_match('/^[a-z_]+(:[0-9A-Za-z_]+){0,2}$/', $c)) $codes[$c] = true;
            if (count($codes) >= 800) break;
        }
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE users SET level = ?, coins = ? WHERE id = ?')->execute([$level, $coins, $uid]);
            $pdo->prepare('DELETE FROM users_inventory WHERE user_id = ?')->execute([$uid]);
            $ins = $pdo->prepare('INSERT INTO users_inventory (user_id, item_code) VALUES (?, ?)');
            foreach (array_keys($codes) as $c) $ins->execute([$uid, $c]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            out(['ok' => false, 'error' => 'Gagal menyimpan data'], 500);
        }
        out(['ok' => true]);

    case 'logout':
        $_SESSION = [];
        session_destroy();
        out(['ok' => true]);

    default:
        out(['ok' => false, 'error' => 'Aksi tidak dikenal'], 400);
}
