-- Migration SQL for Multi-Resume Support with Dynamic Sections
-- Run this file to upgrade the database schema

-- 1. Create resumes table to support multiple resumes per user
CREATE TABLE IF NOT EXISTS resumes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL DEFAULT 'My Resume',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Add resume_id to existing resume_profile table
ALTER TABLE resume_profile
ADD COLUMN IF NOT EXISTS resume_id INTEGER REFERENCES resumes(id) ON DELETE CASCADE;

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_resume_profile_resume_id ON resume_profile(resume_id);

-- 3. Add resume_id to skills table
ALTER TABLE skills
ADD COLUMN IF NOT EXISTS resume_id INTEGER REFERENCES resumes(id) ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS idx_skills_resume_id ON skills(resume_id);

-- 4. Add resume_id to projects table
ALTER TABLE projects
ADD COLUMN IF NOT EXISTS resume_id INTEGER REFERENCES resumes(id) ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS idx_projects_resume_id ON projects(resume_id);

-- 5. Add resume_id to organizations table
ALTER TABLE organizations
ADD COLUMN IF NOT EXISTS resume_id INTEGER REFERENCES resumes(id) ON DELETE CASCADE;

CREATE INDEX IF NOT EXISTS idx_organizations_resume_id ON organizations(resume_id);

-- 6. Create education table (optional section)
CREATE TABLE IF NOT EXISTS education (
    id SERIAL PRIMARY KEY,
    resume_id INTEGER NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
    institution VARCHAR(255) NOT NULL,
    degree VARCHAR(255),
    field_of_study VARCHAR(255),
    start_date VARCHAR(50),
    end_date VARCHAR(50),
    description TEXT,
    sort_order INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_education_resume_id ON education(resume_id);

-- 7. Create work_experience table (optional section)
CREATE TABLE IF NOT EXISTS work_experience (
    id SERIAL PRIMARY KEY,
    resume_id INTEGER NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
    company VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    start_date VARCHAR(50),
    end_date VARCHAR(50),
    description TEXT,
    sort_order INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_work_experience_resume_id ON work_experience(resume_id);

-- 8. Create certifications table (optional section)
CREATE TABLE IF NOT EXISTS certifications (
    id SERIAL PRIMARY KEY,
    resume_id INTEGER NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    issuer VARCHAR(255),
    issue_date VARCHAR(50),
    credential_id VARCHAR(255),
    sort_order INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_certifications_resume_id ON certifications(resume_id);

-- 9. Create awards table (optional section)
CREATE TABLE IF NOT EXISTS awards (
    id SERIAL PRIMARY KEY,
    resume_id INTEGER NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    issuer VARCHAR(255),
    date VARCHAR(50),
    description TEXT,
    sort_order INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_awards_resume_id ON awards(resume_id);

-- 10. Create languages table (optional section)
CREATE TABLE IF NOT EXISTS languages (
    id SERIAL PRIMARY KEY,
    resume_id INTEGER NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
    language VARCHAR(100) NOT NULL,
    proficiency VARCHAR(50),
    sort_order INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_languages_resume_id ON languages(resume_id);

-- 11. Create interests table (optional section)
CREATE TABLE IF NOT EXISTS interests (
    id SERIAL PRIMARY KEY,
    resume_id INTEGER NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
    interest VARCHAR(255) NOT NULL,
    sort_order INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_interests_resume_id ON interests(resume_id);

-- 12. Create resume_sections table to track active sections
CREATE TABLE IF NOT EXISTS resume_sections (
    id SERIAL PRIMARY KEY,
    resume_id INTEGER NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
    section_type VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    UNIQUE(resume_id, section_type)
);

CREATE INDEX IF NOT EXISTS idx_resume_sections_resume_id ON resume_sections(resume_id);

-- 13. Migrate existing data from user_id = 1 to a new resume
-- This creates a resume for the existing data and links it
DO $$
DECLARE
    existing_resume_id INTEGER;
BEGIN
    -- Check if there's existing data for user_id = 1
    IF EXISTS (SELECT 1 FROM resume_profile WHERE user_id = 1) THEN
        -- Create a resume for the existing data
        INSERT INTO resumes (user_id, title, created_at, updated_at)
        VALUES (1, 'Original Resume', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        RETURNING id INTO existing_resume_id;

        -- Update resume_profile to link to the new resume
        UPDATE resume_profile SET resume_id = existing_resume_id WHERE user_id = 1;

        -- Update skills
        UPDATE skills SET resume_id = existing_resume_id WHERE user_id = 1;

        -- Update projects
        UPDATE projects SET resume_id = existing_resume_id WHERE user_id = 1;

        -- Update organizations
        UPDATE organizations SET resume_id = existing_resume_id WHERE user_id = 1;

        RAISE NOTICE 'Migrated existing data to resume ID: %', existing_resume_id;
    END IF;
END $$;

-- 14. Add trigger to update updated_at timestamp on resumes
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_resumes_updated_at BEFORE UPDATE ON resumes
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Migration complete
-- Note: The application now supports multiple resumes per user with dynamic sections
