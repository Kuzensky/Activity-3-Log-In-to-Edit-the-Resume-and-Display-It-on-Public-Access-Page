<?php
session_start();
require_once 'db.php';
require_once 'resume_db.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// All users edit the same default resume (user_id 1)
$user_id = 1;
$success_msg = "";
$error_msg = "";

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $resume_data = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'location' => trim($_POST['location']),
        'summary' => trim($_POST['summary']),
        'skills' => json_decode($_POST['skills_json'], true),
        'projects' => json_decode($_POST['projects_json'], true),
        'organizations' => json_decode($_POST['organizations_json'], true)
    ];

    // Save to database
    if(save_resume_data($pdo, $user_id, $resume_data)){
        // Redirect to homepage after successful save
        header("location: index.php");
        exit;
    } else {
        $error_msg = "Error saving resume data.";
    }
}

// Load resume data from database
$resume_data = load_resume_data($pdo, $user_id);

// Set defaults if empty
if(empty($resume_data['name'])){
    $resume_data['name'] = $_SESSION["email"];
}
if(empty($resume_data['email'])){
    $resume_data['email'] = $_SESSION["email"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resume</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-page {
            background: var(--background);
            min-height: 100vh;
            padding: 40px 24px;
        }

        .edit-container {
            max-width: 900px;
            margin: 24px auto;
            background: var(--surface);
            padding: 48px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border);
            position: relative;
            z-index: 2;
        }

        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .edit-header h2 {
            color: var(--text-primary);
            margin: 0;
        }

        .btn-back {
            padding: 10px 20px;
            background: var(--primary);
            color: var(--text-inverse);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .btn-back:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            background: var(--surface-elevated);
            color: var(--text-primary);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* Custom scrollbar styling */
        .form-group textarea::-webkit-scrollbar {
            width: 8px;
        }

        .form-group textarea::-webkit-scrollbar-track {
            background: var(--surface-elevated);
            border-radius: 4px;
        }

        .form-group textarea::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        .form-group textarea::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        /* For Firefox */
        .form-group textarea {
            scrollbar-width: thin;
            scrollbar-color: var(--border) var(--surface-elevated);
        }

        .form-group small {
            display: block;
            color: var(--text-secondary);
            margin-top: 4px;
            font-size: 12px;
        }

        .btn-save {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: var(--text-inverse);
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .btn-save:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Skills Editor Styles */
        .skills-editor {
            margin-bottom: 30px;
        }

        .skills-category-editor {
            margin-bottom: 25px;
            padding: 20px;
            background: var(--surface-elevated);
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
        }

        .category-header {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .skills-tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            min-height: 40px;
        }

        .skill-tag-edit {
            background: var(--surface);
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid var(--border);
            font-size: 14px;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .skill-tag-edit:hover {
            border-color: var(--primary);
        }

        .remove-skill-btn {
            background: var(--error);
            color: var(--text-inverse);
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            padding: 0;
            transition: var(--transition);
        }

        .remove-skill-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .add-skill-icon {
            background: var(--primary);
            color: var(--text-inverse);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px dashed var(--primary);
            background: transparent;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .add-skill-icon:hover {
            background: var(--primary);
            color: var(--text-inverse);
            border-style: solid;
            transform: scale(1.1);
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--surface);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            max-width: 400px;
            width: 90%;
            animation: scaleIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 20px;
        }

        .modal-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            font-size: 14px;
            background: var(--surface-elevated);
            color: var(--text-primary);
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
        }

        .modal-btn-primary {
            background: var(--primary);
            color: var(--text-inverse);
        }

        .modal-btn-primary:hover {
            background: var(--primary-dark);
        }

        .modal-btn-secondary {
            background: var(--surface-elevated);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .modal-btn-secondary:hover {
            background: var(--border);
        }

        /* Project Editor Styles */
        .project-container {
            background: var(--surface-elevated);
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .project-title-text {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .project-meta {
            font-size: 13px;
            color: var(--text-secondary);
            font-style: italic;
        }

        .project-details-list {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }

        .project-details-list li {
            color: var(--text-primary);
            padding: 6px 0 6px 16px;
            position: relative;
            font-size: 14px;
            line-height: 1.5;
        }

        .project-details-list li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }

        .remove-project-btn {
            background: var(--error);
            color: var(--text-inverse);
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            padding: 0;
            transition: var(--transition);
        }

        .remove-project-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .add-project-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: var(--text-inverse);
            border: 2px dashed var(--primary);
            background: transparent;
            color: var(--primary);
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }

        .add-project-btn:hover {
            background: var(--primary);
            color: var(--text-inverse);
            border-style: solid;
        }

        /* Organization Editor Styles */
        .org-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .org-tag {
            background: var(--surface-elevated);
            padding: 10px 16px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
            font-size: 14px;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .org-tag:hover {
            border-color: var(--primary);
        }

        .remove-org-btn {
            background: var(--error);
            color: var(--text-inverse);
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            padding: 0;
            transition: var(--transition);
        }

        .remove-org-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .add-org-icon {
            background: var(--primary);
            color: var(--text-inverse);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px dashed var(--primary);
            background: transparent;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .add-org-icon:hover {
            background: var(--primary);
            color: var(--text-inverse);
            border-style: solid;
            transform: scale(1.1);
        }

        /* Project Modal Styles */
        .modal-form-group {
            margin-bottom: 15px;
        }

        .modal-form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .modal-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--border-radius);
            font-size: 14px;
            background: var(--surface-elevated);
            color: var(--text-primary);
            font-family: 'Poppins', sans-serif;
            min-height: 100px;
            resize: vertical;
        }

        .modal-row {
            display: flex;
            gap: 10px;
        }

        .modal-row .modal-form-group {
            flex: 1;
        }
    </style>
</head>
<body class="edit-page">
    <div class="edit-container">
        <div class="edit-header">
            <h2>Edit Resume</h2>
            <a href="index.php" class="btn-back">Back to Resume</a>
        </div>

        <?php if(!empty($success_msg)): ?>
            <div class="success-message"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="error-message"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="resumeForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($resume_data['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($resume_data['email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($resume_data['phone']); ?>" required>
            </div>

            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($resume_data['location']); ?>" required>
            </div>

            <div class="form-group">
                <label>Summary</label>
                <textarea name="summary" required><?php echo htmlspecialchars($resume_data['summary']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Technical Skills</label>
                <div class="skills-editor">
                    <!-- Programming Languages -->
                    <div class="skills-category-editor">
                        <div class="category-header">Programming Languages</div>
                        <div class="skills-tags-container" id="programming-container">
                            <?php foreach($resume_data['skills']['programming'] as $skill): ?>
                                <div class="skill-tag-edit">
                                    <span><?php echo htmlspecialchars($skill); ?></span>
                                    <button type="button" class="remove-skill-btn" onclick="removeSkill(this)">×</button>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" class="add-skill-icon" onclick="openModal('programming', 'Programming Language')">+</button>
                        </div>
                    </div>

                    <!-- Database -->
                    <div class="skills-category-editor">
                        <div class="category-header">Database</div>
                        <div class="skills-tags-container" id="database-container">
                            <?php foreach($resume_data['skills']['database'] as $skill): ?>
                                <div class="skill-tag-edit">
                                    <span><?php echo htmlspecialchars($skill); ?></span>
                                    <button type="button" class="remove-skill-btn" onclick="removeSkill(this)">×</button>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" class="add-skill-icon" onclick="openModal('database', 'Database')">+</button>
                        </div>
                    </div>

                    <!-- Tools -->
                    <div class="skills-category-editor">
                        <div class="category-header">Tools</div>
                        <div class="skills-tags-container" id="tools-container">
                            <?php foreach($resume_data['skills']['tools'] as $skill): ?>
                                <div class="skill-tag-edit">
                                    <span><?php echo htmlspecialchars($skill); ?></span>
                                    <button type="button" class="remove-skill-btn" onclick="removeSkill(this)">×</button>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" class="add-skill-icon" onclick="openModal('tools', 'Tool')">+</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="skills_json" id="skills_json">
            </div>

            <div class="form-group">
                <label>Projects</label>
                <div id="projects-container">
                    <?php foreach($resume_data['projects'] as $index => $project): ?>
                        <div class="project-container" data-index="<?php echo $index; ?>">
                            <div class="project-header">
                                <div>
                                    <div class="project-title-text"><?php echo htmlspecialchars($project['title']); ?></div>
                                    <div class="project-meta"><?php echo htmlspecialchars($project['type'] . ' | ' . $project['year']); ?></div>
                                </div>
                                <button type="button" class="remove-project-btn" onclick="removeProject(this)">×</button>
                            </div>
                            <ul class="project-details-list">
                                <?php foreach($project['details'] as $detail): ?>
                                    <li><?php echo htmlspecialchars($detail); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-project-btn" onclick="openProjectModal()">+ Add Project</button>
                <input type="hidden" name="projects_json" id="projects_json">
            </div>

            <div class="form-group">
                <label>Organizations</label>
                <div class="org-container" id="org-container">
                    <?php foreach($resume_data['organizations'] as $org): ?>
                        <div class="org-tag">
                            <span><?php echo htmlspecialchars($org); ?></span>
                            <button type="button" class="remove-org-btn" onclick="removeOrg(this)">×</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="add-org-icon" onclick="openOrgModal()">+</button>
                </div>
                <input type="hidden" name="organizations_json" id="organizations_json">
            </div>

            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>

    <!-- Modal for adding skills -->
    <div class="modal-overlay" id="skillModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">Add Skill</div>
            <input type="text" class="modal-input" id="modalInput" placeholder="Enter skill name...">
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" onclick="confirmAddSkill()">Add</button>
            </div>
        </div>
    </div>

    <!-- Modal for adding projects -->
    <div class="modal-overlay" id="projectModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">Add Project</div>
            <div class="modal-form-group">
                <label>Project Title</label>
                <input type="text" class="modal-input" id="projectTitle" placeholder="e.g., FarmEase – E-Commerce Website">
            </div>
            <div class="modal-row">
                <div class="modal-form-group">
                    <label>Type</label>
                    <input type="text" class="modal-input" id="projectType" placeholder="e.g., School Project">
                </div>
                <div class="modal-form-group">
                    <label>Year</label>
                    <input type="text" class="modal-input" id="projectYear" placeholder="e.g., 2025">
                </div>
            </div>
            <div class="modal-form-group">
                <label>Description (one point per line)</label>
                <textarea class="modal-textarea" id="projectDetails" placeholder="Enter each accomplishment on a new line..."></textarea>
            </div>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeProjectModal()">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" onclick="confirmAddProject()">Add Project</button>
            </div>
        </div>
    </div>

    <!-- Modal for adding organizations -->
    <div class="modal-overlay" id="orgModal">
        <div class="modal-content">
            <div class="modal-header">Add Organization</div>
            <input type="text" class="modal-input" id="orgInput" placeholder="Enter organization name...">
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeOrgModal()">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" onclick="confirmAddOrg()">Add</button>
            </div>
        </div>
    </div>

    <script>
        let currentCategory = '';

        function openModal(category, categoryName) {
            currentCategory = category;
            document.getElementById('modalTitle').textContent = `Add ${categoryName}`;
            document.getElementById('modalInput').value = '';
            document.getElementById('skillModal').classList.add('active');
            document.getElementById('modalInput').focus();
        }

        function closeModal() {
            document.getElementById('skillModal').classList.remove('active');
            currentCategory = '';
        }

        function confirmAddSkill() {
            const skillName = document.getElementById('modalInput').value.trim();

            if (skillName === '') {
                alert('Please enter a skill name');
                return;
            }

            const container = document.getElementById(currentCategory + '-container');
            const addButton = container.querySelector('.add-skill-icon');

            const skillTag = document.createElement('div');
            skillTag.className = 'skill-tag-edit';
            skillTag.innerHTML = `
                <span>${skillName}</span>
                <button type="button" class="remove-skill-btn" onclick="removeSkill(this)">×</button>
            `;

            // Insert before the add button
            container.insertBefore(skillTag, addButton);
            closeModal();
        }

        function removeSkill(button) {
            button.parentElement.remove();
        }

        // Allow Enter key in modal
        document.getElementById('modalInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmAddSkill();
            }
        });

        // Close modal when clicking outside
        document.getElementById('skillModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Projects Functions
        function openProjectModal() {
            document.getElementById('projectTitle').value = '';
            document.getElementById('projectType').value = '';
            document.getElementById('projectYear').value = '';
            document.getElementById('projectDetails').value = '';
            document.getElementById('projectModal').classList.add('active');
            document.getElementById('projectTitle').focus();
        }

        function closeProjectModal() {
            document.getElementById('projectModal').classList.remove('active');
        }

        function confirmAddProject() {
            const title = document.getElementById('projectTitle').value.trim();
            const type = document.getElementById('projectType').value.trim();
            const year = document.getElementById('projectYear').value.trim();
            const details = document.getElementById('projectDetails').value.trim();

            if (!title || !type || !year || !details) {
                alert('Please fill in all fields');
                return;
            }

            const detailsArray = details.split('\n').filter(line => line.trim());

            const projectContainer = document.createElement('div');
            projectContainer.className = 'project-container';
            projectContainer.innerHTML = `
                <div class="project-header">
                    <div>
                        <div class="project-title-text">${title}</div>
                        <div class="project-meta">${type} | ${year}</div>
                    </div>
                    <button type="button" class="remove-project-btn" onclick="removeProject(this)">×</button>
                </div>
                <ul class="project-details-list">
                    ${detailsArray.map(detail => `<li>${detail}</li>`).join('')}
                </ul>
            `;

            document.getElementById('projects-container').appendChild(projectContainer);
            closeProjectModal();
        }

        function removeProject(button) {
            button.closest('.project-container').remove();
        }

        // Organization Functions
        function openOrgModal() {
            document.getElementById('orgInput').value = '';
            document.getElementById('orgModal').classList.add('active');
            document.getElementById('orgInput').focus();
        }

        function closeOrgModal() {
            document.getElementById('orgModal').classList.remove('active');
        }

        function confirmAddOrg() {
            const orgName = document.getElementById('orgInput').value.trim();

            if (!orgName) {
                alert('Please enter an organization name');
                return;
            }

            const orgTag = document.createElement('div');
            orgTag.className = 'org-tag';
            orgTag.innerHTML = `
                <span>${orgName}</span>
                <button type="button" class="remove-org-btn" onclick="removeOrg(this)">×</button>
            `;

            const addButton = document.querySelector('#org-container .add-org-icon');
            document.getElementById('org-container').insertBefore(orgTag, addButton);
            closeOrgModal();
        }

        function removeOrg(button) {
            button.parentElement.remove();
        }

        // Close modals when clicking outside
        document.getElementById('projectModal').addEventListener('click', function(e) {
            if (e.target === this) closeProjectModal();
        });

        document.getElementById('orgModal').addEventListener('click', function(e) {
            if (e.target === this) closeOrgModal();
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeProjectModal();
                closeOrgModal();
            }
        });

        // Before form submission, collect all data into JSON
        document.getElementById('resumeForm').addEventListener('submit', function(e) {
            // Collect skills
            const skills = {
                programming: [],
                database: [],
                tools: []
            };

            document.querySelectorAll('#programming-container .skill-tag-edit span').forEach(span => {
                skills.programming.push(span.textContent);
            });

            document.querySelectorAll('#database-container .skill-tag-edit span').forEach(span => {
                skills.database.push(span.textContent);
            });

            document.querySelectorAll('#tools-container .skill-tag-edit span').forEach(span => {
                skills.tools.push(span.textContent);
            });

            document.getElementById('skills_json').value = JSON.stringify(skills);

            // Collect projects
            const projects = [];
            document.querySelectorAll('#projects-container .project-container').forEach(container => {
                const title = container.querySelector('.project-title-text').textContent;
                const meta = container.querySelector('.project-meta').textContent;
                const [type, year] = meta.split(' | ');
                const details = [];
                container.querySelectorAll('.project-details-list li').forEach(li => {
                    details.push(li.textContent);
                });

                projects.push({ title, type, year, details });
            });

            document.getElementById('projects_json').value = JSON.stringify(projects);

            // Collect organizations
            const organizations = [];
            document.querySelectorAll('#org-container .org-tag span').forEach(span => {
                organizations.push(span.textContent);
            });

            document.getElementById('organizations_json').value = JSON.stringify(organizations);
        });
    </script>
</body>
</html>
