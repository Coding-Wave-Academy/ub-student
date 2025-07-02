
<?php
session_start();
include './config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT name , matriculation_number , faculty_short_name FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

/**
 * Calculates the GPA for a given set of grades.
 *
 * @param array $grades An array of grade records, each with 'grade' and 'credit_value'.
 * @return float The calculated GPA.
 */
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

/**
 * Returns a CSS class based on the grade.
 *
 * @param string $grade The letter grade (e.g., 'A', 'B+').
 * @return string The corresponding CSS class.
 */
function getGradeClass($grade) {
    switch ($grade) {
        case 'A':
            return 'grade-a';
        case 'B+':
            return 'grade-b-plus';
        case 'B':
            return 'grade-b';
        case 'C':
            return 'grade-c';
        case 'F':
            return 'grade-f';
        default:
            return '';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Grades</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        body { font-family: sans-serif; overflow-x:auto;
            overflow-y:hidden;
            scrollbar-width:none;
            scrollbar-color:transparent; }
        /* Basic styling for tabs */
        .tab { overflow: hidden; margin:2em auto; }
        .tab button { background-color: inherit; float: left; border: 2px solid var(--soft-shade-black); outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s;border-radius:2em;margin-right:2em; }
        .tab button:hover { background-color: var(--soft-blue); color:var(--hard-shade-black); }
        .tab button.active { border:2px solid var(--hard-blue); }
        .tabcontent { display: none; padding: .5em 0; border: 2px solid var(--soft-shade-black); animation: fadeEffect 1s; border-radius:1.2em;}
        .gpa{
            margin:1em;
        }
        @keyframes fadeEffect { from {opacity: 0;} to {opacity: 1;} }

        /* Grade Colors */
        .grade-a { background-color: var(--soft-green); color: var(--hard-green); border-radius:2em; text-align:center; width: 20px; } /* Green */
        .grade-b-plus { background-color: var(--soft-yellow); color: var(--hard-yellow); border-radius:2em; text-align:center; width: 20px; } /* Blue */
        .grade-b { background-color: var(--soft-blue); color: var(--hard-blue); border-radius:2em; text-align:center; width: 20px; } /* Light Blue */
        .grade-c { background-color: var(--soft-purple); color: var(--hard-purple); border-radius:2em; text-align:center; width: 20px; } /* Orange */
        .grade-f { background-color: var(--soft-red); color: var(--hard-red); border-radius:2em; text-align:center; width: 20px; } /* Red */

        /* Responsive Table */
        table { width: 100%; border-collapse: collapse; }
        thead {
             /* Hide thead on small screens, the data is shown in td:before */
            display: none;
        }
        th, td { padding: 1em; text-align: left; border-bottom: 1px solid var(--soft-shade-black); }
        
        /* Larger screens */
        @media screen and (min-width: 601px) {
            thead {
                display: table-header-group; /* Show thead on larger screens */
            }
            body{
                 overflow-x:auto;
            overflow-y:scroll;
            scrollbar-width:none;
            scrollbar-color:transparent; }
            
        }

        /* Small screens */
        @media screen and (max-width: 600px) {
            table, thead, tbody, th, td, tr { 
                display: block; 
            }
             body{
                 overflow-x:auto;
            overflow-y:scroll;
            scrollbar-width:none;
            scrollbar-color:transparent; }
            tr { border: 1px solid #ccc; margin-bottom: 10px;}
            td {
                border: none;
                border-bottom: 1px solid #eee; 
                position: relative;
                padding-left: 50%;
                white-space: normal;
                text-align: right;
            }
            td:before {
                position: absolute;
                top: 6px;
                left: 6px;
                width: 45%; 
                padding-right: 10px; 
                white-space: nowrap;
                font-weight: bold;
                text-align: left;
            }
            /* Data labels for cells */
            td:nth-of-type(1):before { content: "Course Name"; }
            td:nth-of-type(2):before { content: "Credit Value"; }
            td:nth-of-type(3):before { content: "CA Marks"; }
            td:nth-of-type(4):before { content: "Exam Marks"; }
            td:nth-of-type(5):before { content: "Total / 100"; }
            td:nth-of-type(6):before { content: "Grade"; }

        }

    </style>
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
</head>
<body>


    <div class="tab">
        <?php
        $semester_result = $conn->query("SELECT DISTINCT semester FROM grades WHERE student_id = $student_id ORDER BY semester ASC");
        while ($semester_row = $semester_result->fetch_assoc()) {
            $semester = $semester_row['semester'];
            echo "<button class='tablinks' onclick=\"openSemester(event, 'Semester" . $semester . "')\">Semester " . $semester . "</button>";
        }
        ?>
    </div>

    <?php
    $gpa_per_semester = [];
    $semester_result->data_seek(0); // Reset pointer
    while ($semester_row = $semester_result->fetch_assoc()) {
        $semester = $semester_row['semester'];
        echo "<div id='Semester" . $semester . "' class='tabcontent'>";
        
        $sql = "SELECT courses.course_name, courses.credit_value, grades.ca_marks, grades.exam_marks, grades.total_marks, grades.grade 
                FROM grades
                JOIN courses ON grades.course_id = courses.id
                WHERE grades.student_id = ? AND grades.semester = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $student_id, $semester);
        $stmt->execute();
        $grades_result = $stmt->get_result();
        $grades_data = $grades_result->fetch_all(MYSQLI_ASSOC);

        $gpa = calculateGPA($grades_data);
        $gpa_per_semester[$semester] = $gpa;
        
        $gpa_change_text = '';
        if (isset($gpa_per_semester[$semester - 1])) {
            $previous_gpa = $gpa_per_semester[$semester - 1];
            $gpa_difference = $gpa - $previous_gpa;
            $gpa_change_text = sprintf("%+.2f", $gpa_difference);
        }


        echo "<table>";
        echo "<thead><tr><th>Course Name</th><th>Credit Value</th><th>CA Marks</th><th>Exam Marks</th><th>Total / 100</th><th>Grade</th></tr></thead>";
        echo "<tbody>";

        foreach ($grades_data as $grade_row) {
            $grade_class = getGradeClass($grade_row['grade']);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($grade_row['course_name']) . "</td>";
            echo "<td>" . htmlspecialchars($grade_row['credit_value']) . "</td>";
            echo "<td>" . htmlspecialchars($grade_row['ca_marks']) . "</td>";
            echo "<td>" . htmlspecialchars($grade_row['exam_marks']) . "</td>";
            echo "<td>" . htmlspecialchars($grade_row['total_marks']) . "</td>";
            echo "<td class='" . $grade_class . "'>" . htmlspecialchars($grade_row['grade']) . "</td>";
           
            echo "</tr>";
        }
        
        echo "</tbody></table>";
         echo '<h3 class="gpa">Final GPA: ' . number_format($gpa, 2) . ' </h3>';
        echo "</div>";
        $stmt->close();
    }
    ?>

    <script>
    // Get the element with id="defaultOpen" and click on it
    if(document.getElementsByClassName("tablinks")[0]){
        document.getElementsByClassName("tablinks")[0].click();
    }
    </script>

</body>
</html>
<?php
$conn->close();
?>
