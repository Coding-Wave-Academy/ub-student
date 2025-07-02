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
    <title>UB Go Student - UB Classroom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <link rel="stylesheet" href="./css/classroom.css">
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

        .participants-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        /* Main Content / Body */
    .dashboard-main {
      flex: 1;
      display: flex; flex-direction: column;
      padding: 0 0px 0 0; min-height: 100vh;
    }

    /* Header */
    .header {
      background: #fff;
      border-top-right-radius: 18px;
      height: 54px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #e5e7eb;
      padding: 0 38px 0 32px;
      position: relative;
    }
    .header h1 {
      font-size: 1.15rem;
      font-weight: 600;
      opacity: .97;
      letter-spacing: 0.015em;
      margin-right: 16px;
    }
    .header-search {
      flex: 1 1 0;
      margin-left: 16px;
      margin-right: 23px;
      position: relative;
      max-width: 395px;
    }
    .header-search input[type="text"] {
      width: 100%;
      padding: 10px 2.5rem 10px 40px;
      border: 1.2px solid #e5e7eb;
      border-radius: 11px;
      background: #f2f5fa;
      font-size: 1rem;
      color: #222;
      outline: none;
      font-family: inherit;
      transition: border .18s;
    }
    .header-search svg {
      position: absolute;
      left: 13px; top: 9px;
      width: 19px; height: 19px;
      color: #b2bac9;
    }
    .header-right {
      display: flex; align-items: center; gap: 21px;
    }
    .header-bell {
      width: 36px; height: 36px;
      background: #f2f5fa;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      position: relative;
      cursor: pointer;
    }
    .header-bell svg { opacity: .65; }
    .header-bell .bell-dot {
      position: absolute; top: 8px; right: 7px;
      width: 9px; height: 9px;
      background: #ff4343;
      border-radius: 9999px;
      border: 2.5px solid #fff;
    }
    .header-avatar {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: #e5e7eb;
      border: 1.6px solid #e5e7eb;
      object-fit: cover;
    }

    /* Body panels layout */
    .body-panels {
      flex: 1; display: flex;
      gap: 22px; padding: 17px 23px 0 23px;
      background: none;
      min-height: 0;
    }
    .dashboard-section {
      flex: 2 1 0;
      min-width: 0; display: flex; flex-direction: column;
      gap: 13px;
    }
    .right-panel {
      flex: 1 1 350px;
      min-width: 288px; max-width: 380px;
      display: flex; flex-direction: column;
      gap: 19px; min-height: 0;
    }

    /* Video Widget */
    .video-widget {
      background: #fff;
      border-radius: 16px;
      border: 1.6px solid #e4e8ee;
      padding: 16px 16px 9px 16px;
      box-shadow: 0 2px 9px rgba(80, 114, 160, 0.04);
      margin-bottom: 5px;
    }
    .video-title-row {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 14px;
    }
    .video-title-row h2 {
      font-size: 1.39rem;
      font-weight: 700;
      margin: 0;
    }
    .video-teacher {
      display: flex; align-items: center; gap: 8px;
      font-size: 0.96rem; color: #9096a3; font-weight: 500;
    }
    .video-teacher img {
      width: 29px; height: 29px; border-radius: 50%; object-fit: cover;
      background: #e2e8f0;
    }
    .video-placeholder {
      width: 100%;
      background: #e6eaf3;
      border-radius: 10px;
      overflow: hidden;
      position:relative;
      aspect-ratio: 16/7.3;
      box-shadow: 0 1.5px 2.5px #7191ab11;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 32px;
    }
    .video-placeholder-img {
      width: 100%; height: 100%; object-fit: cover; min-height: 190px;
      background: #dbe4f5;
      display:block;
    }
    /* Controls Bar */
    .video-controls {
      display: flex; justify-content: center;
      align-items: center; gap: 25px;
      width: 100%;
      position: absolute; bottom: 12px; left: 0; right: 0;
    }
    .video-controls-btn {
      background: #fff;
      border: none;
      margin: 0 0 0 0;
      border-radius: 12px;
      width: 46px; height: 46px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.54rem;
      box-shadow: 0 2.5px 7px #dde4f7bb;
      cursor: pointer;
      border: 1px solid #e4e8f0;
      transition: background .14s, transform .13s;
      color: #4958b3;
    }
    .video-controls-btn:active, .video-controls-btn:hover { background: #e8eefc; }
    .video-controls-btn.blue {
      background: #2563eb;
      color: #fff;
      border: none;
      width: 50px; height: 50px; font-size: 1.7rem;
      box-shadow: 0 3px 14px #2563eb29;
    }
    .video-controls-btn[aria-label="More"] svg {
      width: 22px;height: 22px;
    }

    /* Participants */
    .participants-box {
      background: #fff;
      border-radius: 13px;
      border: 1.6px solid #e4e8ee;
      margin-top: 18px; padding: 16px 20px 11px 20px;
    }
    .participants-title {
      font-size: 1.18rem;
      font-weight: 600;
      margin-bottom: 12px;
      color: #313438;
      letter-spacing: 0.01em;
    }
    .participants-row {
      display: flex; align-items: center;
      gap: 15px; flex-wrap:wrap;
    }
    .participant {
      width: 57px; height: 54px;
      background: #fff;
      border-radius: 13px;
      border: 2px solid #e4e8ee;
      overflow: hidden;
      display: flex; align-items: center; justify-content: center;
      transition: border .16s;
      position: relative;
    }
    .participant.active {
      border: 2px solid #2563eb;
      box-shadow: 0 2px 13px #a9cdf9a9;
    }
    .participant img {
      width: 100%; height: 100%; object-fit: cover;
      border-radius: 12px;
    }
    .participant span {
      font-size: 1rem; color: #232b40; font-weight: 600;
      background: #e5e9f3bd;
      border-radius: 9px;
      padding: 3px 11px;
      position: absolute; left: 0; right: 0; bottom: 14px;
      text-align: center;
      pointer-events: none;
    }

    /* Chat Panel */
    .chat-panel {
      background: #fff;
      border-radius: 13px;
      border: 1.6px solid #e4e8ee;
      padding: 0;
      display: flex; flex-direction: column;
      min-height: 370px; max-height: 512px;
    }
    .chat-header {
      border-bottom: 1.2px solid #eef2f9;
      padding: 20px 19px 11px 19px;
      font-weight: 700; font-size: 1.23rem; color: #2c2f3a;
      display: flex; align-items: center; justify-content: space-between;
      letter-spacing: 0.02em;
    }
    .chat-body {
      flex: 1 1 auto;
      padding: 14px 19px 5px 19px;
      overflow-y: auto; display: flex; flex-direction: column;
      gap: 14px;
      min-height:87px; max-height: 250px;
      background:#fafbfd;
    }
    .chat-msg {
      display: flex; flex-direction: row; align-items: flex-end; gap: 9px;
      font-size: 1.01rem;
    }
    .chat-msg.you { justify-content: flex-end; }
    .chat-msg .avatar {
      width: 35px; height: 35px; border-radius: 50%; object-fit: cover;
      margin-bottom: 2px;
    }
    .chat-bubble {
      background: #f4f6f8;
      padding: 8px 16px; border-radius: 15px 15px 15px 5px;
      font-size: 1rem;
      color: #323856;
      max-width: 235px;
      font-weight: 500;
      margin:0;
    }
    .chat-msg.you .chat-bubble {
      background: #e3ebfd; color: #3463c2; border-radius: 15px 15px 5px 15px;
      font-weight: 600;
    }
    .chat-name {
      font-size: 0.95rem;
      color: #64748b;
      font-weight: 600;
      margin-bottom: 2.5px;
      letter-spacing: 0.01em;
    }
    .chat-time {
      font-size: 0.91rem;
      color: #a4adc2; margin-left:3px;
      font-weight: 400;
      margin-bottom: 1px;
      margin-right:9px;
    }

    /* Chat input */
    .chat-input-row {
      border-top: 1.2px solid #eef2f9;
      padding: 13px 15px 13px 17px;
      display: flex; align-items: center;
      background: #f9fafd;
      gap: 9px;
    }
    .chat-input-row input[type="text"] {
      flex: 1;
      background: #f4f6fb;
      border: 1.2px solid #e4e8ee;
      border-radius: 20px;
      padding: 7px 18px;
      font-size: 1.04rem;
      outline: none;
    }
    .chat-input-row button {
      background: #2563eb; color: #fff;
      border: none; border-radius: 50%;
      width: 36px; height: 36px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.27rem; cursor: pointer;
      transition: background .13s;
    }
    .chat-input-row button:hover { background: #1946ad; }

    /* Accordion */
    .accordion {
      background: #fff; border-radius: 13px;
      border: 1.6px solid #e4e8ee;
      margin-top: 6px; margin-bottom: 0;
      font-size: 1.06rem; font-weight: 600;
      transition: box-shadow .15s;
    }
    .accordion-header {
      padding: 16px 19px;
      cursor: pointer;
      display: flex; align-items: center; justify-content: space-between;
      border-bottom: 1.1px solid #f2f1f7;
      transition: background .15s;
    }
    .accordion-header:hover { background: #f5f7fa; }
    .accordion-arrow {
      transition: transform .19s;
      width: 23px;height: 23px;display:flex;align-items:center;justify-content:center;
    }
    .accordion.open .accordion-arrow { transform: rotate(90deg); }
    .accordion-body {
      display: none;
      color: #6c7c9c;
      font-weight: 500;
      font-size: .97rem;
      padding: 7px 20px 20px 25px;
    }
    .accordion.open .accordion-body { display: block; }
    /* Responsive */
    @media (max-width: 1075px) {
      .body-panels {
        flex-direction: column;
      }
      .right-panel {
        max-width: unset; min-width: 0; width:100%;
      }
      .dashboard-section {
        width:100%; min-width:0;
      }
    }
    @media (max-width:620px) {
      .shell, .body-panels { flex-direction: column; min-width:0;width:100vw; }
      .sidebar, .right-panel { min-width:0;max-width:100vw;width:100vw;}
      .header { padding: 0 8px;}
      .body-panels {padding: 5px;}
    }
    /* Scrollbars */
    ::-webkit-scrollbar { width: 7px; background: #edeef0;}
    ::-webkit-scrollbar-thumb { background: #e4e8ee; border-radius: 5px;}
  
    </style>
    <!-- Desktop Layout -->
    <div class="desktop-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <img src="./assets/imgs/ub.jpg" alt="">
                 <span>UB Go Student</span>
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
                        <a href="classroom.php" class="nav-link active">
                            <span class="nav-icon"><img src="./assets/icons/filled-classroom.svg" alt=""></span>
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

            <!-- Scheduler Section -->
             <!--The code to the video meeting starts here-->
             <div class="body-panels">
      <div class="dashboard-section">

        <!-- Video Widget -->
        <section class="video-widget">
          <button id="start" type="button">Start Meeting</button>
          <div id="video-app">
          </div>
        </section>

        <!-- Participants -->
        <div class="participants-box">
          <div class="participants-title">Participants</div>
          <div class="participants-row">
            <div class="participant active">
              <img src="https://i.pravatar.cc/76?img=67" alt="">
            </div>
            <div class="participant">
              <img src="https://i.pravatar.cc/76?img=58" alt="">
            </div>
            <div class="participant">
              <img src="https://i.pravatar.cc/76?img=34" alt="">
            </div>
            <div class="participant">
              <img src="https://i.pravatar.cc/76?img=6" alt="">
            </div>
            <div class="participant">
              <span>+100<br>more</span>
            </div>
          </div>
        </div>
      </div>
      <!-- Right panel: chat/accordion -->
      <aside class="right-panel">
        <!-- Chat -->
        <div class="chat-panel">
          <div class="chat-header">Chats</div>
          <div class="chat-body" id="chat-body">
            <div class="chat-msg">
              <img class="avatar" src="https://i.pravatar.cc/90?img=21" alt="">
              <div>
                <span class="chat-name">Dr.Njanga Bernard</span>
                <span class="chat-time">9:27PM</span>
                <div class="chat-bubble">Hope the lecture is flowing?</div>
              </div>
            </div>
            <div class="chat-msg">
              <img class="avatar" src="https://i.pravatar.cc/90?img=15" alt="">
              <div>
                <span class="chat-name">Kamini Tchatak</span>
                <span class="chat-time">9:31PM</span>
                <div class="chat-bubble">It actually fun and good sir</div>
              </div>
            </div>
            <div class="chat-msg you">
              <div>
                <span class="chat-time">You </span>
                <div class="chat-bubble" style="margin-left:6px;">All good Sir</div>
              </div>
            </div>
          </div>
          <form class="chat-input-row" id="chat-form" autocomplete="off">
            <input type="text" id="chat-input" placeholder="Type message">
            <button type="submit" title="Send">
              <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
          </form>
        </div>
        <!-- Accordions -->
        <div class="accordion" id="accordion1">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            Lessons Materials
            <span class="accordion-arrow">
              <svg width="19" height="19" stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </div>
          <div class="accordion-body">
            <ul style="list-style:square;padding-left:1.2em;color:#606b80;font-size:0.97rem;margin-top:7px;">
              <li>Intro-to-CS.pdf</li>
              <li>Lecture Slide week 2</li>
              <li>Lab Worksheet.docx</li>
            </ul>
          </div>
        </div>
        <div class="accordion" id="accordion2">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            Jotter / Notes
            <span class="accordion-arrow">
              <svg width="19" height="19" stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </div>
          <div class="accordion-body">
            <ul style="padding-left:1.18em;color:#57608c;font-size:0.97rem;margin-top:5px;">
              <li>Quiz#1 debrief</li>
              <li>Summary from Class</li>
            </ul>
          </div>
        </div>
      </aside>
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
            

                
<!-- Scheduler Mobile Section -->
            

           
                
       

        <div class="mobile-bottom-nav">
            <a href="index.php" class="nav-item-mobile ">
                <span><img src="./assets/icons/no-fill-dashboard.svg" alt=""></span>
                <span>Dashboard</span>
            </a>
            <a href="classroom.php" class="nav-item-mobile active">
                <span><img src="./assets/icons/filled-classroom.svg" alt=""></span>
                <span>Classroom</span>
            </a>
            <a href="schedular.php" class="nav-item-mobile">
                <span><img src="./assets/icons/calendar.svg" alt=""></span>
                <span>Schedule</span>
            </a>
            <a href="grades.php" class="nav-item-mobile ">
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
<script src='https://meet.jit.si/external_api.js'></script>
    <script>
        var button = document.querySelector('#start');
        var container = document.querySelector('#video-app');
        var api = null;
        
        button.addEventListener('click', () => {
            var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var stringLength = 30;// Length of the random string for room name
            button.classList.add('hidden')// Hide the button after starting the meeting
        
            function pickRandom() {
                return possible[Math.floor(Math.random() * possible.length)];
            }
        
        var randomString = Array.apply(null, Array(stringLength)).map(pickRandom).join('');
            var domain = "meet.jit.si";
            var options = {
                "roomName": randomString,// Generate a random room name
                "parentNode": container,// The container where the Jitsi meeting will be rendered
                "width": 550,// Width of the Jitsi meeting container
                "height": 450,// Height of the Jitsi meeting container
            };
            api = new JitsiMeetExternalAPI(domain, options);
        });

        
  // Accordion logic
  function toggleAccordion(header) {
    var parent = header.parentNode;
    parent.classList.toggle('open');
  }
  // Chat add logic
  document.getElementById('chat-form').onsubmit = function(e) {
    e.preventDefault();
    var input = document.getElementById('chat-input');
    var text = input.value.trim();
    if(!text) return;
    var msg = document.createElement('div');
    msg.className = 'chat-msg you';
    msg.innerHTML = `<div><span class="chat-time">You </span>
      <div class="chat-bubble" style="margin-left:6px;">${text.replace(/[<>&"]/g, c => ({'<':'&lt;','>':'&gt;','"':'&quot;','&':'&amp;'}[c]))}</div></div>`;
    var body = document.getElementById('chat-body');
    body.appendChild(msg);
    input.value = '';
    body.scrollTop = body.scrollHeight;
  };
  // Open first accordion by default
  document.getElementById('accordion1').classList.add('open');
    </script>
</body>
</html>