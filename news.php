
<?php
session_start();
include './config.php';

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="./css/news.css">
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
    <a href="index.php">&larr; </a>
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
