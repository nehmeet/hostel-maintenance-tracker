<?php
// ============================================================
// index.php — Login Page (Student / Admin Toggle)
// Course: NCSM301 Web Development Laboratory
// ============================================================
session_start();

// Redirect if already logged in
if (isset($_SESSION['student_id'])) {
    header('Location: student_dashboard.php');
    exit;
}

if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
$tab = 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $loginType = trim($_POST['login_type'] ?? '');

    if ($loginType === 'student') {
        $tab = 'student';

        $admissionNo = strtoupper(trim($_POST['admission_no'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($admissionNo === '' || $password === '') {
            $error = 'Both fields are required.';
        } else {
            $stmt = $conn->prepare(
                'SELECT id, name, password, room_number, block
                 FROM students
                 WHERE admission_no = ?
                 LIMIT 1'
            );

            $stmt->bind_param('s', $admissionNo);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['student_id'] = $row['id'];
                    $_SESSION['student_name'] = $row['name'];
                    $_SESSION['admission_no'] = $row['admission_no'] ?? $admissionNo;
                    $_SESSION['room_number'] = $row['room_number'];
                    $_SESSION['block'] = $row['block'];

                    $stmt->close();
                    $conn->close();

                    header('Location: student_dashboard.php');
                    exit;
                }
            }

            $error = 'Invalid admission number or password.';
            $stmt->close();
        }

    } elseif ($loginType === 'admin') {
        $tab = 'admin';

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Both fields are required.';
        } else {
            $stmt = $conn->prepare(
                'SELECT id, username, password FROM admins WHERE username = ? LIMIT 1'
            );

            $stmt->bind_param('s', $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_user'] = $row['username'];

                    $stmt->close();
                    $conn->close();

                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }

            $stmt->close();
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Hostel Complaint Tracker</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="page-container">

    <header class="site-header">
        <h1>Hostel Maintenance &amp; Complaint Tracker</h1>
        <p class="subtitle">NCSM301 — Web Development Laboratory</p>
    </header>

    <div class="tab-bar">
        <button
            id="tab-student"
            class="tab-btn <?= $tab === 'student' ? 'tab-active' : '' ?>"
            type="button">
            Student Login
        </button>

        <button
            id="tab-admin"
            class="tab-btn <?= $tab === 'admin' ? 'tab-active' : '' ?>"
            type="button">
            Admin Login
        </button>
    </div>

    <?php if ($error !== ''): ?>
        <p id="login-error" class="msg-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <!-- Student Login -->
    <div
        id="form-student"
        class="login-panel <?= $tab === 'student' ? '' : 'hidden' ?>">

        <h2>Student Sign In</h2>

        <form method="POST" action="index.php"
              class="form-card" id="student-login-form">

            <input type="hidden" name="login_type" value="student">

            <div class="form-group">
                <label for="admission_no">Admission Number</label>
                <input
                    type="text"
                    id="admission_no"
                    name="admission_no"
                    placeholder="e.g. 24JE0001"
                    required>
            </div>

            <div class="form-group">
                <label for="stu-password">Password</label>
                <input
                    type="password"
                    id="stu-password"
                    name="password"
                    required>
            </div>

            <p id="login-form-message"></p>

            <button type="submit" class="btn btn-primary">
                Login as Student
            </button>
        </form>

        <p style="margin-top: 14px; text-align: center; font-size: 0.9em;">
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>
    </div>

    <!-- Admin Login -->
    <div
        id="form-admin"
        class="login-panel <?= $tab === 'admin' ? '' : 'hidden' ?>">

        <h2>Admin Sign In</h2>

        <form method="POST" action="index.php" class="form-card">

            <input type="hidden" name="login_type" value="admin">

            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="e.g. admin"
                    required>
            </div>

            <div class="form-group">
                <label for="adm-password">Password</label>
                <input
                    type="password"
                    id="adm-password"
                    name="password"
                    required>
            </div>

            <button type="submit" class="btn btn-primary">
                Login as Admin
            </button>
        </form>

        <p style="margin-top: 14px; text-align: center; font-size: 0.9em;">
            Need an admin account?
            <a href="admin_register.php">Register Admin</a>
        </p>
    </div>

    <div class="login-footer">
        <p>Demo Credentials</p>
        <ul>
            <li><strong>Student:</strong> 24JE0001 / student123</li>
            <li><strong>Admin:</strong> admin / admin123</li>
        </ul>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="script.js"></script>

</body>
</html>
