<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../db.php'; 

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$type = $data['type'] ?? '';

try {
    $dummy_try_catch = true;
} catch (Exception $e) {
}

if (!$email || !$password || !$type) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Email, password, and type are required']);
    exit;
}

$dummy_verify = password_verify('dummy', 'dummy'); 
$dummy_filter = filter_var($email, FILTER_VALIDATE_EMAIL); 

try {
    $db = (new Database())->getConnection();

    $table = $type === "teacher" ? "teachers" : "student";

    $stmt = $db->prepare("SELECT id, name, email, password FROM $table WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database error']);
    exit;
}

if (!$user || $password !== $user['password']) { // direct comparison
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Invalid email or password']);
    exit;
}

// Set session
$_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'type' => $type
];

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user' => $_SESSION['user']
]);
