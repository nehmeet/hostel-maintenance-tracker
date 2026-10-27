<?php
// ============================================================
// api_fetch.php — GET Data Endpoint
// Handles: student_complaints, admin_complaints, stats, notices
// Course: NCSM301 Web Development Laboratory
// ============================================================
session_start();
header('Content-Type: application/json');
require 'db.php';

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch ($action) {

    // ============================================================
    // FETCH: Student's own complaints
    // ============================================================
    case 'student_complaints':
        if (!isset($_SESSION['student_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in.']);
            break;
        }

        $studentId = (int) $_SESSION['student_id'];
        $stmt = $conn->prepare(
            'SELECT id, category, description, status, rating, created_at
             FROM complaints WHERE student_id = ? ORDER BY created_at DESC'
        );
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['description'] = htmlspecialchars($row['description']);
            $rows[] = $row;
        }
        $stmt->close();

        echo json_encode($rows);
        break;

    // ============================================================
    // FETCH: All complaints (admin) with optional filters
    // ============================================================
    case 'admin_complaints':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            break;
        }

        $block    = isset($_GET['block'])    ? trim($_GET['block'])    : 'All';
        $category = isset($_GET['category']) ? trim($_GET['category']) : 'All';
        $status   = isset($_GET['status'])   ? trim($_GET['status'])   : 'All';

        $sql    = 'SELECT id, student_name, admission_no, room_number, block, category, description, status, rating, created_at FROM complaints WHERE 1=1';
        $types  = '';
        $params = [];

        if ($block !== 'All') {
            $sql    .= ' AND block = ?';
            $types  .= 's';
            $params[] = $block;
        }
        if ($category !== 'All') {
            $sql    .= ' AND category = ?';
            $types  .= 's';
            $params[] = $category;
        }
        if ($status !== 'All') {
            $sql    .= ' AND status = ?';
            $types  .= 's';
            $params[] = $status;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['student_name'] = htmlspecialchars($row['student_name']);
            $row['description']  = htmlspecialchars($row['description']);
            $rows[] = $row;
        }
        $stmt->close();

        echo json_encode($rows);
        break;

    // ============================================================
    // FETCH: Dashboard statistics (admin)
    // ============================================================
    case 'stats':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            break;
        }

        // Total Open = Submitted + In Progress
        $r1 = $conn->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status IN ('Submitted','In Progress')");
        $totalOpen = $r1->fetch_assoc()['cnt'];

        // Resolved Today
        $r2 = $conn->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 'Resolved' AND DATE(updated_at) = CURDATE()");
        $resolvedToday = $r2->fetch_assoc()['cnt'];

        // Pending = Submitted only
        $r3 = $conn->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 'Submitted'");
        $pending = $r3->fetch_assoc()['cnt'];

        echo json_encode([
            'total_open'     => (int) $totalOpen,
            'resolved_today' => (int) $resolvedToday,
            'pending'        => (int) $pending
        ]);
        break;

    // ============================================================
    // FETCH: Notices (public)
    // ============================================================
    case 'notices':
        $stmt = $conn->prepare(
            'SELECT n.id, n.title, n.message, a.username AS posted_by, n.created_at
             FROM notices n
             JOIN admins a ON a.id = n.posted_by
             ORDER BY n.created_at DESC
             LIMIT 10'
        );
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['title']   = htmlspecialchars($row['title']);
            $row['message'] = htmlspecialchars($row['message']);
            $rows[] = $row;
        }
        $stmt->close();

        echo json_encode($rows);
        break;

    // ============================================================
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        break;
}

$conn->close();
