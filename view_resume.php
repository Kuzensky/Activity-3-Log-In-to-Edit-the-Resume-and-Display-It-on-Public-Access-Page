<?php
session_start();
require_once 'db.php';
require_once 'resume_db.php';

// Get resume ID from URL
$resume_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($resume_id <= 0) {
    header("location: dashboard.php");
    exit;
}

// Load resume data from database
$resume_data = load_resume_data($pdo, $resume_id);

if (!$resume_data || empty($resume_data['title'])) {
    header("location: dashboard.php");
    exit;
}

// Extract data for display
$title = $resume_data['title'] ?: "Untitled Resume";
$name = $resume_data['name'] ?: "No Resume Data";
$email = $resume_data['email'] ?: "";
$phone = $resume_data['phone'] ?: "";
$location = $resume_data['location'] ?: "";
$summary = $resume_data['summary'] ?: "";
$profile_picture = $resume_data['profile_picture'] ?: "";
$skills = $resume_data['skills'];
$organizations = $resume_data['organizations'];
$education = $resume_data['education'];
$work_experience = $resume_data['work_experience'];
$certifications = $resume_data['certifications'];
$awards = $resume_data['awards'];
$languages = $resume_data['languages'];
$interests = $resume_data['interests'];

// Format projects for display
$projects = [];
foreach($resume_data['projects'] as $project){
    $projects[] = [
        'title' => $project['title'],
        'type' => trim($project['type'] . ' | ' . $project['year'], ' |'),
        'details' => $project['details']
    ];
}

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - <?php echo htmlspecialchars($name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .resume-page {
            background: var(--background);
            min-height: 100vh;
            padding: 24px;
        }

        .action-buttons {
            position: absolute;
            top: 24px;
            right: 24px;
            display: flex;
            gap: 12px;
            z-index: 10;
        }

        .btn-back {
            padding: 12px 20px;
            background: var(--surface);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            border: 1px solid #e5e7eb;
            animation: slideInDown 0.6s ease-out 0.2s both;
        }

        .btn-back:hover {
            background: #f9fafb;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-edit {
            padding: 12px 20px;
            background: var(--primary);
            color: var(--text-inverse);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            animation: slideInDown 0.6s ease-out 0.3s both;
        }

        .btn-edit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-print {
            padding: 12px 20px;
            background: var(--accent);
            color: var(--text-inverse);
            text-decoration: none;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            animation: slideInDown 0.6s ease-out 0.4s both;
        }

        .btn-print:hover {
            background: #d97706;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .resume-header-wrapper {
            display: flex;
            align-items: center;
            gap: 32px;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 2px solid var(--border);
        }

        .resume-profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid var(--border);
            flex-shrink: 0;
        }

        .resume-header-content {
            flex: 1;
        }

        .resume-name {
            font-size: 48px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            letter-spacing: -0.025em;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .resume-contact {
            color: var(--text-secondary);
            font-size: 16px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary);
            display: inline-block;
        }

        .section-content {
            text-align: justify;
            line-height: 1.8;
            color: var(--text-primary);
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
        }

        .skills-category {
            margin-bottom: 30px;
        }

        .skills-category-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 15px;
        }

        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .skill-tag {
            background: var(--surface-elevated);
            padding: 10px 20px;
            border-radius: 20px;
            border: 1px solid var(--border);
            font-weight: 500;
            font-size: 14px;
            color: var(--text-primary);
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .skill-tag:hover {
            background: var(--primary);
            color: var(--text-inverse);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .project-item, .edu-item, .work-item, .cert-item, .award-item {
            background: var(--surface-elevated);
            padding: 24px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .item-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .item-subtitle {
            color: var(--text-secondary);
            font-style: italic;
            margin-bottom: 12px;
        }

        .item-date {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .item-description {
            color: var(--text-primary);
            line-height: 1.6;
        }

        .project-details {
            list-style: none;
            padding: 0;
        }

        .project-details li {
            color: var(--text-primary);
            margin-bottom: 8px;
            padding-left: 16px;
            position: relative;
        }

        .project-details li::before {
            content: '•';
            color: var(--primary);
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .organizations-list, .languages-list, .interests-list {
            list-style: none;
            padding: 0;
        }

        .organizations-list li, .cert-item, .award-item {
            background: var(--surface-elevated);
            padding: 16px 20px;
            margin-bottom: 12px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
            color: var(--text-primary);
            font-weight: 500;
        }

        .languages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }

        .language-item {
            background: var(--surface-elevated);
            padding: 16px 20px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
        }

        .language-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .language-proficiency {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .interests-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .interest-tag {
            background: var(--surface-elevated);
            padding: 10px 20px;
            border-radius: 20px;
            border: 1px solid var(--border);
            font-weight: 500;
            font-size: 14px;
            color: var(--text-primary);
        }

        @media print {
            .resume-page {
                background: white !important;
                padding: 0 !important;
            }

            .action-buttons {
                display: none !important;
            }

            .resume-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 20px !important;
            }

            .resume-profile-picture {
                width: 2in;
                height: 2in;
            }
        }

        @media (max-width: 768px) {
            .action-buttons {
                position: fixed;
                top: 12px;
                right: 12px;
                flex-direction: column;
                gap: 6px;
            }

            .resume-header-wrapper {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .resume-profile-picture {
                width: 120px;
                height: 120px;
            }

            .resume-name {
                font-size: 36px;
            }
        }
    </style>
</head>

<body class="resume-page">
    <div class="action-buttons">
        <a href="dashboard.php" class="btn-back">← Back</a>
        <?php if($is_logged_in): ?>
            <a href="edit_resume.php?id=<?php echo $resume_id; ?>" class="btn-edit">Edit Resume</a>
            <button class="btn-print" onclick="window.print()">Print</button>
        <?php endif; ?>
    </div>

    <div class="resume-container">
        <div class="resume-header-wrapper">
            <?php if(!empty($profile_picture)): ?>
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="resume-profile-picture">
            <?php endif; ?>
            <div class="resume-header-content">
                <h1 class="resume-name"><?php echo htmlspecialchars($name); ?></h1>
                <div class="resume-contact">
                    <?php echo htmlspecialchars($email); ?>
                    <?php if($email && $phone): ?>|<?php endif; ?>
                    <?php echo htmlspecialchars($phone); ?>
                    <?php if(($email || $phone) && $location): ?>|<?php endif; ?>
                    <?php echo htmlspecialchars($location); ?>
                </div>
            </div>
        </div>

        <?php if($summary): ?>
        <div class="resume-section">
            <h2 class="section-title">Summary</h2>
            <div class="section-content">
                <?php echo nl2br(htmlspecialchars($summary)); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($education)): ?>
        <div class="resume-section">
            <h2 class="section-title">Education</h2>
            <div class="section-content">
                <?php foreach ($education as $edu): ?>
                    <div class="edu-item">
                        <h3 class="item-title"><?php echo htmlspecialchars($edu['institution']); ?></h3>
                        <?php if($edu['degree'] || $edu['field_of_study']): ?>
                            <p class="item-subtitle">
                                <?php echo htmlspecialchars($edu['degree']); ?>
                                <?php if($edu['degree'] && $edu['field_of_study']): ?>in<?php endif; ?>
                                <?php echo htmlspecialchars($edu['field_of_study']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if($edu['start_date'] || $edu['end_date']): ?>
                            <p class="item-date">
                                <?php echo htmlspecialchars($edu['start_date']); ?>
                                <?php if($edu['start_date'] && $edu['end_date']): ?>-<?php endif; ?>
                                <?php echo htmlspecialchars($edu['end_date']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if($edu['description']): ?>
                            <p class="item-description"><?php echo nl2br(htmlspecialchars($edu['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($work_experience)): ?>
        <div class="resume-section">
            <h2 class="section-title">Work Experience</h2>
            <div class="section-content">
                <?php foreach ($work_experience as $work): ?>
                    <div class="work-item">
                        <h3 class="item-title"><?php echo htmlspecialchars($work['position']); ?></h3>
                        <p class="item-subtitle"><?php echo htmlspecialchars($work['company']); ?></p>
                        <?php if($work['start_date'] || $work['end_date']): ?>
                            <p class="item-date">
                                <?php echo htmlspecialchars($work['start_date']); ?>
                                <?php if($work['start_date'] && $work['end_date']): ?>-<?php endif; ?>
                                <?php echo htmlspecialchars($work['end_date']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if($work['description']): ?>
                            <p class="item-description"><?php echo nl2br(htmlspecialchars($work['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="resume-section">
            <h2 class="section-title">Technical Skills</h2>
            <div class="section-content">
                <?php if(!empty($skills['programming'])): ?>
                <div class="skills-category">
                    <h3 class="skills-category-title">Programming Languages</h3>
                    <div class="skills-grid">
                        <?php foreach ($skills['programming'] as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($skills['database'])): ?>
                <div class="skills-category">
                    <h3 class="skills-category-title">Database</h3>
                    <div class="skills-grid">
                        <?php foreach ($skills['database'] as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($skills['tools'])): ?>
                <div class="skills-category">
                    <h3 class="skills-category-title">Tools</h3>
                    <div class="skills-grid">
                        <?php foreach ($skills['tools'] as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if(!empty($projects)): ?>
        <div class="resume-section">
            <h2 class="section-title">Projects</h2>
            <div class="section-content">
                <?php foreach ($projects as $project): ?>
                    <div class="project-item">
                        <h3 class="item-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                        <p class="item-subtitle"><?php echo htmlspecialchars($project['type']); ?></p>
                        <?php if(!empty($project['details'])): ?>
                        <ul class="project-details">
                            <?php foreach ($project['details'] as $detail): ?>
                                <li><?php echo htmlspecialchars($detail); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($certifications)): ?>
        <div class="resume-section">
            <h2 class="section-title">Certifications</h2>
            <div class="section-content">
                <?php foreach ($certifications as $cert): ?>
                    <div class="cert-item">
                        <h3 class="item-title"><?php echo htmlspecialchars($cert['name']); ?></h3>
                        <?php if($cert['issuer']): ?>
                            <p class="item-subtitle"><?php echo htmlspecialchars($cert['issuer']); ?></p>
                        <?php endif; ?>
                        <?php if($cert['issue_date']): ?>
                            <p class="item-date"><?php echo htmlspecialchars($cert['issue_date']); ?></p>
                        <?php endif; ?>
                        <?php if($cert['credential_id']): ?>
                            <p class="item-description">Credential ID: <?php echo htmlspecialchars($cert['credential_id']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($awards)): ?>
        <div class="resume-section">
            <h2 class="section-title">Awards & Honors</h2>
            <div class="section-content">
                <?php foreach ($awards as $award): ?>
                    <div class="award-item">
                        <h3 class="item-title"><?php echo htmlspecialchars($award['title']); ?></h3>
                        <?php if($award['issuer']): ?>
                            <p class="item-subtitle"><?php echo htmlspecialchars($award['issuer']); ?></p>
                        <?php endif; ?>
                        <?php if($award['date']): ?>
                            <p class="item-date"><?php echo htmlspecialchars($award['date']); ?></p>
                        <?php endif; ?>
                        <?php if($award['description']): ?>
                            <p class="item-description"><?php echo nl2br(htmlspecialchars($award['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($languages)): ?>
        <div class="resume-section">
            <h2 class="section-title">Languages</h2>
            <div class="section-content">
                <div class="languages-grid">
                    <?php foreach ($languages as $lang): ?>
                        <div class="language-item">
                            <div class="language-name"><?php echo htmlspecialchars($lang['language']); ?></div>
                            <?php if($lang['proficiency']): ?>
                                <div class="language-proficiency"><?php echo htmlspecialchars($lang['proficiency']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($organizations)): ?>
        <div class="resume-section">
            <h2 class="section-title">Organizations</h2>
            <div class="section-content">
                <ul class="organizations-list">
                    <?php foreach ($organizations as $org): ?>
                        <li><?php echo htmlspecialchars($org); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($interests)): ?>
        <div class="resume-section">
            <h2 class="section-title">Interests</h2>
            <div class="section-content">
                <div class="interests-list">
                    <?php foreach ($interests as $interest): ?>
                        <span class="interest-tag"><?php echo htmlspecialchars($interest); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>
