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
    // FETCH: Student's own complaints (Paginated, 10 per page)
    // ============================================================
    case 'student_complaints':
        if (!isset($_SESSION['student_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in.']);
            break;
        }

        $studentId = (int) $_SESSION['student_id'];
        $page      = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit     = 10;
        $offset    = ($page - 1) * $limit;

        // Total count
        $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM complaints WHERE student_id = ?');
        $countStmt->bind_param('i', $studentId);
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $totalRows   = (int) $totalResult->fetch_assoc()['total'];
        $countStmt->close();

        $totalPages = max(1, (int) ceil($totalRows / $limit));
        if ($page > $totalPages) {
            $page   = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        // Paginated rows
        $stmt = $conn->prepare(
            'SELECT id, category, description, status, assigned_staff, rating, created_at
             FROM complaints
             WHERE student_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('iii', $studentId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['description']    = htmlspecialchars($row['description']);
            $row['assigned_staff'] = $row['assigned_staff'] ? htmlspecialchars($row['assigned_staff']) : null;
            $rows[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success'       => true,
            'complaints'    => $rows,
            'page'          => $page,
            'total_pages'   => $totalPages,
            'total_records' => $totalRows
        ]);
        break;

    // ============================================================
    // FETCH: All complaints (admin) with filters & pagination
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
        $page     = isset($_GET['page'])     ? max(1, (int) $_GET['page']) : 1;
        $limit    = 10;
        $offset   = ($page - 1) * $limit;

        $whereSql = ' WHERE 1=1';
        $types    = '';
        $params   = [];

        if ($block !== 'All') {
            $whereSql .= ' AND block = ?';
            $types    .= 's';
            $params[] = $block;
        }
        if ($category !== 'All') {
            $whereSql .= ' AND category = ?';
            $types    .= 's';
            $params[] = $category;
        }
        if ($status !== 'All') {
            $whereSql .= ' AND status = ?';
            $types    .= 's';
            $params[] = $status;
        }

        // Count total matching records
        $countSql  = 'SELECT COUNT(*) AS total FROM complaints' . $whereSql;
        $countStmt = $conn->prepare($countSql);
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $totalRows   = (int) $totalResult->fetch_assoc()['total'];
        $countStmt->close();

        $totalPages = max(1, (int) ceil($totalRows / $limit));
        if ($page > $totalPages) {
            $page   = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        // Fetch paginated page
        $dataSql = 'SELECT id, student_name, admission_no, room_number, block, category, description, status, assigned_staff, rating, created_at FROM complaints' . $whereSql . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $stmt    = $conn->prepare($dataSql);

        $fetchTypes  = $types . 'ii';
        $fetchParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($fetchTypes, ...$fetchParams);

        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['student_name']   = htmlspecialchars($row['student_name']);
            $row['admission_no']   = htmlspecialchars($row['admission_no']);
            $row['description']    = htmlspecialchars($row['description']);
            $row['assigned_staff'] = $row['assigned_staff'] ? htmlspecialchars($row['assigned_staff']) : null;
            $rows[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success'       => true,
            'complaints'    => $rows,
            'page'          => $page,
            'total_pages'   => $totalPages,
            'total_records' => $totalRows
        ]);
        break;

    // ============================================================
    // FETCH: Dashboard statistics (admin)
    // - Total Open
    // - Resolved Today
    // - Pending (> 48 hours)
    // ============================================================
    case 'stats':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            break;
        }

        // 1. Total Open = Submitted + In Progress
        $r1 = $conn->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status IN ('Submitted','In Progress')");
        $totalOpen = (int) $r1->fetch_assoc()['cnt'];

        // 2. Resolved Today = Resolved status & updated today
        $r2 = $conn->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status = 'Resolved' AND DATE(updated_at) = CURDATE()");
        $resolvedToday = (int) $r2->fetch_assoc()['cnt'];

        // 3. Pending (> 48 hours) = Not yet resolved/cancelled, submitted > 48 hours ago
        $r3 = $conn->query("SELECT COUNT(*) AS cnt FROM complaints WHERE status IN ('Submitted','In Progress') AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)");
        $pending48h = (int) $r3->fetch_assoc()['cnt'];

        echo json_encode([
            'total_open'     => $totalOpen,
            'resolved_today' => $resolvedToday,
            'pending_48h'    => $pending48h
        ]);
        break;

    // ============================================================
    // FETCH: Notices (public/student)
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
            $row['title']     = htmlspecialchars($row['title']);
            $row['message']   = htmlspecialchars($row['message']);
            $row['posted_by'] = htmlspecialchars($row['posted_by']);
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
