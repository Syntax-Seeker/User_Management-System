<?php
// =============================================
// logout.php - Session Destroy & Redirect
// =============================================

session_start();
session_unset();      // Remove all session variables
session_destroy();    // Destroy the session

header("Location: login.php");
exit();
?>
