<?php
// ============================================================
// student_dashboard.php — Student Portal
// Course: NCSM301 Web Development Laboratory
// ============================================================
session_start();

if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

// Session values for auto-fill (escaped for safe HTML output)
$studentName  = htmlspecialchars($_SESSION['student_name']);
$admissionNo  = htmlspecialchars($_SESSION['admission_no']);
$roomNumber   = (int) $_SESSION['room_number'];
$block        = htmlspecialchars($_SESSION['block']);
$studentId    = (int) $_SESSION['student_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — Hostel Complaint Tracker</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="page-container">

    <!-- ===== Header ===== -->
    <header class="site-header">
        <h1>Hostel Maintenance &amp; Complaint Tracker</h1>
        <p class="subtitle">
            Welcome, <strong><?php echo $studentName; ?></strong>
            (<?php echo $admissionNo; ?>) &mdash;
            Room <?php echo $roomNumber; ?>, Block <?php echo $block; ?>
        </p>
        <nav class="top-nav">
            <a href="student_dashboard.php" class="nav-active">My Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <!-- ===== Section 1: Noticeboard ===== -->
    <div class="section" id="section-notices">
        <h2>&#128227; Noticeboard</h2>
        <div id="notices-list">
            <p class="msg-info">Loading notices...</p>
        </div>
    </div>

    <!-- ===== Section 2: Submit a Complaint ===== -->
    <div class="section" id="section-submit">
        <h2>Submit a New Complaint</h2>
        <p id="form-message"></p>

        <form id="complaint-form" class="form-card">
            <div class="form-row">
                <div class="form-group half">
                    <label for="admission_no">Admission Number</label>
                    <input type="text" id="admission_no" name="admission_no"
                           value="<?php echo $admissionNo; ?>" readonly>
                </div>
                <div class="form-group half">
                    <label for="room_number">Room Number</label>
                    <input type="text" id="room_number" name="room_number"
                           value="<?php echo $roomNumber; ?>" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="block">Hostel Block</label>
                    <input type="text" id="block" name="block"
                           value="<?php echo $block; ?>" readonly>
                </div>
                <div class="form-group half">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">-- Select Category --</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Network">Network</option>
                        <option value="Housekeeping">Housekeeping</option>
                        <option value="Mess & Hygiene">Mess &amp; Hygiene</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Describe the issue in detail..."></textarea>
            </div>

            <button type="submit" id="btn-submit-complaint" class="btn btn-primary">Submit Complaint</button>
        </form>
    </div>

    <!-- ===== Section 3: My Complaint History ===== -->
    <div class="section" id="section-history">
        <h2>My Complaint History</h2>

        <table id="student-complaints-table" class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- populated by script.js -->
            </tbody>
        </table>
        <p id="history-message" class="msg-info"></p>
    </div>

</div>

<!-- Hidden data for JS -->
<input type="hidden" id="session-student-id" value="<?php echo $studentId; ?>">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="script.js"></script>
</body>
</html>
