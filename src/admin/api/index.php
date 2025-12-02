<?php
// Student Management API

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php'; // يحتوي على class Database مع getConnection()
$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

try {
    if ($method === 'GET') {
        if (isset($_GET['student_id'])) {
            $stmt = $GLOBALS['db']->prepare("SELECT student_id, name, email, created_at FROM students WHERE student_id = :sid");
            $stmt->execute([':sid' => $_GET['student_id']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($student) sendResponse(['success' => true, 'data' => $student]);
            else sendResponse(['success' => false, 'message' => 'Student not found'], 404);
        } else {
            $search = isset($_GET['search']) ? "%".sanitizeInput($_GET['search'])."%" : '%';
            $sortFields = ['name','student_id','email'];
            $sort = isset($_GET['sort']) && in_array($_GET['sort'],$sortFields) ? $_GET['sort'] : 'name';
            $order = isset($_GET['order']) && in_array(strtolower($_GET['order']),['asc','desc']) ? $_GET['order'] : 'asc';
            $stmt = $db->prepare("SELECT student_id, name, email, created_at FROM students WHERE name LIKE :s OR student_id LIKE :s OR email LIKE :s ORDER BY $sort $order");
            $stmt->execute([':s' => $search]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(['success' => true, 'data' => $students]);
        }
    }

    elseif ($method === 'POST') {
        if (isset($_GET['action']) && $_GET['action'] === 'change_password') {
            $sid = sanitizeInput($input['student_id'] ?? '');
            $current = $input['current_password'] ?? '';
            $new = $input['new_password'] ?? '';
            if (!$sid || !$current || !$new) sendResponse(['success'=>false,'message'=>'Missing fields'],400);
            if (strlen($new) < 8) sendResponse(['success'=>false,'message'=>'New password too short'],400);
            $stmt = $db->prepare("SELECT password FROM students WHERE student_id=:sid");
            $stmt->execute([':sid'=>$sid]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$student || !password_verify($current,$student['password'])) sendResponse(['success'=>false,'message'=>'Invalid current password'],401);
            $hash = password_hash($new,PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE students SET password=:p WHERE student_id=:sid");
            $stmt->execute([':p'=>$hash,':sid'=>$sid]);
            sendResponse(['success'=>true,'message'=>'Password updated successfully']);
        } else {
            $sid = sanitizeInput($input['student_id'] ?? '');
            $name = sanitizeInput($input['name'] ?? '');
            $email = sanitizeInput($input['email'] ?? '');
            $password = $input['password'] ?? '';
            if (!$sid || !$name || !$email || !$password) sendResponse(['success'=>false,'message'=>'Missing fields'],400);
            if (!validateEmail($email)) sendResponse(['success'=>false,'message'=>'Invalid email'],400);
            $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE student_id=:sid OR email=:email");
            $stmt->execute([':sid'=>$sid,':email'=>$email]);
            if ($stmt->fetchColumn() > 0) sendResponse(['success'=>false,'message'=>'Student ID or Email exists'],409);
            $hash = password_hash($password,PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO students (student_id,name,email,password) VALUES (:sid,:name,:email,:pass)");
            $stmt->execute([':sid'=>$sid,':name'=>$name,':email'=>$email,':pass'=>$hash]);
            sendResponse(['success'=>true,'message'=>'Student created successfully'],201);
        }
    }

    elseif ($method === 'PUT') {
        $sid = sanitizeInput($input['student_id'] ?? '');
        if (!$sid) sendResponse(['success'=>false,'message'=>'Missing student_id'],400);
        $stmt = $db->prepare("SELECT * FROM students WHERE student_id=:sid");
        $stmt->execute([':sid'=>$sid]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) sendResponse(['success'=>false,'message'=>'Student not found'],404);
        $updates = [];
        $params = [];
        if (isset($input['name'])) { $updates[] = "name=:name"; $params[':name']=sanitizeInput($input['name']); }
        if (isset($input['email'])) {
            $email = sanitizeInput($input['email']);
            $stmt2 = $db->prepare("SELECT COUNT(*) FROM students WHERE email=:email AND student_id!=:sid");
            $stmt2->execute([':email'=>$email,':sid'=>$sid]);
            if ($stmt2->fetchColumn()>0) sendResponse(['success'=>false,'message'=>'Email exists'],409);
            $updates[] = "email=:email";
            $params[':email']=$email;
        }
        if (!$updates) sendResponse(['success'=>false,'message'=>'No fields to update'],400);
        $params[':sid']=$sid;
        $stmt = $db->prepare("UPDATE students SET ".implode(',',$updates)." WHERE student_id=:sid");
        $stmt->execute($params);
        sendResponse(['success'=>true,'message'=>'Student updated successfully']);
    }

    elseif ($method === 'DELETE') {
        $sid = sanitizeInput($_GET['student_id'] ?? ($input['student_id'] ?? ''));
        if (!$sid) sendResponse(['success'=>false,'message'=>'Missing student_id'],400);
        $stmt = $db->prepare("SELECT * FROM students WHERE student_id=:sid");
        $stmt->execute([':sid'=>$sid]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['success'=>false,'message'=>'Student not found'],404);
        $stmt = $db->prepare("DELETE FROM students WHERE student_id=:sid");
        $stmt->execute([':sid'=>$sid]);
        sendResponse(['success'=>true,'message'=>'Student deleted successfully']);
    }

    else {
        sendResponse(['success'=>false,'message'=>'Method not allowed'],405);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse(['success'=>false,'message'=>'Database error'],500);
} catch (Exception $e) {
    sendResponse(['success'=>false,'message'=>'Server error'],500);
}
?>

