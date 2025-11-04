<?php
// Resume Database Helper Functions

// Load resume data for a user
function load_resume_data($pdo, $user_id) {
    $resume_data = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'location' => '',
        'summary' => '',
        'skills' => ['programming' => [], 'database' => [], 'tools' => []],
        'projects' => [],
        'organizations' => []
    ];

    // Load profile
    $stmt = $pdo->prepare("SELECT * FROM resume_profile WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($profile) {
        $resume_data['name'] = $profile['full_name'];
        $resume_data['email'] = $profile['email'];
        $resume_data['phone'] = $profile['phone'];
        $resume_data['location'] = $profile['location'];
        $resume_data['summary'] = $profile['summary'];
    }

    // Load skills
    $stmt = $pdo->prepare("SELECT category, skill_name FROM skills WHERE user_id = ? ORDER BY id");
    $stmt->execute([$user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resume_data['skills'][$row['category']][] = $row['skill_name'];
    }

    // Load projects
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY sort_order, id");
    $stmt->execute([$user_id]);
    while ($project = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $details_stmt = $pdo->prepare("SELECT detail_text FROM project_details WHERE project_id = ? ORDER BY sort_order, id");
        $details_stmt->execute([$project['id']]);
        $details = $details_stmt->fetchAll(PDO::FETCH_COLUMN);

        $resume_data['projects'][] = [
            'title' => $project['title'],
            'type' => $project['type'],
            'year' => $project['year'],
            'details' => $details
        ];
    }

    // Load organizations
    $stmt = $pdo->prepare("SELECT organization_name FROM organizations WHERE user_id = ? ORDER BY sort_order, id");
    $stmt->execute([$user_id]);
    $resume_data['organizations'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $resume_data;
}

// Save resume data for a user
function save_resume_data($pdo, $user_id, $data) {
    try {
        $pdo->beginTransaction();

        // Save/Update profile
        $stmt = $pdo->prepare("
            INSERT INTO resume_profile (user_id, full_name, email, phone, location, summary, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (user_id) DO UPDATE SET
                full_name = EXCLUDED.full_name,
                email = EXCLUDED.email,
                phone = EXCLUDED.phone,
                location = EXCLUDED.location,
                summary = EXCLUDED.summary,
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $user_id,
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['location'],
            $data['summary']
        ]);

        // Delete old skills and insert new ones
        $pdo->prepare("DELETE FROM skills WHERE user_id = ?")->execute([$user_id]);
        $stmt = $pdo->prepare("INSERT INTO skills (user_id, category, skill_name) VALUES (?, ?, ?)");
        foreach ($data['skills'] as $category => $skills) {
            foreach ($skills as $skill) {
                $stmt->execute([$user_id, $category, $skill]);
            }
        }

        // Delete old projects and insert new ones
        $pdo->prepare("DELETE FROM projects WHERE user_id = ?")->execute([$user_id]);
        $project_stmt = $pdo->prepare("INSERT INTO projects (user_id, title, type, year, sort_order) VALUES (?, ?, ?, ?, ?) RETURNING id");
        $detail_stmt = $pdo->prepare("INSERT INTO project_details (project_id, detail_text, sort_order) VALUES (?, ?, ?)");

        foreach ($data['projects'] as $index => $project) {
            $project_stmt->execute([
                $user_id,
                $project['title'],
                $project['type'],
                $project['year'],
                $index
            ]);
            $project_id = $project_stmt->fetchColumn();

            foreach ($project['details'] as $detail_index => $detail) {
                $detail_stmt->execute([$project_id, $detail, $detail_index]);
            }
        }

        // Delete old organizations and insert new ones
        $pdo->prepare("DELETE FROM organizations WHERE user_id = ?")->execute([$user_id]);
        $stmt = $pdo->prepare("INSERT INTO organizations (user_id, organization_name, sort_order) VALUES (?, ?, ?)");
        foreach ($data['organizations'] as $index => $org) {
            $stmt->execute([$user_id, $org, $index]);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving resume: " . $e->getMessage());
        return false;
    }
}
?>
