# UniBridge — Smart Student Onboarding Portal

UniBridge is a Web Development mini project for newly admitted students. It combines the required registration pages with a guided dashboard, readiness score, document tracking and a profile workflow.

## Assignment requirements covered

| Requirement | UniBridge page / table |
|---|---|
| Student Sign-up | `signup.php` |
| Student Login | `login.php` |
| Forgot Password | `forgot.php` and `reset.php` |
| Student Registration | `registration.php` |
| Upload Documents | `documents.php` |
| Edit Current Profile | `profile.php` |
| Student Login table | `student_login` |
| Student Registration table | `student_registration` |
| Document Track table | `document_track` |

## What makes it different

- Student dashboard with registration profile completion, document approval count and readiness score.
- Three-step registration journey that saves progress at each step.
- Document status tracking: Not uploaded, Pending, Approved or Rejected.
- Recent activity feed.
- Responsive, modern UI built without frameworks.
- Password hashing, sessions, CSRF request tokens and file type/size checks.

## Stack

HTML, CSS, vanilla JavaScript, PHP 8+ and MySQL/MariaDB.

## Quick Start with Docker (Recommended)

1. Make sure **Docker** and **Docker Compose** are installed and running.
2. In the project root directory, optionally review `.env` (defaults are preconfigured):
   ```bash
   cp .env.example .env
   ```
3. Start the application stack:
   ```bash
   docker compose up -d --build
   ```
4. Access the services:
   - **Student Portal**: [http://localhost:8080](http://localhost:8080)
   - **phpMyAdmin (Database GUI)**: [http://localhost:8081](http://localhost:8081)
     - Server: `db`
     - Username: `unibridge` (or `root`)
     - Password: `unibridge_secret_pass` (or `root_secure_pass`)
5. The database schema and tables (`database.sql`) are automatically imported upon first container start.
6. To view logs or stop the containers:
   ```bash
   docker compose logs -f app
   docker compose down
   ```

## Alternative: Run with XAMPP

1. Install and start **Apache** and **MySQL** in XAMPP.
2. Copy the whole `WD project` folder into `C:\xampp\htdocs\`.
3. Open `http://localhost/phpmyadmin` and import `database.sql`.
4. Open `config.php`. The default setup expects MySQL user `root` with no password. Change `DB_USER` and `DB_PASS` if yours differs.
5. Visit `http://localhost/WD%20project/`.
6. Create your student account on the sign-up page, then complete registration and upload documents.

## Database CRUD

- **Create:** account signup, registration values, document uploads.
- **Read:** dashboard, profile, document record and activity log.
- **Update:** registration details, profile edits, document replacement and password reset.
- **Delete:** linked student records are removed automatically if a student login record is deleted (foreign-key cascade). An admin page can be added later if your teacher asks to demonstrate deletion in the UI.

## Project layout

```text
WD project/
├── assets/                 # UI styling and small JavaScript interactions
├── uploads/                # Created automatically for uploaded documents
├── config.php              # Database connection, sessions and shared helpers
├── database.sql            # Schema for all project tables
├── signup.php / login.php  # Account and authentication screens
├── registration.php        # Multi-step student registration
├── documents.php           # Upload and document track view
├── profile.php             # Edit current profile
└── dashboard.php           # Student home and readiness score
```

## Demo tip

After importing the database, sign up through the application so your password is securely created. To demonstrate document approval, use phpMyAdmin and change a document row’s `status` from `Pending` to `Approved`; the dashboard score updates automatically.
