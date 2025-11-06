<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once 'db.php';
require_once 'resume_db.php';

// Create a new resume with a temporary title
$resume_id = create_resume($pdo, $_SESSION["id"], 'New Resume');

if($resume_id){
    // Redirect to edit page for the new resume
    header("location: edit_resume.php?id=" . $resume_id);
    exit;
} else {
    // If creation failed, redirect back to dashboard
    header("location: dashboard.php");
    exit;
}
?>
