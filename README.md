UB Student - Student Management & Productivity Web App
1. Introduction
UB Student is a comprehensive, all-in-one web application designed to streamline the academic lives of university students and simplify administrative tasks. Built with PHP and MySQL, this platform provides a centralized hub for students to access their grades, manage their study schedules, and stay updated with campus news. For administrators, it offers a powerful backend to manage student data, courses, grades, and announcements with ease.

This project was developed to create a seamless, intuitive, and functional digital environment, moving from basic grade reporting to a feature-rich productivity suite, including an advanced scheduler with a Pomodoro focus timer.

2. Features
Student-Facing Features:
Secure Login: Students can log in securely with their unique matriculation number and password.

Dashboard: A modern dashboard that provides an at-a-glance summary of:

GPA Tracker: Displays the latest semester GPA and shows the change compared to the previous semester.

Recent Grades: A quick look at the three most recently added grades.

Latest News: The most recent announcement from the administration.

Advanced Scheduler: A powerful, dynamic scheduling page featuring:

Task Management: Add and complete tasks and reminders. The interface updates instantly without page reloads (AJAX).

Personal Timetable: Create and manage a recurring weekly study schedule.

Focus Timer: An integrated Pomodoro timer with customizable sessions (Pomodoro, Short Break, Long Break) to help students manage their study time effectively.

Detailed Grades View:

A complete breakdown of all registered courses and grades, organized by semester in a tabbed view.

Grades are color-coded for quick visual assessment.

The layout is fully responsive for easy viewing on any device.

News & Announcements: A dedicated page to view all news and announcements from the administration, including links to download attached PDF documents.

Admin-Facing Features:
Admin Dashboard: A central control panel for all administrative actions.

Student Management: Add new students to the system.

Course Management: Create and manage the list of available courses and their credit values.

Grade Management: Assign grades (CA marks, exam marks) to students for specific courses and semesters. The system automatically calculates the total score and letter grade.

News & Document Upload:

Create and post news and announcements for all students.

Attach and upload PDF files (e.g., official documents, notices) to news posts, which students can then download.

3. Technologies Used
Backend: PHP

Database: MySQL

Frontend: HTML5, CSS3 (including Tailwind CSS for the scheduler), JavaScript (ES6+)

Server: Apache (typically via XAMPP or WAMP)

4. Setup and Installation
Follow these steps to get the UB Student application running on your local machine.

Prerequisites
A local web server environment like XAMPP or WAMP.

A MySQL database server.

A web browser.

Step-by-Step Installation
Clone/Download the Repository:

Place the entire project folder (e.g., ub_student_app) into your web server's root directory (htdocs for XAMPP).

Database Setup:

Start your Apache and MySQL services from your XAMPP/WAMP control panel.

Open your MySQL client (e.g., phpMyAdmin by navigating to http://localhost/phpmyadmin).

Create a new database and name it ubstudent_db.

Select the new database and go to the "SQL" tab.

Copy the entire SQL code from the SQL.txt section in the project files and execute it. This will create all the necessary tables (students, courses, grades, news, study_timetable, tasks).

Configure Database Connection:

Navigate to the config/ folder within the project.

Open the updateupdate file and copy the database information.

Update the database credentials ($hostname, $username, $password, $database) to match your local MySQL setup. For a default XAMPP installation, you might only need to change the password if you've set one.

Create Uploads Directory:

In the root directory of the project, create a new folder and name it uploads. This folder is required for the PDF upload functionality to work.



5. How to Use
Admin Workflow
Go to the Admin Dashboard.

Use "Add Student" and "Add Course" to populate the system with initial data.

Use "Assign Grade" to link students to courses and record their marks.

Use "Upload News" to post announcements and attach documents for students.

Student Workflow
Go to the Student Login page and enter the credentials created by the admin.

Upon successful login, you will be directed to the main Dashboard.

Click on "Open Scheduler" to access the main productivity page.

Use the "Task" tab to add and complete your daily to-dos.

Switch to the "Personal Timetable" tab to set up your recurring weekly schedule.

Use the Focus Timer on the right to manage study sessions.

From the dashboard, click "View All Grades" to see a detailed semester-by-semester breakdown of your academic performance.

Click on the "News" link in the header to view all announcements and download any attached files.

6. Future Improvements
Real-time Notifications: Implement push notifications for new grades or news announcements.

Admin Data Management: Add functionality for admins to edit and delete existing students, courses, and grades.

Advanced User Profiles: Allow students to update their profiles, change passwords, and upload a profile picture.

Course Registration System: Enable students to register for courses directly through the portal at the beginning of a semester.

# Dashboard - Desktop
![stud1](https://github.com/user-attachments/assets/f6a48138-4cb5-4d4d-b921-5479fe917b5b)

# Dashboard - Mobile
![stud2](https://github.com/user-attachments/assets/2a51a644-e6ea-4143-8195-414ab58bd9bd)
