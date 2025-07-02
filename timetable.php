<?php
session_start();
include './config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}
$student_id = $_SESSION['student_id'];

// Handle form submission for adding a new event
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event'])) {
    $title = $_POST['title'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $conn->prepare("INSERT INTO study_timetable (student_id, title, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $student_id, $title, $day_of_week, $start_time, $end_time);
    $stmt->execute();
    $stmt->close();
    header("Location: timetable.php"); // Refresh to show the new event
    exit();
}

// Handle deletion of an event
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'];
    $stmt = $conn->prepare("DELETE FROM study_timetable WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $event_id, $student_id);
    $stmt->execute();
    $stmt->close();
    header("Location: timetable.php"); // Refresh
    exit();
}

// Fetch all timetable events for the student
$timetable_sql = "SELECT id, title, day_of_week, start_time, end_time FROM study_timetable WHERE student_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
$stmt = $conn->prepare($timetable_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$timetable_events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Study Timetable</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <style>
        body { font-family: sans-serif; margin: 20px; }
        .timetable-container { display: flex; flex-wrap: wrap; gap: 20px;}
        .timetable-form { flex-basis: 300px; }
        .timetable-view { flex-grow: 1; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .delete-btn { color: red; text-decoration: none; border: none; background: none; cursor: pointer; padding: 0; }
    </style>
</head>
<body>
    <a href="scheduler.php">&larr; Back to Scheduler</a>
    <h1>My Personal Timetable</h1>

    <div class="timetable-container">
        <div class="timetable-form">
            <h2>Add New Recurring Event</h2>
            <form method="post">
                <p>Title: <input type="text" name="title" required></p>
                <p>Day: 
                    <select name="day_of_week" required>
                        <?php foreach($days_of_week as $day): ?>
                        <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>Start Time: <input type="time" name="start_time" required></p>
                <p>End Time: <input type="time" name="end_time" required></p>
                <button type="submit" name="add_event">Add Event</button>
            </form>
        </div>

        <div class="timetable-view">
            <h2>My Weekly Schedule</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($timetable_events)): ?>
                        <tr><td colspan="4">Your timetable is empty.</td></tr>
                    <?php else: ?>
                        <?php foreach($timetable_events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                            <td><?php echo htmlspecialchars($event['day_of_week']); ?></td>
                            <td><?php echo date("g:i a", strtotime($event['start_time'])) . ' - ' . date("g:i a", strtotime($event['end_time'])); ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" name="delete_event" class="delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
