<?php
/**
 * ============================================================================
 * RESUME CONTROLLER
 * ============================================================================
 *
 * Handles all resume-related database operations for the multi-resume system.
 *
 * DATABASE ARCHITECTURE:
 * ----------------------
 * The application supports multiple resumes per user with this structure:
 *
 *   users (1) → (*) resumes (1) → (*) resume_data_tables
 *
 * - users table: Contains user accounts (login credentials)
 * - resumes table: Links users to their resumes (user_id → resume_id)
 * - resume_data tables: All resume content (profile, skills, projects, etc.)
 *   uses resume_id to link to a specific resume
 *
 * DATA FLOW:
 * ----------
 * 1. User logs in → gets user_id from session
 * 2. User creates resume → creates record in resumes table
 * 3. User edits resume → all data saved with resume_id
 * 4. To view user's resumes: SELECT * FROM resumes WHERE user_id = ?
 * 5. To view resume data: SELECT * FROM skills WHERE resume_id = ?
 *
 * CORE RESUME DATA TABLES:
 * ------------------------
 * - resume_profile: Basic contact info (name, email, phone, location, summary)
 * - skills: Technical skills organized by category (programming, database, tools)
 * - projects: Portfolio projects with details
 * - organizations: Professional organizations/affiliations
 *
 * DYNAMIC SECTION TABLES (optional):
 * ----------------------------------
 * - education: Academic background
 * - work_experience: Employment history
 * - certifications: Professional certifications
 * - awards: Honors and achievements
 * - languages: Language proficiencies
 * - interests: Personal interests and hobbies
 * - resume_sections: Tracks which optional sections are active per resume
 *
 * All tables use resume_id as foreign key (ON DELETE CASCADE) so deleting
 * a resume automatically removes all associated data.
 */

class ResumeController {
    // DATABASE CONNECTION
    // Store PDO (PHP Data Objects) connection for all database operations
    // PDO provides a secure way to interact with databases using prepared statements
    private $pdo;

    /**
     * CONSTRUCTOR
     *
     * Initializes the controller with a database connection
     *
     * @param PDO $pdo - Database connection object from db.php
     *
     * Example usage:
     *   $controller = new ResumeController($pdo);
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ============================================================================
    // RESUME MANAGEMENT - Core CRUD Operations
    // ============================================================================
    // These methods handle creating, reading, updating, and deleting resumes

    /**
     * GET ALL RESUMES FROM ALL USERS
     *
     * Purpose: Fetch every resume in the system for the public dashboard
     *
     * SQL Concepts Demonstrated:
     * - JOIN: Combines data from multiple tables (resumes, users, resume_profile)
     * - LEFT JOIN: Includes resumes even if profile data doesn't exist yet
     * - ORDER BY: Sorts results by most recently updated first
     *
     * @return array - Array of all resumes with owner info and summaries
     *
     * Used by: dashboard.php (public resume listing)
     */
    public function getAllResumes() {
        // Prepare SQL query (prevents SQL injection attacks)
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.title, r.created_at, r.updated_at,
                   u.email as owner_email,
                   rp.full_name as owner_name,
                   rp.summary
            FROM resumes r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN resume_profile rp ON r.id = rp.resume_id
            ORDER BY r.updated_at DESC
        ");
        $stmt->execute();

        // Return all rows as associative array
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * GET RESUMES FOR A SPECIFIC USER
     *
     * Purpose: Fetch only the resumes belonging to one user
     *
     * Security Note: Uses prepared statement with placeholder (?)
     * This prevents SQL injection by separating SQL code from user data
     *
     * @param int $user_id - The ID of the user whose resumes to fetch
     * @return array - Array of user's resumes
     *
     * Example: If user_id = 5, returns only resumes where user_id = 5
     */
    public function getUserResumes($user_id) {
        // The ? is a placeholder that gets safely replaced with $user_id
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.title, r.created_at, r.updated_at,
                   rp.full_name, rp.summary
            FROM resumes r
            LEFT JOIN resume_profile rp ON r.id = rp.resume_id
            WHERE r.user_id = ?
            ORDER BY r.updated_at DESC
        ");

        // Execute with actual value - PDO handles escaping/sanitization
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * GET BASIC RESUME INFORMATION
     *
     * Purpose: Fetch metadata about a single resume (title, owner, dates)
     * This is lighter than loading all resume content
     *
     * @param int $resume_id - The ID of the resume to fetch
     * @return array|false - Resume info or false if not found
     *
     * Used by: Multiple pages to check if resume exists before loading full data
     */
    public function getResumeInfo($resume_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.title, r.user_id, r.created_at, r.updated_at,
                   u.email as owner_email
            FROM resumes r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$resume_id]);

        // fetch() returns single row (not array of rows)
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * CREATE A NEW RESUME WITH SMART ID REUSE
     *
     * Purpose: Create a new resume and assign it an ID
     *
     * Key Feature: ID REUSE ALGORITHM
     * --------------------------------
     * Traditional approach: IDs keep incrementing (1, 2, 3, 4...)
     * If you delete ID 2, next resume gets ID 5 (leaving a gap at 2)
     *
     * Our approach: REUSE DELETED IDs
     * If you delete ID 2, next resume gets ID 2 (fills the gap)
     *
     * Why this matters:
     * - Keeps IDs compact and organized
     * - Better for testing and debugging
     * - Prevents very large ID numbers over time
     *
     * Algorithm Steps:
     * 1. Generate series of numbers from 1 to MAX(id)+1
     * 2. Find first number NOT in use (the gap)
     * 3. Use that ID for the new resume
     * 4. Update PostgreSQL sequence to prevent conflicts
     *
     * Example:
     *   Existing IDs: [1, 3, 4]  (2 was deleted)
     *   Next ID: 2  (fills the gap)
     *   After insert: [1, 2, 3, 4]
     *
     * @param int $user_id - The user who owns this resume
     * @param string $title - Optional resume title (default: 'My Resume')
     * @return int|false - The new resume ID or false on error
     */
    public function createResume($user_id, $title = 'My Resume') {
        try {
            // STEP 1: Find the first available ID (including gaps from deleted resumes)
            // generate_series(1, max+1) creates sequence: 1, 2, 3, 4, 5...
            // WHERE NOT EXISTS finds numbers not in resumes table
            // LIMIT 1 gets the lowest available number
            $next_id_query = $this->pdo->query("
                SELECT COALESCE(
                    (SELECT s.id
                     FROM generate_series(1, (SELECT COALESCE(MAX(id), 0) + 1 FROM resumes)) AS s(id)
                     WHERE NOT EXISTS (SELECT 1 FROM resumes WHERE resumes.id = s.id)
                     LIMIT 1),
                    1
                ) as next_id
            ");
            $next_id = $next_id_query->fetchColumn();

            // STEP 2: Insert resume with the specific ID we found
            // Note: We manually specify the ID instead of using auto-increment
            $stmt = $this->pdo->prepare("
                INSERT INTO resumes (id, user_id, title, created_at, updated_at)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$next_id, $user_id, $title]);

            // STEP 3: Update PostgreSQL's auto-increment sequence
            // This ensures next auto-generated ID won't conflict with our manual IDs
            // setval() sets the sequence to current MAX(id)
            $this->pdo->exec("SELECT setval('resumes_id_seq', (SELECT MAX(id) FROM resumes))");

            return $next_id;
        } catch (Exception $e) {
            // Log error for debugging but don't expose details to user
            error_log("Error creating resume: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a resume
     */
    public function deleteResume($resume_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM resumes WHERE id = ?");
            $stmt->execute([$resume_id]);
            return true;
        } catch (Exception $e) {
            error_log("Error deleting resume: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update resume title
     */
    public function updateResumeTitle($resume_id, $title) {
        try {
            $stmt = $this->pdo->prepare("UPDATE resumes SET title = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$title, $resume_id]);
            return true;
        } catch (Exception $e) {
            error_log("Error updating resume title: " . $e->getMessage());
            return false;
        }
    }

    // ============================================================================
    // LOAD RESUME DATA
    // ============================================================================

    /**
     * Load complete resume data for a specific resume
     */
    public function loadResumeData($resume_id) {
        $resume_data = [
            'id' => $resume_id,
            'title' => '',
            'name' => '',
            'email' => '',
            'phone' => '',
            'location' => '',
            'summary' => '',
            'profile_picture' => '',
            'skills' => ['programming' => [], 'database' => [], 'tools' => []],
            'projects' => [],
            'organizations' => [],
            'education' => [],
            'work_experience' => [],
            'certifications' => [],
            'awards' => [],
            'languages' => [],
            'interests' => [],
            'active_sections' => []
        ];

        // Load resume info
        $info = $this->getResumeInfo($resume_id);
        if ($info) {
            $resume_data['title'] = $info['title'];
        }

        // Load profile
        $stmt = $this->pdo->prepare("SELECT * FROM resume_profile WHERE resume_id = ?");
        $stmt->execute([$resume_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            $resume_data['name'] = $profile['full_name'];
            $resume_data['email'] = $profile['email'];
            $resume_data['phone'] = $profile['phone'];
            $resume_data['location'] = $profile['location'];
            $resume_data['summary'] = $profile['summary'];
            $resume_data['profile_picture'] = $profile['profile_picture'] ?? '';
        }

        // Load skills
        $stmt = $this->pdo->prepare("SELECT category, skill_name FROM skills WHERE resume_id = ? ORDER BY id");
        $stmt->execute([$resume_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resume_data['skills'][$row['category']][] = $row['skill_name'];
        }

        // Load projects
        $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE resume_id = ? ORDER BY sort_order, id");
        $stmt->execute([$resume_id]);
        while ($project = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $details_stmt = $this->pdo->prepare("SELECT detail_text FROM project_details WHERE project_id = ? ORDER BY sort_order, id");
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
        $stmt = $this->pdo->prepare("SELECT organization_name FROM organizations WHERE resume_id = ? ORDER BY sort_order, id");
        $stmt->execute([$resume_id]);
        $resume_data['organizations'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Load dynamic sections
        $resume_data['education'] = $this->loadEducation($resume_id);
        $resume_data['work_experience'] = $this->loadWorkExperience($resume_id);
        $resume_data['certifications'] = $this->loadCertifications($resume_id);
        $resume_data['awards'] = $this->loadAwards($resume_id);
        $resume_data['languages'] = $this->loadLanguages($resume_id);
        $resume_data['interests'] = $this->loadInterests($resume_id);
        $resume_data['active_sections'] = $this->loadActiveSections($resume_id);

        return $resume_data;
    }

    // ============================================================================
    // SAVE RESUME DATA
    // ============================================================================

    /**
     * Save complete resume data to database
     *
     * This method handles saving all resume sections in a single transaction.
     * If any part fails, the entire save is rolled back to maintain data integrity.
     *
     * Process:
     * 1. Save or update profile (contact info, summary)
     * 2. Auto-generate resume title from name if needed
     * 3. Update resume timestamp
     * 4. Save core sections (skills, projects, organizations)
     * 5. Save optional dynamic sections (education, work, etc.)
     * 6. Track which optional sections are active
     *
     * @param int   $resume_id  The resume ID to save data for
     * @param array $data       Resume data array with all sections
     * @return bool             True on success
     * @throws Exception        On database errors (transaction will be rolled back)
     */
    public function saveResumeData($resume_id, $data) {
        try {
            // Start transaction - all saves must succeed or none will be saved
            $this->pdo->beginTransaction();

            // Save profile (basic contact information)
            // Uses ON CONFLICT to update existing profile or create new one
            $stmt = $this->pdo->prepare("
                INSERT INTO resume_profile (resume_id, full_name, email, phone, location, summary, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT (resume_id) DO UPDATE SET
                    full_name = EXCLUDED.full_name,
                    email = EXCLUDED.email,
                    phone = EXCLUDED.phone,
                    location = EXCLUDED.location,
                    summary = EXCLUDED.summary,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                $resume_id,
                $data['name'] ?? '',
                $data['email'] ?? '',
                $data['phone'] ?? '',
                $data['location'] ?? '',
                $data['summary'] ?? ''
            ]);

            // Auto-update resume title based on name if it's still the default
            $current_title = $this->pdo->prepare("SELECT title FROM resumes WHERE id = ?");
            $current_title->execute([$resume_id]);
            $title = $current_title->fetchColumn();

            if ($title === 'New Resume' && !empty($data['name'])) {
                $this->pdo->prepare("UPDATE resumes SET title = ? WHERE id = ?")->execute([$data['name'] . "'s Resume", $resume_id]);
            }

            // Update resume timestamp
            $this->pdo->prepare("UPDATE resumes SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$resume_id]);

            // Save core sections
            $this->saveSkills($resume_id, $data['skills'] ?? ['programming' => [], 'database' => [], 'tools' => []]);
            $this->saveProjects($resume_id, $data['projects'] ?? []);
            $this->saveOrganizations($resume_id, $data['organizations'] ?? []);

            // Save dynamic sections
            if (isset($data['education']) && is_array($data['education'])) {
                $this->saveEducation($resume_id, $data['education']);
            }
            if (isset($data['work_experience']) && is_array($data['work_experience'])) {
                $this->saveWorkExperience($resume_id, $data['work_experience']);
            }
            if (isset($data['certifications']) && is_array($data['certifications'])) {
                $this->saveCertifications($resume_id, $data['certifications']);
            }
            if (isset($data['awards']) && is_array($data['awards'])) {
                $this->saveAwards($resume_id, $data['awards']);
            }
            if (isset($data['languages']) && is_array($data['languages'])) {
                $this->saveLanguages($resume_id, $data['languages']);
            }
            if (isset($data['interests']) && is_array($data['interests'])) {
                $this->saveInterests($resume_id, $data['interests']);
            }
            if (isset($data['active_sections']) && is_array($data['active_sections'])) {
                $this->saveActiveSections($resume_id, $data['active_sections']);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error saving resume: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // ============================================================================
    // PRIVATE HELPER METHODS - Core Sections
    // ============================================================================

    private function saveSkills($resume_id, $skills) {
        $this->pdo->prepare("DELETE FROM skills WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO skills (resume_id, category, skill_name) VALUES (?, ?, ?)");
        foreach ($skills as $category => $skill_list) {
            foreach ($skill_list as $skill) {
                if (trim($skill)) {
                    $stmt->execute([$resume_id, $category, $skill]);
                }
            }
        }
    }

    private function saveProjects($resume_id, $projects) {
        $this->pdo->prepare("DELETE FROM projects WHERE resume_id = ?")->execute([$resume_id]);
        $project_stmt = $this->pdo->prepare("INSERT INTO projects (resume_id, title, type, year, sort_order) VALUES (?, ?, ?, ?, ?) RETURNING id");
        $detail_stmt = $this->pdo->prepare("INSERT INTO project_details (project_id, detail_text, sort_order) VALUES (?, ?, ?)");

        foreach ($projects as $index => $project) {
            $project_stmt->execute([
                $resume_id,
                $project['title'],
                $project['type'],
                $project['year'],
                $index
            ]);
            $project_id = $project_stmt->fetchColumn();

            foreach ($project['details'] as $detail_index => $detail) {
                if (trim($detail)) {
                    $detail_stmt->execute([$project_id, $detail, $detail_index]);
                }
            }
        }
    }

    private function saveOrganizations($resume_id, $organizations) {
        $this->pdo->prepare("DELETE FROM organizations WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO organizations (resume_id, organization_name, sort_order) VALUES (?, ?, ?)");
        foreach ($organizations as $index => $org) {
            if (trim($org)) {
                $stmt->execute([$resume_id, $org, $index]);
            }
        }
    }

    // ============================================================================
    // PRIVATE HELPER METHODS - Dynamic Sections
    // ============================================================================

    private function loadEducation($resume_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM education WHERE resume_id = ? ORDER BY sort_order, id");
        $stmt->execute([$resume_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveEducation($resume_id, $education) {
        $this->pdo->prepare("DELETE FROM education WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO education (resume_id, institution, degree, field_of_study, start_date, end_date, description, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($education as $index => $edu) {
            $stmt->execute([
                $resume_id,
                $edu['institution'],
                $edu['degree'] ?? '',
                $edu['field_of_study'] ?? '',
                $edu['start_date'] ?? '',
                $edu['end_date'] ?? '',
                $edu['description'] ?? '',
                $index
            ]);
        }
    }

    private function loadWorkExperience($resume_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM work_experience WHERE resume_id = ? ORDER BY sort_order, id");
        $stmt->execute([$resume_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveWorkExperience($resume_id, $work_experience) {
        $this->pdo->prepare("DELETE FROM work_experience WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO work_experience (resume_id, company, position, start_date, end_date, description, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($work_experience as $index => $work) {
            $stmt->execute([
                $resume_id,
                $work['company'],
                $work['position'],
                $work['start_date'] ?? '',
                $work['end_date'] ?? '',
                $work['description'] ?? '',
                $index
            ]);
        }
    }

    private function loadCertifications($resume_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM certifications WHERE resume_id = ? ORDER BY sort_order, id");
        $stmt->execute([$resume_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveCertifications($resume_id, $certifications) {
        $this->pdo->prepare("DELETE FROM certifications WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO certifications (resume_id, name, issuer, issue_date, credential_id, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($certifications as $index => $cert) {
            $stmt->execute([
                $resume_id,
                $cert['name'],
                $cert['issuer'] ?? '',
                $cert['issue_date'] ?? '',
                $cert['credential_id'] ?? '',
                $index
            ]);
        }
    }

    private function loadAwards($resume_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM awards WHERE resume_id = ? ORDER BY sort_order, id");
        $stmt->execute([$resume_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveAwards($resume_id, $awards) {
        $this->pdo->prepare("DELETE FROM awards WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO awards (resume_id, title, issuer, date, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($awards as $index => $award) {
            $stmt->execute([
                $resume_id,
                $award['title'],
                $award['issuer'] ?? '',
                $award['date'] ?? '',
                $award['description'] ?? '',
                $index
            ]);
        }
    }

    private function loadLanguages($resume_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM languages WHERE resume_id = ? ORDER BY sort_order, id");
        $stmt->execute([$resume_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function saveLanguages($resume_id, $languages) {
        $this->pdo->prepare("DELETE FROM languages WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO languages (resume_id, language, proficiency, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($languages as $index => $lang) {
            $stmt->execute([
                $resume_id,
                $lang['language'],
                $lang['proficiency'] ?? '',
                $index
            ]);
        }
    }

    private function loadInterests($resume_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM interests WHERE resume_id = ? ORDER BY sort_order, id");
        $stmt->execute([$resume_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function saveInterests($resume_id, $interests) {
        $this->pdo->prepare("DELETE FROM interests WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO interests (resume_id, interest, sort_order) VALUES (?, ?, ?)");
        foreach ($interests as $index => $interest) {
            if (trim($interest)) {
                $stmt->execute([$resume_id, $interest, $index]);
            }
        }
    }

    private function loadActiveSections($resume_id) {
        $stmt = $this->pdo->prepare("SELECT section_type FROM resume_sections WHERE resume_id = ? AND is_active = true");
        $stmt->execute([$resume_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function saveActiveSections($resume_id, $active_sections) {
        $this->pdo->prepare("DELETE FROM resume_sections WHERE resume_id = ?")->execute([$resume_id]);
        $stmt = $this->pdo->prepare("INSERT INTO resume_sections (resume_id, section_type, is_active) VALUES (?, ?, true) ON CONFLICT (resume_id, section_type) DO UPDATE SET is_active = true");
        foreach ($active_sections as $section_type) {
            $stmt->execute([$resume_id, $section_type]);
        }
    }
}
?>
