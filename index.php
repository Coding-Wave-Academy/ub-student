<?php

session_start();
include './config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT name , matriculation_number , faculty_short_name , image FROM students WHERE id = ?");
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
    <title>UB Student - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <link rel="stylesheet" href="./css/style.css">
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
*{
    text-decoration:none;
}
a{
    color:var(--hard-shade-black);
}

         .grade-letter.grade-a { background-color: var(--soft-green);color: var(--hard-green); }
        .grade-letter.grade-b-plus { background-color: var(--soft-yellow);color: var(--hard-yellow); }
        .grade-letter.grade-b { background-color: var(--soft-purple);color: var(--hard-purple); }
        .grade-letter.grade-c { background-color: var(--soft-blue); color: var(--hard-blue); }
        .grade-letter.grade-f { background-color: var(--soft-red); color: var(--hard-red); }

        .full-grades-link { display: block; margin-top: 20px; text-align: center; }
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
                        <a href="index.php" class="nav-link active">
                            <span class="nav-icon"><img src="./assets/icons/filled-dashboard.svg" alt=""></span>
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
                        <a href="grades.php" class="nav-link">
                            <span class="nav-icon"><img src="./assets/icons/academic-cap-nocolor.svg" alt=""></span>
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

            <!-- Cards Grid -->
            <div class="cards-grid">
              
                    <div class="card">
                    <div class="card-header">
                        <div class="card-icon purple"><img src="./assets/icons/library.svg" alt="UB Structure"></div>
                        <span class="card-label">UB Structure</span>
                    </div>
                   <a href="#"> <div class="card-title">Academic Structure</div></a>
                </div>

              
               
                    <div class="card">
                    <div class="card-header">
                        <div class="card-icon blue"><img src="./assets/icons/book-open.svg" alt="Registered Courses"></div>
                        <span class="card-label">Registered Courses</span>
                    </div>
                   <a href="#"> <div class="card-title">Student Form B</div></a>
                </div>
               

                
                    <div class="card">
                    <div class="card-header">
                        <div class="card-icon green"><img src="./assets/icons/academic-cap.svg" alt="Grade Traker"></div>
                        <span class="card-label">GPA Tracker</span>
                    </div>
                   <a href="./grades.php"> <div class="card-value"><?php echo number_format($latest_gpa, 2); ?></div></a>
                    <div class="card-subtitle">
                          <span class="semester-info"><?php echo $latest_semester ? $latest_semester . 'nd Semester' : 'N/A'; ?></span>
                        <?php 
                    $diff_class = 'neutral';
                    if ($gpa_change > 0) $diff_class = 'positive';
                    if ($gpa_change < 0) $diff_class = 'negative';
                ?>
                <span class="gpa-diff card-badge <?php echo $diff_class; ?>">
                    <?php echo sprintf("%+.1f", $gpa_change); ?>
                </span>
                          <!-- <span class="card-badge">+0.0</span> -->
                    </div>

                </div>
               

              
                 <div class="card">
                    <div class="card-header">
                        <div class="card-icon yellow"><img src="./assets/icons/clock.svg" alt="Study Hours"></div>
                        <span class="card-label">Study Hours</span>
                    </div>
                    <a href="./schedular.php"><div class="card-value">8.5</div></a>
                    <div class="card-subtitle">Hours this week<span class="card-badge-study">+1.5</span></div>
                </div>

             
                
               
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon red"><img src="./assets/icons/users.svg" alt="UB Classroom"></div>
                        <span class="card-label">UB Classroom</span>
                    </div>
                   <a href="classroom.php"> <div class="card-title">No Conference Class</div></a>
                </div>
               

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon cyan"><img src="./assets/icons/globe.svg" alt="News Feed"></div>
                        <span class="card-label">News Feed</span>
                    </div>
                   <a href="./news.php"> <div class="card-title">News and Announcements</div></a>
                </div>
            </div>

            <!-- Study Timetable Section -->
            <div class="study-section">
                <div class="section-header">
                    <h2 class="section-title">Study TimeTable</h2>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <span class="date-range">May 1st - May 21st,2025</span>
                        <div class="calendar-nav">
                            <button class="nav-btn">‹</button>
                            <button class="nav-btn">›</button>
                        </div>
                    </div>
                </div>

                <div class="calendar-header">
                    <div class="calendar-day">Sun</div>
                    <div class="calendar-day">Mon</div>
                    <div class="calendar-day">Tue</div>
                    <div class="calendar-day">Wed</div>
                    <div class="calendar-day">Thurs</div>
                    <div class="calendar-day">Fri</div>
                    <div class="calendar-day">Sat</div>
                </div>

                
            </div>
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

            <!-- Grades  -->
                <h3>Grades</h3>
               

                <div class="grades-container">
                <?php
                $recent_grades = array_slice($grades_list, 0, 3);
                foreach ($recent_grades as $grade_item):
                ?>
                <div class="grade-item">
                    <div class="grade-info">
                        <h4 class="course-name"><?php echo htmlspecialchars($grade_item['course_name']); ?></h4>
                        <span class="semester-text"><?php echo $grade_item['semester']; ?>st Semester</span>
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

        <!--  Remainder  -->

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
                <!-- <img src="./assets/imgs/kandi_innovate_upscayl_2x_realesrgan-x4plus.png" alt="Avatar" class="mobile-avatar"> -->
               <div class="mobile-avater-div">
                 <img src="<?= $photo ?>" alt="Avatar" class="mobile-avatar">
               </div>
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
            <div class="mobile-cards">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon purple"><img src="./assets/icons/library.svg" alt="UB Structure"></div>
                        <span class="card-label">UB Structure</span>
                    </div>
                   <a href="#"> <div class="card-title">Academic Structure</div></a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon blue"><img src="./assets/icons/book-open.svg" alt="Registered Courses"></div>
                        <span class="card-label">Your  Courses</span>
                    </div>
                    <a href="#"><div class="card-title">Student Form B</div></a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon green"><img src="./assets/icons/academic-cap.svg" alt="Grade Traker"></div>
                        <span class="card-label">GPA Tracker</span>
                    </div>
                    <a href="./grades.php"> <div class="card-value"><?php echo number_format($latest_gpa, 2); ?></div></a>
                    <div class="card-subtitle">
                          <span class="semester-info"><?php echo $latest_semester ? $latest_semester . 'nd Semester' : 'N/A'; ?></span>
                        <?php 
                    $diff_class = 'neutral';
                    if ($gpa_change > 0) $diff_class = 'positive';
                    if ($gpa_change < 0) $diff_class = 'negative';
                ?>
                <span class="gpa-diff card-badge <?php echo $diff_class; ?>">
                    <?php echo sprintf("%+.1f", $gpa_change); ?>
                </span>
                          <!-- <span class="card-badge">+0.0</span> -->
                </div>
            </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon yellow"><img src="./assets/icons/clock.svg" alt="Study Hours"></div>
                        <span class="card-label">Study Hours</span>
                    </div>
                    <a href="#"><div class="card-value">8.5</div></a>
                    <div class="card-subtitle">Hours this week<span class="card-badge-study">+1.5</span></div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon red"><img src="./assets/icons/users.svg" alt="UB Classroom"></div>
                        <span class="card-label">UB Classroom</span>
                    </div>
                    <a href="#"><div class="card-title">No Conference Class</div></a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon cyan"><img src="./assets/icons/globe.svg" alt="News Feed"></div>
                        <span class="card-label">News Feed</span>
                    </div>
                   <a href="./news.php"> <div class="card-title">Latest Stories</div></a>
                </div>
            </div>

            <div class="mobile-calendar-section">
                <div class="section-header">
                    <h3 class="section-title">Study TimeTable</h3>
                    <!-- <span class="date-range">May 1st - May 21st,2025</span> -->
                    <div class="calendar-nav">
                        <button class="nav-btn">‹</button>
                        <button class="nav-btn">›</button>
                    </div>
                </div>
                <div class="mobile-calendar-header">
                    <div>S</div>
                    <div>M</div>
                    <div>T</div>
                    <div>W</div>
                    <div>TH</div>
                    <div>F</div>
                    <div>S</div>
                </div>
            </div>
        </div>

        <div class="mobile-bottom-nav">
            <a href="index.php" class="nav-item-mobile active">
                <span><img src="./assets/icons/filled-dashboard.svg" alt=""></span>
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
            <a href="grades.php" class="nav-item-mobile">
                <span><img src="./assets/icons/academic-cap-nocolor.svg" alt=""></span>
                <span>Grades</span>
            </a>
            <a href="note.php" class="nav-item-mobile">
                <span><img src="./assets/icons/pencil-alt.svg" alt=""></span>
                <span>Notes</span>
            </a>
        </div>
    </div>

<script src="./js/main.js"></script>
</body>
</html>