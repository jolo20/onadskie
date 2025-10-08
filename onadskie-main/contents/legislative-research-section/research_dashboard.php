<?php

// Include other necessary files using the session variable
require_once $_SERVER['DOCUMENT_ROOT'] . '/lgu-2-main-main/auth.php';
require_once 'connection.php'; // Includes the lgu2 database connection

// Get the current month and year from the URL, or default to the current date
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch daily counts for the selected month and year
$incomingTasksDaily = [];
try {
    $sqlDailyCounts = "SELECT DATE(date_created) as measure_date, COUNT(*) as daily_count FROM m9_similaritychecking WHERE measure_status = 'Draft' AND YEAR(date_created) = ? AND MONTH(date_created) = ? GROUP BY DATE(date_created) ORDER BY measure_date ASC";
    $stmt = $lgu2_conn->prepare($sqlDailyCounts);
    $stmt->bind_param("ss", $currentYear, $currentMonth);
    $stmt->execute();
    $resultDailyCounts = $stmt->get_result();

    if ($resultDailyCounts) {
        while ($row = $resultDailyCounts->fetch_assoc()) {
            $incomingTasksDaily[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    // Log the error or display a user-friendly message
    $errorMessage = "Database error: " . $e->getMessage();
}

// Map the fetched data into an associative array for easy lookup
$tasksByDate = [];
foreach ($incomingTasksDaily as $task) {
    $tasksByDate[$task['measure_date']] = $task['daily_count'];
}

// Calendar generation logic
$firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$monthName = date('F Y', $firstDayOfMonth);
$daysInMonth = date('t', $firstDayOfMonth);
$startDayOfWeek = date('w', $firstDayOfMonth); // 0 (Sunday) to 6 (Saturday)

// Check if this is an AJAX request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $lgu2_conn->close();
    // Return only the calendar content
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    ?>
    <div class="calendar-nav-container">
        <a href="#" class="calendar-nav-link" data-month="<?php echo date('m', strtotime('-1 month', $firstDayOfMonth)); ?>" data-year="<?php echo date('Y', strtotime('-1 month', $firstDayOfMonth)); ?>"><i class="fas fa-chevron-left"></i></a>
        <h4 class="calendar-header mb-0"><?php echo htmlspecialchars($monthName); ?></h4>
        <a href="#" class="calendar-nav-link" data-month="<?php echo date('m', strtotime('+1 month', $firstDayOfMonth)); ?>" data-year="<?php echo date('Y', strtotime('+1 month', $firstDayOfMonth)); ?>"><i class="fas fa-chevron-right"></i></a>
    </div>

    <div class="calendar-container ">
        <?php foreach ($dayNames as $dayName): ?>
            <div class="calendar-day-header"><?php echo htmlspecialchars($dayName); ?></div>
        <?php endforeach; ?>

        <?php 
        for ($i = 0; $i < $startDayOfWeek; $i++) {
            echo '<div class="calendar-day empty-day"></div>';
        }
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $taskCount = $tasksByDate[$date] ?? 0;
            echo '<div class="calendar-day">';
            echo '<span class="calendar-day-number">' . htmlspecialchars($day) . '</span>';
            if ($taskCount > 0) {
                echo '<span class="task-badge">' . htmlspecialchars($taskCount) . ' Task(s)</span>';
            }
            echo '</div>';
        }
        ?>
    </div>
    <?php
    exit; // Stop execution after sending the HTML fragment
}

// Fetch counts for the dashboard statistics (only for full page load)
try {
    // Count Incoming Tasks (Draft)
    $sqlDraftCount = "SELECT COUNT(*) AS total FROM m9_similaritychecking WHERE measure_status = 'Draft'";
    $resultDraftCount = $lgu2_conn->query($sqlDraftCount);
    if ($resultDraftCount) {
        $totalDraftMeasures = $resultDraftCount->fetch_assoc()['total'];
    }

    // Count Pending Measures
    $sqlPendingCount = "SELECT COUNT(*) AS total FROM m9_similaritychecking WHERE measure_status = 'Pending'";
    $resultPendingCount = $lgu2_conn->query($sqlPendingCount);
    if ($resultPendingCount) {
        $totalPendingMeasures = $resultPendingCount->fetch_assoc()['total'];
    }

    // Count Proposed Ordinances (1st, 2nd, 3rd Reading)
    $sqlOrdinanceCount = "SELECT COUNT(*) AS total FROM m9_similaritychecking WHERE measure_type = 'Ordinance' AND measure_status IN ('1st Reading', '2nd Reading', '3rd Reading')";
    $resultOrdinanceCount = $lgu2_conn->query($sqlOrdinanceCount);
    if ($resultOrdinanceCount) {
        $totalOrdinances = $resultOrdinanceCount->fetch_assoc()['total'];
    }

    // Count Proposed Resolutions (1st, 2nd, 3rd Reading)
    $sqlResolutionCount = "SELECT COUNT(*) AS total FROM m9_similaritychecking WHERE measure_type = 'Resolution' AND measure_status IN ('1st Reading', '2nd Reading', '3rd Reading')";
    $resultResolutionCount = $lgu2_conn->query($sqlResolutionCount);
    if ($resultResolutionCount) {
        $totalResolutions = $resultResolutionCount->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}

$lgu2_conn->close();

$pageTitle = "Dashboard";
require_once $_SERVER['DOCUMENT_ROOT'] . '/lgu-2-main-main/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
    
    <div class="container">

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card dashboard-card">
                <div class="card-body dashboard-card-body">
                    <img clss="dashboard-icon" src="/lgu-2-main-main/assets/img/task.gif" alt="">
                    <h5 class="dashboard-title">Incoming Tasks</h5>
                    <p class="dashboard-count"><?php echo $totalDraftMeasures; ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card dashboard-card">
                <div class="card-body dashboard-card-body">
                    <img clss="dashboard-icon" src="/lgu-2-main-main/assets/img/file.gif" alt="">
                    <h5 class="dashboard-title">Pending Measures</h5>
                    <p class="dashboard-count"><?php echo $totalPendingMeasures; ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card dashboard-card">
                <div class="card-body dashboard-card-body">
                    <img clss="dashboard-icon" src="/lgu-2-main-main/assets/img/court.gif" alt="">
                    <h5 class="dashboard-title">Proposed Ordinances</h5>
                    <p class="dashboard-count"><?php echo $totalOrdinances; ?></p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card dashboard-card">
                <div class="card-body dashboard-card-body">
                    <img clss="dashboard-icon" src="/lgu-2-main-main/assets/img/resolution.gif" alt="">
                    <h5 class="dashboard-title">Proposed Resolutions</h5>
            <a href=""></a>        <p class="dashboard-count"><?php echo $totalResolutions; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class=" container ">
        <div class="col-lg-12 col-12 ">
            <div class="card mb-4">
                <div class="card-header card-header-custom">
                    Incoming Tasks (by Date)
                </div>
                <div class="card-body">
                    <div id="calendar-wrapper">
                        <?php
                            $dayNames = ['Sun', 'Mon', 'Tues', 'Wed', 'Thurs', 'Fri', 'Sat'];
                        ?>
                        <div class="calendar-nav-container">
                            <a href="#" class="calendar-nav-link" data-month="<?php echo date('m', strtotime('-1 month', $firstDayOfMonth)); ?>" data-year="<?php echo date('Y', strtotime('-1 month', $firstDayOfMonth)); ?>"><i class="fas fa-chevron-left"></i></a>
                            <h4 class="calendar-header mb-0"><?php echo htmlspecialchars($monthName); ?></h4>
                            <a href="#" class="calendar-nav-link" data-month="<?php echo date('m', strtotime('+1 month', $firstDayOfMonth)); ?>" data-year="<?php echo date('Y', strtotime('+1 month', $firstDayOfMonth)); ?>"><i class="fas fa-chevron-right"></i></a>
                        </div>
                        <div class="calendar-container">
                            <?php foreach ($dayNames as $dayName): ?>
                                <div class="calendar-day-header"><?php echo htmlspecialchars($dayName); ?></div>
                            <?php endforeach; ?>
                            <?php 
                                for ($i = 0; $i < $startDayOfWeek; $i++) {
                                    echo '<div class="calendar-day empty-day  "></div>';
                                }
                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $date = $currentYear . '-' . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                    $taskCount = $tasksByDate[$date] ?? 0;
                                    echo '<div class="calendar-day">';
                                    echo '<span class="calendar-day-number">' . htmlspecialchars($day) . '</span>';
                                    if ($taskCount > 0) {
                                        echo '<span class="task-badge">' . htmlspecialchars($taskCount) . ' Task(s)</span>';
                                    }
                                    echo '</div>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarWrapper = document.getElementById('calendar-wrapper');

        // Add event listener to the calendar wrapper
        calendarWrapper.addEventListener('click', function(e) {
            // Check if a navigation link was clicked
            if (e.target.closest('.calendar-nav-link')) {
                e.preventDefault();
                
                const link = e.target.closest('.calendar-nav-link');
                const newMonth = link.getAttribute('data-month');
                const newYear = link.getAttribute('data-year');

                // Make an AJAX request to load the new month's data
                fetch(`?ajax=1&month=${newMonth}&year=${newYear}`)
                    .then(response => response.text())
                    .then(html => {
                        // Replace the calendar content with the new HTML
                        calendarWrapper.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error loading calendar:', error);
                    });
            }
        });
    });
</script>

<?php
// Close the database connection (only for the full page load)
require_once $_SERVER['DOCUMENT_ROOT'] . '/lgu-2-main-main/includes/footer.php';
?>