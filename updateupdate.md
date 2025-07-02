<?php
/*
================================================================================
|                                                                              |
|                           -- D A T A B A S E . P H P --                        |
|                                                                              |
================================================================================
|                                                                              |
|   This file is responsible for establishing a connection to the MySQL        |
|   database. It uses the mysqli extension. You should replace the placeholder |
|   values for hostname, username, password, and database name with your       |
|   actual database credentials.                                               |
|                                                                              |
================================================================================
*/

// config/database.php

$hostname = "localhost";
$username = "root";
$password = "";
$database = "student_grades";

$conn = new mysqli($hostname, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/*
================================================================================
|                                                                              |
|                               -- S Q L . T X T --                            |
|                                                                              |
================================================================================
|                                                                              |
|   This file contains the SQL queries to create the necessary tables in your  |
|   database. You should run these queries in your MySQL client (like         |
|   phpMyAdmin) to set up the database structure before running the            |
|   application.                                                               |
|                                                                              |
|   **IMPORTANT**: After setting up, create a folder named 'uploads' in your   |
|   project's root directory. This is where uploaded PDF files will be stored. |
|                                                                              |
================================================================================
*/

-- SQL for creating tables

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    matriculation_number VARCHAR(255) UNIQUE NOT NULL,
    faculty_short_name VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    credit_value INT NOT NULL
);

CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    ca_marks INT,
    exam_marks INT,
    total_marks INT,
    grade VARCHAR(10),
    semester INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE study_timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    due_date DATETIME NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    color_category VARCHAR(20) DEFAULT 'blue',
    estimated_hours DECIMAL(4, 2) DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);


/*
================================================================================
|                                                                              |
|                        -- A D M I N / I N D E X . P H P --                     |
|                                                                              |
================================================================================
|                                                                              |
|   This is the main dashboard for the admin. It provides links to manage      |
|   students, courses, grades, and news announcements.                         |
|                                                                              |
================================================================================
*/

// admin/index.php

include '../config/database.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>

    <h1>Admin Dashboard</h1>

    <h2><a href="add_student.php">Add Student</a></h2>
    <h2><a href="add_course.php">Add Course</a></h2>
    <h2><a href="assign_grade.php">Assign Grade</a></h2>
    <h2><a href="upload_news.php">Upload News</a></h2>

    <hr>

    <h2>Students</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Matriculation Number</th>
        </tr>
        <?php
        $result = $conn->query("SELECT * FROM students");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['matriculation_number'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <hr>

    <h2>Courses</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Course Name</th>
            <th>Credit Value</th>
        </tr>
        <?php
        $result = $conn->query("SELECT * FROM courses");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['course_name'] . "</td>";
            echo "<td>" . $row['credit_value'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <hr>

    <h2>Grades</h2>
    <table border="1">
        <tr>
            <th>Grade ID</th>
            <th>Student Name</th>
            <th>Course Name</th>
            <th>CA Marks</th>
            <th>Exam Marks</th>
            <th>Total Marks</th>
            <th>Grade</th>
            <th>Semester</th>
        </tr>
        <?php
        $sql = "SELECT grades.id, students.name AS student_name, courses.course_name, grades.ca_marks, grades.exam_marks, grades.total_marks, grades.grade, grades.semester 
                FROM grades
                JOIN students ON grades.student_id = students.id
                JOIN courses ON grades.course_id = courses.id";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['student_name'] . "</td>";
            echo "<td>" . $row['course_name'] . "</td>";
            echo "<td>" . $row['ca_marks'] . "</td>";
            echo "<td>" . $row['exam_marks'] . "</td>";
            echo "<td>" . $row['total_marks'] . "</td>";
            echo "<td>" . $row['grade'] . "</td>";
            echo "<td>" . $row['semester'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>

</body>
</html>

<?php
$conn->close();
?>

/*
================================================================================
|                                                                              |
|                  -- A D M I N / A D D _ S T U D E N T . P H P --               |
|                                                                              |
================================================================================
|                                                                              |
|   This file provides a form for the admin to add new students to the         |
|   database. It takes the student's name, matriculation number, and a         |
|   password as input. The password is then hashed for security.               |
|                                                                              |
================================================================================
*/

// admin/add_student.php

include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $matriculation_number = $_POST['matriculation_number'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO students (name, matriculation_number, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $matriculation_number, $password);

    if ($stmt->execute()) {
        echo "New student added successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Student</title>
</head>
<body>

    <h2>Add New Student</h2>
    <form method="post" action="">
        Name: <input type="text" name="name" required><br>
        Matriculation Number: <input type="text" name="matriculation_number" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" value="Add Student">
    </form>

    <br>
    <a href="index.php">Back to Admin Dashboard</a>

</body>
</html>

/*
================================================================================
|                                                                              |
|                   -- A D M I N / A D D _ C O U R S E . P H P --                |
|                                                                              |
================================================================================
|                                                                              |
|   This file provides a form for the admin to add new courses to the          |
|   database. It takes the course name and credit value as input.              |
|                                                                              |
================================================================================
*/

// admin/add_course.php

include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_name = $_POST['course_name'];
    $credit_value = $_POST['credit_value'];

    $stmt = $conn->prepare("INSERT INTO courses (course_name, credit_value) VALUES (?, ?)");
    $stmt->bind_param("si", $course_name, $credit_value);

    if ($stmt->execute()) {
        echo "New course added successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Course</title>
</head>
<body>

    <h2>Add New Course</h2>
    <form method="post" action="">
        Course Name: <input type="text" name="course_name" required><br>
        Credit Value: <input type="number" name="credit_value" required><br>
        <input type="submit" value="Add Course">
    </form>

    <br>
    <a href="index.php">Back to Admin Dashboard</a>

</body>
</html>

/*
================================================================================
|                                                                              |
|                 -- A D M I N / A S S I G N _ G R A D E . P H P --              |
|                                                                              |
================================================================================
|                                                                              |
|   This file allows the admin to assign a grade to a student for a specific   |
|   course. It takes the student, course, marks, and semester as input. The    |
|   total marks and grade are calculated automatically.                        |
|                                                                              |
================================================================================
*/

// admin/assign_grade.php

include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    $ca_marks = $_POST['ca_marks'];
    $exam_marks = $_POST['exam_marks'];
    $semester = $_POST['semester'];

    $total_marks = $ca_marks + $exam_marks;
    $grade = '';
    if ($total_marks >= 80) {
        $grade = 'A';
    } elseif ($total_marks >= 70) {
        $grade = 'B+';
    } elseif ($total_marks >= 60) {
        $grade = 'B';
    } elseif ($total_marks >= 50) {
        $grade = 'C';
    } else {
        $grade = 'F';
    }

    $stmt = $conn->prepare("INSERT INTO grades (student_id, course_id, ca_marks, exam_marks, total_marks, grade, semester) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisisi", $student_id, $course_id, $ca_marks, $exam_marks, $total_marks, $grade, $semester);

    if ($stmt->execute()) {
        echo "Grade assigned successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Grade</title>
</head>
<body>

    <h2>Assign Grade to Student</h2>
    <form method="post" action="">
        <label for="student_id">Select Student:</label>
        <select name="student_id" required>
            <?php
            $result = $conn->query("SELECT id, name FROM students");
            while($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
            }
            ?>
        </select><br>

        <label for="course_id">Select Course:</label>
        <select name="course_id" required>
             <?php
            $result = $conn->query("SELECT id, course_name FROM courses");
            while($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['id'] . "'>" . $row['course_name'] . "</option>";
            }
            ?>
        </select><br>

        CA Marks: <input type="number" name="ca_marks" required><br>
        Exam Marks: <input type="number" name="exam_marks" required><br>
        Semester: <input type="number" name="semester" required><br>
        <input type="submit" value="Assign Grade">
    </form>

    <br>
    <a href="index.php">Back to Admin Dashboard</a>

</body>
</html>

<?php
$conn->close();
?>

/*
================================================================================
|                                                                              |
|                    -- A D M I N / U P L O A D _ N E W S . P H P --             |
|                                                                              |
================================================================================
|                                                                              |
|   This page allows the administrator to post news, announcements, and        |
|   upload an optional PDF document.                                           |
|                                                                              |
================================================================================
*/

<?php
include '../config/database.php';
$upload_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $file_path = NULL;

    // Check if a file was uploaded without errors
    if (isset($_FILES['document']) && $_FILES['document']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/"; // Relative to the 'admin' folder
        $file_name = uniqid() . '-' . basename($_FILES["document"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allow only PDF files
        if ($file_type != "pdf") {
            $upload_message = "Error: Only PDF files are allowed.";
        } else {
            // Ensure uploads directory exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
                $file_path = $file_name; // Store only the file name in DB
            } else {
                $upload_message = "Error: There was a problem uploading your file.";
            }
        }
    }

    // Proceed with DB insertion only if file upload was successful or no file was uploaded
    if (empty($upload_message)) {
        $stmt = $conn->prepare("INSERT INTO news (title, content, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $content, $file_path);

        if ($stmt->execute()) {
            $upload_message = "News uploaded successfully!";
        } else {
            $upload_message = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload News</title>
</head>
<body>
    <h2>Upload News / Announcement</h2>

    <?php if (!empty($upload_message)): ?>
        <p><strong><?php echo $upload_message; ?></strong></p>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        Title: <input type="text" name="title" required size="50"><br><br>
        Content:<br>
        <textarea name="content" required rows="10" cols="50"></textarea><br><br>
        Attach Document (PDF only): <input type="file" name="document" accept=".pdf"><br><br>
        <input type="submit" value="Upload News">
    </form>
    <br>
    <a href="index.php">Back to Admin Dashboard</a>
</body>
</html>


/*
================================================================================
|                                                                              |
|                      -- S T U D E N T / L O G I N . P H P --                   |
|                                                                              |
================================================================================
|                                                                              |
|   This is the login page for students. It takes the matriculation number     |
|   and password as input. If the credentials are correct, it starts a         |
|   session and redirects the student to their new dashboard.                  |
|                                                                              |
================================================================================
*/

// student/login.php

session_start();
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matriculation_number = $_POST['matriculation_number'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM students WHERE matriculation_number = ?");
    $stmt->bind_param("s", $matriculation_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['student_id'] = $row['id'];
            header("Location: dashboard.php"); // Redirect to the new dashboard
            exit();
        } else {
            echo "Invalid password";
        }
    } else {
        echo "No student found with that matriculation number";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Login</title>
</head>
<body>

    <h2>Student Login</h2>
    <form method="post" action="">
        Matriculation Number: <input type="text" name="matriculation_number" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" value="Login">
    </form>

</body>
</html>

/*
================================================================================
|                                                                              |
|                   -- S T U D E N T / D A S H B O A R D . P H P --              |
|                                                                              |
================================================================================
|                                                                              |
|   This is the main student dashboard, showing a summary of their academic    |
|   progress, including a GPA tracker, recent grades, timetable, and news.     |
|   It now links to the new advanced scheduler page.                           |
|                                                                              |
================================================================================
*/

<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student's name
$stmt = $conn->prepare("SELECT name FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Helper function to calculate GPA
function calculateGPA($grades) {
    $total_points = 0;
    $total_credits = 0;
    $grade_points = ['A' => 4.0, 'B+' => 3.5, 'B' => 3.0, 'C' => 2.0, 'F' => 0.0];
    foreach ($grades as $grade) {
        if (isset($grade_points[$grade['grade']])) {
            $total_points += $grade_points[$grade['grade']] * $grade['credit_value'];
            $total_credits += $grade['credit_value'];
        }
    }
    return $total_credits > 0 ? $total_points / $total_credits : 0;
}

// Fetch all grades for GPA calculation
$all_grades_sql = "SELECT c.credit_value, g.grade, g.semester FROM grades g JOIN courses c ON g.course_id = c.id WHERE g.student_id = ? ORDER BY g.semester";
$stmt = $conn->prepare($all_grades_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$all_grades_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$gpa_per_semester = [];
$grades_by_semester = [];
foreach($all_grades_data as $grade_record) { $grades_by_semester[$grade_record['semester']][] = $grade_record; }
foreach($grades_by_semester as $semester => $grades) { $gpa_per_semester[$semester] = calculateGPA($grades); }

$latest_semester = !empty($gpa_per_semester) ? max(array_keys($gpa_per_semester)) : 0;
$latest_gpa = $latest_semester ? $gpa_per_semester[$latest_semester] : 0.0;
$gpa_change = 0.0;
if ($latest_semester > 1 && isset($gpa_per_semester[$latest_semester - 1])) {
    $gpa_change = $latest_gpa - $gpa_per_semester[$latest_semester - 1];
}

// Fetch recent grades
$grades_list_sql = "SELECT c.course_name, g.semester, g.grade FROM grades g JOIN courses c ON g.course_id = c.id WHERE g.student_id = ? ORDER BY g.id DESC LIMIT 3";
$stmt = $conn->prepare($grades_list_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch latest news
$news_sql = "SELECT title, created_at FROM news ORDER BY created_at DESC LIMIT 1";
$latest_news = $conn->query($news_sql)->fetch_assoc();

function getGradeClass($grade) {
    switch ($grade) {
        case 'A': return 'grade-a';
        case 'B+': return 'grade-b-plus';
        case 'B': return 'grade-b';
        case 'C': return 'grade-c';
        case 'F': return 'grade-f';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; color: #333; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
        .header h1 { margin: 0; }
        .header a { text-decoration: none; color: #007bff; }
        .notification-bell { position: relative; }
        .notification-dot { position: absolute; top: -5px; right: -5px; width: 10px; height: 10px; background-color: red; border-radius: 50%; border: 2px solid white; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; }
        .card h3 { margin-top: 0; font-size: 16px; color: #6c757d; }
        .gpa-value { font-size: 2.5em; font-weight: bold; }
        .grade-box { display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
        .grade-letter { font-size: 1.2em; font-weight: bold; padding: 5px 12px; border-radius: 8px; color: white; }
        .grade-a { background-color: #28a745; } .grade-b-plus { background-color: #17a2b8; } .grade-b { background-color: #007bff; } .grade-c { background-color: #ffc107; color: #333; } .grade-f { background-color: #dc3545; }
        .full-details-link { display: block; margin-top: 15px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h1>
        <a href="news.php" class="notification-bell">
            <span>News</span>
            <?php if ($latest_news): ?>
                <span class="notification-dot"></span>
            <?php endif; ?>
        </a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h3>GPA Tracker</h3>
            <div class="gpa-value"><?php echo number_format($latest_gpa, 2); ?></div>
        </div>

        <div class="card">
            <h3>Scheduler & Tasks</h3>
             <p>Organize your tasks, manage your personal timetable, and use the focus timer to stay productive.</p>
            <a href="scheduler.php" class="full-details-link">Open Scheduler &rarr;</a>
        </div>
        
        <div class="card">
            <h3>Recent Grades</h3>
            <?php foreach ($grades_list as $grade_item): ?>
            <div class="grade-box">
                <span><?php echo htmlspecialchars($grade_item['course_name']); ?></span>
                <div class="grade-letter <?php echo getGradeClass($grade_item['grade']); ?>"><?php echo htmlspecialchars($grade_item['grade']); ?></div>
            </div>
            <?php endforeach; ?>
            <a href="index.php" class="full-details-link">View All Grades &rarr;</a>
        </div>

        <div class="card">
             <h3>Latest News</h3>
            <?php if ($latest_news): ?>
                <div class="news-item">
                    <span><?php echo htmlspecialchars($latest_news['title']); ?></span>
                    <small><?php echo date("M d", strtotime($latest_news['created_at'])); ?></small>
                </div>
            <?php else: ?>
                <p>No news at the moment.</p>
            <?php endif; ?>
            <a href="news.php" class="full-details-link">View All News &rarr;</a>
        </div>
    </div>
</body>
</html>

/*
================================================================================
|                                                                              |
|                     -- S T U D E N T / I N D E X . P H P --                    |
|                                                                              |
================================================================================
|                                                                              |
|   This is the student's detailed grades page. It displays their grades for   |
|   each semester in separate tabs. It is responsive and color-codes grades.   |
|                                                                              |
================================================================================
*/

<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT name FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

function calculateGPA($grades) {
    $total_points = 0;
    $total_credits = 0;
    $grade_points = ['A' => 4.0, 'B+' => 3.5, 'B' => 3.0, 'C' => 2.0, 'F' => 0.0];
    foreach ($grades as $grade) {
        if (isset($grade_points[$grade['grade']])) {
            $total_points += $grade_points[$grade['grade']] * $grade['credit_value'];
            $total_credits += $grade['credit_value'];
        }
    }
    return $total_credits > 0 ? $total_points / $total_credits : 0;
}

function getGradeClass($grade) {
    switch ($grade) {
        case 'A': return 'grade-a';
        case 'B+': return 'grade-b-plus';
        case 'B': return 'grade-b';
        case 'C': return 'grade-c';
        case 'F': return 'grade-f';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Grades</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .tab { overflow: hidden; border: 1px solid #ccc; background-color: #f1f1f1; }
        .tab button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; }
        .tab button:hover { background-color: #ddd; }
        .tab button.active { background-color: #ccc; }
        .tabcontent { display: none; padding: 6px 12px; border: 1px solid #ccc; border-top: none; animation: fadeEffect 1s;}
        @keyframes fadeEffect { from {opacity: 0;} to {opacity: 1;} }
        .grade-a { background-color: #4CAF50; color: white; }
        .grade-b-plus { background-color: #2196F3; color: white; }
        .grade-b { background-color: #add8e6; color: black; }
        .grade-c { background-color: #ff9800; color: white; }
        .grade-f { background-color: #f44336; color: white; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: none; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        @media screen and (min-width: 601px) { thead { display: table-header-group; } }
        @media screen and (max-width: 600px) {
            table, thead, tbody, th, td, tr { display: block; }
            tr { border: 1px solid #ccc; margin-bottom: 10px;}
            td { border: none; border-bottom: 1px solid #eee; position: relative; padding-left: 50%; white-space: normal; text-align: right; }
            td:before { position: absolute; top: 6px; left: 6px; width: 45%; padding-right: 10px; white-space: nowrap; font-weight: bold; text-align: left; }
            td:nth-of-type(1):before { content: "Course Name"; }
            td:nth-of-type(2):before { content: "Credit Value"; }
            td:nth-of-type(3):before { content: "CA Marks"; }
            td:nth-of-type(4):before { content: "Exam Marks"; }
            td:nth-of-type(5):before { content: "Total / 100"; }
            td:nth-of-type(6):before { content: "Grade"; }
        }
    </style>
</head>
<body>
    <a href="dashboard.php">&larr; Back to Dashboard</a>
    <h1>Full Grade Report</h1>
    <div class="tab">
        <?php
        $semester_result = $conn->query("SELECT DISTINCT semester FROM grades WHERE student_id = {$_SESSION['student_id']} ORDER BY semester ASC");
        while ($semester_row = $semester_result->fetch_assoc()) {
            echo "<button class='tablinks' onclick=\"openSemester(event, 'Semester{$semester_row['semester']}')\">Semester {$semester_row['semester']}</button>";
        }
        ?>
    </div>
    <?php
    $semester_result->data_seek(0);
    $gpa_per_semester = [];
    while ($semester_row = $semester_result->fetch_assoc()) {
        $semester = $semester_row['semester'];
        echo "<div id='Semester{$semester}' class='tabcontent'>";
        $sql = "SELECT c.course_name, c.credit_value, g.ca_marks, g.exam_marks, g.total_marks, g.grade FROM grades g JOIN courses c ON g.course_id = c.id WHERE g.student_id = ? AND g.semester = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $_SESSION['student_id'], $semester);
        $stmt->execute();
        $grades_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $gpa = calculateGPA($grades_data);
        $gpa_per_semester[$semester] = $gpa;
        $gpa_change_text = '';
        if (isset($gpa_per_semester[$semester - 1])) {
            $gpa_difference = $gpa - $gpa_per_semester[$semester - 1];
            $gpa_change_text = sprintf(" (%+.2f)", $gpa_difference);
        }
        echo "<h3>Semester {$semester} - GPA: " . number_format($gpa, 2) . "{$gpa_change_text}</h3>";
        echo "<table><thead><tr><th>Course Name</th><th>Credit Value</th><th>CA Marks</th><th>Exam Marks</th><th>Total / 100</th><th>Grade</th></tr></thead><tbody>";
        foreach ($grades_data as $grade_row) {
            echo "<tr><td>" . htmlspecialchars($grade_row['course_name']) . "</td><td>" . htmlspecialchars($grade_row['credit_value']) . "</td><td>" . htmlspecialchars($grade_row['ca_marks']) . "</td><td>" . htmlspecialchars($grade_row['exam_marks']) . "</td><td>" . htmlspecialchars($grade_row['total_marks']) . "</td><td class='" . getGradeClass($grade_row['grade']) . "'>" . htmlspecialchars($grade_row['grade']) . "</td></tr>";
        }
        echo "</tbody></table></div>";
        $stmt->close();
    }
    ?>
    <script>
        function openSemester(evt, semesterName) {
            let i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");
            document.getElementById(semesterName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        if(document.getElementsByClassName("tablinks")[0]) document.getElementsByClassName("tablinks")[0].click();
    </script>
</body>
</html>
<?php $conn->close(); ?>

/*
================================================================================
|                                                                              |
|                    -- S T U D E N T / S C H E D U L E R . P H P --             |
|                                                                              |
================================================================================
|                                                                              |
|   A dynamic task management and focus page, replicating the provided UI.     |
|   It includes task management, a calendar, and a Pomodoro timer.             |
|                                                                              |
================================================================================
*/
<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student's name
$student_stmt = $conn->prepare("SELECT name FROM students WHERE id = ?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();
$student_name_parts = explode(' ', $student['name']);
$student_first_name = $student_name_parts[0];
$student_stmt->close();

// Fetch tasks for today
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$tasks_today_stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE student_id = ? AND due_date BETWEEN ? AND ? AND is_completed = FALSE");
$tasks_today_stmt->bind_param("iss", $student_id, $today_start, $today_end);
$tasks_today_stmt->execute();
$tasks_today_count = $tasks_today_stmt->get_result()->fetch_assoc()['count'];
$tasks_today_stmt->close();

// Fetch all incomplete tasks
$tasks_stmt = $conn->prepare("SELECT * FROM tasks WHERE student_id = ? AND is_completed = FALSE ORDER BY due_date ASC");
$tasks_stmt->bind_param("i", $student_id);
$tasks_stmt->execute();
$tasks = $tasks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tasks_stmt->close();

// Function to determine relative time
function get_relative_time($datetime) {
    $now = new DateTime();
    $due = new DateTime($datetime);
    $interval = $now->diff($due);
    $due_date_only = $due->format('Y-m-d');
    $today_date_only = $now->format('Y-m-d');
    $tomorrow_date_only = (new DateTime('tomorrow'))->format('Y-m-d');

    if ($due_date_only == $today_date_only) return "Today";
    if ($due_date_only == $tomorrow_date_only) return "Tomorrow";
    if ($due < $now) return $due->format('M d');
    if ($interval->days < 7) return "This Week";
    
    return "Next Week";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduler</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/phosphor-icons"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .main-grid { display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: auto 1fr; gap: 24px; max-width: 1200px; margin: auto; padding: 24px;}
        .header-card { grid-column: 1 / -1; }
        .task-input-card { grid-column: 1 / -1; }
        .tasks-card { grid-column: 1 / 2; }
        .right-column { grid-column: 2 / -1; display: flex; flex-direction: column; gap: 24px; }
        .calendar-card, .focus-card { width: 100%; }
        .tab.active { background-color: #4f46e5; color: white; }
        .task-item-color { width: 4px; height: 24px; border-radius: 2px; }
        .task-item-color.green { background-color: #10b981; }
        .task-item-color.yellow { background-color: #f59e0b; }
        .task-item-color.blue { background-color: #3b82f6; }
        .focus-btn.active { background-color: #3b82f6; color: white; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="main-grid">
        <!-- Header -->
        <div class="header-card bg-blue-600 text-white p-6 rounded-xl shadow-lg">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Good Morning, <?php echo htmlspecialchars($student_first_name); ?>!</h1>
                    <p class="text-blue-200 mt-1">You have <?php echo $tasks_today_count; ?> tasks for today</p>
                </div>
                <div class="text-right">
                    <p class="font-medium">Hours this week: 8.5</p>
                    <p class="text-sm text-blue-300">(+3.5 vs last week)</p>
                </div>
            </div>
        </div>

        <!-- Task Input -->
        <div class="task-input-card bg-white p-4 rounded-xl shadow">
            <div class="tabs flex border-b mb-4">
                 <button class="tab py-2 px-4 rounded-t-lg font-semibold active">Task</button>
                 <button onclick="window.location.href='timetable.php'" class="tab py-2 px-4 rounded-t-lg font-semibold text-gray-600 hover:bg-gray-200">Personal Timetable</button>
            </div>
            <div class="add-task-form flex items-center gap-4">
                <i class="ph-plus-circle text-gray-400 text-2xl"></i>
                <input type="text" id="task-title" class="flex-grow bg-transparent focus:outline-none" placeholder="Add task or remainder">
                <button id="add-task-btn" class="bg-blue-600 text-white rounded-full p-2 hover:bg-blue-700">
                    <i class="ph-paper-plane-tilt-fill text-xl"></i>
                </button>
            </div>
             <div class="task-options flex items-center gap-4 mt-4 text-gray-500">
                 <button class="flex items-center gap-1 hover:bg-gray-100 px-2 py-1 rounded-md"><i class="ph-clock"></i><span>--:--</span></button>
                 <button class="flex items-center gap-1 hover:bg-gray-100 px-2 py-1 rounded-md"><i class="ph-calendar-blank"></i><span>Today</span></button>
                 <button class="flex items-center gap-1 hover:bg-gray-100 px-2 py-1 rounded-md"><i class="ph-calendar-plus"></i><span>Tomorrow</span></button>
                 <button class="flex items-center gap-1 hover:bg-gray-100 px-2 py-1 rounded-md"><i class="ph-calendar-x"></i><span>Custom</span></button>
            </div>
        </div>

        <!-- Tasks List -->
        <div class="tasks-card bg-white p-6 rounded-xl shadow">
            <h2 class="text-xl font-bold mb-4">Task and Remainders</h2>
            <div id="task-list" class="space-y-4">
                <?php foreach ($tasks as $task): ?>
                <div class="task-item flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <input type="checkbox" class="h-6 w-6 rounded border-gray-300 text-blue-600 focus:ring-blue-500" data-id="<?php echo $task['id']; ?>">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($task['title']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo get_relative_time($task['due_date']); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-semibold text-gray-600"><?php echo date('g:i A', strtotime($task['due_date'])); ?></span>
                        <div class="task-item-color <?php echo $task['color_category']; ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                 <?php if (empty($tasks)): ?>
                    <p class="text-gray-500">No pending tasks. Great job!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
            <!-- Calendar -->
            <div id="calendar-card" class="calendar-card bg-white p-4 rounded-xl shadow"></div>

            <!-- Focus Timer -->
            <div class="focus-card bg-white p-6 rounded-xl shadow text-center">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">FOCUS</h2>
                    <button class="text-sm flex items-center gap-1 text-gray-500 hover:text-blue-600"><i class="ph-music-note-simple"></i> Add Music</button>
                </div>
                <p class="text-gray-500 mb-4">current task: <span id="current-task" class="font-semibold text-gray-700">Select a task</span></p>
                <div class="grid grid-cols-2 gap-2 mb-6">
                    <button class="focus-btn p-2 rounded-lg bg-gray-100 hover:bg-gray-200 active" data-time="25">Pomodoro</button>
                    <button class="focus-btn p-2 rounded-lg bg-gray-100 hover:bg-gray-200" data-time="5">Short Break</button>
                    <button class="focus-btn p-2 rounded-lg bg-gray-100 hover:bg-gray-200" data-time="15">Long Break</button>
                    <button class="focus-btn p-2 rounded-lg bg-gray-100 hover:bg-gray-200" data-time="0">Custom</button>
                </div>
                <div id="timer-display" class="text-7xl font-bold my-6">25:00</div>
                <div class="flex justify-center items-center gap-8">
                    <button id="timer-control" class="bg-blue-600 text-white rounded-full h-16 w-16 flex items-center justify-center text-4xl shadow-lg hover:bg-blue-700">
                        <i id="play-icon" class="ph-play-fill"></i>
                        <i id="pause-icon" class="ph-pause-fill hidden"></i>
                    </button>
                    <button id="reset-btn" class="text-gray-400 hover:text-gray-600 text-3xl"><i class="ph-arrow-counter-clockwise"></i></button>
                </div>
            </div>
        </div>
    </div>
    
<script>
document.addEventListener('DOMContentLoaded', () => {

    // --- Task Management ---
    const addTaskBtn = document.getElementById('add-task-btn');
    const taskTitleInput = document.getElementById('task-title');
    const taskListContainer = document.getElementById('task-list');

    addTaskBtn.addEventListener('click', async () => {
        const title = taskTitleInput.value.trim();
        if (!title) return;

        // For now, let's default the due date to tomorrow at noon
        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + 1);
        dueDate.setHours(12, 0, 0, 0);

        const formData = new FormData();
        formData.append('action', 'add_task');
        formData.append('title', title);
        formData.append('due_date', dueDate.toISOString().slice(0, 19).replace('T', ' '));

        try {
            const response = await fetch('scheduler_actions.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                // To keep it simple, we reload the page to see the new task
                window.location.reload();
            } else {
                alert('Error adding task: ' + result.message);
            }
        } catch (error) {
            console.error('Failed to add task:', error);
            alert('An error occurred. Please try again.');
        }
    });

    taskListContainer.addEventListener('change', async (e) => {
        if (e.target.type === 'checkbox') {
            const taskId = e.target.dataset.id;
            const isCompleted = e.target.checked;

            const formData = new FormData();
            formData.append('action', 'update_task_status');
            formData.append('task_id', taskId);
            formData.append('is_completed', isCompleted ? 1 : 0);

            try {
                const response = await fetch('scheduler_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    // Remove the task item from the view
                    e.target.closest('.task-item').remove();
                } else {
                    alert('Error updating task: ' + result.message);
                    e.target.checked = !isCompleted; // Revert checkbox on failure
                }
            } catch (error) {
                console.error('Failed to update task:', error);
                alert('An error occurred. Please try again.');
                 e.target.checked = !isCompleted;
            }
        }
    });


    // --- Calendar ---
    const calendarCard = document.getElementById('calendar-card');
    const now = new Date(2025, 5, 25); // Set to June 25, 2025 as per image
    
    function generateCalendar(year, month) {
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const dayNames = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        let html = `<div class="flex justify-between items-center mb-4"><h3 class="font-bold text-lg">${monthNames[month]} ${year}</h3><div><button>&lt;</button><button>&gt;</button></div></div>`;
        html += '<div class="grid grid-cols-7 text-center text-sm text-gray-500">';
        dayNames.forEach(day => { html += `<div>${day}</div>`; });
        html += '</div>';
        
        html += '<div class="grid grid-cols-7 text-center mt-2">';
        let startingDay = firstDay.getDay(); // 0=Sun, 1=Mon
        if (startingDay === 0) startingDay = 6; else startingDay -= 1;

        for (let i = 0; i < startingDay; i++) {
            html += '<div></div>';
        }

        for (let i = 1; i <= lastDay.getDate(); i++) {
            const isToday = (i === now.getDate() && month === now.getMonth() && year === now.getFullYear());
            html += `<div class="p-1"><span class="h-8 w-8 flex items-center justify-center rounded-full ${isToday ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'}">${i}</span></div>`;
        }
        html += '</div>';
        calendarCard.innerHTML = html;
    }
    
    generateCalendar(now.getFullYear(), now.getMonth());

    // --- Pomodoro Timer ---
    const timerDisplay = document.getElementById('timer-display');
    const timerControlBtn = document.getElementById('timer-control');
    const playIcon = document.getElementById('play-icon');
    const pauseIcon = document.getElementById('pause-icon');
    const resetBtn = document.getElementById('reset-btn');
    const focusBtns = document.querySelectorAll('.focus-btn');
    
    let timerInterval;
    let totalSeconds = 25 * 60;
    let isRunning = false;

    function updateDisplay() {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    function startTimer() {
        if (isRunning) return;
        isRunning = true;
        playIcon.classList.add('hidden');
        pauseIcon.classList.remove('hidden');
        timerInterval = setInterval(() => {
            totalSeconds--;
            updateDisplay();
            if (totalSeconds <= 0) {
                clearInterval(timerInterval);
                alert("Time's up!");
                resetTimer(25 * 60);
            }
        }, 1000);
    }

    function pauseTimer() {
        isRunning = false;
        playIcon.classList.remove('hidden');
        pauseIcon.classList.add('hidden');
        clearInterval(timerInterval);
    }
    
    function resetTimer(newTimeInMinutes) {
        pauseTimer();
        totalSeconds = newTimeInMinutes * 60;
        updateDisplay();
    }
    
    timerControlBtn.addEventListener('click', () => {
        if (isRunning) {
            pauseTimer();
        } else {
            startTimer();
        }
    });

    focusBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            focusBtns.forEach(b => b.classList.remove('active', 'bg-blue-600', 'text-white'));
            btn.classList.add('active', 'bg-blue-600', 'text-white');
            const newTime = parseInt(btn.dataset.time, 10);
            resetTimer(newTime);
        });
    });
    
    resetBtn.addEventListener('click', () => {
        const activeBtn = document.querySelector('.focus-btn.active');
        const newTime = parseInt(activeBtn.dataset.time, 10);
        resetTimer(newTime);
    });

    updateDisplay(); // Initial display
});
</script>
</body>
</html>

/*
================================================================================
|                                                                              |
|               -- S T U D E N T / S C H E D U L E R _ A C T I O N S . P H P --  |
|                                                                              |
================================================================================
|                                                                              |
|   This is a helper file that handles backend actions (like adding or         |
|   updating tasks) for the scheduler page via AJAX requests, preventing       |
|   full page reloads and creating a smoother user experience.                 |
|                                                                              |
================================================================================
*/
<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}
$student_id = $_SESSION['student_id'];

$action = $_POST['action'] ?? '';

if ($action == 'add_task') {
    $title = $_POST['title'] ?? '';
    $due_date = $_POST['due_date'] ?? '';

    if (empty($title) || empty($due_date)) {
        echo json_encode(['success' => false, 'message' => 'Missing title or due date.']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO tasks (student_id, title, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $student_id, $title, $due_date);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'task_id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add task.']);
    }
    $stmt->close();
} elseif ($action == 'update_task_status') {
    $task_id = $_POST['task_id'] ?? 0;
    $is_completed = $_POST['is_completed'] ?? 0;

    if (empty($task_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing task ID.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE tasks SET is_completed = ? WHERE id = ? AND student_id = ?");
    $stmt->bind_param("iii", $is_completed, $task_id, $student_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update task.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();
?>

/*
================================================================================
|                                                                              |
|                    -- S T U D E N T / T I M E T A B L E . P H P --             |
|                                                                              |
================================================================================
|                                                                              |
|   Allows students to create, view, and manage their personal study           |
|   timetable. Now linked from the main scheduler page.                        |
|                                                                              |
================================================================================
*/
<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}
$student_id = $_SESSION['student_id'];

// Handle form submission for adding a new event
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event'])) {
    $title = $_POST['title'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $conn->prepare("INSERT INTO study_timetable (student_id, title, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $student_id, $title, $day_of_week, $start_time, $end_time);
    $stmt->execute();
    $stmt->close();
    header("Location: timetable.php"); // Refresh to show the new event
    exit();
}

// Handle deletion of an event
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'];
    $stmt = $conn->prepare("DELETE FROM study_timetable WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $event_id, $student_id);
    $stmt->execute();
    $stmt->close();
    header("Location: timetable.php"); // Refresh
    exit();
}

// Fetch all timetable events for the student
$timetable_sql = "SELECT id, title, day_of_week, start_time, end_time FROM study_timetable WHERE student_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
$stmt = $conn->prepare($timetable_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$timetable_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Study Timetable</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <style>
        body { font-family: sans-serif; margin: 20px; }
        .timetable-container { display: flex; flex-wrap: wrap; gap: 20px;}
        .timetable-form { flex-basis: 300px; }
        .timetable-view { flex-grow: 1; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .delete-btn { color: red; text-decoration: none; border: none; background: none; cursor: pointer; padding: 0; }
    </style>
</head>
<body>
    <a href="scheduler.php">&larr; Back to Scheduler</a>
    <h1>My Personal Timetable</h1>

    <div class="timetable-container">
        <div class="timetable-form">
            <h2>Add New Recurring Event</h2>
            <form method="post">
                <p>Title: <input type="text" name="title" required></p>
                <p>Day: 
                    <select name="day_of_week" required>
                        <?php foreach($days_of_week as $day): ?>
                        <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>Start Time: <input type="time" name="start_time" required></p>
                <p>End Time: <input type="time" name="end_time" required></p>
                <button type="submit" name="add_event">Add Event</button>
            </form>
        </div>

        <div class="timetable-view">
            <h2>My Weekly Schedule</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($timetable_events)): ?>
                        <tr><td colspan="4">Your timetable is empty.</td></tr>
                    <?php else: ?>
                        <?php foreach($timetable_events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                            <td><?php echo htmlspecialchars($event['day_of_week']); ?></td>
                            <td><?php echo date("g:i a", strtotime($event['start_time'])) . ' - ' . date("g:i a", strtotime($event['end_time'])); ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" name="delete_event" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

/*
================================================================================
|                                                                              |
|                       -- S T U D E N T / N E W S . P H P --                    |
|                                                                              |
================================================================================
|                                                                              |
|   Displays all news and announcements posted by the administrator,           |
|   including links to download attached PDF files.                            |
|                                                                              |
================================================================================
*/

<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$news_sql = "SELECT title, content, file_path, created_at FROM news ORDER BY created_at DESC";
$news_result = $conn->query($news_sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>News & Announcements</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .news-container { max-width: 800px; margin: auto; }
        .news-article { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .news-article h2 { margin-top: 0; }
        .news-article small { color: #6c757d; }
        .news-article p { white-space: pre-wrap; } /* Respect newlines in content */
        .download-link { display: inline-block; margin-top: 15px; padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <a href="dashboard.php">&larr; Back to Dashboard</a>
    <h1>News & Announcements</h1>

    <div class="news-container">
        <?php if ($news_result->num_rows > 0): ?>
            <?php while($article = $news_result->fetch_assoc()): ?>
                <div class="news-article">
                    <h2><?php echo htmlspecialchars($article['title']); ?></h2>
                    <small>Posted on <?php echo date("F j, Y, g:i a", strtotime($article['created_at'])); ?></small>
                    <hr>
                    <p><?php echo htmlspecialchars($article['content']); ?></p>
                    <?php if (!empty($article['file_path'])): ?>
                        <a href="../uploads/<?php echo htmlspecialchars($article['file_path']); ?>" class="download-link" download>Download Attached PDF</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>There are no news articles at the moment.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>


/*
================================================================================
|                                                                              |
|                    -- S T U D E N T / L O G O U T . P H P --                   |
|                                                                              |
================================================================================
|                                                                              |
|   This file handles the student logout process. It destroys the session and  |
|   redirects the user to the login page.                                      |
|                                                                              |
================================================================================
*/

// student/logout.php

session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();

?>
