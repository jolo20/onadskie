<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/ONADSKIE-MAIN/auth.php'; ?>
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); 
define('DB_NAME', 'login');

$success = '';
$error = '';


$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    $error = 'Database connection failed: ' . $mysqli->connect_error;
} else {
    
    $stmt = $mysqli->prepare('SELECT username, email, avatar_url FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $avatar_path = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size = 2 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Invalid file type. Please upload a JPG, PNG, or WebP image.';
            } elseif ($file['size'] > $max_size) {
                $error = 'File is too large. Maximum size is 2MB.';
            } else {
                $upload_dir = '../../assets/avatars/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('avatar_') . '.' . $ext;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $avatar_path = $filepath;
                } else {
                    $error = 'Failed to upload image. Please try again.';
                }
            }
        }

        if (empty($username) || empty($email)) {
            $error = 'Username and email are required.';
        } elseif ($new_password !== '' && strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            // Verify current password if changing password
            $can_update = true;
            if ($new_password !== '') {
                $stmt = $mysqli->prepare('SELECT password FROM users WHERE id = ?');
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $current = $result->fetch_assoc();
                $stmt->close();

                if (!password_verify($current_password, $current['password'])) {
                    $error = 'Current password is incorrect.';
                    $can_update = false;
                }
            }

            if ($can_update) {
                // Start transaction
                $mysqli->begin_transaction();
                try {
                    // Update username, email, and avatar if provided
                    if ($avatar_path) {
                        $stmt = $mysqli->prepare('UPDATE users SET username = ?, email = ?, avatar_url = ? WHERE id = ?');
                        $stmt->bind_param('sssi', $username, $email, $avatar_path, $_SESSION['user_id']);
                    } else {
                        $stmt = $mysqli->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                        $stmt->bind_param('ssi', $username, $email, $_SESSION['user_id']);
                    }
                    $stmt->execute();
                    $stmt->close();

                    // Update password if provided
                    if ($new_password !== '') {
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
                        $stmt->bind_param('si', $hash, $_SESSION['user_id']);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $mysqli->commit();
                    $success = 'Settings updated successfully.';
                    
                    // Update session username
                    $_SESSION['username'] = $username;
                    
                    // Refresh user data
                    $user['username'] = $username;
                    $user['email'] = $email;
                    
                } catch (Exception $e) {
                    $mysqli->rollback();
                    $error = 'An error occurred. Please try again.';
                }
            }
        }
    }

    $mysqli->close();
}
?>

 <?php
    // 1. CONFIGURATION (Update these with your actual details!)
    $servername = "localhost";
    $username = "root"; 
    $password = ""; 
    $dbname = "login"; 
    $tablename = "users";          
    $name_column = "email";    // The exact name of your column (e.g., 'name', 'user_name')

    // 2. CONNECT AND QUERY
    // Suppress potential connection errors to keep output clean, but a 'die' block is included for necessary failure.
    $conn = @new mysqli($servername, $username, $password, $dbname);

    // Stop script if connection fails
    if ($conn->connect_error) {
        die("Database connection failed.");
    }

    $sql = "SELECT $name_column FROM $tablename"; 
    $result = $conn->query($sql);
    
    $row = $result->fetch_assoc() ?? null;

// Use the null coalescing operator (??) to get the name or the default value
$display_name = $row[$name_column] ?? $default_name;

// 5. CLEAN UP
if (isset($result) && is_object($result)) {
    $result->free();
}
$conn->close();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="../../assets/img/Quezon_City.svg.png" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.2.0/css/line.css">
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.2.0/css/solid.css">
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.2.0/css/thinline.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <link href="/ONADSKIE-MAIN/assets/css/sidebar.css" rel="stylesheet">
  <link href="/ONADSKIE-MAIN/assets/css/modal-fix.css" rel="stylesheet">
  <title><?= $pageTitle ?? 'Local Government Unit 2' ?></title>


</head>
<body id="body" class="g-0">
  <!-- Header -->
   <div>
   <div class="blue col-12 fs-1 d-lg-flex d-md-flex d-none d-sm-block d-sm-none d-md-block d-md-none d-lg-block justify-content-center">
      <img class="logo col-12 " src="../../assets/img/Quezon_City.svg.png" alt="" >
    </div>
    
  <header class="header bg-light d-flex g-0 align-items-center  col-md-12 col-lg-12 col-xl-12">
    <div class="burger-bg bg-danger position-relative" >
      <button class=" btn burger  fs-1 mb-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions"><i class="uil uil-bars"></i></button>
    </div>      
   <img class="qc-text" src="../../assets/img/QC.png" alt="">
    <div class="title d-flex align-items-center justify-content-between">
        <p class="  text-dark title mt-3 fs-md-5">LOCAL GOVERNMENT UNIT 2</p>
        <div class="profile dropdown">
      <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="user-name"><?= ucfirst($_SESSION['username'] ?? 'Guest') ?></div>
        <i class="fa-solid fa-caret-down"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="/logout.php" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"><i
              class="fa-solid fa-right-from-bracket me-2"></i>Logouts</a></li>
        <li><a class="dropdown-item" href="/ONADSKIE-MAIN/contents/profile/profile.php"><i class="fa-solid fa-user-gear me-2"></i>Account Settings</a></li>
      </ul>
    </div>
  </header>

</div>
  <!-- Mobile backdrop -->
  <div id="backdrop" class="backdrop " aria-hidden="true"></div>

  <!-- Main layout -->
  <div class="wrapper" id="wrapper">
    <!-- Sidebar -->
   
    <aside class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
          <div class="offcanvas-header col-12 d-flex align-items-center p-1">
        <button class="close " data-bs-dismiss="offcanvas"><i class="uil uil-list-ui-alt"></i></button>
      </div>  
      <div class="sidebar-top ">
        <div class="profile-pod">
          <div class="text-center w-100">
            <div class="avatar-64 mx-auto mb-3 me-2 ms-4">
            <img src="<?= htmlspecialchars($user['avatar_url']) ?>" >            
            </div>
            <div class="user-name1  d-flex flex-row align-items-center ">
              <label class="mt-3"><?= ucfirst($_SESSION['username'] ?? 'Guest') ?><br>
              <label class="email text-secondary"><?php  echo htmlspecialchars($display_name)?></label>
            </label>
            </div>
            
          </div>
        </div>
      </div>
  <hr class="h-10">
      <nav class="side-nav" id="sideNav">
        <!-- Dashboard - Single link, no collapse -->
        <div class="nav-group">
          <a href="contents/dashboard/dashboard.php" class="group-toggle no-caret" style="text-decoration: none;">
            <span class="ico"><i class="fa-solid fa-gauge"></i></span>
            Dashboard
          </a>
        </div>

        <!-- 1 Ordinance & Resolution Tracking -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-gavel"></i></span>
            Ordinance and Resolution
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>contents/ordinance-resolution-tracking/draft-creation.php" class="nav-link">Draft Creation &
                Editing</a></li>
            <li><a href="<?= $root ?>contents/ordinance-resolution-tracking/sponsorship-management.php" class="nav-link">Sponsorship &
                Author Management</a></li>
          </ul>
        </div>

        <!-- 2 Session & Meeting Management -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-handshake-angle"></i></span>
            Minutes Section
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>contents/session-meeting-management/session-scheduling.php" class="nav-link">Session Scheduling
                and Notifications</a></li>
            <li><a href="contents/session-meeting-management/agenda-builder.php" class="nav-link">Agenda Builder</a></li>
          </ul>
        </div>

        <!-- 3 Legislative Agenda & Calendar (placeholder) -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-calendar-days"></i></span>
            Agenda and Briefing
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>contents/legislative-agenda-calendar/placeholder.php?t=Event%20Calendar%20(Sessions,%20Hearings,%20Consultations)"
                class="nav-link">Event Calendar (Sessions, Hearings, Consultations)</a></li>
            <li><a href="<?= $root ?>contents/legislative-agenda-calendar/placeholder.php?t=Priority%20Legislative%20List"
                class="nav-link">Priority Legislative List</a></li>
          </ul>
        </div>

        <!-- 4 Committee Management System (placeholder) -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-users"></i></span>
            Committee Management System
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>contents/committee-management-system/placeholder.php?t=Committee%20Creation%20%26%20Membership"
                class="nav-link">Committee Creation & Membership</a></li>
            <li><a href="contents/committee-management-system/placeholder.php?t=Assignment%20of%20Legislative%20Items"
                class="nav-link">Assignment of Legislative Items</a></li>
          </ul>
        </div>

        <!-- 5 Voting & Decision-Making -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-check-to-slot"></i></span>
            Committee Journal
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="contents/voting-decision-making-system/placeholder.php?t=Roll%20Call%20Management"
                class="nav-link">Roll Call Management</a></li>
            <li><a href="contents/voting-decision-making-system/placeholder.php?t=Motion%20Creation%20%26%20Seconding"
                class="nav-link">Motion Creation & Seconding</a></li>
          </ul>
        </div>

        <!-- 6 Legislative Records Management -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-folder-open"></i></span>
            Records And Correspondence
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>contents/records-and-correspondence/measure-docketing.php" class="nav-link">Measure Docketing</a></li>
            <li><a href="<?= $root ?>contents/records-and-correspondence/categorization-and-classification.php" class="nav-link">Categorization and Classification</a></li>
            <li><a href="<?= $root ?>contents/records-and-correspondence/document-tracking.php" class="nav-link">Document Tracking</a></li>
          </ul>
        </div>

        <!-- 7 Public Hearing Management -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-microphone-lines"></i></span>
            Committee Hearing
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>contents/public-hearing-management/placeholder.php?t=Hearing%20Schedule" class="nav-link">Hearing
                Schedule</a></li>
            <li><a href="contents/public-hearing-management/placeholder.php?t=Speaker/Participant%20Registration" class="nav-link">Speaker/Participant
                Registration</a></li>
          </ul>
        </div>

        <!-- 8 Legislative Archives -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-box-archive"></i></span>
            Archive Section
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>contents/legislative-archives/placeholder.php?t=Enacted%20Ordinances%20Archive"
                class="nav-link">Enacted Ordinances Archive</a></li>
          </ul>
        </div>

        <!-- 9 Legislative Research & Analysis -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-magnifying-glass-chart"></i></span>
            Research Section
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>" class="nav-link">Legislative
                Trends Dashboard</a></li>
            <li><a href="<?= $root ?>contents/legislative-research-section/KeywordandTopicSearch.php"class="nav-link">Keyword
                & Topic Search</a></li>
          </ul>
        </div>

        <!-- 10 Public Consultation Management -->
        <div class="nav-group">
          <button class="group-toggle">
            <span class="ico"><i class="fa-solid fa-comments"></i></span>
            Public Consultation Management
            <i class="fa-solid fa-chevron-down caret"></i>
          </button>
          <ul class="sublist">
            <li><a href="<?= $root ?>contents/public-consultation-management/placeholder.php?t=Public%20Feedback%20Portal"
                class="nav-link">Public Feedback Portal</a></li>
            <li><a href="<?= $root ?>contents/public-consultation-management/placeholder.php?t=Survey%20Builder"
                class="nav-link">Survey Builder</a></li>
            <li><a href="<?= $root ?>contents/public-consultation-management/placeholder.php?t=Issue%20Mapping"
                class="nav-link">Issue Mapping</a></li>
          </ul>
        </div>
      </nav>

      <div class="sidebar-bottom"></div>
    </aside>

    <!-- Content -->
    <main class="content" id="content">
    