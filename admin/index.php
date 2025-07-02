<?php
include "../config.php"
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
    <h2><a href="./upload_news.php">Upload News</a></h2>

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