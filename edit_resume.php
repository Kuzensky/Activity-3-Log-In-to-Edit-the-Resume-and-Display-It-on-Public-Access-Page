<?php
session_start();
require_once 'db.php';
require_once 'resume_db.php';


// AUTHENTICATION & VALIDATION
// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Get resume ID from URL parameter
$resume_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate resume ID
if ($resume_id <= 0) {
    header("location: dashboard.php");
    exit;
}

// Check if resume exists in database
$check_stmt = $pdo->prepare("SELECT id FROM resumes WHERE id = ?");
$check_stmt->execute([$resume_id]);
if (!$check_stmt->fetch()) {
    // Resume doesn't exist, redirect to dashboard
    header("location: dashboard.php");
    exit;
}


// FORM SUBMISSION HANDLER
$success_msg = "";
$error_msg = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Collect all form data
    $resume_data = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'location' => trim($_POST['location']),
        'summary' => trim($_POST['summary']),
        'skills' => json_decode($_POST['skills_json'], true),
        'projects' => json_decode($_POST['projects_json'], true),
        'organizations' => json_decode($_POST['organizations_json'], true),
        'education' => json_decode($_POST['education_json'], true),
        'work_experience' => json_decode($_POST['work_experience_json'], true),
        'certifications' => json_decode($_POST['certifications_json'], true),
        'awards' => json_decode($_POST['awards_json'], true),
        'languages' => json_decode($_POST['languages_json'], true),
        'interests' => json_decode($_POST['interests_json'], true),
        'active_sections' => json_decode($_POST['active_sections_json'], true)
    ];

    // Save to database (transaction-based, all or nothing)
    try {
        if(save_resume_data($pdo, $resume_id, $resume_data)){
            // Success - redirect to view page
            header("location: view_resume.php?id=" . $resume_id);
            exit;
        } else {
            $error_msg = "Error saving resume data. Please check the error log for details.";
        }
    } catch (Exception $e) {
        // Display detailed error message for debugging
        $error_msg = "Error saving resume data: " . $e->getMessage();
    }
}


// LOAD RESUME DATA FOR EDITING
// Load resume data from database
$resume_data = load_resume_data($pdo, $resume_id);

// Set defaults if empty
if(empty($resume_data['name'])){
    $resume_data['name'] = $_SESSION["email"];
}
if(empty($resume_data['email'])){
    $resume_data['email'] = $_SESSION["email"];
}

// Get active sections
$active_sections = $resume_data['active_sections'] ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resume</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/edit_resume.css">
</head>
<body class="edit-page">
    <div class="edit-container">
        <div class="edit-header">
            <h2>Edit Resume</h2>
            <a href="view_resume.php?id=<?php echo $resume_id; ?>" class="btn-back">Back to Resume</a>
        </div>

        <?php if(!empty($success_msg)): ?>
            <div class="success-message"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="error-message"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $resume_id; ?>" id="resumeForm">
            <!-- Basic Info -->
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($resume_data['name']); ?>" required>
            </div>

            <!-- Profile Picture -->
            <div class="form-group">
                <label>Profile Picture (2x2)</label>
                <div class="profile-picture-container">
                    <div class="profile-picture-preview">
                        <?php if (!empty($resume_data['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($resume_data['profile_picture']); ?>" alt="Profile Picture" id="profilePicturePreview">
                        <?php else: ?>
                            <div class="profile-picture-placeholder" id="profilePicturePlaceholder">
                                <span>No photo</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-picture-controls">
                        <input type="file" id="profilePictureInput" accept="image/*" style="display: none;">
                        <button type="button" class="btn-upload-picture" onclick="document.getElementById('profilePictureInput').click()">
                            <?php echo !empty($resume_data['profile_picture']) ? 'Change Photo' : 'Upload Photo'; ?>
                        </button>
                        <?php if (!empty($resume_data['profile_picture'])): ?>
                            <button type="button" class="btn-remove-picture" onclick="removeProfilePicture()">Remove</button>
                        <?php endif; ?>
                        <p class="form-help-text">Recommended: Square image (2x2 inches, 300x300px minimum)</p>
                    </div>
                </div>
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

            <!-- Add Section Button -->
            <div class="form-group">
                <button type="button" class="btn-add-section" onclick="openSectionSelectorModal()">+ Add Section</button>
                <p class="form-help-text">Add sections like Technical Skills, Projects, Education, Work Experience, etc.</p>
            </div>

            <!-- Dynamic Sections Container -->
            <input type="hidden" name="skills_json" id="skills_json">
            <input type="hidden" name="projects_json" id="projects_json">
            <input type="hidden" name="organizations_json" id="organizations_json">
            <div id="dynamic-sections-container">
                <!-- Technical Skills Section -->
                <?php if(in_array('skills', $active_sections) || !empty(array_filter($resume_data['skills']))): ?>
                <div class="dynamic-section" data-section="skills" id="section-skills" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Technical Skills</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('skills')">Remove Section</button>
                    </div>
                    <div class="skills-editor">
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
                </div>
                <?php endif; ?>

                <!-- Projects Section -->
                <?php if(in_array('projects', $active_sections) || !empty($resume_data['projects'])): ?>
                <div class="dynamic-section" data-section="projects" id="section-projects" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Projects</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('projects')">Remove Section</button>
                    </div>
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
                </div>
                <?php endif; ?>

                <!-- Organizations Section -->
                <?php if(in_array('organizations', $active_sections) || !empty($resume_data['organizations'])): ?>
                <div class="dynamic-section" data-section="organizations" id="section-organizations" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Organizations</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('organizations')">Remove Section</button>
                    </div>
                    <div class="org-container" id="org-container">
                        <?php foreach($resume_data['organizations'] as $org): ?>
                            <div class="org-tag">
                                <span><?php echo htmlspecialchars($org); ?></span>
                                <button type="button" class="remove-org-btn" onclick="removeOrg(this)">×</button>
                            </div>
                        <?php endforeach; ?>
                        <button type="button" class="add-org-icon" onclick="openOrgModal()">+</button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Education Section -->
                <?php if(in_array('education', $active_sections) || !empty($resume_data['education'])): ?>
                <div class="dynamic-section" data-section="education" id="section-education" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Education</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('education')">Remove Section</button>
                    </div>
                    <div id="education-container">
                        <?php foreach($resume_data['education'] as $edu): ?>
                            <div class="edu-item-edit">
                                <input type="text" class="edu-institution" placeholder="Institution" value="<?php echo htmlspecialchars($edu['institution']); ?>">
                                <div class="two-col">
                                    <input type="text" class="edu-degree" placeholder="Degree" value="<?php echo htmlspecialchars($edu['degree']); ?>">
                                    <input type="text" class="edu-field" placeholder="Field of Study" value="<?php echo htmlspecialchars($edu['field_of_study']); ?>">
                                </div>
                                <div class="two-col">
                                    <input type="text" class="edu-start" placeholder="Start Date" value="<?php echo htmlspecialchars($edu['start_date']); ?>">
                                    <input type="text" class="edu-end" placeholder="End Date" value="<?php echo htmlspecialchars($edu['end_date']); ?>">
                                </div>
                                <textarea class="edu-description" placeholder="Description (optional)"><?php echo htmlspecialchars($edu['description']); ?></textarea>
                                <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-item-btn" onclick="addEducation()">+ Add Education</button>
                </div>
                <?php endif; ?>

                <!-- Work Experience Section -->
                <?php if(in_array('work_experience', $active_sections) || !empty($resume_data['work_experience'])): ?>
                <div class="dynamic-section" data-section="work_experience" id="section-work_experience" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Work Experience</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('work_experience')">Remove Section</button>
                    </div>
                    <div id="work-container">
                        <?php foreach($resume_data['work_experience'] as $work): ?>
                            <div class="work-item-edit">
                                <input type="text" class="work-position" placeholder="Position" value="<?php echo htmlspecialchars($work['position']); ?>">
                                <input type="text" class="work-company" placeholder="Company" value="<?php echo htmlspecialchars($work['company']); ?>">
                                <div class="two-col">
                                    <input type="text" class="work-start" placeholder="Start Date" value="<?php echo htmlspecialchars($work['start_date']); ?>">
                                    <input type="text" class="work-end" placeholder="End Date" value="<?php echo htmlspecialchars($work['end_date']); ?>">
                                </div>
                                <textarea class="work-description" placeholder="Description"><?php echo htmlspecialchars($work['description']); ?></textarea>
                                <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-item-btn" onclick="addWorkExperience()">+ Add Work Experience</button>
                </div>
                <?php endif; ?>

                <!-- Certifications Section -->
                <?php if(in_array('certifications', $active_sections) || !empty($resume_data['certifications'])): ?>
                <div class="dynamic-section" data-section="certifications" id="section-certifications" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Certifications</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('certifications')">Remove Section</button>
                    </div>
                    <div id="cert-container">
                        <?php foreach($resume_data['certifications'] as $cert): ?>
                            <div class="cert-item-edit">
                                <input type="text" class="cert-name" placeholder="Certification Name" value="<?php echo htmlspecialchars($cert['name']); ?>">
                                <input type="text" class="cert-issuer" placeholder="Issuer" value="<?php echo htmlspecialchars($cert['issuer']); ?>">
                                <div class="two-col">
                                    <input type="text" class="cert-date" placeholder="Issue Date" value="<?php echo htmlspecialchars($cert['issue_date']); ?>">
                                    <input type="text" class="cert-credential" placeholder="Credential ID (optional)" value="<?php echo htmlspecialchars($cert['credential_id']); ?>">
                                </div>
                                <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-item-btn" onclick="addCertification()">+ Add Certification</button>
                </div>
                <?php endif; ?>

                <!-- Awards Section -->
                <?php if(in_array('awards', $active_sections) || !empty($resume_data['awards'])): ?>
                <div class="dynamic-section" data-section="awards" id="section-awards" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Awards & Honors</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('awards')">Remove Section</button>
                    </div>
                    <div id="awards-container">
                        <?php foreach($resume_data['awards'] as $award): ?>
                            <div class="award-item-edit">
                                <input type="text" class="award-title" placeholder="Award Title" value="<?php echo htmlspecialchars($award['title']); ?>">
                                <div class="two-col">
                                    <input type="text" class="award-issuer" placeholder="Issuer" value="<?php echo htmlspecialchars($award['issuer']); ?>">
                                    <input type="text" class="award-date" placeholder="Date" value="<?php echo htmlspecialchars($award['date']); ?>">
                                </div>
                                <textarea class="award-description" placeholder="Description (optional)"><?php echo htmlspecialchars($award['description']); ?></textarea>
                                <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-item-btn" onclick="addAward()">+ Add Award</button>
                </div>
                <?php endif; ?>

                <!-- Languages Section -->
                <?php if(in_array('languages', $active_sections) || !empty($resume_data['languages'])): ?>
                <div class="dynamic-section" data-section="languages" id="section-languages" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Languages</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('languages')">Remove Section</button>
                    </div>
                    <div id="languages-container">
                        <?php foreach($resume_data['languages'] as $lang): ?>
                            <div class="lang-item-edit">
                                <input type="text" class="lang-name" placeholder="Language" value="<?php echo htmlspecialchars($lang['language']); ?>">
                                <input type="text" class="lang-proficiency" placeholder="Proficiency (e.g., Native, Fluent)" value="<?php echo htmlspecialchars($lang['proficiency']); ?>">
                                <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-item-btn" onclick="addLanguage()">+ Add Language</button>
                </div>
                <?php endif; ?>

                <!-- Interests Section -->
                <?php if(in_array('interests', $active_sections) || !empty($resume_data['interests'])): ?>
                <div class="dynamic-section" data-section="interests" id="section-interests" draggable="true">
                    <div class="section-header-with-remove">
                        <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                        <label>Interests</label>
                        <button type="button" class="btn-remove-section" onclick="removeSection('interests')">Remove Section</button>
                    </div>
                    <div class="interests-tags-container" id="interests-container">
                        <?php foreach($resume_data['interests'] as $interest): ?>
                            <div class="interest-tag-edit">
                                <span><?php echo htmlspecialchars($interest); ?></span>
                                <button type="button" class="remove-interest-btn" onclick="removeInterest(this)">×</button>
                            </div>
                        <?php endforeach; ?>
                        <button type="button" class="add-interest-icon" onclick="openInterestModal()">+</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Hidden inputs for dynamic sections -->
            <input type="hidden" name="education_json" id="education_json">
            <input type="hidden" name="work_experience_json" id="work_experience_json">
            <input type="hidden" name="certifications_json" id="certifications_json">
            <input type="hidden" name="awards_json" id="awards_json">
            <input type="hidden" name="languages_json" id="languages_json">
            <input type="hidden" name="interests_json" id="interests_json">
            <input type="hidden" name="active_sections_json" id="active_sections_json">

            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>

    <!-- Existing Modals -->
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

    <!-- Interest Modal -->
    <div class="modal-overlay" id="interestModal">
        <div class="modal-content">
            <div class="modal-header">Add Interest</div>
            <input type="text" class="modal-input" id="interestInput" placeholder="Enter interest...">
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeInterestModal()">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" onclick="confirmAddInterest()">Add</button>
            </div>
        </div>
    </div>

    <!-- Section Selector Modal -->
    <div class="modal-overlay" id="sectionSelectorModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">Add Section</div>
            <p class="modal-subtitle">Choose which section to add to your resume</p>
            <div class="section-options">
                <div class="section-option" onclick="addSection('skills')" data-section="skills">
                    <div class="section-info">
                        <div class="section-name">Technical Skills</div>
                        <div class="section-desc">List your programming languages, databases, and tools</div>
                    </div>
                </div>
                <div class="section-option" onclick="addSection('projects')" data-section="projects">
                    <div class="section-info">
                        <div class="section-name">Projects</div>
                        <div class="section-desc">Showcase your portfolio projects and contributions</div>
                    </div>
                </div>
                <div class="section-option" onclick="addSection('organizations')" data-section="organizations">
                    <div class="section-info">
                        <div class="section-name">Organizations</div>
                        <div class="section-desc">Add professional organizations and affiliations</div>
                    </div>
                </div>
                <div class="section-option" onclick="addSection('education')" data-section="education">
                    <div class="section-info">
                        <div class="section-name">Education</div>
                        <div class="section-desc">Add your academic background and degrees</div>
                    </div>
                </div>
                <div class="section-option" onclick="addSection('work_experience')" data-section="work_experience">
                    <div class="section-info">
                        <div class="section-name">Work Experience</div>
                        <div class="section-desc">List your professional work history</div>
                    </div>
                </div>
                <div class="section-option" onclick="addSection('certifications')" data-section="certifications">
                    <div class="section-info">
                        <div class="section-name">Certifications</div>
                        <div class="section-desc">Add professional certifications and licenses</div>
                    </div>
                </div>
                <div class="section-option" onclick="addSection('awards')" data-section="awards">
                    <div class="section-info">
                        <div class="section-name">Awards & Honors</div>
                        <div class="section-desc">Showcase your achievements and recognitions</div>
                    </div>
                </div>
                <div class="section-option" onclick="addSection('languages')" data-section="languages">
                    <div class="section-info">
                        <div class="section-name">Languages</div>
                        <div class="section-desc">List languages you can speak and proficiency</div>
                    </div>
                </div>
                <div class="section-option" onclick="addSection('interests')" data-section="interests">
                    <div class="section-info">
                        <div class="section-name">Interests</div>
                        <div class="section-desc">Share your hobbies and personal interests</div>
                    </div>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeSectionSelectorModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script src="js/edit_resume.js"></script>
</body>
</html>
