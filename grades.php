<?php

session_start();
include './config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT name , matriculation_number , faculty_short_name, image FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

$photo = !empty($student['image'])
    ? htmlspecialchars($student['image'])
    : './studentimgs/default-pp.png';


// Split Names are return in the format "Lastname Firstname"
$parts = explode(' ', trim($student['name']));
$firstName = $parts[1];



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

    if ($total_credits == 0) {
        return 0;
    }

    return $total_points / $total_credits;
}

// Fetch all grades for the student to calculate GPAs
$all_grades_sql = "SELECT c.credit_value, g.grade, g.semester 
                   FROM grades g 
                   JOIN courses c ON g.course_id = c.id 
                   WHERE g.student_id = ? 
                   ORDER BY g.semester";
$stmt = $conn->prepare($all_grades_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$all_grades_result = $stmt->get_result();
$all_grades_data = $all_grades_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$gpa_per_semester = [];
$grades_by_semester = [];

foreach($all_grades_data as $grade_record) {
    $grades_by_semester[$grade_record['semester']][] = $grade_record;
}

foreach($grades_by_semester as $semester => $grades) {
    $gpa_per_semester[$semester] = calculateGPA($grades);
}

// Get latest semester and GPA
$latest_semester = 0;
$latest_gpa = 0.0;
$gpa_change = 0.0;

if(!empty($gpa_per_semester)) {
    $latest_semester = max(array_keys($gpa_per_semester));
    $latest_gpa = $gpa_per_semester[$latest_semester];
    $previous_semester = $latest_semester - 1;
    if(isset($gpa_per_semester[$previous_semester])) {
        $gpa_change = $latest_gpa - $gpa_per_semester[$previous_semester];
    }
}

// Fetch all courses and grades for the "Grades" section
$grades_list_sql = "SELECT c.course_name, g.semester, g.grade 
                    FROM grades g
                    JOIN courses c ON g.course_id = c.id
                    WHERE g.student_id = ?
                    ORDER BY g.id DESC"; // Show most recent first
$stmt = $conn->prepare($grades_list_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades_list_result = $stmt->get_result();
$grades_list = $grades_list_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UB Student - Grades</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <link rel="stylesheet" href="./css/grade.css">
</head>
<body>
    <style>
        :root{
    --hard-blue:#3B82F6;
    --soft-blue:rgba(59, 130, 246, 0.12);
    --hard-red:#FF5255;
    --soft-red:rgba(255, 82, 85, 0.12);
    --hard-green:#15803D;
    --soft-green:rgba(220, 252, 231, 0.87);
    --hard-yellow:#D99804;
    --soft-yellow:rgba(255, 243, 72, 0.65);
    --hard-shade-black:#0D092B;
    --soft-shade-black:#8991A0;
    --hard-purple:#3700FF;
    --soft-purple:rgba(55, 0, 255, 0.21);
    --hard-white:#fff;
    --soft-white:#F5F5F7;
    --hard-turquois:#0D9488;
    --soft-turquois:rgba(13, 148, 136, 0.17);
}

        .grade-iframe{
            width: 100%;
            height:100%;
            overflow-x:auto;
            overflow-y:hidden;
            scrollbar-width:hidden;
            scrollbar-color:var(--soft-shade-black);
        }
        .grade-iframe::-webkit-scrollbar{
            width: 0;
            background-color:var(--soft-shade-black);
        }
    </style>
    <!-- Desktop Layout -->
    <div class="desktop-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="./assets/imgs/ub.jpg" alt="">
                 <span>UB Student</span>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link ">
                            <span class="nav-icon"><img src="./assets/icons/no-fill-dashboard.svg" alt=""></span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="classroom.php" class="nav-link">
                            <span class="nav-icon"><img src="./assets/icons/video-camera.svg" alt=""></span>
                            Classroom
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="schedular.php" class="nav-link">
                            <span class="nav-icon"><img src="./assets/icons/calendar.svg" alt=""></span>
                            Schedular
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="grades.php" class="nav-link active">
                            <span class="nav-icon"><img src="./assets/icons/filled-grade.svg" alt=""></span>
                            Grades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="note.php" class="nav-link">
                            <span class="nav-icon"><img src="./assets/icons/pencil-alt.svg" alt=""></span>
                            Notes
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Student Portal</h1>
                <!-- <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search courses, grade, or notes">
                    <span class="search-icon">🔍</span>
                </div> -->
                <div class="header-side">
                    <button class="head-btn" title="Notifications">
                    <img src="./assets/icons/bell.svg" alt="Notification">
                </button>
                <button class="head-btn" title="Help & Support">
                    <img src="./assets/icons/information-circle.svg" alt="Help and Support">
                </button>
                <button class="head-btn" title="Settings">
                    <img src="./assets/icons/cog.svg" alt="Settings">
                </button>
                </div>
            </div>

            <!-- Grades Section -->
             <iframe class="grade-iframe" src="./grade.php" frameborder="0"></iframe>

            
            
        </div>

        <!-- Profile Panel -->
        <div class="profile-panel">
            <h3>Profile</h3>
            <div class="profile-header">
                <div class="profile-badge"> <?php echo htmlspecialchars($student['faculty_short_name']); ?> Student</div>
                <img src="<?= $photo ?>" alt="Avatar" class="profile-avatar">
                <div class="profile-name"> <?php echo htmlspecialchars($student['name']); ?></div>
                <div class="profile-matricule">MAT NO:  <?php echo htmlspecialchars($student['matriculation_number']); ?></div>
            </div>

            <div class="profile-section">
                <h3>Grades</h3>
                <!-- <div class="grade-item">
                    <div class="grade-info">
                        <h4>Python for Networking</h4>
                        <p>2nd Semester</p>
                    </div>
                    <div class="grade-badge grade-a">A</div>
                </div> -->

                <div class="grades-container">
                <?php
                $recent_grades = array_slice($grades_list, 10, 3);
                foreach ($recent_grades as $grade_item):
                ?>
                <div class="grade-item">
                    <div class="grade-info">
                        <h4 class="course-name"><?php echo htmlspecialchars($grade_item['course_name']); ?></h4>
                        <span class="semester-text"><?php echo $grade_item['semester']; ?>nd Semester</span>
                    </div>
                    <div class="grade-badge grade-letter <?php echo getGradeClass($grade_item['grade']); ?>">
                        <?php echo htmlspecialchars($grade_item['grade']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($recent_grades)): ?>
                    <p>No recent grades to display.</p>
                <?php endif; ?>
            </div>
                
                
            </div>

            <div class="profile-section">
                <h3>Reminder</h3>
                <div class="remainder">
                     <div class="reminder-item">
                    <div class="reminder-icon purple"><img src="./assets/icons/puzzle.svg" alt="Remainder 1"></div>
                    <div class="reminder-info">
                        <h4>Data Security CA</h4>
                        <p>Tomorrow, 9:30 AM</p>
                    </div>
                </div>
                <div class="reminder-item">
                    <div class="reminder-icon yellow"><img src="./assets/icons/book-icon.svg" alt="Remainder 2"></div>
                    <div class="reminder-info">
                        <h4>Study Mathematics IV</h4>
                        <p>Today, 7:30 PM</p>
                    </div>
                </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Layout -->
    <div class="mobile-layout">
        <div class="mobile-header">
            <div class="mobile-profile">
                <img src="<?= $photo ?>" alt="Avatar" class="mobile-avatar">
                <div class="gpa-badge">
                <div class="mobile-gpa-badge">
                    <img src="./assets/icons/Star 1.svg" alt="star"> <span><?php echo number_format($latest_gpa, 2); ?></span>
                </div>
                
            </div>
                <div class="mobile-profile-info">
                    <h2>Hello,  <?php echo htmlspecialchars($firstName); ?></h2>
                    <p> <?php echo htmlspecialchars($student['matriculation_number']); ?></p>
                </div>
            </div>
            
            <div class="mobile-bell"><img src="./assets/icons/filled-bell.svg" alt=""></div>
        </div>

        <div class="mobile-main">
            

                
<!-- Grades Section -->
             <iframe class="grade-iframe-mobile" src="./grade.php" frameborder="0" height="100vh"></iframe>
                

                

              

                
            </div>

           
                
       

        <div class="mobile-bottom-nav">
            <a href="index.php" class="nav-item-mobile ">
                <span><img src="./assets/icons/no-fill-dashboard.svg" alt=""></span>
                <span>Dashboard</span>
            </a>
            <a href="classroom.php" class="nav-item-mobile">
                <span><img src="./assets/icons/video-camera.svg" alt=""></span>
                <span>Classroom</span>
            </a>
            <a href="schedular.php" class="nav-item-mobile">
                <span><img src="./assets/icons/calendar.svg" alt=""></span>
                <span>Schedule</span>
            </a>
            <a href="grades.php" class="nav-item-mobile active">
                <span><img src="./assets/icons/filled-grade.svg" alt=""></span>
                <span>Grades</span>
            </a>
            <a href="note.php" class="nav-item-mobile">
                <span><img src="./assets/icons/pencil-alt.svg" alt=""></span>
                <span>Notes</span>
            </a>
        </div>
    </div>

<script src="./js/main.js"></script>
<script>
        function openSemester(evt, semesterName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(semesterName).style.display = "block";
            evt.currentTarget.className += " active";
        }
    </script>
</body>
</html>