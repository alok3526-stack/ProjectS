<div align="center">

# 🎓 CampusHire

**A student placement management system and applicant tracking system (ATS)**

Students apply to campus drives, companies manage candidates, and admins oversee it all.

[![Live Demo](https://img.shields.io/badge/Live_Demo-campushire--mai2.onrender.com-46E3B7?style=for-the-badge&logo=render&logoColor=white)](https://campushire-mai2.onrender.com/)

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php&logoColor=white)
![MongoDB](https://img.shields.io/badge/MongoDB-Atlas-47A248?style=flat-square&logo=mongodb&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat-square&logo=docker&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-Server-D22128?style=flat-square&logo=apache&logoColor=white)
![Render](https://img.shields.io/badge/Hosted_on-Render-46E3B7?style=flat-square&logo=render&logoColor=white)

![Last Commit](https://img.shields.io/github/last-commit/alok3526-stack/ProjectS?style=flat-square)
![Repo Size](https://img.shields.io/github/repo-size/alok3526-stack/ProjectS?style=flat-square)
![Stars](https://img.shields.io/github/stars/alok3526-stack/ProjectS?style=flat-square)
![Top Language](https://img.shields.io/github/languages/top/alok3526-stack/ProjectS?style=flat-square)

</div>

---

## 📑 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Database Schema](#-database-schema)
- [Getting Started](#-getting-started)
- [Connecting MongoDB Atlas](#-connecting-mongodb-atlas)
- [Deployment](#-deployment)
- [Troubleshooting](#-troubleshooting)
- [Security Notes](#-security-notes)
- [Author](#-author)

---

## 🔎 Overview

CampusHire replaces spreadsheets and email threads in campus placements. Each role gets its own dashboard:

| Role | What they do |
|---|---|
| 🧑‍🎓 **Student** | Build a profile, browse drives they're eligible for, apply in one click, track status |
| 🏢 **Company** | Post job drives, review applicants, move them through the hiring pipeline |
| 🛡️ **Admin** | Verify accounts and monitor drives and applications across campus |

**Try it live:** [campushire-mai2.onrender.com](https://campushire-mai2.onrender.com/)

> The login page has one-click demo fill buttons, so you can explore each role without registering.

<!--
SCREENSHOTS: add images to assets/screenshots/ and uncomment.

## 📸 Screenshots

| Student Dashboard | Company Portal | Admin Panel |
|---|---|---|
| ![Student](assets/screenshots/student.png) | ![Company](assets/screenshots/company.png) | ![Admin](assets/screenshots/admin.png) |
-->

---

## ✨ Features

### 🔐 Authentication
- Three roles: student, company, admin
- Passwords hashed with `password_hash()` and checked with `password_verify()`
- Session-based login that routes each user to their own dashboard

### 🧑‍🎓 Student Portal
- Academic profile: CGPA, department, graduation year, skills, resume link, bio
- Jobs filtered automatically by CGPA (`student.cgpa >= job.min_cgpa`)
- One-click apply
- Live status tracking: `Applied` → `Shortlisted` → `Hired` / `Rejected`

### 🏢 Company Portal
- Post drives with title, description, minimum CGPA, CTC, location, and deadline
- Open or close a drive at any time
- Review applicants with profiles and resumes
- Update pipeline status and leave recruiter notes

### 🛡️ Admin Portal
- View all students, companies, drives, and applications
- Approve or revoke accounts with one click
- Campus-wide metrics and cutoff audits

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 |
| Database | MongoDB Atlas via `mongodb/mongodb` |
| Frontend | Bootstrap 5.3, Bootstrap Icons |
| Server | Apache (`php:8.2-apache` Docker image) |
| Hosting | Render |

---

## 📁 Project Structure

```
ProjectS/
├── assets/          CSS and static files
├── dashboard/       Student, company, and admin pages
├── data/            JSON files used by demo mode
├── includes/        Shared header, navbar, auth helpers, seed data
├── config.php       MongoDB URI, DB name, app name
├── db.php           Database connection and demo-mode fallback
├── index.php        Landing page
├── login.php        Login
├── register.php     Registration
├── logout.php       Logout
├── test_e2e.php     End-to-end test script
├── Dockerfile       Container build
└── composer.json    PHP dependencies
```

---

## 🗄️ Database Schema

Database: `placement_db`

<details>
<summary><b>users</b></summary>

| Field | Description |
|---|---|
| `_id` | Document ID |
| `name` | Full name or company name |
| `email` | Unique email |
| `password` | Hashed password |
| `role` | `student` \| `company` \| `admin` |
| `status` | `approved` \| `pending` \| `rejected` |
| `created_at` | Datetime string |

</details>

<details>
<summary><b>profiles</b></summary>

| Field | Description |
|---|---|
| `user_id` | Reference to the student's user ID |
| `cgpa` | Cumulative CGPA (float, e.g. `8.2`) |
| `skills` | Comma-separated technical skills |
| `department` | Branch or department |
| `phone` | Contact number |
| `graduation_year` | Year of graduation |
| `resume_link` | Link to resume or portfolio |
| `bio` | Career summary |
| `updated_at` | Datetime string |

</details>

<details>
<summary><b>jobs</b></summary>

| Field | Description |
|---|---|
| `company_id` | Reference to the company's user ID |
| `company_name` | Hiring firm |
| `title` | Job designation |
| `description` | Responsibilities and requirements |
| `min_cgpa` | Minimum CGPA (float, e.g. `7.5`) |
| `ctc` | Compensation package |
| `location` | Location, Remote, or Hybrid |
| `deadline` | Application cutoff date |
| `status` | `active` \| `closed` |
| `created_at` | Datetime string |

</details>

<details>
<summary><b>applications</b></summary>

| Field | Description |
|---|---|
| `job_id` | Reference to the job drive |
| `student_id` | Reference to the student |
| `student_name`, `student_email` | Applicant details |
| `student_cgpa` | CGPA at time of application |
| `job_title`, `company_id`, `company_name` | Drive details |
| `resume_link` | Resume URL |
| `status` | `Applied` \| `Shortlisted` \| `Hired` \| `Rejected` |
| `notes` | Recruiter feedback |
| `applied_at`, `updated_at` | Datetime strings |

</details>

### Demo mode

If `config.php` has placeholder credentials, or the Atlas connection fails, `db.php` falls back to a local JSON store in `data/` and seeds sample data automatically. This is for local testing only. On Render the filesystem resets on every deploy, so anything saved in demo mode is lost.

---

## 🚀 Getting Started

### Requirements
- PHP 8.1+
- Composer
- PHP `mongodb` extension
- A MongoDB Atlas cluster (optional for local testing)

### Run locally

```bash
git clone https://github.com/alok3526-stack/ProjectS.git
cd ProjectS
composer install
```

Open `config.php` and set your connection string and database name:

```php
define('MONGODB_URI', 'mongodb+srv://<username>:<password>@<cluster>.mongodb.net/');
define('MONGODB_DB_NAME', 'placement_db');
```

Start the server:

```bash
php -S localhost:8000
```

Open `http://localhost:8000`.

### Run with Docker

```bash
docker build -t campushire .
docker run -p 8080:80 campushire
```

Open `http://localhost:8080`.

---

## 🔗 Connecting MongoDB Atlas

1. Create a free cluster on [MongoDB Atlas](https://www.mongodb.com/atlas).
2. Under **Database Access**, create a user with read/write permissions.
3. Under **Network Access**, allow your server's IP (`0.0.0.0/0` works for testing).
4. Copy the connection string into `config.php`. URL-encode any special characters in the password.
5. Restart the app. When the connection succeeds, demo mode turns off automatically.

---

## ☁️ Deployment

The app runs on [Render](https://render.com) using the included `Dockerfile`.

1. Push the repo to GitHub.
2. In Render, create a **Web Service** and select the repo.
3. Set the environment to **Docker**.
4. Allow Render's outbound IPs in Atlas Network Access.
5. Every push to `main` redeploys automatically.

---

## 🩺 Troubleshooting

<details>
<summary><b>Permission denied on <code>data/users.json</code></b></summary>

Apache runs as `www-data` and needs write access to `data/`. The Dockerfile handles it:

```dockerfile
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/data
```

</details>

<details>
<summary><b>"Headers already sent" warning</b></summary>

Usually a side effect of an earlier warning printing output. Fix the first warning and this one disappears.

</details>

<details>
<summary><b>Stuck in demo mode</b></summary>

Temporarily add this to see why Atlas isn't connecting, then remove it:

```php
var_dump(get_db_status());
```

Check the `error` field. Common causes: IP not allowed in Atlas, unencoded password, or a placeholder URI.

</details>

<details>
<summary><b>Show or hide the database status bar</b></summary>

In `includes/header.php`, set `$showDbBanner` to `true` or `false`.

</details>

---

## 🔒 Security Notes

- Never commit real database credentials.
- Block public access to `data/` with a `data/.htaccess` file containing `Require all denied`.
- Demo-mode JSON files contain password hashes, so don't expose them.

---

## 👤 Author

**Alok**, BCA student, Mumbai

[![GitHub](https://img.shields.io/badge/GitHub-alok3526--stack-181717?style=flat-square&logo=github)](https://github.com/alok3526-stack)

---

<div align="center">

⭐ If you found this useful, consider starring the repo.

</div>
