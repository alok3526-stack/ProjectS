# CampusHire | Student Placement Management System

A complete, modern Student Placement Management and Applicant Tracking System (ATS) built from scratch using **PHP 8+**, the official **MongoDB PHP Library (`mongodb/mongodb`)**, and **Bootstrap 5.3 CDN**.

---

## 🌟 Key Features

### 1. Unified Authentication System (`login.php`, `register.php`)
- **Role-Based Access Control**: Students, Companies, and Campus Placement Administrators.
- **Security**: Passwords hashed securely using PHP's native `password_hash()` and verified with `password_verify()`.
- **Session Management**: Session authentication guards route users automatically to their role-specific dashboard.
- **Quick Demo Fillers**: 1-click test fill buttons on the login page.

### 2. Student Portal (`/dashboard/student.php`)
- **Academic Profile Builder**: Enter CGPA, branch/department, graduation year, technical skills, and public resume/portfolio links.
- **Intelligent CGPA Job Filtering**: Real-time filtering shows only campus job drives where `student.cgpa >= job.min_cgpa`.
- **1-Click Fast Apply**: Submit applications instantly with academic credentials attached.
- **Live ATS Status Tracker**: Track application progress through stages (`Applied` &rarr; `Shortlisted` &rarr; `Hired` &rarr; `Rejected`).

### 3. Company Recruitment Portal (`/dashboard/company.php`)
- **Job Drive Publisher**: Create and post job drives with title, job description, minimum CGPA cutoff, compensation package (CTC), work location, and application deadline.
- **Drive Lifecycle**: Toggle drives active or closed.
- **Candidate Pipeline (ATS)**: Review applicant cards, view student profiles and resumes, and update hiring pipeline status: `Applied` &rarr; `Shortlisted` &rarr; `Hired` with custom recruiter notes.

### 4. Admin Governance Portal (`/dashboard/admin.php`)
- **Institutional Oversight**: View all registered students, partner companies, drives, and applications across campus.
- **Account Verification System**: Approve/verify or revoke student and company accounts with 1 click.
- **Audit Logging & Analytics**: Drive metrics, cutoff audits, and account management.

---

## 🗄️ NoSQL Database Schema (MongoDB Atlas)

The system is architected around four core collections:

1. **`users` Collection**:
   - `_id`: Document identifier
   - `name`: Full name or Company name
   - `email`: Unique email address
   - `password`: Hashed password
   - `role`: `'student'` | `'company'` | `'admin'`
   - `status`: `'approved'` | `'pending'` | `'rejected'`
   - `created_at`: Datetime string

2. **`profiles` Collection**:
   - `user_id`: Reference to student's user ID
   - `cgpa`: Academic Cumulative CGPA (float e.g., `8.2`)
   - `skills`: Comma-separated technical skills
   - `department`: Academic department / branch
   - `phone`: Contact number
   - `graduation_year`: Year of graduation
   - `resume_link`: URL to Google Drive / portfolio resume
   - `bio`: Professional career summary
   - `updated_at`: Datetime string

3. **`jobs` Collection**:
   - `company_id`: Reference to company's user ID
   - `company_name`: Name of hiring firm
   - `title`: Job designation
   - `description`: Role responsibilities and requirements
   - `min_cgpa`: Minimum CGPA threshold (float e.g., `7.5`)
   - `ctc`: Salary package (e.g. `"$115,000 / 22 LPA"`)
   - `location`: Location / Remote / Hybrid
   - `deadline`: Drive cutoff date
   - `status`: `'active'` | `'closed'`
   - `created_at`: Datetime string

4. **`applications` Collection**:
   - `job_id`: Reference to job drive
   - `student_id`: Reference to student
   - `student_name`: Student full name
   - `student_email`: Student email
   - `student_cgpa`: CGPA at time of application
   - `job_title`: Job role title
   - `company_id`: Company identifier
   - `company_name`: Company name
   - `resume_link`: Resume URL
   - `status`: `'Applied'` | `'Shortlisted'` | `'Hired'` | `'Rejected'`
   - `notes`: Recruiter feedback
   - `applied_at`: Datetime string
   - `updated_at`: Datetime string

---

## 🚀 Connecting Your MongoDB Atlas Cluster

Open [`config.php`](file:///c:/Users/admin/Desktop/ProjectS/config.php) and replace the placeholder connection string with your MongoDB Atlas cluster URI:



>

---

## 🏃 Starting the Development Server

Run the built-in PHP development server in PowerShell or CMD:

```powershell
php -S localhost:8000
```

Then open your browser and visit: **`http://localhost:8000`**

---

