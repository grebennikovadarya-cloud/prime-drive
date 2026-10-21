<?php
header('Content-Type: application/json; charset=utf-8');

$dbFile = __DIR__ . '/reviews.db';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            name     TEXT    NOT NULL,
            rating   INTEGER NOT NULL,
            text     TEXT    NOT NULL,
            date     TEXT    NOT NULL,
            approved INTEGER NOT NULL DEFAULT 0
        )
    ");

    try {
        $pdo->exec("ALTER TABLE reviews ADD COLUMN approved INTEGER NOT NULL DEFAULT 1");
    } catch (Exception $e) {
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];


if ($method === 'GET') {
    $showAll = isset($_GET['all']) && $_GET['all'] === '1';

    if ($showAll) {
        $stmt = $pdo->query("
            SELECT id, name, rating, text, date, approved
            FROM reviews
            ORDER BY approved ASC, date DESC
        ");
    } else {
        $stmt = $pdo->query("
            SELECT id, name, rating, text, date
            FROM reviews
            WHERE approved = 1
            ORDER BY date DESC
        ");
    }

    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reviews as &$r) {
        $r['id']     = (int)$r['id'];
        $r['rating'] = (int)$r['rating'];
        if (isset($r['approved'])) {
            $r['approved'] = (int)$r['approved'];
        }
    }
    unset($r);

    echo json_encode($reviews, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST' && isset($_POST['action'])) {

    $config = require __DIR__ . '/config.php';
    $token  = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';

    if (empty($config['admin_token']) || !hash_equals($config['admin_token'], $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'];

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный id'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE reviews SET approved = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    if (!empty($_POST['website'])) {
        echo json_encode(['success' => true, 'id' => 0], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name   = trim($_POST['name']   ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $text   = trim($_POST['text']   ?? '');

    $errors = [];

    if ($name === '') {
        $errors[] = 'Укажите имя';
    } elseif (mb_strlen($name) > 50) {
        $errors[] = 'Имя слишком длинное';
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Оценка должна быть от 1 до 5';
    }

    if ($text === '') {
        $errors[] = 'Напишите текст отзыва';
    } elseif (mb_strlen($text) > 1000) {
        $errors[] = 'Отзыв слишком длинный (макс. 1000 символов)';
    }

    if ($errors) {
        http_response_code(422);
        echo json_encode(['errors' => $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO reviews (name, rating, text, date, approved)
        VALUES (:name, :rating, :text, :date, 0)
    ");
    $stmt->execute([
        ':name'   => $name,
        ':rating' => $rating,
        ':text'   => $text,
        ':date'   => date('c')
    ]);

    echo json_encode([
        'success' => true,
        'id'      => (int)$pdo->lastInsertId()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);