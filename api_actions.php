<?php
// ============================================================
// api_actions.php — POST Action Router
// Handles: submit_complaint, update_status, cancel_complaint,
//          submit_rating, post_notice
// Course: NCSM301 Web Development Laboratory
// ============================================================
session_start();
header('Content-Type: application/json');
require 'db.php';

// Read JSON body
$input  = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? trim($input['action']) : '';

// ---- Route by action ----
switch ($action) {

    // ============================================================
    // ACTION: Submit a new complaint (student only)
    // ============================================================
    case 'submit_complaint':
        if (!isset($_SESSION['student_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in.']);
            break;
        }

        $studentId   = (int) $_SESSION['student_id'];
        $admissionNo = $_SESSION['admission_no'];
        $studentName = $_SESSION['student_name'];
        $roomNumber  = (int) $_SESSION['room_number'];
        $block       = $_SESSION['block'];

        $category    = isset($input['category'])    ? trim($input['category'])    : '';
        $description = isset($input['description']) ? trim($input['description']) : '';

        $validCats = ['Electrical', 'Plumbing', 'Network', 'Housekeeping', 'Mess & Hygiene'];

        if ($category === '' || $description === '') {
            echo json_encode(['success' => false, 'error' => 'Category and description are required.']);
            break;
        }
        if (!in_array($category, $validCats, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid category.']);
            break;
        }

        $stmt = $conn->prepare(
            'INSERT INTO complaints (student_id, admission_no, student_name, room_number, block, category, description)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ississs', $studentId, $admissionNo, $studentName, $roomNumber, $block, $category, $description);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error.']);
        }
        $stmt->close();
        break;

    // ============================================================
    // ACTION: Update complaint status (admin only)
    // Submitted -> In Progress -> Resolved
    // ============================================================
    case 'update_status':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            break;
        }

        $id = isset($input['id']) ? (int) $input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid complaint ID.']);
            break;
        }

        $transitions = [
            'Submitted'   => 'In Progress',
            'In Progress' => 'Resolved'
        ];

        $stmt = $conn->prepare('SELECT status FROM complaints WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Complaint not found.']);
            $stmt->close();
            break;
        }

        $current = $result->fetch_assoc()['status'];
        $stmt->close();

        if (!isset($transitions[$current])) {
            echo json_encode(['success' => false, 'error' => 'No further status transition available.']);
            break;
        }

        $newStatus = $transitions[$current];
        $upd = $conn->prepare('UPDATE complaints SET status = ? WHERE id = ?');
        $upd->bind_param('si', $newStatus, $id);
        $upd->execute();
        $upd->close();

        echo json_encode(['success' => true, 'status' => $newStatus]);
        break;

    // ============================================================
    // ACTION: Cancel a complaint (student only, status must be Submitted)
    // ============================================================
    case 'cancel_complaint':
        if (!isset($_SESSION['student_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in.']);
            break;
        }

        $id        = isset($input['id']) ? (int) $input['id'] : 0;
        $studentId = (int) $_SESSION['student_id'];

        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid complaint ID.']);
            break;
        }

        // Verify ownership and current status
        $stmt = $conn->prepare('SELECT status FROM complaints WHERE id = ? AND student_id = ?');
        $stmt->bind_param('ii', $id, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Complaint not found or not yours.']);
            $stmt->close();
            break;
        }

        $current = $result->fetch_assoc()['status'];
        $stmt->close();

        if ($current !== 'Submitted') {
            echo json_encode(['success' => false, 'error' => 'Only complaints with status "Submitted" can be cancelled.']);
            break;
        }

        $cancelled = 'Cancelled';
        $upd = $conn->prepare('UPDATE complaints SET status = ? WHERE id = ?');
        $upd->bind_param('si', $cancelled, $id);
        $upd->execute();
        $upd->close();

        echo json_encode(['success' => true, 'status' => 'Cancelled']);
        break;

    // ============================================================
    // ACTION: Submit a rating (student only, status must be Resolved)
    // ============================================================
    case 'submit_rating':
        if (!isset($_SESSION['student_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in.']);
            break;
        }

        $id        = isset($input['id'])     ? (int) $input['id']     : 0;
        $rating    = isset($input['rating']) ? (int) $input['rating'] : 0;
        $studentId = (int) $_SESSION['student_id'];

        if ($id <= 0 || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'error' => 'Invalid complaint ID or rating (1-5).']);
            break;
        }

        // Verify ownership, status = Resolved, and not already rated
        $stmt = $conn->prepare('SELECT status, rating FROM complaints WHERE id = ? AND student_id = ?');
        $stmt->bind_param('ii', $id, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Complaint not found or not yours.']);
            $stmt->close();
            break;
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['status'] !== 'Resolved') {
            echo json_encode(['success' => false, 'error' => 'Can only rate resolved complaints.']);
            break;
        }
        if ($row['rating'] !== null) {
            echo json_encode(['success' => false, 'error' => 'You have already rated this complaint.']);
            break;
        }

        $upd = $conn->prepare('UPDATE complaints SET rating = ? WHERE id = ?');
        $upd->bind_param('ii', $rating, $id);
        $upd->execute();
        $upd->close();

        echo json_encode(['success' => true, 'rating' => $rating]);
        break;

    // ============================================================
    // ACTION: Post a notice (admin only)
    // ============================================================
    case 'post_notice':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            break;
        }

        $title   = isset($input['title'])   ? trim($input['title'])   : '';
        $message = isset($input['message']) ? trim($input['message']) : '';
        $adminId = (int) $_SESSION['admin_id'];

        if ($title === '' || $message === '') {
            echo json_encode(['success' => false, 'error' => 'Title and message are required.']);
            break;
        }

        $stmt = $conn->prepare('INSERT INTO notices (title, message, posted_by) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $title, $message, $adminId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error.']);
        }
        $stmt->close();
        break;

    // ============================================================
    // DEFAULT: Unknown action
    // ============================================================
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        break;
}

$conn->close();
