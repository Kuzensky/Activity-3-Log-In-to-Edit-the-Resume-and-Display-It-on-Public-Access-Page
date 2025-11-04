<?php
session_start();
require_once 'db.php';
require_once 'resume_db.php';

// Always show the default public resume (user_id 1)
// All logged-in users edit this same resume
$user_id = 1;

// Load resume data from database
$resume_data = load_resume_data($pdo, $user_id);

// Extract data for display
$name = $resume_data['name'] ?: "No Resume Data";
$email = $resume_data['email'] ?: "";
$phone = $resume_data['phone'] ?: "";
$location = $resume_data['location'] ?: "";
$summary = $resume_data['summary'] ?: "";
$skills = $resume_data['skills'];
$organizations = $resume_data['organizations'];

// Format projects for display
$projects = [];
foreach($resume_data['projects'] as $project){
    $projects[] = [
        'title' => $project['title'],
        'type' => trim($project['type'] . ' | ' . $project['year'], ' |'),
        'details' => $project['details']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?> - Resume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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

        .btn-login {
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

        .btn-login:hover {
            background: #667eea;
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

        .btn-logout {
            padding: 12px 20px;
            background: var(--error);
            color: var(--text-inverse);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            animation: slideInDown 0.6s ease-out 0.4s both;
        }

        .btn-logout:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-print {
            padding: 12px 20px;
            background: var(--primary);
            color: var(--text-inverse);
            text-decoration: none;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            animation: slideInDown 0.6s ease-out 0.5s both;
        }

        .btn-print:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .resume-name {
            font-size: 48px;
            font-weight: 700;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 12px;
            letter-spacing: -0.025em;
        }

        .resume-contact {
            text-align: center;
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 2px solid var(--border);
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

        .skill-icon {
            font-size: 18px;
        }

        .project-item {
            background: var(--surface-elevated);
            padding: 24px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .project-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .project-type {
            color: var(--text-secondary);
            font-style: italic;
            margin-bottom: 12px;
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

        .organizations-list {
            list-style: none;
            padding: 0;
        }

        .organizations-list li {
            background: var(--surface-elevated);
            padding: 16px 20px;
            margin-bottom: 12px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
            color: var(--text-primary);
            font-weight: 500;
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
        }

        @media (max-width: 1024px) {
            .action-buttons {
                top: 16px;
                right: 16px;
                gap: 8px;
            }

            .btn-login,
            .btn-edit,
            .btn-logout,
            .btn-print {
                padding: 10px 16px;
                font-size: 13px;
            }
        }

        @media (max-width: 768px) {
            .action-buttons {
                position: fixed;
                top: 12px;
                right: 12px;
                flex-direction: column;
                gap: 6px;
                width: auto;
            }

            .btn-login,
            .btn-edit,
            .btn-logout,
            .btn-print {
                padding: 8px 14px;
                font-size: 12px;
                white-space: nowrap;
            }

            .resume-name {
                font-size: 36px;
                padding-right: 80px;
            }

            .section-title {
                font-size: 20px;
            }

            .resume-container {
                padding: 32px 20px;
            }
        }

        @media (max-width: 480px) {
            .resume-page {
                padding: 12px;
            }

            .resume-name {
                font-size: 24px;
                padding-right: 70px;
            }

            .resume-contact {
                font-size: 13px;
                flex-direction: column;
                text-align: center;
            }

            .action-buttons {
                top: 8px;
                right: 8px;
                gap: 4px;
            }

            .btn-login,
            .btn-edit,
            .btn-logout,
            .btn-print {
                padding: 6px 10px;
                font-size: 11px;
                min-width: 60px;
            }

            .resume-container {
                padding: 24px 16px;
                margin: 12px auto;
            }

            .skills-grid {
                gap: 8px;
            }

            .skill-tag {
                padding: 8px 14px;
                font-size: 13px;
            }

            .project-item {
                padding: 16px;
            }

            .section-title {
                font-size: 18px;
            }
        }
    </style>
</head>

<body class="resume-page">
    <div class="action-buttons">
        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <a href="edit_resume.php" class="btn-edit">Edit Resume</a>
            <button class="btn-print" onclick="printResume()">Print Resume</button>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn-login">Login</a>
        <?php endif; ?>
    </div>

    <div class="resume-container">
        <h1 class="resume-name"><?php echo $name; ?></h1>
        <div class="resume-contact">
            <?php echo $email; ?> | <?php echo $phone; ?> | <?php echo $location; ?>
        </div>

        <div class="resume-section">
            <h2 class="section-title">Summary</h2>
            <div class="section-content">
                <?php echo $summary; ?>
            </div>
        </div>

        <div class="resume-section">
            <h2 class="section-title">Technical Skills</h2>
            <div class="section-content">
                <div class="skills-category">
                    <h3 class="skills-category-title">Programming Languages</h3>
                    <div class="skills-grid">
                        <?php foreach ($skills['programming'] as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="skills-category">
                    <h3 class="skills-category-title">Database</h3>
                    <div class="skills-grid">
                        <?php foreach ($skills['database'] as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="skills-category">
                    <h3 class="skills-category-title">Tools</h3>
                    <div class="skills-grid">
                        <?php foreach ($skills['tools'] as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="resume-section">
            <h2 class="section-title">Projects</h2>
            <div class="section-content">
                <?php foreach ($projects as $project): ?>
                    <div class="project-item">
                        <h3 class="project-title"><?php echo $project['title']; ?></h3>
                        <p class="project-type"><?php echo $project['type']; ?></p>
                        <ul class="project-details">
                            <?php foreach ($project['details'] as $detail): ?>
                                <li><?php echo $detail; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="resume-section">
            <h2 class="section-title">Organizations</h2>
            <div class="section-content">
                <ul class="organizations-list">
                    <?php foreach ($organizations as $org): ?>
                        <li><?php echo $org; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function printResume() {
            window.print();
        }

        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>
