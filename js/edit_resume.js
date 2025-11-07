
let currentCategory = '';  // Tracks which skill category is being edited (programming, database, tools)


function openModal(category, categoryName) {
    // Store which category we're adding to (needed when confirming)
    currentCategory = category;

    // Update modal title dynamically (e.g., "Add Programming Language")
    document.getElementById('modalTitle').textContent = `Add ${categoryName}`;

    // Clear previous input
    document.getElementById('modalInput').value = '';

    // Show the modal (CSS handles the visual transition)
    document.getElementById('skillModal').classList.add('active');

    // Auto-focus input field so user can start typing immediately
    document.getElementById('modalInput').focus();
}

function closeModal() {
    // Hide modal by removing 'active' class
    document.getElementById('skillModal').classList.remove('active');

    // Reset category tracking
    currentCategory = '';
}


function confirmAddSkill() {
    // Get skill name and remove extra whitespace
    const skillName = document.getElementById('modalInput').value.trim();

    // Validation: Don't allow empty skills
    if (skillName === '') {
        alert('Please enter a skill name');
        return;
    }

    // Find the container for this category
    const container = document.getElementById(currentCategory + '-container');

    // Find the "+" button so we can insert before it
    const addButton = container.querySelector('.add-skill-icon');

    // Create new skill tag element
    const skillTag = document.createElement('div');
    skillTag.className = 'skill-tag-edit';

    // Set inner HTML with skill name and remove button
    // escapeHtml() prevents malicious code injection
    skillTag.innerHTML = `
        <span>${escapeHtml(skillName)}</span>
        <button type="button" class="remove-skill-btn" onclick="removeSkill(this)">×</button>
    `;

    // Insert the new tag before the "+" button
    // This keeps the "+" button always at the end
    container.insertBefore(skillTag, addButton);

    // Close the modal
    closeModal();
}

function removeSkill(button) {
    button.parentElement.remove();
}

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
                <div class="project-title-text">${escapeHtml(title)}</div>
                <div class="project-meta">${escapeHtml(type)} | ${escapeHtml(year)}</div>
            </div>
            <button type="button" class="remove-project-btn" onclick="removeProject(this)">×</button>
        </div>
        <ul class="project-details-list">
            ${detailsArray.map(detail => `<li>${escapeHtml(detail)}</li>`).join('')}
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
        <span>${escapeHtml(orgName)}</span>
        <button type="button" class="remove-org-btn" onclick="removeOrg(this)">×</button>
    `;

    const addButton = document.querySelector('#org-container .add-org-icon');
    document.getElementById('org-container').insertBefore(orgTag, addButton);
    closeOrgModal();
}

function removeOrg(button) {
    button.parentElement.remove();
}

// NEW DYNAMIC SECTIONS FUNCTIONALITY

// Section Selector Modal
function openSectionSelectorModal() {
    updateSectionOptionsAvailability();
    document.getElementById('sectionSelectorModal').classList.add('active');
}

function closeSectionSelectorModal() {
    document.getElementById('sectionSelectorModal').classList.remove('active');
}

function updateSectionOptionsAvailability() {
    const sections = ['skills', 'projects', 'organizations', 'education', 'work_experience', 'certifications', 'awards', 'languages', 'interests'];
    sections.forEach(section => {
        const sectionElement = document.getElementById(`section-${section}`);
        const optionElement = document.querySelector(`.section-option[data-section="${section}"]`);

        if (sectionElement) {
            // Section already exists, disable the option
            optionElement.style.opacity = '0.5';
            optionElement.style.pointerEvents = 'none';
            optionElement.style.cursor = 'not-allowed';
        } else {
            // Section doesn't exist, enable the option
            optionElement.style.opacity = '1';
            optionElement.style.pointerEvents = 'auto';
            optionElement.style.cursor = 'pointer';
        }
    });
}

// Add Section
function addSection(sectionType) {
    // Check if section already exists
    if (document.getElementById(`section-${sectionType}`)) {
        alert('This section has already been added!');
        return;
    }

    const container = document.getElementById('dynamic-sections-container');
    const sectionHTML = getSectionHTML(sectionType);

    const sectionDiv = document.createElement('div');
    sectionDiv.className = 'dynamic-section';
    sectionDiv.setAttribute('data-section', sectionType);
    sectionDiv.setAttribute('draggable', 'true');
    sectionDiv.id = `section-${sectionType}`;
    sectionDiv.innerHTML = sectionHTML;

    container.appendChild(sectionDiv);
    closeSectionSelectorModal();
}

// Remove Section
function removeSection(sectionType) {
    if (confirm('Are you sure you want to remove this section?')) {
        const section = document.getElementById(`section-${sectionType}`);
        if (section) {
            section.remove();
        }
    }
}

// Remove Item (generic for all edit items)
function removeItem(button) {
    button.closest('[class$="-edit"]').remove();
}

// Get Section HTML Templates
function getSectionHTML(sectionType) {
    const templates = {
        education: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Education</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('education')">Remove Section</button>
            </div>
            <div id="education-container"></div>
            <button type="button" class="add-item-btn" onclick="addEducation()">+ Add Education</button>
        `,
        work_experience: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Work Experience</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('work_experience')">Remove Section</button>
            </div>
            <div id="work-container"></div>
            <button type="button" class="add-item-btn" onclick="addWorkExperience()">+ Add Work Experience</button>
        `,
        certifications: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Certifications</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('certifications')">Remove Section</button>
            </div>
            <div id="cert-container"></div>
            <button type="button" class="add-item-btn" onclick="addCertification()">+ Add Certification</button>
        `,
        awards: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Awards & Honors</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('awards')">Remove Section</button>
            </div>
            <div id="awards-container"></div>
            <button type="button" class="add-item-btn" onclick="addAward()">+ Add Award</button>
        `,
        languages: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Languages</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('languages')">Remove Section</button>
            </div>
            <div id="languages-container"></div>
            <button type="button" class="add-item-btn" onclick="addLanguage()">+ Add Language</button>
        `,
        interests: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Interests</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('interests')">Remove Section</button>
            </div>
            <div class="interests-tags-container" id="interests-container">
                <button type="button" class="add-interest-icon" onclick="openInterestModal()">+</button>
            </div>
        `,
        skills: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Technical Skills</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('skills')">Remove Section</button>
            </div>
            <div class="skills-editor">
                <div class="skills-category-editor">
                    <div class="category-header">Programming Languages</div>
                    <div class="skills-tags-container" id="programming-container">
                        <button type="button" class="add-skill-icon" onclick="openModal('programming', 'Programming Language')">+</button>
                    </div>
                </div>
                <div class="skills-category-editor">
                    <div class="category-header">Database</div>
                    <div class="skills-tags-container" id="database-container">
                        <button type="button" class="add-skill-icon" onclick="openModal('database', 'Database')">+</button>
                    </div>
                </div>
                <div class="skills-category-editor">
                    <div class="category-header">Tools</div>
                    <div class="skills-tags-container" id="tools-container">
                        <button type="button" class="add-skill-icon" onclick="openModal('tools', 'Tool')">+</button>
                    </div>
                </div>
            </div>
        `,
        projects: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Projects</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('projects')">Remove Section</button>
            </div>
            <div id="projects-container"></div>
            <button type="button" class="add-project-btn" onclick="openProjectModal()">+ Add Project</button>
        `,
        organizations: `
            <div class="section-header-with-remove">
                <span class="drag-handle" title="Drag to reorder">⋮⋮</span>
                <label>Organizations</label>
                <button type="button" class="btn-remove-section" onclick="removeSection('organizations')">Remove Section</button>
            </div>
            <div class="org-container" id="org-container">
                <button type="button" class="add-org-icon" onclick="openOrgModal()">+</button>
            </div>
        `
    };

    return templates[sectionType] || '';
}

// Add Education Entry
function addEducation() {
    const container = document.getElementById('education-container');
    const eduItem = document.createElement('div');
    eduItem.className = 'edu-item-edit';
    eduItem.innerHTML = `
        <input type="text" class="edu-institution" placeholder="Institution">
        <div class="two-col">
            <input type="text" class="edu-degree" placeholder="Degree">
            <input type="text" class="edu-field" placeholder="Field of Study">
        </div>
        <div class="two-col">
            <input type="text" class="edu-start" placeholder="Start Date">
            <input type="text" class="edu-end" placeholder="End Date">
        </div>
        <textarea class="edu-description" placeholder="Description (optional)"></textarea>
        <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
    `;
    container.appendChild(eduItem);
}

// Add Work Experience Entry
function addWorkExperience() {
    const container = document.getElementById('work-container');
    const workItem = document.createElement('div');
    workItem.className = 'work-item-edit';
    workItem.innerHTML = `
        <input type="text" class="work-position" placeholder="Position">
        <input type="text" class="work-company" placeholder="Company">
        <div class="two-col">
            <input type="text" class="work-start" placeholder="Start Date">
            <input type="text" class="work-end" placeholder="End Date">
        </div>
        <textarea class="work-description" placeholder="Description"></textarea>
        <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
    `;
    container.appendChild(workItem);
}

// Add Certification Entry
function addCertification() {
    const container = document.getElementById('cert-container');
    const certItem = document.createElement('div');
    certItem.className = 'cert-item-edit';
    certItem.innerHTML = `
        <input type="text" class="cert-name" placeholder="Certification Name">
        <input type="text" class="cert-issuer" placeholder="Issuer">
        <div class="two-col">
            <input type="text" class="cert-date" placeholder="Issue Date">
            <input type="text" class="cert-credential" placeholder="Credential ID (optional)">
        </div>
        <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
    `;
    container.appendChild(certItem);
}

// Add Award Entry
function addAward() {
    const container = document.getElementById('awards-container');
    const awardItem = document.createElement('div');
    awardItem.className = 'award-item-edit';
    awardItem.innerHTML = `
        <input type="text" class="award-title" placeholder="Award Title">
        <div class="two-col">
            <input type="text" class="award-issuer" placeholder="Issuer">
            <input type="text" class="award-date" placeholder="Date">
        </div>
        <textarea class="award-description" placeholder="Description (optional)"></textarea>
        <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
    `;
    container.appendChild(awardItem);
}

// Add Language Entry
function addLanguage() {
    const container = document.getElementById('languages-container');
    const langItem = document.createElement('div');
    langItem.className = 'lang-item-edit';
    langItem.innerHTML = `
        <input type="text" class="lang-name" placeholder="Language">
        <input type="text" class="lang-proficiency" placeholder="Proficiency (e.g., Native, Fluent)">
        <button type="button" class="btn-remove-item" onclick="removeItem(this)">Remove</button>
    `;
    container.appendChild(langItem);
}

// Interests Functions
function openInterestModal() {
    document.getElementById('interestInput').value = '';
    document.getElementById('interestModal').classList.add('active');
    document.getElementById('interestInput').focus();
}

function closeInterestModal() {
    document.getElementById('interestModal').classList.remove('active');
}

function confirmAddInterest() {
    const interest = document.getElementById('interestInput').value.trim();

    if (!interest) {
        alert('Please enter an interest');
        return;
    }

    const interestTag = document.createElement('div');
    interestTag.className = 'interest-tag-edit';
    interestTag.innerHTML = `
        <span>${escapeHtml(interest)}</span>
        <button type="button" class="remove-interest-btn" onclick="removeInterest(this)">×</button>
    `;

    const addButton = document.querySelector('#interests-container .add-interest-icon');
    document.getElementById('interests-container').insertBefore(interestTag, addButton);
    closeInterestModal();
}

function removeInterest(button) {
    button.parentElement.remove();
}

// UTILITY FUNCTIONS

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// EVENT LISTENERS

// Allow Enter key in modals
document.getElementById('modalInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        confirmAddSkill();
    }
});

document.getElementById('orgInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        confirmAddOrg();
    }
});

document.getElementById('interestInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        confirmAddInterest();
    }
});

// Close modals when clicking outside
document.getElementById('skillModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('projectModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeProjectModal();
});

document.getElementById('orgModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeOrgModal();
});

document.getElementById('interestModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeInterestModal();
});

document.getElementById('sectionSelectorModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeSectionSelectorModal();
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeProjectModal();
        closeOrgModal();
        closeInterestModal();
        closeSectionSelectorModal();
    }
});

// DRAG AND DROP FUNCTIONALITY FOR SECTIONS

let draggedElement = null;

// Initialize drag and drop for all dynamic sections
function initializeDragAndDrop() {
    const container = document.getElementById('dynamic-sections-container');
    const sections = container.querySelectorAll('.dynamic-section');

    sections.forEach(section => {
        section.addEventListener('dragstart', handleDragStart);
        section.addEventListener('dragend', handleDragEnd);
        section.addEventListener('dragover', handleDragOver);
        section.addEventListener('drop', handleDrop);
        section.addEventListener('dragenter', handleDragEnter);
        section.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');

    // Remove drag-over class from all sections
    document.querySelectorAll('.dynamic-section').forEach(section => {
        section.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDragEnter(e) {
    if (this !== draggedElement) {
        this.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }

    if (draggedElement !== this) {
        const container = document.getElementById('dynamic-sections-container');
        const allSections = [...container.querySelectorAll('.dynamic-section')];

        const draggedIndex = allSections.indexOf(draggedElement);
        const targetIndex = allSections.indexOf(this);

        if (draggedIndex < targetIndex) {
            this.parentNode.insertBefore(draggedElement, this.nextSibling);
        } else {
            this.parentNode.insertBefore(draggedElement, this);
        }
    }

    this.classList.remove('drag-over');
    return false;
}

// Initialize drag and drop on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeDragAndDrop();
});

// Re-initialize when new sections are added
const originalAddSection = addSection;
addSection = function(sectionType) {
    originalAddSection(sectionType);
    setTimeout(initializeDragAndDrop, 100);
};

// FORM SUBMISSION HANDLER

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

    // Collect education
    const education = [];
    document.querySelectorAll('#education-container .edu-item-edit').forEach(item => {
        education.push({
            institution: item.querySelector('.edu-institution')?.value || '',
            degree: item.querySelector('.edu-degree')?.value || '',
            field_of_study: item.querySelector('.edu-field')?.value || '',
            start_date: item.querySelector('.edu-start')?.value || '',
            end_date: item.querySelector('.edu-end')?.value || '',
            description: item.querySelector('.edu-description')?.value || ''
        });
    });

    document.getElementById('education_json').value = JSON.stringify(education);

    // Collect work experience
    const work_experience = [];
    document.querySelectorAll('#work-container .work-item-edit').forEach(item => {
        work_experience.push({
            position: item.querySelector('.work-position')?.value || '',
            company: item.querySelector('.work-company')?.value || '',
            start_date: item.querySelector('.work-start')?.value || '',
            end_date: item.querySelector('.work-end')?.value || '',
            description: item.querySelector('.work-description')?.value || ''
        });
    });

    document.getElementById('work_experience_json').value = JSON.stringify(work_experience);

    // Collect certifications
    const certifications = [];
    document.querySelectorAll('#cert-container .cert-item-edit').forEach(item => {
        certifications.push({
            name: item.querySelector('.cert-name')?.value || '',
            issuer: item.querySelector('.cert-issuer')?.value || '',
            issue_date: item.querySelector('.cert-date')?.value || '',
            credential_id: item.querySelector('.cert-credential')?.value || ''
        });
    });

    document.getElementById('certifications_json').value = JSON.stringify(certifications);

    // Collect awards
    const awards = [];
    document.querySelectorAll('#awards-container .award-item-edit').forEach(item => {
        awards.push({
            title: item.querySelector('.award-title')?.value || '',
            issuer: item.querySelector('.award-issuer')?.value || '',
            date: item.querySelector('.award-date')?.value || '',
            description: item.querySelector('.award-description')?.value || ''
        });
    });

    document.getElementById('awards_json').value = JSON.stringify(awards);

    // Collect languages
    const languages = [];
    document.querySelectorAll('#languages-container .lang-item-edit').forEach(item => {
        languages.push({
            language: item.querySelector('.lang-name')?.value || '',
            proficiency: item.querySelector('.lang-proficiency')?.value || ''
        });
    });

    document.getElementById('languages_json').value = JSON.stringify(languages);

    // Collect interests
    const interests = [];
    document.querySelectorAll('#interests-container .interest-tag-edit span').forEach(span => {
        interests.push(span.textContent);
    });

    document.getElementById('interests_json').value = JSON.stringify(interests);

    // Collect active sections
    const active_sections = [];
    document.querySelectorAll('.dynamic-section').forEach(section => {
        const sectionType = section.getAttribute('data-section');
        if (sectionType) {
            active_sections.push(sectionType);
        }
    });

    document.getElementById('active_sections_json').value = JSON.stringify(active_sections);
});

// PROFILE PICTURE UPLOAD

// Get resume ID from URL
const urlParams = new URLSearchParams(window.location.search);
const resumeId = urlParams.get('id');

// Handle profile picture file selection
document.getElementById('profilePictureInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file');
        return;
    }

    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        return;
    }

    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('profilePicturePreview');
        const placeholder = document.getElementById('profilePicturePlaceholder');

        if (preview) {
            preview.src = e.target.result;
        } else if (placeholder) {
            placeholder.outerHTML = `<img src="${e.target.result}" alt="Profile Picture" id="profilePicturePreview">`;
        }
    };
    reader.readAsDataURL(file);

    // Upload to server
    uploadProfilePicture(file);
});

// Upload profile picture to server
function uploadProfilePicture(file) {
    const formData = new FormData();
    formData.append('profile_picture', file);
    formData.append('resume_id', resumeId);

    // Show loading state
    const uploadBtn = document.querySelector('.btn-upload-picture');
    const originalText = uploadBtn.textContent;
    uploadBtn.textContent = 'Uploading...';
    uploadBtn.disabled = true;

    fetch('upload_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button text and add remove button if needed
            uploadBtn.textContent = 'Change Photo';
            uploadBtn.disabled = false;

            // Add remove button if it doesn't exist
            if (!document.querySelector('.btn-remove-picture')) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn-remove-picture';
                removeBtn.textContent = 'Remove';
                removeBtn.onclick = removeProfilePicture;
                uploadBtn.parentElement.insertBefore(removeBtn, uploadBtn.nextSibling);
            }

            // Show success message
            alert('Profile picture uploaded successfully!');
        } else {
            alert('Upload failed: ' + data.message);
            uploadBtn.textContent = originalText;
            uploadBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Upload failed. Please try again.');
        uploadBtn.textContent = originalText;
        uploadBtn.disabled = false;
    });
}

// Remove profile picture
function removeProfilePicture() {
    if (!confirm('Are you sure you want to remove your profile picture?')) {
        return;
    }

    fetch('upload_profile_picture.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `resume_id=${resumeId}&remove=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Replace image with placeholder
            const preview = document.getElementById('profilePicturePreview');
            if (preview) {
                preview.outerHTML = `<div class="profile-picture-placeholder" id="profilePicturePlaceholder"><span>No photo</span></div>`;
            }

            // Update button text and remove the remove button
            const uploadBtn = document.querySelector('.btn-upload-picture');
            uploadBtn.textContent = 'Upload Photo';

            const removeBtn = document.querySelector('.btn-remove-picture');
            if (removeBtn) {
                removeBtn.remove();
            }

            alert('Profile picture removed successfully!');
        } else {
            alert('Remove failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Remove failed. Please try again.');
    });
}
