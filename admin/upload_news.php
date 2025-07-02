
<?php
include '../config.php';
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