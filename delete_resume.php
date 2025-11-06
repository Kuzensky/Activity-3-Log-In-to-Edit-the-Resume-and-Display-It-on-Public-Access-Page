<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once 'db.php';
require_once 'resume_db.php';

// Get resume ID from URL
$resume_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($resume_id > 0) {
    // Delete the resume
    if(delete_resume($pdo, $resume_id)) {
        // Redirect to dashboard with success message
        header("location: dashboard.php?deleted=1");
        exit;
    } else {
        // Redirect to dashboard with error message
        header("location: dashboard.php?error=1");
        exit;
    }
} else {
    // Invalid ID, redirect to dashboard
    header("location: dashboard.php");
    exit;
}
?>
