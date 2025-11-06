<?php

session_start();

// LOAD DEPENDENCIES
require_once 'db.php';          // Database connection ($pdo object)
require_once 'resume_db.php';   // Resume database functions (wrapper for ResumeController)

// CHECK AUTHENTICATION STATUS

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// FETCH ALL RESUMES
$resumes = get_all_resumes($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-page">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Resume Dashboard</h1>
                <p class="dashboard-subtitle">Browse and manage professional resumes</p>
            </div>
            <div class="header-actions">
                <?php if ($is_logged_in): ?>
                    <a href="quick_create.php" class="btn-add-resume">
                        <span>+</span> Add Resume
                    </a>
                    <a href="logout.php" class="btn-logout">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($resumes)): ?>
            <div class="empty-state">
                <h2 class="empty-state-title">No Resumes Yet</h2>
                <p class="empty-state-text">
                    <?php if ($is_logged_in): ?>
                        Get started by creating your first resume!
                    <?php else: ?>
                        Login to create and manage your resumes.
                    <?php endif; ?>
                </p>
                <?php if ($is_logged_in): ?>
                    <a href="quick_create.php" class="btn-add-resume">
                        <span>+</span> Create Your First Resume
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="resume-grid">
                <?php foreach ($resumes as $resume): ?>
                    <div class="resume-card">
                        <div class="resume-card-header">
                            <div>
                                <h2 class="resume-title"><?php echo htmlspecialchars($resume['owner_name'] ?: $resume['owner_email'] ?: 'Unknown'); ?></h2>
                            </div>
                            <?php if ($is_logged_in): ?>
                                <button class="btn-delete-resume" onclick="deleteResume(<?php echo $resume['id']; ?>, '<?php echo addslashes($resume['owner_name'] ?: $resume['owner_email'] ?: 'Unknown'); ?>')" title="Delete Resume">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2 4h12M5.333 4V2.667a1.333 1.333 0 011.334-1.334h2.666a1.333 1.333 0 011.334 1.334V4m2 0v9.333a1.333 1.333 0 01-1.334 1.334H4.667a1.333 1.333 0 01-1.334-1.334V4h9.334z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="resume-card-body" onclick="window.location.href='view_resume.php?id=<?php echo $resume['id']; ?>'">
                            <?php if (!empty($resume['summary'])): ?>
                                <div class="resume-preview">
                                    <?php echo htmlspecialchars($resume['summary']); ?>
                                </div>
                            <?php else: ?>
                                <div class="resume-preview" style="font-style: italic; opacity: 0.5;">
                                    No summary available
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="resume-meta">
                            <span class="resume-date">
                                Updated <?php echo date('M d, Y', strtotime($resume['updated_at'])); ?>
                            </span>
                            <div class="resume-actions">
                                <button class="btn-view-resume" onclick="window.location.href='view_resume.php?id=<?php echo $resume['id']; ?>'">
                                    View
                                </button>
                                <?php if ($is_logged_in): ?>
                                    <button class="btn-edit-resume" onclick="window.location.href='edit_resume.php?id=<?php echo $resume['id']; ?>'">
                                        Edit
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-modal-overlay" id="deleteModal">
        <div class="delete-modal">
            <div class="delete-modal-header">
                <h3 class="delete-modal-title">Delete Resume</h3>
            </div>
            <div class="delete-modal-body">
                Are you sure you want to delete <span class="delete-modal-resume-name" id="deleteResumeName"></span>?
                <br><br>
                This action cannot be undone.
            </div>
            <div class="delete-modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="modal-btn modal-btn-delete" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let deleteResumeIdToDelete = null;

        function deleteResume(resumeId, resumeTitle) {
            deleteResumeIdToDelete = resumeId;
            document.getElementById('deleteResumeName').textContent = resumeTitle;
            document.getElementById('deleteModal').classList.add('active');

            // Set up confirm button
            document.getElementById('confirmDeleteBtn').onclick = function() {
                window.location.href = 'delete_resume.php?id=' + deleteResumeIdToDelete;
            };
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteResumeIdToDelete = null;
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });

        // Show success/error messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('deleted') === '1') {
            alert('Resume deleted successfully!');
            // Clean URL
            window.history.replaceState({}, document.title, 'dashboard.php');
        } else if (urlParams.get('error') === '1') {
            alert('Error deleting resume. Please try again.');
            window.history.replaceState({}, document.title, 'dashboard.php');
        }
    </script>
</body>
</html>
