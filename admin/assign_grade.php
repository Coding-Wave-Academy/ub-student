<?php
include '../config.php';

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
