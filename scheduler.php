<?php

session_start();
include './config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}



if (!isset($_SESSION['student_id'])) {
    $_SESSION['student_id'] = 1; // Default to student 1
}
$student_id = $_SESSION['student_id'];

// --- MOCK DATABASE & DATA (Remove this block when using a real database) ---
class DBMock {
    private $tasks = [];
    private $timetable_events = [];
    private $next_task_id = 1;
    private $next_event_id = 1;

    public function __construct() {
        // Pre-populate with some data for demonstration
        $this->tasks = [
            ['id' => $this->next_task_id++, 'student_id' => 1, 'title' => 'Data Security CA', 'due_date' => date('Y-m-d H:i:s', strtotime('tomorrow 09:30')), 'is_completed' => false, 'color_category' => 'green'],
            ['id' => $this->next_task_id++, 'student_id' => 1, 'title' => 'Study Mathematics IV', 'due_date' => date('Y-m-d H:i:s', strtotime('today 19:30')), 'is_completed' => false, 'color_category' => 'yellow'],
            ['id' => $this->next_task_id++, 'student_id' => 1, 'title' => 'Write Digital Electronics', 'due_date' => date('Y-m-d H:i:s', strtotime('+3 days 10:30')), 'is_completed' => false, 'color_category' => 'blue'],
        ];
        $this->timetable_events = [
            ['id' => $this->next_event_id++, 'student_id' => 1, 'title' => 'Algorithms Lecture', 'day_of_week' => 'Monday', 'start_time' => '10:00:00', 'end_time' => '12:00:00'],
            ['id' => $this->next_event_id++, 'student_id' => 1, 'title' => 'Project Work', 'day_of_week' => 'Wednesday', 'start_time' => '14:00:00', 'end_time' => '17:00:00'],
        ];
    }
    
    // Simulate prepared statements
    public function prepare($query) {
        return new StmtMock($this, $query);
    }
}

class StmtMock {
    private $db;
    private $query;
    private $params = [];
    public function __construct($db, $query) { $this->db = $db; $this->query = $query; }
    public function bind_param($types, &...$vars) { $this->params = $vars; }
    public function execute() { /* Simulate execution */ return true; }
    public function get_result() { 
        // This is a simplified mock. It only handles specific queries from the original code.
        if (str_contains($this->query, "FROM students")) {
            return new ResultMock([['name' => 'Kandi Junior']]);
        }
        if (str_contains($this->query, "FROM tasks") && str_contains($this->query, "COUNT")) {
            return new ResultMock([['count' => 1]]); // Mock 1 task for today
        }
        if (str_contains($this->query, "FROM tasks")) {
            return new ResultMock($this->db->tasks ?? []);
        }
        if (str_contains($this->query, "FROM study_timetable")) {
            return new ResultMock($this->db->timetable_events ?? []);
        }
        return new ResultMock([]);
    }
    public function close() {}
}

class ResultMock {
    private $data;
    private $pointer = 0;
    public function __construct($data) { $this->data = $data; }
    public function fetch_assoc() { return $this->data[$this->pointer++] ?? null; }
    public function fetch_all($type) { return $this->data; }
}
$conn = new DBMock();
// --- END MOCK DATABASE ---

// --- DATA FETCHING ---
$student_stmt = $conn->prepare("SELECT name FROM students WHERE id = ?");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();
$student_first_name = $student ? explode(' ', $student['name'])[0] : 'Student';
$student_stmt->close();

$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$tasks_today_stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE student_id = ? AND due_date BETWEEN ? AND ? AND is_completed = FALSE");
$tasks_today_stmt->bind_param("iss", $student_id, $today_start, $today_end);
$tasks_today_stmt->execute();
$tasks_today_count = $tasks_today_stmt->get_result()->fetch_assoc()['count'];
$tasks_today_stmt->close();

$tasks_stmt = $conn->prepare("SELECT id, title, due_date, color_category FROM tasks WHERE student_id = ? AND is_completed = FALSE ORDER BY due_date ASC");
$tasks_stmt->bind_param("i", $student_id);
$tasks_stmt->execute();
$tasks = $tasks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tasks_stmt->close();

$timetable_stmt = $conn->prepare("SELECT id, title, day_of_week, start_time, end_time FROM study_timetable WHERE student_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
$timetable_stmt->bind_param("i", $student_id);
$timetable_stmt->execute();
$timetable_events = $timetable_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$timetable_stmt->close();

// --- HELPER FUNCTIONS ---
function get_relative_time($datetime) {
    $due = new DateTime($datetime);
    $now = new DateTime();
    $due_date_only = $due->format('Y-m-d');
    $today_date_only = $now->format('Y-m-d');
    $tomorrow_date_only = (new DateTime('tomorrow'))->format('Y-m-d');

    if ($due_date_only == $today_date_only) return "Today";
    if ($due_date_only == $tomorrow_date_only) return "Tomorrow";
    if ($due < $now) return $due->format('M d');
    if ($now->diff($due)->days < 7) return "This Week";
    
    return "Next Week";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduler</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f8fc; }
        .main-container { max-width: 1200px; margin: auto; padding: 24px; }
        .main-grid { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 24px; }
        .card { background-color: white; border-radius: 1.25rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
        .tab { transition: all 0.2s; border-bottom: 2px solid transparent; }
        .tab.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .task-item-color { width: 5px; height: 24px; border-radius: 99px; }
        .task-item-color.green { background-color: #10b981; }
        .task-item-color.yellow { background-color: #f59e0b; }
        .task-item-color.blue { background-color: #3b82f6; }
        .focus-btn.active { background-color: #eef2ff; color: #3b82f6; }
        .delete-btn { background: none; border: none; cursor: pointer; color: #f87171; }
        .delete-btn:hover { color: #ef4444; }
    </style>
</head>
<body class="text-gray-800">
    <div class="main-container">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6 rounded-xl shadow-lg mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Good Morning, <?php echo htmlspecialchars($student_first_name); ?>!</h1>
                    <p class="text-blue-200 mt-1">You have <span id="tasks-today-count"><?php echo $tasks_today_count; ?></span> tasks for today</p>
                </div>
                <div class="text-right">
                    <p class="font-medium">Hours this week: 8.5</p>
                    <p class="text-sm text-blue-300">(+3.5 vs last week)</p>
                </div>
            </div>
        </div>

        <div class="main-grid">
            <!-- Left Column -->
            <div class="flex flex-col gap-6">
                <!-- Tabs and Input Area -->
                <div class="card p-5">
                    <div class="tabs flex gap-6 border-b border-gray-200 mb-4">
                        <button class="tab active py-2 px-1 font-semibold" data-tab="tasks">Task</button>
                        <button class="tab py-2 px-1 font-semibold text-gray-500" data-tab="personal-timetable">Personal Timetable</button>
                    </div>
                     <!-- Task Input -->
                    <div id="task-input-section">
                        <form id="add-task-form" class="flex items-center gap-3">
                            <i class="ph ph-plus-circle text-gray-400 text-2xl"></i>
                            <input type="text" id="task-title" name="title" class="flex-grow bg-transparent focus:outline-none text-base" placeholder="Add task or remainder" required>
                            <button type="submit" class="bg-blue-500 text-white rounded-full p-2 hover:bg-blue-600 transition">
                                <i class="ph-fill ph-paper-plane-tilt text-xl"></i>
                            </button>
                        </form>
                         <div class="flex items-center gap-2 mt-3 text-gray-500 text-xs">
                             <button class="flex items-center gap-1.5 hover:bg-gray-100 px-2 py-1 rounded-md"><i class="ph ph-clock"></i><span>--:--</span></button>
                             <button class="flex items-center gap-1.5 hover:bg-gray-100 px-2 py-1 rounded-md"><i class="ph ph-calendar-blank"></i><span>Today</span></button>
                             <button class="flex items-center gap-1.5 hover:bg-gray-100 px-2 py-1 rounded-md"><i class="ph ph-calendar-plus"></i><span>Tomorrow</span></button>
                             <button class="flex items-center gap-1.5 hover:bg-gray-100 px-2 py-1 rounded-md"><i class="ph ph-calendar-x"></i><span>Custom</span></button>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <div id="main-content-area" class="card p-6">
                    <!-- Task List View -->
                    <div id="tasks-view">
                        <h2 class="text-xl font-bold mb-4">Task and Remainders</h2>
                        <div id="task-list" class="space-y-3">
                            <?php if (empty($tasks)): ?>
                                <p id="no-tasks-msg" class="text-gray-500 p-2">No pending tasks. Great job!</p>
                            <?php else: ?>
                                <?php foreach ($tasks as $task): ?>
                                <div class="task-item flex items-center justify-between p-2 rounded-lg hover:bg-gray-50" data-task-id="<?php echo $task['id']; ?>">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" class="task-checkbox h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                        <div>
                                            <p class="font-medium"><?php echo htmlspecialchars($task['title']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo get_relative_time($task['due_date']); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-semibold text-gray-600"><?php echo date('g:i A', strtotime($task['due_date'])); ?></span>
                                        <div class="task-item-color <?php echo htmlspecialchars($task['color_category']); ?>"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Personal Timetable Management View -->
                    <div id="personal-timetable-view" class="hidden">
                        <h2 class="text-xl font-bold mb-4">Manage Personal Timetable</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold mb-2 text-gray-700">Add New Recurring Event</h3>
                                <form id="add-event-form" class="space-y-3">
                                    <input type="text" name="title" placeholder="Event Title" class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-300 focus:border-blue-500" required>
                                    <select name="day_of_week" class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-300 focus:border-blue-500" required>
                                        <option value="Monday">Monday</option> <option value="Tuesday">Tuesday</option> <option value="Wednesday">Wednesday</option> <option value="Thursday">Thursday</option> <option value="Friday">Friday</option> <option value="Saturday">Saturday</option> <option value="Sunday">Sunday</option>
                                    </select>
                                    <div class="flex gap-2">
                                        <input type="time" name="start_time" class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-300 focus:border-blue-500" required>
                                        <input type="time" name="end_time" class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-300 focus:border-blue-500" required>
                                    </div>
                                    <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 font-semibold">Add Event</button>
                                </form>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-2 text-gray-700">My Weekly Schedule</h3>
                                <table id="personal-timetable-table" class="w-full text-sm">
                                    <tbody class="divide-y divide-gray-100">
                                        <?php if(empty($timetable_events)): ?>
                                            <tr id="no-events-msg"><td colspan="3" class="p-2 text-gray-500 text-center">No recurring events.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($timetable_events as $event): ?>
                                                <tr data-event-id="<?php echo $event['id']; ?>">
                                                    <td class="p-2 font-medium"><?php echo htmlspecialchars($event['title']); ?> <span class="text-xs text-gray-400 block"><?php echo htmlspecialchars($event['day_of_week']); ?></span></td>
                                                    <td class="p-2 text-gray-600 text-right"><?php echo date("g:i a", strtotime($event['start_time'])) . ' - ' . date("g:i a", strtotime($event['end_time'])); ?></td>
                                                    <td class="p-2 text-right"><button class="delete-btn text-lg"><i class="ph ph-trash-simple"></i></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="flex flex-col">
                <div class="card p-6 text-center flex-grow flex flex-col justify-center">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">FOCUS</h2>
                        <button class="text-sm flex items-center gap-1 text-gray-500 hover:text-blue-600"><i class="ph-bold ph-music-note-simple"></i> Add Music</button>
                    </div>
                    <p class="text-gray-500 mb-4">current task: <span id="current-task" class="font-semibold text-gray-800">Select a task</span></p>
                    <div class="grid grid-cols-2 gap-2 mb-6">
                        <button class="focus-btn p-2 rounded-lg font-semibold bg-gray-100 hover:bg-gray-200 active" data-time="25">Pomodoro</button>
                        <button class="focus-btn p-2 rounded-lg font-semibold bg-gray-100 hover:bg-gray-200" data-time="5">Short Break</button>
                        <button class="focus-btn p-2 rounded-lg font-semibold bg-gray-100 hover:bg-gray-200" data-time="15">Long Break</button>
                        <button class="focus-btn p-2 rounded-lg font-semibold bg-gray-100 hover:bg-gray-200" data-time="0">Custom</button>
                    </div>
                    <div id="timer-display" class="text-7xl font-bold my-6 tracking-tighter">25:00</div>
                    <div class="flex justify-center items-center gap-8">
                        <button id="timer-control" class="bg-blue-600 text-white rounded-full h-16 w-16 flex items-center justify-center text-4xl shadow-lg hover:bg-blue-700 transition">
                            <i id="play-icon" class="ph-fill ph-play"></i>
                            <i id="pause-icon" class="ph-fill ph-pause hidden"></i>
                        </button>
                        <button id="reset-btn" class="text-gray-400 hover:text-gray-600 text-3xl"><i class="ph-bold ph-arrow-counter-clockwise"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<script>
document.addEventListener('DOMContentLoaded', () => {

    // --- Tab Switching Logic ---
    const tabs = document.querySelectorAll('.tab');
    const views = {
        tasks: document.getElementById('tasks-view'),
        'personal-timetable': document.getElementById('personal-timetable-view')
    };
    const taskInputSection = document.getElementById('task-input-section');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;
            if (tabName) {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                Object.values(views).forEach(view => view.style.display = 'none');
                if (views[tabName]) {
                    views[tabName].style.display = 'block';
                }
                taskInputSection.style.display = (tabName === 'tasks') ? 'block' : 'none';
            }
        });
    });

    // --- AJAX Helper ---
    async function sendRequest(formData) {
        try {
            // Using a dummy actions file for demonstration. Replace with your actual backend file.
            const response = await fetch('scheduler_actions.php', { method: 'POST', body: formData });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error('AJAX Error:', error);
            return { success: false, message: 'A network error occurred.' };
        }
    }

    // --- Task Management (AJAX) ---
    document.getElementById('add-task-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'add_task');
        
        const result = await sendRequest(formData);

        if(result.success) {
            const task = result.task;
            const taskList = document.getElementById('task-list');
            const noTasksMsg = document.getElementById('no-tasks-msg');
            if(noTasksMsg) noTasksMsg.remove();
            
            const taskEl = document.createElement('div');
            taskEl.className = 'task-item flex items-center justify-between p-2 rounded-lg hover:bg-gray-50';
            taskEl.dataset.taskId = task.id;
            taskEl.innerHTML = `
                <div class="flex items-center gap-3">
                    <input type="checkbox" class="task-checkbox h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                    <div><p class="font-medium">${task.title}</p><p class="text-sm text-gray-500">${task.relative_time}</p></div>
                </div>
                <div class="flex items-center gap-3"><span class="text-sm font-semibold text-gray-600">${task.time}</span><div class="task-item-color ${task.color_category}"></div></div>`;
            taskList.prepend(taskEl);
            form.reset();
        } else {
            alert(result.message || 'Failed to add task.');
        }
    });

    document.getElementById('task-list').addEventListener('change', async (e) => {
        if (e.target.classList.contains('task-checkbox')) {
            const taskItem = e.target.closest('.task-item');
            const taskId = taskItem.dataset.taskId;
            
            const formData = new FormData();
            formData.append('action', 'update_task_status');
            formData.append('task_id', taskId);
            
            const result = await sendRequest(formData);
            
            if(result.success) {
                taskItem.style.transition = 'opacity 0.5s';
                taskItem.style.opacity = '0';
                setTimeout(() => taskItem.remove(), 500);
            } else {
                alert('Failed to update task.');
                e.target.checked = false;
            }
        }
    });
    
    // --- Personal Timetable (AJAX) ---
    document.getElementById('add-event-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'add_timetable_event');

        const result = await sendRequest(formData);

        if (result.success) {
            const event = result.event;
            const tableBody = document.querySelector('#personal-timetable-table tbody');
            const noEventsMsg = document.getElementById('no-events-msg');
            if(noEventsMsg) noEventsMsg.remove();

            const row = document.createElement('tr');
            row.dataset.eventId = event.id;
            row.innerHTML = `
                <td class="p-2 font-medium">${event.title} <span class="text-xs text-gray-400 block">${event.day_of_week}</span></td>
                <td class="p-2 text-gray-600 text-right">${event.time_range}</td>
                <td class="p-2 text-right"><button class="delete-btn text-lg"><i class="ph ph-trash-simple"></i></button></td>`;
            tableBody.appendChild(row);
            form.reset();
        } else {
            alert(result.message || "Failed to add event.");
        }
    });

    document.getElementById('personal-timetable-table').addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn && confirm('Are you sure you want to delete this event?')) {
            const row = deleteBtn.closest('tr');
            const eventId = row.dataset.eventId;
            const formData = new FormData();
            formData.append('action', 'delete_timetable_event');
            formData.append('event_id', eventId);
            
            const result = await sendRequest(formData);
            if(result.success) row.remove();
            else alert('Failed to delete event.');
        }
    });

    // --- Focus Timer Logic ---
    const timerDisplay = document.getElementById('timer-display');
    const timerControlBtn = document.getElementById('timer-control');
    const playIcon = document.getElementById('play-icon');
    const pauseIcon = document.getElementById('pause-icon');
    const resetBtn = document.getElementById('reset-btn');
    const focusBtns = document.querySelectorAll('.focus-btn');
    let timerInterval, totalSeconds = 25 * 60, isRunning = false;

    function updateTimerDisplay() {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
    function startTimer() {
        if (isRunning) return;
        isRunning = true;
        playIcon.classList.add('hidden');
        pauseIcon.classList.remove('hidden');
        timerInterval = setInterval(() => {
            totalSeconds--;
            updateTimerDisplay();
            if (totalSeconds < 0) {
                clearInterval(timerInterval);
                alert("Time's up!");
                const activeBtn = document.querySelector('.focus-btn.active');
                resetTimer(parseInt(activeBtn.dataset.time, 10));
            }
        }, 1000);
    }
    function pauseTimer() {
        isRunning = false;
        playIcon.classList.remove('hidden');
        pauseIcon.classList.add('hidden');
        clearInterval(timerInterval);
    }
    function resetTimer(newTimeInMinutes) {
        pauseTimer();
        totalSeconds = newTimeInMinutes * 60;
        updateTimerDisplay();
    }
    timerControlBtn.addEventListener('click', () => isRunning ? pauseTimer() : startTimer());
    focusBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            focusBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            resetTimer(parseInt(btn.dataset.time, 10));
        });
    });
    resetBtn.addEventListener('click', () => {
        const activeBtn = document.querySelector('.focus-btn.active');
        resetTimer(parseInt(activeBtn.dataset.time, 10));
    });
    updateTimerDisplay();
});
</script>
</body>
</html>
