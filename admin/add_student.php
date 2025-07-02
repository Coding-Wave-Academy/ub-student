<?php
include '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name                 = trim($_POST['name']);
    $matriculation_number = trim($_POST['matriculation_number']);
    $faculty_short_name   = trim($_POST['faculty_short_name']);
    $password             = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Handle upload
    $upload_dir = __DIR__ . '/studentimgs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $image_path = null;
    if (!empty($_FILES['student_image']['name'])) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['student_image']['size'] <= 2*1024*1024) {
            $new_filename = uniqid('stu_', true) . '.' . $ext;
            $dest = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['student_image']['tmp_name'], $dest)) {
                // store relative path
                $image_path = './studentimgs/' . $new_filename;
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid image file or too large (max 2MB).";
        }
    }

    if (!isset($error)) {
        $stmt = $conn->prepare("
            INSERT INTO students
              (name, matriculation_number, faculty_short_name, password, image)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss",
            $name,
            $matriculation_number,
            $faculty_short_name,
            $password,
            $image_path
        );

        if ($stmt->execute()) {
            $success = "New student added successfully.";
        } else {
            $error = "Database error: " . $stmt->error;
            // clean up uploaded file if DB insert failed
            if ($image_path) unlink(__DIR__ . '/' . $image_path);
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <style>
      body { font-family: sans-serif; padding: 20px; max-width: 500px; margin: auto; }
      form div { margin-bottom: 12px; }
      label { display: block; margin-bottom: 4px; }
      input[type="text"],
      input[type="password"],
      input[type="file"] { width: 100%; padding: 8px; }
      .msg { padding: 12px; border-radius: 4px; margin-bottom: 12px; }
      .success { background: #e3f7e3; color: #2d662d; }
      .error   { background: #fbeaea; color: #942626; }
      img.preview { max-width: 100px; margin-top: 8px; border-radius: 4px; }
    </style>
</head>
<body>

    <h2>Add New Student</h2>

    <?php if (!empty($success)): ?>
      <div class="msg success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <div>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div>
            <label for="matriculation_number">Matriculation Number</label>
            <input type="text" id="matriculation_number" name="matriculation_number" required>
        </div>
        <div>
            <label for="faculty_short_name">Faculty Short Name</label>
            <input type="text" id="faculty_short_name" name="faculty_short_name" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="student_image">Student Photo (jpg/png, max 2MB)</label>
            <input type="file" id="student_image" name="student_image" accept="image/*">
        </div>
        <button type="submit">Add Student</button>
    </form>

    <p><a href="index.php">Back to Admin Dashboard</a></p>

    <?php if (!empty($image_path) && empty($error)): ?>
        <h3>Uploaded Photo Preview:</h3>
        <img src="<?= htmlspecialchars($image_path) ?>" class="preview" alt="Student Photo">
    <?php endif; ?>

</body>
</html>
