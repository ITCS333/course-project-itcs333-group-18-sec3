<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();  }

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once '../config/Database.php';

// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection();


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$input = json_decode(file_get_contents('php://input'), true);

// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = $_GET['resource'] ?? '';
$id = $_GET['id'] ?? '';
$week_id = $_GET['week_id'] ?? '';

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'start_date';
    $order = $_GET['order'] ?? 'asc';

    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
     $sql = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    $params = [];
    
    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
      if (!empty($search)) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;  }
    
    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
     $allowedSort = ['title', 'start_date', 'created_at'];
    if (!in_array($sort, $allowedSort)) {
        $sort = 'start_date'; }
    
    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)
    $allowedOrder = ['asc', 'desc'];
    if (!in_array($order, $allowedOrder)) {
        $order = 'asc'; }
    
    // TODO: Add ORDER BY clause to the query
    $sql .= " ORDER BY $sort $order";
    
    // TODO: Prepare the SQL query using PDO
     $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
     if (!empty($search)) {
        $stmt->bindParam(1, $params[0]);
        $stmt->bindParam(2, $params[1]);  }
    
    // TODO: Execute the query
     $stmt->execute();
    
    // TODO: Fetch all results as an associative array
     $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
     foreach ($weeks as &$week) {
        $week['links'] = json_decode($week['links'], true) ?? []; }
    
    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse(true, 'Weeks retrieved successfully', $weeks);

}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
     if (empty($weekId)) {
        http_response_code(400);
        sendResponse(false, 'Week ID is required');
        return;  }
    
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $sql = "SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind the week_id parameter
    $stmt->bindParam(1, $weekId);

    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch the result
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
     if ($week) {
        $week['links'] = json_decode($week['links'], true) ?? [];
        sendResponse(true, 'Week retrieved successfully', $week);
    } else {
        http_response_code(404);
        sendResponse(false, 'Week not found');
    }
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            sendResponse(false, "Missing required field: $field");
            return;  }  }
    
    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
    $week_id = trim($data['week_id']);
    $title = trim($data['title']);
    $start_date = trim($data['start_date']);
    $description = trim($data['description']);

    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
        http_response_code(400);
        sendResponse(false, 'Invalid date format. Use YYYY-MM-DD');
        return;  }
    
    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checkStmt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $checkStmt->bindParam(1, $week_id);
    $checkStmt->execute();
    if ($checkStmt->fetch()) {
        http_response_code(409);
        sendResponse(false, 'Week ID already exists');
        return;  }
    
    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : '[]';

    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
    $sql = "INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);

    // TODO: Bind parameters
    $stmt->bindParam(1, $week_id);
    $stmt->bindParam(2, $title);
    $stmt->bindParam(3, $start_date);
    $stmt->bindParam(4, $description);
    $stmt->bindParam(5, $links);

    // TODO: Execute the query
     if ($stmt->execute()) {
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
        http_response_code(201);
        $newWeek = [
            'week_id' => $week_id,
            'title' => $title,
            'start_date' => $start_date,
            'description' => $description,
            'links' => json_decode($links, true)
        ];
        sendResponse(true, 'Week created successfully', $newWeek);
    } else {
        http_response_code(500);
        sendResponse(false, 'Failed to create week'); }
    }


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
     if (empty($data['week_id'])) {
        http_response_code(400);
        sendResponse(false, 'Week ID is required');
        return;  }
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $checkStmt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $checkStmt->bindParam(1, $data['week_id']);
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        sendResponse(false, 'Week not found');
        return;  }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $setClauses = [];
    $values = [];
    
    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    // If start_date is provided, validate format and add "start_date = ?"
    // If description is provided, add "description = ?"
    // If links is provided, encode to JSON and add "links = ?"
     if (isset($data['title'])) {
        $setClauses[] = "title = ?";
        $values[] = trim($data['title']);
    }
    
    if (isset($data['start_date'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['start_date'])) {
            http_response_code(400);
            sendResponse(false, 'Invalid date format. Use YYYY-MM-DD');
            return;
        }
        $setClauses[] = "start_date = ?";
        $values[] = $data['start_date'];
    }
    
    if (isset($data['description'])) {
        $setClauses[] = "description = ?";
        $values[] = trim($data['description']);
    }
    
    if (isset($data['links'])) {
        $setClauses[] = "links = ?";
        $values[] = is_array($data['links']) ? json_encode($data['links']) : '[]';
    }
    
    // TODO: If no fields to update, return error response with 400 status
     if (empty($setClauses)) {
        http_response_code(400);
        sendResponse(false, 'No fields to update');
        return;
    }

    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
     $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    
    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    $sql = "UPDATE weeks SET " . implode(', ', $setClauses) . " WHERE week_id = ?";
    $values[] = $data['week_id'];
    
    // TODO: Prepare the query
     $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
     foreach ($values as $index => $value) {
        $stmt->bindValue($index + 1, $value);
    }
    
    // TODO: Execute the query
     if ($stmt->execute()) {
         
    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
    $updatedWeek = $db->prepare("SELECT * FROM weeks WHERE week_id = ?");
        $updatedWeek->bindParam(1, $data['week_id']);
        $updatedWeek->execute();
        $week = $updatedWeek->fetch(PDO::FETCH_ASSOC);
        $week['links'] = json_decode($week['links'], true) ?? [];
        
        sendResponse(true, 'Week updated successfully', $week);
    } else {
        http_response_code(500);
        sendResponse(false, 'Failed to update week');
    }   
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        http_response_code(400);
        sendResponse(false, 'Week ID is required');
        return;
    }
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkStmt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $checkStmt->bindParam(1, $weekId);
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        sendResponse(false, 'Week not found');
        return;
    }
    
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
    $deleteCommentsStmt = $db->prepare("DELETE FROM comments WHERE week_id = ?");
    $deleteCommentsStmt->bindParam(1, $weekId);
    
    // TODO: Execute comment deletion query
    $deleteCommentsStmt->execute();
    
    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $deleteWeekStmt = $db->prepare("DELETE FROM weeks WHERE week_id = ?");
    
    // TODO: Bind the week_id parameter
    $deleteWeekStmt->bindParam(1, $weekId);
    
    // TODO: Execute the query
    if ($deleteWeekStmt->execute()) {
        
    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
        sendResponse(true, 'Week and associated comments deleted successfully');
    } else {
    // If no, return error response with 500 status
        http_response_code(500);
        sendResponse(false, 'Failed to delete week');
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
     if (empty($weekId)) {
        http_response_code(400);
        sendResponse(false, 'Week ID is required');
        return;
    }
    
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $sql = "SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);

    // TODO: Bind the week_id parameter
     $stmt->bindParam(1, $weekId);
    
    // TODO: Execute the query
      $stmt->execute();
    
    // TODO: Fetch all results as an associative array
      $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
     sendResponse(true, 'Comments retrieved successfully', $comments);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        http_response_code(400);
        sendResponse(false, 'Missing required fields: week_id, author, and text are required');
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $week_id = trim($data['week_id']);
    $author = trim($data['author']);
    $text = trim($data['text']);
    
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
     if (empty($text)) {
        http_response_code(400);
        sendResponse(false, 'Comment text cannot be empty');
        return;
    }
    
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    $checkStmt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $checkStmt->bindParam(1, $week_id);
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        sendResponse(false, 'Week not found');
        return;
    }
    
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
    $sql = "INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters
    $stmt->bindParam(1, $week_id);
    $stmt->bindParam(2, $author);
    $stmt->bindParam(3, $text);
    
    // TODO: Execute the query
     if ($stmt->execute()) {
         
    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
        $commentId = $db->lastInsertId();
        $newComment = [
            'id' => $commentId,
            'week_id' => $week_id,
            'author' => $author,
            'text' => $text
        ];
        http_response_code(201);
        sendResponse(true, 'Comment created successfully', $newComment);
    } else {
        // If no, return error response with 500 status
        http_response_code(500);
        sendResponse(false, 'Failed to create comment');
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
     if (empty($commentId)) {
        http_response_code(400);
        sendResponse(false, 'Comment ID is required');
        return;
    }
    
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkStmt = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $checkStmt->bindParam(1, $commentId);
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        sendResponse(false, 'Comment not found');
        return;
    }
    
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
     $deleteStmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    
    // TODO: Bind the id parameter
     $deleteStmt->bindParam(1, $commentId);
    // TODO: Execute the query
     if ($deleteStmt->execute()) {
    // TODO: Check if delete was successful
    // If yes, return success response
        sendResponse(true, 'Comment deleted successfully');
    } else {
    // If no, return error response with 500 status
        http_response_code(500);
        sendResponse(false, 'Failed to delete comment');
    }

}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
     $resource = $_GET['resource'] ?? 'weeks';
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
             if (!empty($_GET['week_id'])) {
                getWeekById($db, $_GET['week_id']);
            } else {
                getAllWeeks($db);
            }
            
        } elseif ($method === 'POST') {
             // TODO: Call createWeek() with the decoded request body
             createWeek($db, $input);
            
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
             updateWeek($db, $input);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
              $weekId = $_GET['week_id'] ?? $input['week_id'] ?? '';
            deleteWeek($db, $weekId);
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
             http_response_code(405);
            sendResponse(false, 'Method not allowed');
            
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            $weekId = $_GET['week_id'] ?? '';
            getCommentsByWeek($db, $weekId);
            
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            createComment($db, $input);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            $commentId = $_GET['id'] ?? $input['id'] ?? '';
            deleteComment($db, $commentId);
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            http_response_code(405);
            sendResponse(false, 'Method not allowed');
        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
        http_response_code(400);
        sendResponse(false, "Invalid resource. Use 'weeks' or 'comments'");
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
     error_log($e->getMessage());
    
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    http_response_code(500);
    sendResponse(false, "Database error occurred");
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status 
    error_log($e->getMessage());
    http_response_code(500);
    sendResponse(false, "Server error occurred");
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
      http_response_code($statusCode);
    
    // TODO: Echo JSON encoded data
    // Use json_encode($data)
     echo json_encode($data);
    
    // TODO: Exit to prevent further execution
     exit;
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
     $response = ['success' => false, 'error' => $message];
    
    // TODO: Call sendResponse() with the error array and status code
     sendResponse($response, $statusCode);
    
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
     $data = trim($data);
    
    // TODO: Strip HTML tags using strip_tags()
     $data = strip_tags($data);
    
    // TODO: Convert special characters using htmlspecialchars()
     $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return sanitized data
     return $data;
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
     return in_array($field, $allowedFields);
}

?>
