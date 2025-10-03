<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); 
define('DB_NAME', 'login');

if (isset($_SESSION['flash'])) {
    $error = $_SESSION['flash'];
    unset($_SESSION['flash']);
} else {
    $error = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Please enter email/username and password.';
    } else {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($mysqli->connect_errno) {
            $error = 'Database connection failed: ' . $mysqli->connect_error;
        } else {
            $tableCheck = $mysqli->query("SHOW TABLES LIKE 'users'");
            if ($tableCheck->num_rows == 0) {

                $createTable = "CREATE TABLE `users` (
                    `id` int NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `password` varchar(255) NOT NULL COMMENT 'Stores hashed passwords',
                    `email` varchar(100) NOT NULL,
                    `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `avatar_url` varchar(255) NOT NULL DEFAULT 'assets/img/default-avatar.jpg',
                    `user_type` varchar(50) DEFAULT NULL COMMENT 'Defines user role and permissions',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_username` (`username`),
                    UNIQUE KEY `idx_email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores user authentication and profile data'";

                try {
                    if (!$mysqli->query($createTable)) {
                        throw new Exception('Failed to create users table: ' . $mysqli->error);
                    }
                    $adminUsername = 'administrator';
                    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                    $adminEmail = 'admin@lgu.gov.ph';
                    $adminType = 'admin';

                    $stmt = $mysqli->prepare("INSERT INTO `users` 
                        (`username`, `password`, `email`, `user_type`) 
                        VALUES (?, ?, ?, ?)");

                    if (!$stmt) {
                        throw new Exception('Failed to prepare admin user creation: ' . $mysqli->error);
                    }

                    $stmt->bind_param('ssss', $adminUsername, $adminPassword, $adminEmail, $adminType);

                    if (!$stmt->execute()) {
                        throw new Exception('Failed to create default admin user: ' . $stmt->error);
                    }

                    $stmt->close();

                } catch (Exception $e) {
                    $error = $e->getMessage();
                    $mysqli->close();
                    return;
                }
            }
            // Debug the input
            error_log("Login attempt - Identifier: " . $identifier);

            $sql = 'SELECT id, username, password, email, user_type FROM users WHERE email = ? OR username = ? LIMIT 1';
            error_log("SQL Query: " . $sql);
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ss', $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $hash = $row['password'];
                    $verified = false;
                    if (password_verify($password, $hash)) {
                        $verified = true;
                    } elseif ($password === $hash) {
                        $verified = true;
                    }

                    if ($verified) {
                        // Debug information
                        error_log("User data from DB: " . print_r($row, true));

                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['user_type'] = strtolower($row['user_type'] ?? '');

                        // Debug session data
                        error_log("Session data after setting: " . print_r($_SESSION, true));

                        if ($row['user_type'] === 'records') {
                            header("Location: contents/records-and-correspondence/document-tracking.php");
                            exit;
                        } else {
                            header("Location: /lgu-2-main-main/contents/dashboard/dashboard.php");
                        }
                        exit;
                    } else {
                        $error = 'Incorrect password. Please try again.';
                    }
                } else {
                    $error = 'Email or username not found. Please check and try again.';
                }
                $stmt->close();
            } else {
                $error = 'Database query failed.';
            }
            $mysqli->close();
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>LGU2 — Login</title>
    <link href="assets/img/Quezon_City.svg.png" rel="icon">
    <link href="assets/css/login.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.2.0/css/line.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body class="g-0">
    <div class="main-container container-fluid g-0">
        <div class="left d-lg-flex align-items-center justify-content-center col-lg-6 d-none d-lg-block ">
            <img class="text-pic" src="assets/img/QC.png" alt="">
            <h4 class="fw-bolder ms-1">LOCAL GOVERNMENT UNIT 2</h4>
        </div>    
            
        
        <div class="right-1">           
            <div class="top-logo  d-lg-none d-sm-block p-5 ">
            <img class="logo-pic" src="assets/img/qclogo.png" alt="">
        </div>
            
            <div class="form-box right-2 col-lg-8 col-md-8 col-8 d-flex align-items-center justify-content-center" role="form" aria-labelledby="login-title">
                <div class="col-9">
                    <h3 id="login-title" class="fw-bolder">Login</h3>
                <p class="sub">Welcome back! Let's continue building a better community, together.</p>
                </div>

                <form method="post" action="" class=" d-flex align-items-center justify-content-center flex-column">
                    <div class="form-field">
                        
                        <input id="email"   name="email" type="text" placeholder="Enter your email or username:" autocomplete="username" value="<?=isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''?>">
                    </div>

                    <div class="form-field">
                        
                        <input id="password" name="password" type="password" placeholder="Enter your password:" autocomplete="current-password">
                        <span class="toggle-password" onclick="togglePasswordVisibility()">
                        <i class="uil uil-eye-slash"></i>
                        </span>
                    </div>

                    <div class="controls">
                        <a class="forgot" href="#">Forgot Password?</a>
                    </div>
                    <button class="btn-login" id="btnLogin" type="submit">LOGIN</button>

                    <?php if ($error): ?>
                        <div class="error"><?=htmlspecialchars($error)?></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
<script>
    
</script>
<script src="./assets/js/login.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</html>