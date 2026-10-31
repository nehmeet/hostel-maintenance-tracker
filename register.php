<?php
session_start();
require_once 'db.php';

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

$name = '';
$admission_no = '';
$room_raw = '';
$block_raw = '';
$room_number = '';
$block = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $admission_no = strtoupper(trim($_POST['admission_no'] ?? ''));
    $room_raw = trim($_POST['room_number'] ?? '');
    $block_raw = strtoupper(trim($_POST['block'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $room_number = ($room_raw !== '') ? (int)$room_raw : 101;
    $block = ($block_raw !== '') ? $block_raw : 'A';

    if (
        $name === '' ||
        $admission_no === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } else {
        // Check which optional columns exist
        $columns = [];
        $result = $conn->query("SHOW COLUMNS FROM students");

        if ($result) {
            while ($column = $result->fetch_assoc()) {
                $columns[$column['Field']] = true;
            }
        }

        $hasRoom = isset($columns['room_number']);
        $hasBlock = isset($columns['block']);

        // Check duplicate admission number
        $check = $conn->prepare(
            'SELECT id FROM students WHERE admission_no = ? LIMIT 1'
        );
        $check->bind_param('s', $admission_no);
        $check->execute();

        $existing = $check->get_result();

        if ($existing && $existing->num_rows > 0) {
            $error = 'Admission number is already registered.';
            $check->close();
        } else {
            $check->close();

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert according to the available schema columns
            if ($hasRoom && $hasBlock) {
                $stmt = $conn->prepare(
                    'INSERT INTO students
                    (name, admission_no, password, room_number, block)
                    VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->bind_param(
                    'sssis',
                    $name,
                    $admission_no,
                    $hashed_password,
                    $room_number,
                    $block
                );
            } elseif ($hasRoom) {
                $stmt = $conn->prepare(
                    'INSERT INTO students
                    (name, admission_no, password, room_number)
                    VALUES (?, ?, ?, ?)'
                );

                $stmt->bind_param(
                    'sssi',
                    $name,
                    $admission_no,
                    $hashed_password,
                    $room_number
                );
            } elseif ($hasBlock) {
                $stmt = $conn->prepare(
                    'INSERT INTO students
                    (name, admission_no, password, block)
                    VALUES (?, ?, ?, ?)'
                );

                $stmt->bind_param(
                    'ssss',
                    $name,
                    $admission_no,
                    $hashed_password,
                    $block
                );
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO students
                    (name, admission_no, password)
                    VALUES (?, ?, ?)'
                );

                $stmt->bind_param(
                    'sss',
                    $name,
                    $admission_no,
                    $hashed_password
                );
            }

            if ($stmt->execute()) {
                $student_id = $conn->insert_id;
                $stmt->close();

                // Establish student session
                session_regenerate_id(true);

                $_SESSION['student_id'] = $student_id;
                $_SESSION['student_name'] = $name;
                $_SESSION['admission_no'] = $admission_no;

                if ($hasRoom) {
                    $_SESSION['room_number'] = $room_number;
                }

                if ($hasBlock) {
                    $_SESSION['block'] = $block;
                }

                $conn->close();

                header('Location: student_dashboard.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration — Hostel Complaint Tracker</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="page-container">

    <header class="site-header">
        <h1>Hostel Maintenance &amp; Complaint Tracker</h1>
        <p class="subtitle">NCSM301 — Web Development Laboratory</p>
    </header>

    <div class="login-panel">
        <h2>Student Registration</h2>

        <?php if ($error !== ''): ?>
            <p class="msg-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="register.php" class="form-card">

            <div class="form-group">
                <label for="name">Full Name *</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    placeholder="e.g. Rahul Sharma"
                    value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="admission_no">Admission Number *</label>
                <input
                    type="text"
                    id="admission_no"
                    name="admission_no"
                    maxlength="20"
                    placeholder="e.g. 24JE0004"
                    value="<?= htmlspecialchars($admission_no, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="room_number">Room Number</label>
                    <input
                        type="number"
                        id="room_number"
                        name="room_number"
                        min="1"
                        placeholder="e.g. 101"
                        value="<?= htmlspecialchars($room_raw, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="form-group half">
                    <label for="block">Hostel Block</label>
                    <input
                        type="text"
                        id="block"
                        name="block"
                        maxlength="1"
                        placeholder="e.g. A"
                        value="<?= htmlspecialchars($block_raw, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    placeholder="Enter password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    placeholder="Confirm password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary">
                Register
            </button>
        </form>

        <p style="margin-top: 16px; text-align: center; font-size: 0.9em;">
            Already have an account?
            <a href="index.php">Login here</a>
        </p>
    </div>

</div>

</body>
</html>
