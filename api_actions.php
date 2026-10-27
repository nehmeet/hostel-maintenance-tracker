<?php
// ============================================================
// api_actions.php — POST Action Router
// Handles: submit_complaint, update_status (with staff assignment),
//          cancel_complaint, submit_rating, post_notice
// Course: NCSM301 Web Development Laboratory
// ============================================================
session_start();
header('Content-Type: application/json');
require 'db.php';

// Read JSON body
$input  = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? trim($input['action']) : '';

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
            echo json_encode(['success' => false, 'error' => 'Invalid category selected.']);
            break;
        }

        $stmt = $conn->prepare(
            'INSERT INTO complaints (student_id, admission_no, student_name, room_number, block, category, description)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ississs', $studentId, $admissionNo, $studentName, $roomNumber, $block, $category, $description);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'message' => 'Complaint #' . $stmt->insert_id . ' submitted successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error while saving complaint.']);
        }
        $stmt->close();
        break;

    // ============================================================
    // ACTION: Update complaint status & staff assignment (admin only)
    // Submitted -> In Progress (Staff required) -> Resolved
    // ============================================================
    case 'update_status':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorised access.']);
            break;
        }

        $id = isset($input['id']) ? (int) $input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid complaint ID.']);
            break;
        }

        $stmt = $conn->prepare('SELECT status, assigned_staff FROM complaints WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Complaint not found.']);
            $stmt->close();
            break;
        }

        $row     = $result->fetch_assoc();
        $current = $row['status'];
        $stmt->close();

        if ($current === 'Submitted') {
            // Moving to In Progress — MUST select a staff member
            $staff = isset($input['assigned_staff']) ? trim($input['assigned_staff']) : '';
            if ($staff === '') {
                echo json_encode(['success' => false, 'error' => 'Please select a staff member before setting status to In Progress.']);
                break;
            }

            $newStatus = 'In Progress';
            $upd = $conn->prepare('UPDATE complaints SET status = ?, assigned_staff = ? WHERE id = ?');
            $upd->bind_param('ssi', $newStatus, $staff, $id);
            $upd->execute();
            $upd->close();

            echo json_encode([
                'success'        => true,
                'status'         => $newStatus,
                'assigned_staff' => htmlspecialchars($staff),
                'message'        => 'Complaint #' . $id . ' assigned to ' . htmlspecialchars($staff) . ' and marked In Progress.'
            ]);
        } elseif ($current === 'In Progress') {
            // Moving to Resolved
            $newStatus = 'Resolved';
            $upd = $conn->prepare('UPDATE complaints SET status = ? WHERE id = ?');
            $upd->bind_param('si', $newStatus, $id);
            $upd->execute();
            $upd->close();

            echo json_encode([
                'success' => true,
                'status'  => $newStatus,
                'message' => 'Complaint #' . $id . ' marked as Resolved.'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No further status transitions available for status: ' . $current]);
        }
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

        // Verify ownership and status
        $stmt = $conn->prepare('SELECT status FROM complaints WHERE id = ? AND student_id = ?');
        $stmt->bind_param('ii', $id, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Complaint not found or not owned by you.']);
            $stmt->close();
            break;
        }

        $current = $result->fetch_assoc()['status'];
        $stmt->close();

        if ($current !== 'Submitted') {
            echo json_encode(['success' => false, 'error' => 'Only complaints with "Submitted" status can be cancelled.']);
            break;
        }

        $cancelled = 'Cancelled';
        $upd = $conn->prepare('UPDATE complaints SET status = ? WHERE id = ?');
        $upd->bind_param('si', $cancelled, $id);
        $upd->execute();
        $upd->close();

        echo json_encode([
            'success' => true,
            'status'  => 'Cancelled',
            'message' => 'Complaint #' . $id . ' has been successfully cancelled.'
        ]);
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
            echo json_encode(['success' => false, 'error' => 'Please select a valid rating between 1 and 5.']);
            break;
        }

        // Verify ownership and status
        $stmt = $conn->prepare('SELECT status, rating FROM complaints WHERE id = ? AND student_id = ?');
        $stmt->bind_param('ii', $id, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Complaint not found or not owned by you.']);
            $stmt->close();
            break;
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['status'] !== 'Resolved') {
            echo json_encode(['success' => false, 'error' => 'Only resolved complaints can be rated.']);
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

        echo json_encode([
            'success' => true,
            'rating'  => $rating,
            'message' => 'Thank you! Rating of ' . $rating . ' star(s) submitted for Complaint #' . $id . '.'
        ]);
        break;

    // ============================================================
    // ACTION: Post a notice (admin only)
    // ============================================================
    case 'post_notice':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorised access.']);
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
            echo json_encode([
                'success' => true,
                'id'      => $stmt->insert_id,
                'message' => 'Notice published successfully to the student noticeboard!'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error while posting notice.']);
        }
        $stmt->close();
        break;

    // ============================================================
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        break;
}

$conn->close();
