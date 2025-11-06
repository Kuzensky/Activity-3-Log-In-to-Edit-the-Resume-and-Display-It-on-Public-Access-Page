<?php
require_once __DIR__ . '/controllers/ResumeController.php';
require_once __DIR__ . '/db.php';

// Initialize global controller instance
$resumeController = new ResumeController($pdo);

// Get all resumes from all users (for dashboard)
function get_all_resumes($pdo) {
    global $resumeController;
    return $resumeController->getAllResumes();
}

// Get resumes for a specific user
function get_user_resumes($pdo, $user_id) {
    global $resumeController;
    return $resumeController->getUserResumes($user_id);
}

// Get basic info about a specific resume
function get_resume_info($pdo, $resume_id) {
    global $resumeController;
    return $resumeController->getResumeInfo($resume_id);
}

// Create a new resume for a user
function create_resume($pdo, $user_id, $title = 'My Resume') {
    global $resumeController;
    return $resumeController->createResume($user_id, $title);
}

// Delete a resume and all its data (CASCADE)
function delete_resume($pdo, $resume_id) {
    global $resumeController;
    return $resumeController->deleteResume($resume_id);
}

// Update the title of a resume
function update_resume_title($pdo, $resume_id, $title) {
    global $resumeController;
    return $resumeController->updateResumeTitle($resume_id, $title);
}

// Load complete resume data for editing/viewing
function load_resume_data($pdo, $resume_id) {
    global $resumeController;
    return $resumeController->loadResumeData($resume_id);
}

// Save complete resume data
function save_resume_data($pdo, $resume_id, $data) {
    global $resumeController;
    return $resumeController->saveResumeData($resume_id, $data);
}
?>
