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

require_once '../db.php'; 
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
        if (isset($_GET['id'])) {
            $stmt = $GLOBALS['db']->prepare("SELECT id, name, email, created_at FROM student WHERE id = :sid");
            $stmt->execute([':sid' => $_GET['id']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($student) sendResponse(['success' => true, 'data' => $student]);
            else sendResponse(['success' => false, 'message' => 'Student not found'], 404);
        } else {
            $search = isset($_GET['search']) ? "%".sanitizeInput($_GET['search'])."%" : '%';
            $sortFields = ['name','id','email'];
            $sort = isset($_GET['sort']) && in_array($_GET['sort'],$sortFields) ? $_GET['sort'] : 'name';
            $order = isset($_GET['order']) && in_array(strtolower($_GET['order']),['asc','desc']) ? $_GET['order'] : 'asc';
            $stmt = $db->prepare("SELECT id, name, email, created_at FROM student WHERE name LIKE :s OR id LIKE :s OR email LIKE :s ORDER BY $sort $order");
            $stmt->execute([':s' => $search]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(['success' => true, 'data' => $students]);
        }
    }

    elseif ($method === 'POST') {
        if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'change_teacher_password') {
            $teacher_id = sanitizeInput($input['teacher_id'] ?? '');
            $new = $input['new_password'] ?? '';

            if (!$teacher_id || !$new) sendResponse(['success'=>false,'message'=>'Missing fields'],400);
            if (strlen($new) < 8) sendResponse(['success'=>false,'message'=>'New password too short'],400);

            $stmt = $db->prepare("UPDATE teachers SET password=:p WHERE id=:tid");
            $stmt->execute([':p'=>$new, ':tid'=>$teacher_id]);

            sendResponse(['success'=>true,'message'=>'Password updated successfully']);
        }
            elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'change_password') {
                $sid = sanitizeInput($input['id'] ?? '');
                $new = $input['new_password'] ?? '';
    
                if (!$sid || !$new) sendResponse(['success'=>false,'message'=>'Missing fields'],400);
                if (strlen($new) < 8) sendResponse(['success'=>false,'message'=>'New password too short'],400);
    
                $stmt = $db->prepare("UPDATE student SET password=:p WHERE id=:sid");
                $stmt->execute([':p'=>$new, ':sid'=>$sid]);
    
                sendResponse(['success'=>true,'message'=>'Password updated successfully']);
        } else {
            $name = sanitizeInput($input['name'] ?? '');
            $email = sanitizeInput($input['email'] ?? '');
            $password = $input['password'] ?? '';
            if ( !$name || !$email || !$password) sendResponse(['success'=>false,'message'=>'Missing fields'],400);
            if (!validateEmail($email)) sendResponse(['success'=>false,'message'=>'Invalid email'],400);
            $stmt = $db->prepare("SELECT COUNT(*) FROM student WHERE  email=:email");
            $stmt->execute([':email'=>$email]);
            if ($stmt->fetchColumn() > 0) sendResponse(['success'=>false,'message'=>'Student ID or Email exists'],409);
            
            $stmt = $db->prepare("INSERT INTO student (name,email,password) VALUES (:name,:email,:pass)");
            $stmt->execute([':name'=>$name,':email'=>$email,':pass'=>$password]);
            sendResponse(['success'=>true,'message'=>'Student created successfully'],201);
        }
    }

    elseif ($method === 'PUT') {
        $sid = sanitizeInput($input['id'] ?? '');
        if (!$sid) sendResponse(['success'=>false,'message'=>'Missing id'],400);
        $stmt = $db->prepare("SELECT * FROM student WHERE id=:sid");
        $stmt->execute([':sid'=>$sid]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) sendResponse(['success'=>false,'message'=>'Student not found'],404);
        $updates = [];
        $params = [];
        if (isset($input['name'])) { $updates[] = "name=:name"; $params[':name']=sanitizeInput($input['name']); }
        if (isset($input['email'])) {
            $email = sanitizeInput($input['email']);
            $stmt2 = $db->prepare("SELECT COUNT(*) FROM student WHERE email=:email AND id!=:sid");
            $stmt2->execute([':email'=>$email,':sid'=>$sid]);
            if ($stmt2->fetchColumn()>0) sendResponse(['success'=>false,'message'=>'Email exists'],409);
            $updates[] = "email=:email";
            $params[':email']=$email;
        }
        if (!$updates) sendResponse(['success'=>false,'message'=>'No fields to update'],400);
        $params[':sid']=$sid;
        $stmt = $db->prepare("UPDATE student SET ".implode(',',$updates)." WHERE id=:sid");
        $stmt->execute($params);
        sendResponse(['success'=>true,'message'=>'Student updated successfully']);
    }

    elseif ($method === 'DELETE') {
        $sid = sanitizeInput($_GET['id'] ?? ($input['id'] ?? ''));
        if (!$sid) sendResponse(['success'=>false,'message'=>'Missing id'],400);
        $stmt = $db->prepare("SELECT * FROM student WHERE id=:sid");
        $stmt->execute([':sid'=>$sid]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['success'=>false,'message'=>'Student not found'],404);
        $stmt = $db->prepare("DELETE FROM student WHERE id=:sid");
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

