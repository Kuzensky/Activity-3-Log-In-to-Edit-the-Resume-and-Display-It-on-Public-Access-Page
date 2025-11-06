# Resume Management System

A multi-resume management application with dynamic sections, profile pictures, and drag-and-drop functionality built with PHP and PostgreSQL.

---

## 📁 File Structure & Explanation

### **Root PHP Files**

#### **Entry & Navigation**
- **`index.php`**
  - Entry point of the application
  - Redirects to dashboard.php
  - Simple redirect logic

#### **Authentication**
- **`login.php`**
  - User login page
  - Validates credentials against database
  - Creates user session on successful login
  - Any registered user can login and manage all resumes

- **`register.php`**
  - User registration page
  - Creates new user accounts
  - Hashes passwords with `password_hash()`
  - Validates email uniqueness

- **`logout.php`**
  - Destroys user session
  - Redirects to login page
  - Simple logout handler

#### **Resume Management Pages**
- **`dashboard.php`**
  - Main landing page after login
  - Displays all resumes in a grid layout
  - Shows resume cards with owner name, summary preview, and actions
  - Features:
    - Create new resume button
    - View, Edit, Delete actions per resume
    - Custom delete confirmation modal
    - Public access (anyone can view)
    - Responsive grid layout

- **`view_resume.php`**
  - Displays formatted resume
  - Shows profile picture (2x2 inches when printed)
  - Professional resume layout with sections:
    - Header with name, contact info, and photo
    - Summary (justified text)
    - Technical Skills
    - Projects
    - Organizations
    - Dynamic sections (Education, Work, etc.)
  - Features:
    - Print button for PDF export
    - Edit button (when logged in)
    - Back to dashboard button
    - Responsive design

- **`edit_resume.php`**
  - Resume editor with rich functionality
  - Features:
    - Profile picture upload (with live preview)
    - Basic info fields (name, email, phone, location, summary)
    - Dynamic section management (add/remove sections)
    - Drag-and-drop section reordering
    - Skills editor with tags
    - Projects with multiple details
    - Organizations list
    - Dynamic sections: Education, Work Experience, Certifications, Awards, Languages, Interests
  - Auto-saves to database
  - Validates required fields

#### **Action Handlers**
- **`quick_create.php`**
  - Creates a new blank resume
  - Assigns resume to logged-in user
  - Automatically reuses deleted resume IDs (fills gaps)
  - Redirects to edit page
  - No user interface - pure backend logic

- **`delete_resume.php`**
  - Deletes a resume and all associated data
  - Uses CASCADE delete (removes all related records)
  - Redirects to dashboard with success/error message
  - No user interface - pure backend handler

- **`upload_profile_picture.php`**
  - Handles profile picture uploads via AJAX
  - Validates image type (JPG, PNG, GIF)
  - Validates file size (max 5MB)
  - Stores images in `uploads/` folder
  - Removes old profile picture when uploading new one
  - Supports picture removal
  - Returns JSON responses

#### **Backend Core**
- **`db.php`**
  - Database connection configuration
  - Connects to PostgreSQL using PDO
  - Returns `$pdo` object for database operations
  - Error handling for connection failures

- **`resume_db.php`**
  - Backward compatibility wrapper
  - Provides procedural functions that call ResumeController methods
  - Functions:
    - `get_all_resumes($pdo)` - Get all resumes
    - `get_user_resumes($pdo, $user_id)` - Get user's resumes
    - `create_resume($pdo, $user_id, $title)` - Create new resume
    - `delete_resume($pdo, $resume_id)` - Delete resume
    - `load_resume_data($pdo, $resume_id)` - Load resume data
    - `save_resume_data($pdo, $resume_id, $data)` - Save resume data
  - Allows old code to work with new controller architecture
  - Can be removed if all pages are refactored to use ResumeController directly

---

### **📂 Folders**

#### **`controllers/`**
- **`ResumeController.php`**
  - Main business logic class for resume operations
  - Object-oriented database operations
  - Methods:
    - `getAllResumes()` - Fetch all resumes with owner info
    - `getUserResumes($user_id)` - Get resumes for specific user
    - `getResumeInfo($resume_id)` - Get basic resume info
    - `createResume($user_id, $title)` - Create new resume (reuses deleted IDs)
    - `deleteResume($resume_id)` - Delete resume with CASCADE
    - `updateResumeTitle($resume_id, $title)` - Update title
    - `loadResumeData($resume_id)` - Load complete resume data
    - `saveResumeData($resume_id, $data)` - Save all resume sections
    - Private helper methods for each section type
  - Uses transactions for data integrity
  - Comprehensive error handling

#### **`css/`**
- **`style.css`**
  - Global styles for the application
  - CSS variables for theming (colors, spacing, shadows)
  - Styles for:
    - Authentication pages (login/register)
    - Resume container and formatting
    - Buttons and forms
    - Animations and transitions
    - Responsive breakpoints

- **`dashboard.css`**
  - Dashboard-specific styles
  - Styles for:
    - Dashboard header and layout
    - Resume grid and cards
    - Action buttons
    - Empty state
    - Delete confirmation modal
    - Responsive design

- **`edit_resume.css`**
  - Edit page specific styles
  - Styles for:
    - Edit form layouts
    - Dynamic sections
    - Drag-and-drop indicators
    - Skills tag editor
    - Projects and organizations editors
    - Section selector modal
    - Profile picture upload preview
    - Two-column grids
    - Mobile responsiveness

#### **`js/`**
- **`edit_resume.js`**
  - Client-side functionality for resume editor
  - Features:
    - Skills tag management (add/remove)
    - Projects management (add/remove/edit details)
    - Organizations list management
    - Dynamic section addition/removal
    - Drag-and-drop section reordering
    - Form data collection and JSON serialization
    - Section selector modal
    - Profile picture upload with preview
    - Education, Work, Certifications, Awards, Languages, Interests editors
  - Form validation before submission

#### **`uploads/`**
- Stores profile picture uploads
- Naming format: `profile_{resume_id}_{timestamp}.{ext}`
- Created automatically if doesn't exist
- Contains uploaded images (JPG, PNG, GIF)

#### **`sql/`**
- **`migration.sql`**
  - Database schema definition
  - Creates all tables:
    - `users` - User accounts with hashed passwords
    - `resumes` - Resume records (links to users)
    - `resume_profile` - Contact info, summary, profile picture
    - `skills` - Technical skills by category
    - `projects` / `project_details` - Projects with details
    - `organizations` - Organization memberships
    - `education` - Educational background
    - `work_experience` - Work history
    - `certifications` - Professional certifications
    - `awards` - Awards and honors
    - `languages` - Language proficiency
    - `interests` - Personal interests
    - `resume_sections` - Tracks active sections per resume
  - Sets up CASCADE delete relationships
  - Already applied to database

---

## 🚀 Features

### **User Management**
- User registration and login
- Password hashing with bcrypt
- Session-based authentication
- Any logged-in user can manage all resumes

### **Resume Management**
- Create multiple resumes per user
- View all resumes in dashboard
- Edit resume with rich editor
- Delete resumes with confirmation modal
- Automatic ID reuse (fills gaps from deleted resumes)

### **Profile Pictures**
- Upload profile pictures (JPG, PNG, GIF)
- Live preview in editor
- Automatic image cropping to square (2x2)
- Remove/replace pictures
- Displays in resume view

### **Dynamic Sections**
- Add sections on demand:
  - Education
  - Work Experience
  - Certifications
  - Awards & Honors
  - Languages
  - Interests
- Remove sections when not needed
- Drag-and-drop to reorder sections

### **Built-in Sections**
- Profile (Name, Email, Phone, Location, Summary)
- Technical Skills (Programming, Database, Tools)
- Projects (with multiple detail points)
- Organizations

### **User Experience**
- Responsive design (mobile-friendly)
- Drag-and-drop section reordering
- Tag-based skills editor
- Modal dialogs for confirmations
- Live preview in editor
- Print-friendly resume view
- Justified text in summary

---

## 🛠️ Technology Stack

- **Backend**: PHP 8+
- **Database**: PostgreSQL 17+
- **Frontend**: Vanilla JavaScript, CSS3
- **Architecture**: MVC-inspired with OOP controllers
- **Authentication**: Session-based with password hashing
- **File Upload**: Native PHP file handling

---

## 📊 Database Schema

### **Key Relationships**
```
users (1) → (*) resumes (1) → (*) resume_data_tables
```

- **users** → **resumes**: One user can have many resumes
- **resumes** → **all data tables**: One resume has many data records
- **CASCADE DELETE**: Deleting a resume removes all associated data

### **Tables**
| Table | Purpose |
|-------|---------|
| `users` | User accounts |
| `resumes` | Resume records |
| `resume_profile` | Contact info, summary, profile picture |
| `skills` | Technical skills by category |
| `projects` | Project entries |
| `project_details` | Project detail points |
| `organizations` | Organization memberships |
| `education` | Educational background |
| `work_experience` | Work history |
| `certifications` | Professional certifications |
| `awards` | Awards and honors |
| `languages` | Language proficiency |
| `interests` | Personal interests |
| `resume_sections` | Tracks which sections are active |

---

## 🎯 Usage Flow

### **1. User Registration/Login**
```
register.php → Creates account → login.php → Creates session → dashboard.php
```

### **2. Create Resume**
```
dashboard.php → Click "Add Resume" → quick_create.php → Creates blank resume → edit_resume.php
```

### **3. Edit Resume**
```
edit_resume.php → Fill in data → Add sections → Drag to reorder → Save → view_resume.php
```

### **4. View Resume**
```
dashboard.php → Click "View" → view_resume.php → Print/Edit options
```

### **5. Delete Resume**
```
dashboard.php → Click delete icon → Confirmation modal → delete_resume.php → Back to dashboard
```

---

## 🔧 Key Implementation Details

### **ID Reuse System**
- When a resume is deleted, its ID becomes available
- Next created resume uses the lowest available ID
- Prevents gaps: If IDs 1,3,4 exist, next will be 2
- Implemented in `ResumeController::createResume()`

### **Profile Picture Storage**
- Images stored in `uploads/` folder
- Path saved in database as relative: `uploads/profile_1_123456789.jpg`
- Old picture deleted when new one uploaded
- Square cropping with CSS: `object-fit: cover`

### **Drag and Drop**
- Uses HTML5 Drag and Drop API
- Sections have `draggable="true"` attribute
- Visual feedback during drag (.dragging, .drag-over classes)
- Smart reordering based on drag direction
- Works with dynamically added sections

### **Data Flow**
```
edit_resume.php (UI) → edit_resume.js (Collect data) → POST to edit_resume.php
→ resume_db.php (Wrapper) → ResumeController.php (Logic) → Database
```

---

## 📝 Notes

- **Public Access**: All resumes are viewable by anyone (no resume-level privacy)
- **User Permissions**: Any logged-in user can edit/delete any resume
- **Database Connection**: Configure in `db.php` (PostgreSQL credentials)
- **File Uploads**: Requires write permissions on `uploads/` folder
- **Browser Compatibility**: Modern browsers with HTML5 support

---

## 🔮 Future Improvements

- [ ] User-level privacy controls (private resumes)
- [ ] Resume templates/themes
- [ ] Export to PDF (server-side)
- [ ] Resume versioning/history
- [ ] Search and filter resumes
- [ ] Tags/categories for resumes
- [ ] Role-based permissions
- [ ] API for external integrations

---

## 👨‍💻 Development

### **File Naming Conventions**
- Pages: `lowercase_with_underscores.php`
- Classes: `PascalCase.php`
- Folders: `lowercase/`

### **Code Style**
- PSR-12 inspired PHP code style
- 4-space indentation
- Camel case for JavaScript
- Snake case for PHP variables
- Descriptive function/variable names

### **Database Operations**
- Always use prepared statements (PDO)
- Use transactions for multi-step operations
- Handle errors gracefully with try-catch
- Use CASCADE for foreign key deletes

---

---

## 🎓 Presentation Guide for Professors

### **Code Tour - What to Show**

#### **1. Database Architecture (5 minutes)**
**File**: `sql/migration.sql`
- Show the multi-resume schema design
- Explain CASCADE delete relationships
- Highlight foreign key constraints

**Key Points**:
- "One user can have many resumes"
- "All resume data uses resume_id, not user_id"
- "Deleting a resume automatically removes all associated data"

#### **2. Object-Oriented Design (5 minutes)**
**File**: `controllers/ResumeController.php`
- Show the class structure
- Explain the ID reuse algorithm (lines 160-229)
- Demonstrate prepared statements for security

**Key Points**:
- "Uses PDO for database security"
- "Implements ID reuse to prevent gaps"
- "All operations use transactions for data integrity"

#### **3. Frontend Interactivity (5 minutes)**
**File**: `js/edit_resume.js`
- Show DOM manipulation (lines 96-128)
- Explain HTML5 Drag and Drop (lines 493-577)
- Demonstrate AJAX file upload (lines 715-859)

**Key Points**:
- "Vanilla JavaScript - no frameworks needed"
- "Dynamic content creation without page refresh"
- "Security: All user input is escaped to prevent XSS"

#### **4. Features Demo (5 minutes)**
**Live Demo**:
1. Navigate to dashboard - show all resumes
2. Create new resume - show ID reuse working
3. Edit resume - show drag-and-drop sections
4. Upload profile picture - show live preview
5. View resume - show print-ready format
6. Delete resume - show custom modal

### **Technical Concepts to Highlight**

#### **Security**
- ✅ Prepared statements (SQL injection prevention)
- ✅ Password hashing with `password_hash()`
- ✅ Session-based authentication
- ✅ XSS prevention with `escapeHtml()`
- ✅ File upload validation (type, size)

#### **Database Design**
- ✅ Normalized schema (3NF)
- ✅ Foreign key relationships
- ✅ CASCADE delete for data integrity
- ✅ Indexing on frequently queried columns
- ✅ Smart ID management

#### **User Experience**
- ✅ Responsive design (mobile-friendly)
- ✅ Drag-and-drop interface
- ✅ Live preview (no page refresh)
- ✅ Modal dialogs (better UX than alerts)
- ✅ Form validation

#### **Code Quality**
- ✅ MVC-inspired architecture
- ✅ Separation of concerns
- ✅ Comprehensive inline documentation
- ✅ Consistent naming conventions
- ✅ Error handling and logging
---

**Last Updated**: November 6, 2025
**Version**: 2.0
**Author**: Christian B. Nayre
