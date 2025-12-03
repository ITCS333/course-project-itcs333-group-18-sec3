<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header("Content-Type: application/json; charset=UTF-8");

// TODO: Set CORS headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}


// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
require_once "database.php"; 

// TODO: Create database connection
$db = $conn;

// TODO: Set PDO to throw exceptions on errors
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$input = json_decode(file_get_contents("php://input"), true); 

// TODO: Parse query parameters
$id = $_GET['id'] ?? null; 


// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // TODO: Start building the SQL query
    $sql = "SELECT * FROM assignments WHERE 1";
    
    // TODO: Check if 'search' query parameter exists in $_GET
    if (isset($_GET['search']) && $_GET['search'] !== "") {
        $search = "%" . $_GET['search'] . "%";
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
    }
    
    // TODO: Check if 'sort' and 'order' query parameters exist
    $sortField = $_GET['sort'] ?? "id";
    $sortOrder = $_GET['order'] ?? "ASC";
    $sql .= " ORDER BY $sortField $sortOrder";
    
    // TODO: Prepare the SQL statement using $db->prepare()
     $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if search is used
    if (isset($_GET['search']) && $_GET['search'] !== "") {
        $stmt->bindParam(":search", $search);
    }
    
    // TODO: Execute the prepared statement
    $stmt->execute();
    
    // TODO: Fetch all results as associative array
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    // TODO: For each assignment, decode the 'files' field from JSON to array
    foreach ($assignments as &$asg) {
        $asg['files'] = json_decode($asg['files'], true);
    }
    
    // TODO: Return JSON response
    echo json_encode($assignments);
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (!$assignmentId) {
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }
    
    // TODO: Prepare SQL query to select assignment by id
     $sql = "SELECT * FROM assignments WHERE id = :id LIMIT 1";
     $stmt = $db->prepare($sql);
    
    // TODO: Bind the :id parameter
    $stmt->bindParam(":id", $assignmentId, PDO::PARAM_INT);
    
    // TODO: Execute the statement
     $stmt->execute();
    
    // TODO: Fetch the result as associative array
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    
    // TODO: Check if assignment was found
     if (!$assignment) {
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }
    
    // TODO: Decode the 'files' field from JSON to array
     $assignment['files'] = json_decode($assignment['files'], true);
    
    // TODO: Return success response with assignment data
    echo json_encode($assignment);
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
     if (!isset($data['title'], $data['description'], $data['due_date'])) {
        echo json_encode(["error" => "Missing required fields"]);
        return;
    }
    
    // TODO: Sanitize input data
    $title       = htmlspecialchars(trim($data['title']));
    $description = htmlspecialchars(trim($data['description']));
    $due_date    = trim($data['due_date']);
    
    // TODO: Validate due_date format
     if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $due_date)) {
        echo json_encode(["error" => "Invalid date format, expected YYYY-MM-DD"]);
        return;
    }
    
    // TODO: Generate a unique assignment ID
    
    
    // TODO: Handle the 'files' field
    $files = isset($data['files']) ? json_encode($data['files']) : json_encode([]);

    
    // TODO: Prepare INSERT query
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':due_date', $due_date);
    $stmt->bindParam(':files', $files);
    
    // TODO: Execute the statement
    $result = $stmt->execute();
    
    // TODO: Check if insert was successful
    if ($result) {
        echo json_encode([
            "status" => "success",
            "message" => "Assignment created successfully",
            "id" => $db->lastInsertId()
        ]);
        return;
    }
    
    // TODO: If insert failed, return 500 error
     http_response_code(500);
    echo json_encode(["error" => "Failed to create assignment"]);
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }
    
    // TODO: Store assignment ID in variable
    $id = $data['id'];

    
    // TODO: Check if assignment exists
    $check = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $check->bindParam(":id", $id, PDO::PARAM_INT);
    $check->execute();
    if ($check->rowCount() == 0) {
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    $fields = [];
    $params = [];

    
    // TODO: Check which fields are provided and add to SET clause
    if (isset($data['title'])) {
        $fields[] = "title = :title";
        $params[':title'] = htmlspecialchars(trim($data['title']));
    }
    if (isset($data['description'])) {
        $fields[] = "description = :description";
        $params[':description'] = htmlspecialchars(trim($data['description']));
    }
    if (isset($data['due_date'])) {
        $fields[] = "due_date = :due_date";
        $params[':due_date'] = $data['due_date'];
    }
    if (isset($data['files'])) {
        $fields[] = "files = :files";
        $params[':files'] = json_encode($data['files']);
    }
    
    // TODO: If no fields to update (besides updated_at), return 400 error
    if (empty($fields)) {
        echo json_encode(["error" => "No data provided to update"]);
        return;
    }

    
    // TODO: Complete the UPDATE query
    $sql = "UPDATE assignments SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = :id";

    
    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);

    
    // TODO: Bind all parameters dynamically
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    
    // TODO: Execute the statement
    $stmt->execute();

    
    // TODO: Check if update was successful
    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Assignment updated"]);
        return;
    }
    
    // TODO: If no rows affected, return appropriate message
    echo json_encode(["status" => "no_change", "message" => "No fields were changed"]);

}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }
    
    // TODO: Check if assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }
    
    // TODO: Delete associated comments first (due to foreign key constraint)
    $deleteCommentsStmt = $db->prepare("DELETE FROM comments WHERE assignment_id = :id");
    $deleteCommentsStmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
    $deleteCommentsStmt->execute();
    
    // TODO: Prepare DELETE query for assignment
    $deleteAssignmentStmt = $db->prepare("DELETE FROM assignments WHERE id = :id");

    
    // TODO: Bind the :id parameter
    $deleteAssignmentStmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);

    
    // TODO: Execute the statement
    $success = $deleteAssignmentStmt->execute();

    
    // TODO: Check if delete was successful
     if ($success && $deleteAssignmentStmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Assignment deleted successfully"]);
    } else {
        // TODO: If delete failed, return 500 error
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete assignment"]);
    }
    
    // TODO: If delete failed, return 500 error
    if (!$success || $deleteAssignmentStmt->rowCount() === 0) {
    http_response_code(500); // إرسال رمز حالة 500 للخادم
    echo json_encode([
        "error" => "Failed to delete assignment" // رسالة خطأ واضحة
    ]);
    return;
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }
    
    // TODO: Prepare SQL query to select all comments for the assignment
    $stmt = $db->prepare("SELECT id, assignment_id, author, content, created_at 
                          FROM comments 
                          WHERE assignment_id = :assignment_id
                          ORDER BY created_at ASC");
    
    // TODO: Bind the :assignment_id parameter
    $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);

    
    // TODO: Execute the statement
     if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to retrieve comments"]);
        return;
    }
    
    // TODO: Fetch all results as associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    // TODO: Return success response with comments data
    echo json_encode([
        "success" => true,
        "data" => $comments
    ]);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
     if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        http_response_code(400);
        echo json_encode(["error" => "assignment_id, author, and text are required"]);
        return;
    }
    
    // TODO: Sanitize input data
    $assignmentId = intval($data['assignment_id']);
    $author = htmlspecialchars(trim($data['author']));
    $text = htmlspecialchars(trim($data['text']));
    
    // TODO: Validate that text is not empty after trimming
    if ($text === "") {
        http_response_code(400);
        echo json_encode(["error" => "Comment text cannot be empty"]);
        return;
    }
    
    // TODO: Verify that the assignment exists
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindParam(':id', $assignmentId, PDO::PARAM_INT);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }
    
    // TODO: Prepare INSERT query for comment
    $insertStmt = $db->prepare("INSERT INTO comments (assignment_id, author, content, created_at)
                                VALUES (:assignment_id, :author, :content, NOW())");
    
    // TODO: Bind all parameters
    $insertStmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $insertStmt->bindParam(':author', $author, PDO::PARAM_STR);
    $insertStmt->bindParam(':content', $text, PDO::PARAM_STR);
    
    // TODO: Execute the statement
    if (!$insertStmt->execute()) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to create comment"]);
        return;
    }
    
    // TODO: Get the ID of the inserted comment
    $commentId = $db->lastInsertId();

    
    // TODO: Return success response with created comment data
    echo json_encode([
        "success" => true,
        "data" => [
            "id" => $commentId,
            "assignment_id" => $assignmentId,
            "author" => $author,
            "content" => $text,
            "created_at" => date("Y-m-d H:i:s")
        ]
    ]);
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if (empty($commentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Comment ID is required"]);
        return;
    }
    
    // TODO: Check if comment exists
     $checkStmt = $db->prepare("SELECT id FROM comments WHERE id = :id");
    $checkStmt->bindParam(':id', $commentId, PDO::PARAM_INT);
    $checkStmt->execute();
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Comment not found"]);
        return;
    }
    
    // TODO: Prepare DELETE query
        $deleteStmt = $db->prepare("DELETE FROM comments WHERE id = :id");

    
    // TODO: Bind the :id parameter
        $deleteStmt->bindParam(':id', $commentId, PDO::PARAM_INT);

    
    // TODO: Execute the statement
        $success = $deleteStmt->execute();

    
    // TODO: Check if delete was successful
     if ($success && $deleteStmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Comment deleted successfully"]);
    } else {
    
    // TODO: If delete failed, return 500 error
     http_response_code(500);
        echo json_encode(["error" => "Failed to delete comment"]);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    $resource = $_GET['resource'] ?? null;
    
    // TODO: Route based on HTTP method and resource type
    
    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            if ($id) {
                getAssignmentById($db, $id);
            } else {
                getAllAssignments($db);
            }

        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
             $assignmentId = $_GET['assignment_id'] ?? null;

            if ($assignmentId) {
                getCommentsByAssignment($db, $assignmentId);
            } else {
                echo json_encode(["error" => "assignment_id is required"]);
            }

        } else {
            // TODO: Invalid resource, return 400 error
             http_response_code(400);
            echo json_encode(["error" => "Invalid resource"]);
        }
        
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            createAssignment($db, $input);

        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            createComment($db, $input);

        } else {
            // TODO: Invalid resource, return 400 error
            http_response_code(400);
            echo json_encode(["error" => "Invalid resource"]);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            updateAssignment($db, $input);

        } else {
            // TODO: PUT not supported for other resources
            http_response_code(400);
            echo json_encode(["error" => "PUT not supported for this resource"]);
        }
        
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            $assignmentId = $id ?? ($input['id'] ?? null);
             if ($assignmentId) {
                deleteAssignment($db, $assignmentId);
            } else {
                echo json_encode(["error" => "Assignment ID is required"]);
            }

        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
        $commentId = $_GET['id'] ?? null;
         if ($commentId) {
                deleteComment($db, $commentId);
            } else {
                echo json_encode(["error" => "Comment ID is required"]);
            }

        } else {
            // TODO: Invalid resource, return 400 error
             http_response_code(400);
            echo json_encode(["error" => "Invalid resource"]);
        }
        
    } else {
        // TODO: Method not supported
        http_response_code(405);
        echo json_encode(["error" => "Method not supported"]);
        
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    http_response_code(500);
    echo json_encode(["db_error" => $e->getMessage()]);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
    
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    // TODO: Ensure data is an array
    if (!is_array($data)) {
        $data = ["message" => $data];
    }
    
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    
    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return the sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);
    
    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    $isValid = in_array($value, $allowedValues, true);
    
    // TODO: Return the result
     return $isValid;
}

?>
