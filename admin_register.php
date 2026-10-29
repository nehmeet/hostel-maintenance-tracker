<?php
session_start();
require_once 'db.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

if (isset($_SESSION['student_id'])) {
    header('Location: student_dashboard.php');
    exit;
}

/**
 * Resolve ADMIN_REGISTRATION_KEY from the environment or a local .env file.
 */
function getAdminRegistrationKey(): string {
    $key = getenv('ADMIN_REGISTRATION_KEY');
    if ($key !== false && $key !== '') {
        return (string)$key;
    }
    if (!empty($_ENV['ADMIN_REGISTRATION_KEY'])) {
        return (string)$_ENV['ADMIN_REGISTRATION_KEY'];
    }
    if (!empty($_SERVER['ADMIN_REGISTRATION_KEY'])) {
        return (string)$_SERVER['ADMIN_REGISTRATION_KEY'];
    }

    $envPath = __DIR__ . '/.env';
    if (file_exists($envPath) && is_readable($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    list($k, $v) = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v, " \t\n\r\0\x0B\"'");
                    if ($k === 'ADMIN_REGISTRATION_KEY') {
                        return $v;
                    }
                }
            }
        }
    }

    return '';
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_key = (string)($_POST['admin_key'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $expectedKey = getAdminRegistrationKey();

    if (
        $admin_key === '' ||
        $username === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $error = 'Please fill in all required fields.';
    } elseif ($expectedKey === '') {
        $error = 'Admin registration is disabled by system configuration.';
    } elseif (!hash_equals($expectedKey, $admin_key)) {
        $error = 'Invalid admin registration key.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } else {
        // Check for an existing username
        $check = $conn->prepare(
            'SELECT id FROM admins WHERE username = ? LIMIT 1'
        );
        $check->bind_param('s', $username);
        $check->execute();

        $existing = $check->get_result();

        if ($existing && $existing->num_rows > 0) {
            $error = 'This username is already registered.';
            $check->close();
        } else {
            $check->close();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                'INSERT INTO admins (username, password) VALUES (?, ?)'
            );

            $stmt->bind_param('ss', $username, $hashed_password);

            if ($stmt->execute()) {
                $new_admin_id = $conn->insert_id;

                session_regenerate_id(true);
                $_SESSION['admin_id'] = $new_admin_id;
                $_SESSION['admin_user'] = $username;

                $stmt->close();
                $conn->close();

                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = 'Registration failed. Username may already exist.';
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration — Hostel Complaint Tracker</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="page-container">

    <header class="site-header">
        <h1>Hostel Maintenance &amp; Complaint Tracker</h1>
        <p class="subtitle">NCSM301 — Web Development Laboratory</p>
    </header>

    <div class="login-panel">
        <h2>Admin Registration</h2>

        <?php if ($error !== ''): ?>
            <p class="msg-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="admin_register.php"
              class="form-card">

            <div class="form-group">
                <label for="admin_key">Admin Registration Key *</label>
                <input
                    type="password"
                    id="admin_key"
                    name="admin_key"
                    placeholder="Enter secret admin key"
                    required
                >
            </div>

            <div class="form-group">
                <label for="username">Username *</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    maxlength="50"
                    placeholder="Choose a username"
                    value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
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
                Register Admin
            </button>
        </form>

        <p style="margin-top: 16px; text-align: center;">
            Already registered?
            <a href="index.php">Admin Login</a>
        </p>
    </div>

</div>

</body>
</html>
