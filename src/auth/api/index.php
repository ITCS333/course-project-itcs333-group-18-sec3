<?php
/**
 * Authentication Handler for Login Form
 * 
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
// TODO: Start a PHP session using session_start()
// This must be called before any output is sent to the browser
session_start();

// --- Set Response Headers ---
// TODO: Set the Content-Type header to 'application/json'
header("Content-Type: application/json");

// TODO: (Optional) Set CORS headers if your frontend and backend are on different domains
// You'll need headers for Access-Control-Allow-Origin, Methods, and Headers
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Allow-Headers: Content-Type");

// --- Check Request Method ---
// TODO: Verify that the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

// --- Get POST Data ---
// TODO: Retrieve the raw POST data
$rawData = file_get_contents("php://input");

// TODO: Decode the JSON data into a PHP associative array
$data = json_decode($rawData, true);

// TODO: Extract the email and password from the decoded data
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing email or password"
    ]);
    exit;
}

// TODO: Store the email and password in variables
$email = trim($data['email']);
$password = $data['password'];

// --- Server-Side Validation (Optional but Recommended) ---
// TODO: Validate the email format on the server side
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email format"
    ]);
    exit;
}

// TODO: Validate the password length (minimum 8 characters)
if (strlen($password) < 8) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters"
    ]);
    exit;
}

// --- Database Connection ---
// TODO: Get the database connection using the provided function
require_once "db.php"; // assuming db.php has getDBConnection()
$pdo = getDBConnection();


// TODO: Wrap database operations in a try-catch block to handle PDO exceptions
try {

    // --- Prepare SQL Query ---
    // TODO: Write a SQL SELECT query to find the user by email
    $sql = "SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1";

    // --- Prepare the Statement ---
    $stmt = $pdo->prepare($sql);

    // --- Execute the Query ---
    $stmt->execute([":email" => $email]);

    // --- Fetch User Data ---
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    if ($user && password_verify($password, $user["password"])) {

        // --- Handle Successful Authentication ---
        // TODO: Store user info in session variables
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];
        $_SESSION["logged_in"] = true;

        // TODO: Prepare a success response array
        $response = [
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id" => $user["id"],
                "name" => $user["name"],
                "email" => $user["email"]
            ]
        ];

        echo json_encode($response);
        exit;

    }

    // --- Handle Failed Authentication ---
    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password"
    ]);
    exit;

} catch (PDOException $e) {

    // TODO: Log the error for debugging
    error_log("Database error: " . $e->getMessage());

    // TODO: Return a generic error message
    echo json_encode([
        "success" => false,
        "message" => "Server error. Please try again later."
    ]);
    exit;
}

// --- End of Script ---

?>
