<?php
include '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_name = $_POST['course_name'];
    $course_code = $_POST['course_code'];
    $credit_value = $_POST['credit_value'];

    $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code, credit_value) VALUES (?,?,?)");
    $stmt->bind_param("ssi", $course_name, $course_code,$credit_value);

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
         Course Code: <input type="text" name="course_code" required><br>
        Credit Value: <input type="number" name="credit_value" required><br>
        <input type="submit" value="Add Course">
    </form>

    <br>
    <a href="index.php">Back to Admin Dashboard</a>

</body>
</html>