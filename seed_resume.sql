-- Insert resume profile
INSERT INTO resume_profile (user_id, full_name, email, phone, location, summary)
VALUES (
    1,
    'Nayre, Christian B.',
    'cbnayre04@gmail.com',
    '+63 956-513-6811',
    'Darasa, Tanauan City, Batangas',
    'Computer Science student with a focus on web development and web design. Skilled in creating responsive, user-friendly websites using HTML, CSS, JavaScript, and modern frameworks. Passionate about blending creativity with technology to build engaging digital experiences, while continuously learning and adapting to new tools. Strong teamwork and problem-solving abilities with a genuine interest in contributing to innovative projects.'
)
ON CONFLICT (user_id) DO UPDATE SET
    full_name = EXCLUDED.full_name,
    email = EXCLUDED.email,
    phone = EXCLUDED.phone,
    location = EXCLUDED.location,
    summary = EXCLUDED.summary;

-- Insert programming skills
INSERT INTO skills (user_id, category, skill_name) VALUES
(1, 'programming', 'Python'),
(1, 'programming', 'Java'),
(1, 'programming', 'C#'),
(1, 'programming', 'C++'),
(1, 'programming', 'JavaScript'),
(1, 'programming', 'HTML'),
(1, 'programming', 'CSS');

-- Insert database skills
INSERT INTO skills (user_id, category, skill_name) VALUES
(1, 'database', 'MySQL'),
(1, 'database', 'PostgreSQL'),
(1, 'database', 'MongoDB');

-- Insert tools
INSERT INTO skills (user_id, category, skill_name) VALUES
(1, 'tools', 'GitHub'),
(1, 'tools', 'XAMPP'),
(1, 'tools', 'VS Code'),
(1, 'tools', 'React'),
(1, 'tools', 'Flutter');

-- Insert Project 1: FarmEase
DO $$
DECLARE
    project1_id INTEGER;
BEGIN
    INSERT INTO projects (user_id, title, type, year, sort_order)
    VALUES (1, 'FarmEase – Farmer-to-Market E-Commerce Website', 'School Project', '2025', 1)
    RETURNING id INTO project1_id;

    INSERT INTO project_details (project_id, detail_text, sort_order) VALUES
    (project1_id, 'Developed an e-commerce platform using React Framework and Tailwind CSS, deployed via Vercel.', 1),
    (project1_id, 'Implemented CRUD operations (GET, POST, UPDATE) for product management and integrated third-party authentication and database services for secure user access.', 2),
    (project1_id, 'Optimized website performance with faster image loading and responsive design for seamless user experience.', 3);
END $$;

-- Insert Project 2: UI/UX Competition
DO $$
DECLARE
    project2_id INTEGER;
BEGIN
    INSERT INTO projects (user_id, title, type, year, sort_order)
    VALUES (1, 'UI/UX Design Competition – 3rd Place', 'School Competition', '2025', 2)
    RETURNING id INTO project2_id;

    INSERT INTO project_details (project_id, detail_text, sort_order) VALUES
    (project2_id, 'Developed an e-commerce platform using React Framework and Tailwind CSS, deployed via Vercel.', 1),
    (project2_id, 'Implemented CRUD operations (GET, POST, UPDATE) for product management and integrated third-party authentication and database services for secure user access.', 2);
END $$;

-- Insert Project 3: Quiller
DO $$
DECLARE
    project3_id INTEGER;
BEGIN
    INSERT INTO projects (user_id, title, type, year, sort_order)
    VALUES (1, 'Quiller – Learning Management System', 'Capstone Project', '2023', 3)
    RETURNING id INTO project3_id;

    INSERT INTO project_details (project_id, detail_text, sort_order) VALUES
    (project3_id, 'Designed and developed a learning management system (LMS) inspired by Google Classroom using HTML, CSS, and JavaScript.', 1),
    (project3_id, 'Enabled teachers to upload lessons, resources, and activities, while allowing students to access materials, track progress, and engage with content.', 2),
    (project3_id, 'Focused on scalable database integration (conceptual planning) and clean UI/UX to deliver an intuitive digital learning environment.', 3);
END $$;

-- Insert organizations
INSERT INTO organizations (user_id, organization_name, sort_order) VALUES
(1, 'Junior Philippine Computer Society (JPCS) – Member', 1),
(1, 'Association of Computer Engineering Students and Scholars (ACCESS) – Member', 2);

-- Success message
DO $$
BEGIN
    RAISE NOTICE '========================================';
    RAISE NOTICE 'Resume data seeded successfully!';
    RAISE NOTICE '========================================';
    RAISE NOTICE 'Default public resume (User ID: 1)';
END $$;
