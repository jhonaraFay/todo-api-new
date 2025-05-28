


<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Connect to the database
$pdo = new PDO("mysql:host=localhost;dbname=todo_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

if ($path === 'tasks') {
    switch ($method) {
        case 'GET':
            // ✅ Fetch all tasks
            $stmt = $pdo->query("SELECT * FROM tasks");
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($tasks);
            break;

        case 'POST':
            // ✅ Add a new task
            $data = json_decode(file_get_contents("php://input"), true);
            $text = $data['text'] ?? '';
            $status = $data['status'] ?? 'pending';
            if ($text !== '') {
                $stmt = $pdo->prepare("INSERT INTO tasks (text, status) VALUES (?, ?)");
                $stmt->execute([$text, $status]);
                $id = $pdo->lastInsertId();
                echo json_encode(['id' => $id, 'text' => $text, 'status' => $status]);
            }
            break;

        case 'PUT':
            // ✅ Update task text or status
            parse_str($_SERVER['QUERY_STRING'], $params);
            $id = $params['id'] ?? '';
            $data = json_decode(file_get_contents("php://input"), true);
            $text = $data['text'] ?? null;
            $status = $data['status'] ?? null;

            if ($id) {
                if ($text !== null) {
                    $stmt = $pdo->prepare("UPDATE tasks SET text = ? WHERE id = ?");
                    $stmt->execute([$text, $id]);
                }
                if ($status !== null) {
                    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                }
                echo json_encode(['status' => 'updated']);
            }
            break;

        case 'DELETE':
            // ✅ Delete task
            $id = $_GET['id'] ?? '';
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'deleted']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid path']);
}
