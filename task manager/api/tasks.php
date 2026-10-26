<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

/* ================= GET — list / single ================= */
if ($method === 'GET') {

    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([(int)$_GET['id'], $userId]);
        $task = $stmt->fetch();
        if (!$task) jsonResponse(['error' => 'Not found'], 404);
        jsonResponse(['task' => $task]);
    }

    $where  = 'WHERE user_id = ?';
    $params = [$userId];

    if (!empty($_GET['status']) && in_array($_GET['status'], ['todo', 'doing', 'done'], true)) {
        $where .= ' AND status = ?';
        $params[] = $_GET['status'];
    }

    if (!empty($_GET['priority']) && in_array($_GET['priority'], ['low', 'medium', 'high'], true)) {
        $where .= ' AND priority = ?';
        $params[] = $_GET['priority'];
    }

    if (!empty($_GET['search'])) {
        $where .= ' AND (title LIKE ? OR description LIKE ?)';
        $s = '%' . $_GET['search'] . '%';
        $params[] = $s;
        $params[] = $s;
    }

    $stmt = $pdo->prepare("SELECT * FROM tasks $where ORDER BY created_at DESC");
    $stmt->execute($params);
    jsonResponse(['tasks' => $stmt->fetchAll()]);
}

/* ================= POST — mutations ================= */
if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (!verifyCsrf($input['csrf'] ?? null)) {
    jsonResponse(['error' => 'Invalid CSRF token'], 403);
}

$action = $input['action'] ?? '';

switch ($action) {

    /* ---------- CREATE ---------- */
    case 'create': {
        $title = trim($input['title'] ?? '');
        if ($title === '' || mb_strlen($title) > 255) {
            jsonResponse(['error' => 'العنوان مطلوب (حتى 255 حرفًا).'], 422);
        }

        $desc     = trim($input['description'] ?? '');
        $status   = in_array($input['status'] ?? '', ['todo','doing','done'], true)  ? $input['status']   : 'todo';
        $priority = in_array($input['priority'] ?? '', ['low','medium','high'], true) ? $input['priority'] : 'medium';
        $due      = $input['due_date'] ?? null;
        if ($due !== null && $due !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) $due = null;
        if ($due === '') $due = null;

        $stmt = $pdo->prepare(
            'INSERT INTO tasks (user_id, title, description, status, priority, due_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $desc, $status, $priority, $due]);
        $id = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'task' => $stmt->fetch()]);
    }

    /* ---------- UPDATE ---------- */
    case 'update': {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['error' => 'ID required'], 422);

        $check = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ? LIMIT 1');
        $check->execute([$id, $userId]);
        if (!$check->fetch()) jsonResponse(['error' => 'Not found'], 404);

        $fields = [];
        $params = [];

        if (isset($input['title'])) {
            $t = trim($input['title']);
            if ($t === '' || mb_strlen($t) > 255) {
                jsonResponse(['error' => 'عنوان غير صالح.'], 422);
            }
            $fields[] = 'title = ?';
            $params[] = $t;
        }

        if (isset($input['description'])) {
            $fields[] = 'description = ?';
            $params[] = trim($input['description']);
        }

        if (isset($input['status']) && in_array($input['status'], ['todo','doing','done'], true)) {
            $fields[] = 'status = ?';
            $params[] = $input['status'];
        }

        if (isset($input['priority']) && in_array($input['priority'], ['low','medium','high'], true)) {
            $fields[] = 'priority = ?';
            $params[] = $input['priority'];
        }

        if (array_key_exists('due_date', $input)) {
            $d = $input['due_date'];
            if ($d !== null && $d !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $d = null;
            $fields[] = 'due_date = ?';
            $params[] = ($d === '' ? null : $d);
        }

        if ($fields) {
            $params[] = $id;
            $params[] = $userId;
            $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?';
            $pdo->prepare($sql)->execute($params);
        }

        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'task' => $stmt->fetch()]);
    }

    /* ---------- DELETE ---------- */
    case 'delete': {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['error' => 'ID required'], 422);

        $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        jsonResponse(['success' => true]);
    }

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
