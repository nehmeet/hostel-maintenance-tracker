<?php
// ============================================================
// admin_dashboard.php — Admin / Staff Dashboard
// Course: NCSM301 Web Development Laboratory
// ============================================================
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$adminUser = htmlspecialchars($_SESSION['admin_user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Hostel Complaint Tracker</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="page-container">

    <!-- ===== Header ===== -->
    <header class="site-header">
        <h1>Hostel Maintenance &amp; Complaint Tracker</h1>
        <p class="subtitle">Admin Dashboard &mdash; Logged in as <strong><?php echo $adminUser; ?></strong></p>
        <nav class="top-nav">
            <a href="admin_dashboard.php" class="nav-active">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <!-- ===== Section 1: Statistics ===== -->
    <div class="section" id="section-stats">
        <h2>&#128202; Dashboard Statistics</h2>
        <div class="stats-row">
            <div class="stat-card stat-open">
                <p class="stat-number" id="stat-open">—</p>
                <p class="stat-label">Total Open</p>
            </div>
            <div class="stat-card stat-resolved">
                <p class="stat-number" id="stat-resolved-today">—</p>
                <p class="stat-label">Resolved Today</p>
            </div>
            <div class="stat-card stat-pending">
                <p class="stat-number" id="stat-pending">—</p>
                <p class="stat-label">Pending</p>
            </div>
        </div>
    </div>

    <!-- ===== Section 2: Post a Notice ===== -->
    <div class="section" id="section-notice-form">
        <h2>&#128227; Post a Notice</h2>
        <p id="notice-form-message"></p>

        <form id="notice-form" class="form-card">
            <div class="form-group">
                <label for="notice-title">Title</label>
                <input type="text" id="notice-title" name="title"
                       placeholder="e.g. Water supply interruption">
            </div>
            <div class="form-group">
                <label for="notice-message">Message</label>
                <textarea id="notice-message" name="message" rows="3"
                          placeholder="Write the notice content..."></textarea>
            </div>
            <button type="submit" id="btn-post-notice" class="btn btn-primary">Post Notice</button>
        </form>
    </div>

    <!-- ===== Section 3: Manage Complaints ===== -->
    <div class="section" id="section-admin-complaints">
        <h2>Manage Complaints</h2>

        <div class="filter-bar">
            <div class="filter-group">
                <label for="admin-filter-block">Block:</label>
                <select id="admin-filter-block">
                    <option value="All">All Blocks</option>
                    <option value="A">Block A (Jasper)</option>
                    <option value="B">Block B (Amber)</option>
                    <option value="C">Block C (Sapphire)</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="admin-filter-category">Category:</label>
                <select id="admin-filter-category">
                    <option value="All">All</option>
                    <option value="Electrical">Electrical</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Network">Network</option>
                    <option value="Housekeeping">Housekeeping</option>
                    <option value="Mess & Hygiene">Mess &amp; Hygiene</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="admin-filter-status">Status:</label>
                <select id="admin-filter-status">
                    <option value="All">All</option>
                    <option value="Submitted">Submitted</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <button id="admin-btn-refresh" class="btn btn-secondary">Refresh</button>
        </div>

        <table id="admin-complaints-table" class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Adm. No.</th>
                    <th>Room</th>
                    <th>Block</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Rating</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- populated by script.js -->
            </tbody>
        </table>
        <p id="admin-table-message" class="msg-info"></p>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="script.js"></script>
</body>
</html>
