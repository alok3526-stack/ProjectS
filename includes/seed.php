<?php
/**
 * Data Seeder Module
 * Seeds initial demo data (Admin, Companies, Students, Jobs, Applications)
 * if collections are empty.
 */

function seed_initial_data_if_needed() {
    global $usersCollection, $profilesCollection, $jobsCollection, $applicationsCollection;

    if (!$usersCollection) {
        return;
    }

    try {
        $count = $usersCollection->countDocuments();
        if ($count > 0) {
            return; // Data already exists
        }

        // 1. Seed Admin
        $adminId = (string)($usersCollection->insertOne([
            'name' => 'Campus Placement Dean',
            'email' => 'admin@campus.edu',
            'password' => password_hash('AdminPassword123!', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'approved',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        // 2. Seed Companies
        $googleId = (string)($usersCollection->insertOne([
            'name' => 'Google Technologies',
            'email' => 'recruiter@google.com',
            'password' => password_hash('CompanyPassword123!', PASSWORD_DEFAULT),
            'role' => 'company',
            'status' => 'approved',
            'industry' => 'Cloud & AI Technology',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        $microsoftId = (string)($usersCollection->insertOne([
            'name' => 'Microsoft Corporation',
            'email' => 'careers@microsoft.com',
            'password' => password_hash('CompanyPassword123!', PASSWORD_DEFAULT),
            'role' => 'company',
            'status' => 'approved',
            'industry' => 'Enterprise Software',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        $startupId = (string)($usersCollection->insertOne([
            'name' => 'Starlight Robotics (Pending Approval)',
            'email' => 'hr@starlightrobotics.io',
            'password' => password_hash('CompanyPassword123!', PASSWORD_DEFAULT),
            'role' => 'company',
            'status' => 'pending',
            'industry' => 'Robotics & Hardware',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        // 3. Seed Students
        $alexStudentId = (string)($usersCollection->insertOne([
            'name' => 'Alex Johnson',
            'email' => 'alex.student@campus.edu',
            'password' => password_hash('StudentPassword123!', PASSWORD_DEFAULT),
            'role' => 'student',
            'status' => 'approved',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        $profilesCollection->insertOne([
            'user_id' => $alexStudentId,
            'name' => 'Alex Johnson',
            'email' => 'alex.student@campus.edu',
            'cgpa' => 8.2,
            'skills' => 'PHP, MongoDB, Python, JavaScript, React, Docker',
            'department' => 'Computer Science & Engineering',
            'phone' => '+1 (555) 234-5678',
            'graduation_year' => '2027',
            'resume_link' => 'https://drive.google.com/sample-alex-resume.pdf',
            'bio' => 'Passionate full-stack developer with experience in PHP microservices and NoSQL databases.',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $samanthaStudentId = (string)($usersCollection->insertOne([
            'name' => 'Samantha Miller (Pending Verification)',
            'email' => 'samantha.miller@campus.edu',
            'password' => password_hash('StudentPassword123!', PASSWORD_DEFAULT),
            'role' => 'student',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        $profilesCollection->insertOne([
            'user_id' => $samanthaStudentId,
            'name' => 'Samantha Miller',
            'email' => 'samantha.miller@campus.edu',
            'cgpa' => 7.1,
            'skills' => 'Java, Spring Boot, MySQL, REST APIs',
            'department' => 'Information Technology',
            'phone' => '+1 (555) 876-5432',
            'graduation_year' => '2027',
            'resume_link' => 'https://drive.google.com/sample-samantha-resume.pdf',
            'bio' => 'Aspiring backend engineer focusing on scalable enterprise systems.',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 4. Seed Job Drives
        $job1Id = (string)($jobsCollection->insertOne([
            'company_id' => $googleId,
            'company_name' => 'Google Technologies',
            'title' => 'Associate Software Engineer',
            'description' => 'Build scalable global services, optimize latency, and collaborate with world-class engineers across Google Cloud.',
            'min_cgpa' => 7.5,
            'ctc' => '$115,000 / 22 LPA',
            'location' => 'Mountain View, CA / Remote',
            'deadline' => '2026-12-15',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        $job2Id = (string)($jobsCollection->insertOne([
            'company_id' => $googleId,
            'company_name' => 'Google Technologies',
            'title' => 'Frontend Web Developer',
            'description' => 'Create responsive, accessible, high-performance web applications using modern JavaScript/TypeScript and web components.',
            'min_cgpa' => 6.5,
            'ctc' => '$95,000 / 16 LPA',
            'location' => 'New York, NY / Hybrid',
            'deadline' => '2026-11-30',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        $job3Id = (string)($jobsCollection->insertOne([
            'company_id' => $microsoftId,
            'company_name' => 'Microsoft Corporation',
            'title' => 'AI & Machine Learning Research Engineer',
            'description' => 'Develop state-of-the-art transformer models, fine-tune LLMs, and deploy inference pipelines to Azure AI.',
            'min_cgpa' => 8.5,
            'ctc' => '$135,000 / 28 LPA',
            'location' => 'Redmond, WA / Remote',
            'deadline' => '2026-12-31',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ])->getInsertedId());

        // 5. Seed Applications
        $applicationsCollection->insertOne([
            'job_id' => $job2Id,
            'student_id' => $alexStudentId,
            'student_name' => 'Alex Johnson',
            'student_email' => 'alex.student@campus.edu',
            'student_cgpa' => 8.2,
            'job_title' => 'Frontend Web Developer',
            'company_id' => $googleId,
            'company_name' => 'Google Technologies',
            'resume_link' => 'https://drive.google.com/sample-alex-resume.pdf',
            'status' => 'Shortlisted',
            'notes' => 'Passed technical screening round with flying colors.',
            'applied_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]);

        $applicationsCollection->insertOne([
            'job_id' => $job1Id,
            'student_id' => $alexStudentId,
            'student_name' => 'Alex Johnson',
            'student_email' => 'alex.student@campus.edu',
            'student_cgpa' => 8.2,
            'job_title' => 'Associate Software Engineer',
            'company_id' => $googleId,
            'company_name' => 'Google Technologies',
            'resume_link' => 'https://drive.google.com/sample-alex-resume.pdf',
            'status' => 'Applied',
            'notes' => 'Application received, pending review.',
            'applied_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]);

    } catch (\Throwable $e) {
        // Silently continue if seeding fails
    }
}
