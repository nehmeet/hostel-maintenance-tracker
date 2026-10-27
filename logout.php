<?php
// ============================================================
// logout.php — Destroy Session & Redirect
// Course: NCSM301 Web Development Laboratory
// ============================================================
session_start();
session_unset();
session_destroy();
header('Location: index.php');
exit;
