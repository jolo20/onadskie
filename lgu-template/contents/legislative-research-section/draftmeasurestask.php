<?php
// START OF THE FIXED PHP BLOCK
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/lgu-2-main-main/auth.php';
require_once 'connection.php'; // Includes the lgu2 database connection

// Define the API key
$api_key = '7b5e4c6f2a3a5f6c8d1c9e3e7f9e8a5b6d7a4f9c8d0a3e4f5c6b7e8d9a4b5c6f'; // <-- Use the full key here

// Pagination and search settings
$records_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_query = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$offset = ($current_page - 1) * $records_per_page;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_task'])) {
    
    // Sanitize and retrieve the form data
    $m9_SC_ID = isset($_POST['m9_SC_ID']) ? (int)$_POST['m9_SC_ID'] : 0;
    $m9_SC_Code = isset($_POST['m9_SC_Code']) ? htmlspecialchars($_POST['m9_SC_Code']) : '';
    $date_created = isset($_POST['date_created']) ? htmlspecialchars($_POST['date_created']) : '';
    $measure_type = isset($_POST['measure_type']) ? htmlspecialchars($_POST['measure_type']) : '';
    $title = isset($_POST['measure_title']) ? htmlspecialchars($_POST['measure_title']) : '';
    $content = isset($_POST['measure_content']) ? htmlspecialchars($_POST['measure_content']) : '';
    $introducers = isset($_POST['introducers']) ? htmlspecialchars($_POST['introducers']) : '';
    
    // Set the measure_status to 'Pending'
    $measure_status = 'Pending';
    
    $checking_remarks = isset($_POST['checking_remarks']) ? htmlspecialchars($_POST['checking_remarks']) : '';
    $checking_notes = isset($_POST['checking_notes']) ? htmlspecialchars($_POST['checking_notes']) : '';
    $checked_by = isset($_POST['checked_by']) ? htmlspecialchars($_POST['checked_by']) : '';
    $datetime_submitted = isset($_POST['datetime_submitted']) ? htmlspecialchars($_POST['datetime_submitted']) : '';

    try {
        // Start a transaction for safe database operation
        $lgu2_conn->begin_transaction();
    
        // 1. Update the `m9_similaritychecking` table with the new status
        $sqlUpdateSc = "UPDATE m9_similaritychecking SET measure_status = ?, checking_remarks = ?, checking_notes = ?, checked_by = ?, datetime_submitted = ? WHERE m9_SC_ID = ?";
        $stmtUpdateSc = $lgu2_conn->prepare($sqlUpdateSc);
        if ($stmtUpdateSc === false) {
            throw new Exception('Error preparing UPDATE m9_similaritychecking statement: ' . $lgu2_conn->error);
        }
        $stmtUpdateSc->bind_param("sssssi", $measure_status, $checking_remarks, $checking_notes, $checked_by, $datetime_submitted, $m9_SC_ID);
        if (!$stmtUpdateSc->execute()) {
            throw new Exception('Error executing UPDATE m9_similaritychecking statement: ' . $stmtUpdateSc->error);
        }
        $stmtUpdateSc->close();

        // --- API SENDING LOGIC STARTS HERE ---
        // Create the payload with the updated status
        $api_url = 'http://127.0.0.1/jsonapi/contents/legislative-research-section/receive_from_lgu2.php';
        $payload = [
            'api_key' => $api_key,
            'm9_SC_ID' => $m9_SC_ID,
            'm9_SC_Code' => $m9_SC_Code,
            'date_created' => $date_created,
            'measure_type' => $measure_type,
            'measure_title' => $title,
            'measure_content' => $content,
            'introducers' => $introducers,
            'measure_status' => $measure_status, // Use the new 'Pending' status
            'checking_remarks' => $checking_remarks,
            'checking_notes' => $checking_notes,
            'checked_by' => $checked_by,
            'datetime_submitted' => $datetime_submitted
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($payload))
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $api_message = '';
        if ($curl_error) {
            throw new Exception("API Send Error: " . $curl_error);
        } else {
            $api_result = json_decode($response, true);
            if ($api_result === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("API call failed with HTTP code {$http_code}. Response: " . htmlspecialchars($response));
            } elseif (!isset($api_result['success']) || !$api_result['success']) {
                throw new Exception("API call failed: " . ($api_result['message'] ?? 'Unknown error'));
            } else {
                $api_message = "API call successful: " . $api_result['message'];
            }
        }
        
        // If the database operation AND API call are successful, commit the changes
        $lgu2_conn->commit();
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Task successfully updated and sent to the other system! ' . $api_message];

    } catch (Exception $e) {
        // An error occurred (either DB or API), roll back the transaction
        $lgu2_conn->rollback();
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Process failed and changes were rolled back. Error: ' . $e->getMessage()];
    } finally {
        if (isset($lgu2_conn)) {
            $lgu2_conn->close();
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$pageTitle = "Draft Measures Task";
require_once $_SERVER['DOCUMENT_ROOT'] . '/lgu-2-main-main/includes/header.php';
?>

<style>


/* General Body and Container */
body {
    background-color: #f0f4f7; /* Light gray-blue, common in government sites */
    font-family: Montserrat, system-ui, -apple-system, Arial, Helvetica, sans-serif;
    color: #333; /* Dark gray for text */
    line-height: 1.6;
}

.container {
    margin-top: 1rem;
    padding: 2rem;
    background-color: #fff;
    border-radius: 0; /* Remove rounded corners for a sharper, more official look */
    box-shadow: 0 4px 6px rgba(0,0,0,.1); /* A subtle shadow */
}

/* Headings and Links */
h2, h3, h4 {
    color: #004a8b; /* A deep, professional blue */
    font-weight: 600;
}

a {
    color: #004a8b;
    text-decoration: none; /* No underline by default */
}

a:hover {
    text-decoration: underline; /* Underline on hover for clarity */
}

/* Tabs Navigation */
.nav-tabs {
    border-bottom: 2px solid #004a8b;
}

.nav-tabs .nav-link {
    border: 1px solid transparent;
    border-bottom: none;
    background-color: transparent;
    color: #555;
    font-weight: 500;
    transition: all 0.3s ease-in-out;
}

.nav-tabs .nav-link.active {
    color: #fff;
    background-color: #004a8b;
    border-color: #004a8b;
    font-weight: 600;
}

.nav-tabs .nav-link:hover {
    border-color: #ddd #ddd #004a8b;
}

/* Card and Table */
.card {
    border: 1px solid #e0e0e0;
    box-shadow: none;
}

.table th, .table td {
    padding: 0.75rem;
    vertical-align: middle; /* Aligns content vertically */
    border-top: 1px solid #e9ecef;
}

/* Add a min-width to the first column to prevent date wrapping */
.table th:nth-child(1),
.table td:nth-child(1) {
    min-width: 125px; /* Adjust this value as needed */
    text-align: center; /* Center the date header and content */
}

/* Explicitly set header and data alignment for each column for clarity */
.table th:nth-child(2),
.table td:nth-child(2) {
    text-align: left; /* Aligns 'Title' header and content to the left */
}

.table th:nth-child(3),
.table td:nth-child(3) {
    text-align: left; /* Aligns 'Introducers' header and content to the left */
}

/* Ensure the 'Actions' column is always centered */
.table th:last-child,
.table td:last-child {
    text-align: center;
}

/* General table alignment to catch any unaligned elements */
.table {
    text-align: center;
}

.table-responsive {
    border: 1px solid #e0e0e0;
    border-radius: 0;
    margin-bottom: 2rem;
}

.table {
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
    border-collapse: collapse;
}

.table thead th {
    background-color: #004a8b;
    color: #fff;
    border-bottom: 2px solid #004a8b;
    font-weight: 600;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: #f9f9f9;
}

.table-hover tbody tr:hover {
    background-color: #eaf1f7;
}

.table td .btn-action {
    width: 75px !important;
    margin: 2px 0;
    border-radius: 0;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Buttons and Forms */
.btn-primary {
    background-color: #004a8b;
    border-color: #004a8b;
    transition: background-color 0.3s;
}

.btn-primary:hover {
    background-color: #003866;
    border-color: #003866;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    transition: background-color 0.3s;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    transition: background-color 0.3s;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

.btn-outline-secondary {
    color: #004a8b;
    border-color: #004a8b;
}

.btn-outline-secondary:hover {
    color: #fff;
    background-color: #004a8b;
    border-color: #004a8b;
}

.form-control {
    border-radius: 0;
    border: 1px solid #ccc;
    box-shadow: none;
}

/* Pagination */
.pagination-container {
    padding: 0.75rem;
    border: 1px solid #e0e0e0;
    background-color: #f8f9fa;
}

.pagination .page-item .page-link {
    color: #004a8b;
    border: 1px solid #e0e0e0;
    border-radius: 0;
}

.pagination .page-item.active .page-link {
    background-color: #004a8b;
    border-color: #004a8b;
    color: #fff;
}

.pagination .page-item:not(.active) .page-link:hover {
    background-color: #eaf1f7;
    color: #004a8b;
}

.pagination-container > span {
    font-size: 0.9rem;
    color: #6c757d;
}


/* Responsive adjustments */
@media (max-width: 768px) {
    .table td, .table th {
        font-size: 0.8rem;
    }
}
</style>

<div class="container">
    <div id="alertContainer" class="mt-4"></div>
    <div class="card p-4">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="incoming-tab" data-bs-toggle="tab" data-bs-target="#incoming" type="button" role="tab" aria-controls="incoming" aria-selected="true">Incoming Task</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-measures" type="button" role="tab" aria-controls="pending-measures" aria-selected="false">Pending Measures</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ordinances-tab" data-bs-toggle="tab" data-bs-target="#ordinances" type="button" role="tab" aria-controls="ordinances" aria-selected="false">Proposed Ordinances</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="resolutions-tab" data-bs-toggle="tab" data-bs-target="#resolutions" type="button" role="tab" aria-controls="resolutions" aria-selected="false">Proposed Resolutions</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            
            <div class="tab-pane fade show active" id="incoming" role="tabpanel" aria-labelledby="incoming-tab">
                <h2 class="mt-4 mb-3">Incoming Task</h2>
                <form method="GET" action="" class="mb-3">
                    <div class="input-group">
                        <input type="hidden" name="tab" value="incoming">
                        <input type="text" name="search" class="form-control" placeholder="Search by title" value="<?php echo $search_query; ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if (!empty($search_query)): ?>
                            <a href="?page=1" class="btn btn-outline-danger">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date Created</th>
                                <th>Title</th>
                                <th>Introducers</th>
                                <th>Measure Type</th>
                                <th>Measure Status</th>
                                <th>Date & Time Submitted</th>                                
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $search_conditions = "WHERE measure_status = 'Draft'";
                            if (!empty($search_query)) {
                                $search_terms = explode(' ', $search_query);
                                $search_conditions_array = [];
                                foreach ($search_terms as $term) {
                                    $search_conditions_array[] = "(measure_title LIKE ?)";
                                }
                                $search_conditions .= " AND (" . implode(" OR ", $search_conditions_array) . ")";
                            }

                            // Count total rows for pagination
                            $sql_count = "SELECT COUNT(*) AS total FROM m9_similaritychecking " . $search_conditions;
                            $stmt_count = $lgu2_conn->prepare($sql_count);
                            if (!empty($search_query)) {
                                $bind_params = [];
                                $types = '';
                                foreach ($search_terms as $term) {
                                    $types .= 's';
                                    $bind_params[] = "%" . $term . "%";
                                }
                                $stmt_count->bind_param($types, ...$bind_params);
                            }
                            $stmt_count->execute();
                            $result_count = $stmt_count->get_result();
                            $row_count = $result_count->fetch_assoc();
                            $total_records = $row_count['total'];
                            $total_pages = ceil($total_records / $records_per_page);
                            
                            // Query for the 'incoming' tab
                            $sql_incoming = "SELECT * FROM m9_similaritychecking " . $search_conditions . " LIMIT ? OFFSET ?";
                            $stmt_incoming = $lgu2_conn->prepare($sql_incoming);
                            if ($stmt_incoming === false) {
                                die("Error preparing statement for incoming tasks: " . $lgu2_conn->error);
                            }
                            if (!empty($search_query)) {
                                $bind_params_data = [];
                                $types_data = '';
                                foreach ($search_terms as $term) {
                                    $types_data .= 's';
                                    $bind_params_data[] = "%" . $term . "%";
                                }
                                $types_data .= 'ii';
                                $bind_params_data[] = $records_per_page;
                                $bind_params_data[] = $offset;
                                $stmt_incoming->bind_param($types_data, ...$bind_params_data);
                            } else {
                                $stmt_incoming->bind_param("ii", $records_per_page, $offset);
                            }

                            if (!$stmt_incoming->execute()) {
                                die("Error executing statement for incoming tasks: " . $stmt_incoming->error);
                            }
                            $result_incoming = $stmt_incoming->get_result();
                            
                            if ($result_incoming->num_rows > 0) {
                                while ($row = $result_incoming->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['date_created']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['introducers']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_status']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['datetime_submitted']) . "</td>";                                    
                                    echo "<td>
                                        <button type='button' class='modal1 btn btn-primary btn-sm btn-action view-task-btn' data-bs-toggle='modal' data-bs-target='#ITViewModal'
                                            data-title='" . htmlspecialchars($row['measure_title']) . "'
                                            data-introducers='" . htmlspecialchars($row['introducers']) . "'
                                            data-content='" . nl2br(htmlspecialchars($row['measure_content'], ENT_QUOTES, 'UTF-8')) . "'>VIEW</button>
                                        <button class='btn btn-success btn-sm btn-action send-task-btn' data-bs-toggle='modal' data-bs-target='#ITSendModal'
                                            data-id='" . htmlspecialchars($row['m9_SC_ID']) . "'
                                            data-idcode='" . htmlspecialchars($row['m9_SC_Code']) . "'
                                            data-date='" . htmlspecialchars($row['date_created']) . "'
                                            data-type='" . htmlspecialchars($row['measure_type']) . "'
                                            data-title='" . htmlspecialchars($row['measure_title']) . "'
                                            data-content='" . htmlspecialchars($row['measure_content']) . "'
                                            data-introducers='" . htmlspecialchars($row['introducers']) . "'
                                            data-status='" . htmlspecialchars($row['measure_status']) . "'
                                            data-datetime='" . htmlspecialchars($row['datetime_submitted']) . "'>SEND</button>
                                        
                                        </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No incoming tasks found.</td></tr>";
                            }
                            $stmt_incoming->close();
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container">
                    <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php if($current_page <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search_query); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php if($current_page == $i) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if($current_page >= $total_pages) echo 'disabled'; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search_query); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <div class="tab-pane fade" id="pending-measures" role="tabpanel" aria-labelledby="pending-tab">
                <h2 class="mt-4 mb-3">Pending Measures</h2>
                <form method="GET" action="" class="mb-3">
                    <div class="input-group">
                        <input type="hidden" name="tab" value="pending-measures">
                        <input type="text" name="search_pending" class="form-control" placeholder="Search by title" value="<?php echo isset($_GET['search_pending']) ? htmlspecialchars($_GET['search_pending']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if (isset($_GET['search_pending']) && !empty($_GET['search_pending'])): ?>
                            <a href="?tab=pending-measures" class="btn btn-outline-danger">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date Created</th>
                                <th>Title</th>
                                <th>Introducers</th>
                                <th>Measure Type</th>
                                <th>Measure Status</th>
                                <th>Date & Time Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_page_pending = isset($_GET['page_pending']) ? (int)$_GET['page_pending'] : 1;
                            $search_query_pending = isset($_GET['search_pending']) ? htmlspecialchars($_GET['search_pending']) : '';
                            $offset_pending = ($current_page_pending - 1) * $records_per_page;

                            // Base condition for pending measures
                            $search_conditions_pending = "WHERE measure_status = 'Pending'";

                            // Append search query condition if it exists
                            if (!empty($search_query_pending)) {
                                $search_terms_pending = explode(' ', $search_query_pending);
                                $search_conditions_array_pending = [];
                                foreach ($search_terms_pending as $term) {
                                    $search_conditions_array_pending[] = "(measure_title LIKE ?)";
                                }
                                // Combine the base condition with the search query using AND
                                $search_conditions_pending .= " AND (" . implode(" OR ", $search_conditions_array_pending) . ")";
                            }

                            $sql_count_pending = "SELECT COUNT(*) AS total FROM m9_similaritychecking " . $search_conditions_pending;
                            $stmt_count_pending = $lgu2_conn->prepare($sql_count_pending);

                            // Prepare and bind for search query
                            if (!empty($search_query_pending)) {
                                $bind_params_pending = [];
                                $types_pending = '';
                                foreach ($search_terms_pending as $term) {
                                    $types_pending .= 's';
                                    $bind_params_pending[] = "%" . $term . "%";
                                }
                                $stmt_count_pending->bind_param($types_pending, ...$bind_params_pending);
                            }
                            $stmt_count_pending->execute();
                            $result_count_pending = $stmt_count_pending->get_result();
                            $row_count_pending = $result_count_pending->fetch_assoc();
                            $total_records_pending = $row_count_pending['total'];
                            $total_pages_pending = ceil($total_records_pending / $records_per_page);

                            // Query for the pending measures
                            $sql_pending = "SELECT * FROM m9_similaritychecking " . $search_conditions_pending . " LIMIT ? OFFSET ?";
                            $stmt_pending = $lgu2_conn->prepare($sql_pending);
                            if ($stmt_pending === false) {
                                die("Error preparing statement for pending measures: " . $lgu2_conn->error);
                            }

                            // Prepare and bind for the main query
                            if (!empty($search_query_pending)) {
                                $bind_params_data_pending = [];
                                $types_data_pending = '';
                                foreach ($search_terms_pending as $term) {
                                    $types_data_pending .= 's';
                                    $bind_params_data_pending[] = "%" . $term . "%";
                                }
                                $types_data_pending .= 'ii';
                                $bind_params_data_pending[] = $records_per_page;
                                $bind_params_data_pending[] = $offset_pending;
                                $stmt_pending->bind_param($types_data_pending, ...$bind_params_data_pending);
                            } else {
                                $stmt_pending->bind_param("ii", $records_per_page, $offset_pending);
                            }
                            if (!$stmt_pending->execute()) {
                                die("Error executing statement for pending measures: " . $stmt_pending->error);
                            }
                            $result_pending = $stmt_pending->get_result();

                            if ($result_pending->num_rows > 0) {
                                while ($row = $result_pending->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['date_created']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['introducers']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_status']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['datetime_submitted']) . "</td>";
                                    echo "<td>
                                        <button type='button' class='modal1 btn btn-primary btn-sm btn-action view-task-btn' data-bs-toggle='modal' data-bs-target='#ITViewModal'
                                            data-title='" . htmlspecialchars($row['measure_title']) . "'
                                            data-introducers='" . htmlspecialchars($row['introducers']) . "'
                                            data-content='" . nl2br(htmlspecialchars($row['measure_content'], ENT_QUOTES, 'UTF-8')) . "'>VIEW</button>
                                        </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No pending measures found.</td></tr>";
                            }
                            $stmt_pending->close();
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container">
                    <span>Page <?php echo $current_page_pending; ?> of <?php echo $total_pages_pending; ?></span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php if($current_page_pending <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_pending=<?php echo $current_page_pending - 1; ?>&search_pending=<?php echo urlencode($search_query_pending); ?>&tab=pending-measures" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages_pending; $i++): ?>
                                <li class="page-item <?php if($current_page_pending == $i) echo 'active'; ?>">
                                    <a class="page-link" href="?page_pending=<?php echo $i; ?>&search_pending=<?php echo urlencode($search_query_pending); ?>&tab=pending-measures"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if($current_page_pending >= $total_pages_pending) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_pending=<?php echo $current_page_pending + 1; ?>&search_pending=<?php echo urlencode($search_query_pending); ?>&tab=pending-measures" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <div class="tab-pane fade" id="ordinances" role="tabpanel" aria-labelledby="ordinances-tab">
                <h2 class="mt-4 mb-3">Proposed Ordinances</h2>
                <form method="GET" action="" class="mb-3">
                    <div class="input-group">
                        <input type="hidden" name="tab" value="ordinances">
                        <input type="text" name="search_po" class="form-control" placeholder="Search by title" value="<?php echo isset($_GET['search_po']) ? htmlspecialchars($_GET['search_po']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if (isset($_GET['search_po']) && !empty($_GET['search_po'])): ?>
                            <a href="?tab=ordinances" class="btn btn-outline-danger">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Docket No.</th>
                                <th>Title</th>
                                <th>Introducers</th>
                                <th>Checking Remarks</th>
                                <th>Measure Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_page_po = isset($_GET['page_po']) ? (int)$_GET['page_po'] : 1;
                            $search_query_po = isset($_GET['search_po']) ? htmlspecialchars($_GET['search_po']) : '';
                            $offset_po = ($current_page_po - 1) * $records_per_page;

                            $search_conditions_po = "WHERE measure_type = 'Ordinance' AND measure_status IN ('1st Reading', '2nd Reading', '3rd Reading')";
                            if (!empty($search_query_po)) {
                                $search_terms_po = explode(' ', $search_query_po);
                                $search_conditions_array_po = [];
                                foreach ($search_terms_po as $term) {
                                    $search_conditions_array_po[] = "(measure_title LIKE ?)";
                                }
                                $search_conditions_po .= " AND (" . implode(" OR ", $search_conditions_array_po) . ")";
                            }

                            $sql_count_po = "SELECT COUNT(*) AS total FROM m9_similaritychecking " . $search_conditions_po;
                            $stmt_count_po = $lgu2_conn->prepare($sql_count_po);
                            if (!empty($search_query_po)) {
                                $bind_params_po = [];
                                $types_po = '';
                                foreach ($search_terms_po as $term) {
                                    $types_po .= 's';
                                    $bind_params_po[] = "%" . $term . "%";
                                }
                                $stmt_count_po->bind_param($types_po, ...$bind_params_po);
                            }
                            $stmt_count_po->execute();
                            $result_count_po = $stmt_count_po->get_result();
                            $row_count_po = $result_count_po->fetch_assoc();
                            $total_records_po = $row_count_po['total'];
                            $total_pages_po = ceil($total_records_po / $records_per_page);

                            $sql_ordinances = "SELECT * FROM m9_similaritychecking " . $search_conditions_po . " LIMIT ? OFFSET ?";
                            $stmt_ordinances = $lgu2_conn->prepare($sql_ordinances);
                            if ($stmt_ordinances === false) {
                                die("Error preparing statement for ordinances: " . $lgu2_conn->error);
                            }
                            if (!empty($search_query_po)) {
                                $bind_params_data_po = [];
                                $types_data_po = '';
                                foreach ($search_terms_po as $term) {
                                    $types_data_po .= 's';
                                    $bind_params_data_po[] = "%" . $term . "%";
                                }
                                $types_data_po .= 'ii';
                                $bind_params_data_po[] = $records_per_page;
                                $bind_params_data_po[] = $offset_po;
                                $stmt_ordinances->bind_param($types_data_po, ...$bind_params_data_po);
                            } else {
                                $stmt_ordinances->bind_param("ii", $records_per_page, $offset_po);
                            }
                            if (!$stmt_ordinances->execute()) {
                                die("Error executing statement for ordinances: " . $stmt_ordinances->error);
                            }
                            $result_ordinances = $stmt_ordinances->get_result();

                            if ($result_ordinances->num_rows > 0) {
                                while ($row = $result_ordinances->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['docket_no']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['introducers']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['checking_remarks']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_status']) . "</td>";
                                    echo "<td>
                                        <button type='button' class='modal1 btn btn-primary btn-sm btn-action view-task-btn' data-bs-toggle='modal' data-bs-target='#POViewModal'
                                            data-title='" . htmlspecialchars($row['measure_title']) . "'
                                            data-introducers='" . htmlspecialchars($row['introducers']) . "'
                                            data-content='" . nl2br(htmlspecialchars($row['measure_content'], ENT_QUOTES, 'UTF-8')) . "'>VIEW</button>
                                        
                                        </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No proposed ordinances found.</td></tr>";
                            }
                            $stmt_ordinances->close();
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container">
                    <span>Page <?php echo $current_page_po; ?> of <?php echo $total_pages_po; ?></span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php if($current_page_po <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_po=<?php echo $current_page_po - 1; ?>&search_po=<?php echo urlencode($search_query_po); ?>&tab=ordinances" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages_po; $i++): ?>
                                <li class="page-item <?php if($current_page_po == $i) echo 'active'; ?>">
                                    <a class="page-link" href="?page_po=<?php echo $i; ?>&search_po=<?php echo urlencode($search_query_po); ?>&tab=ordinances"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if($current_page_po >= $total_pages_po) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_po=<?php echo $current_page_po + 1; ?>&search_po=<?php echo urlencode($search_query_po); ?>&tab=ordinances" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            
            <div class="tab-pane fade" id="resolutions" role="tabpanel" aria-labelledby="resolutions-tab">
                <h2 class="mt-4 mb-3">Proposed Resolutions</h2>
                <form method="GET" action="" class="mb-3">
                    <div class="input-group">
                        <input type="hidden" name="tab" value="resolutions">
                        <input type="text" name="search_pr" class="form-control" placeholder="Search by title" value="<?php echo isset($_GET['search_pr']) ? htmlspecialchars($_GET['search_pr']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if (isset($_GET['search_pr']) && !empty($_GET['search_pr'])): ?>
                            <a href="?tab=resolutions" class="btn btn-outline-danger">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Docket No.</th>
                                <th>Title</th>
                                <th>Introducers</th>
                                <th>Checking Remarks</th>
                                <th>Measure Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_page_pr = isset($_GET['page_pr']) ? (int)$_GET['page_pr'] : 1;
                            $search_query_pr = isset($_GET['search_pr']) ? htmlspecialchars($_GET['search_pr']) : '';
                            $offset_pr = ($current_page_pr - 1) * $records_per_page;
                            
                            $search_conditions_pr = "WHERE measure_type = 'Resolution' AND measure_status IN ('1st Reading', '2nd Reading', '3rd Reading')";
                            if (!empty($search_query_pr)) {
                                $search_terms_pr = explode(' ', $search_query_pr);
                                $search_conditions_array_pr = [];
                                foreach ($search_terms_pr as $term) {
                                    $search_conditions_array_pr[] = "(measure_title LIKE ?)";
                                }
                                $search_conditions_pr .= " AND (" . implode(" OR ", $search_conditions_array_pr) . ")";
                            }

                            $sql_count_pr = "SELECT COUNT(*) AS total FROM m9_similaritychecking " . $search_conditions_pr;
                            $stmt_count_pr = $lgu2_conn->prepare($sql_count_pr);
                            if (!empty($search_query_pr)) {
                                $bind_params_pr = [];
                                $types_pr = '';
                                foreach ($search_terms_pr as $term) {
                                    $types_pr .= 's';
                                    $bind_params_pr[] = "%" . $term . "%";
                                }
                                $stmt_count_pr->bind_param($types_pr, ...$bind_params_pr);
                            }
                            $stmt_count_pr->execute();
                            $result_count_pr = $stmt_count_pr->get_result();
                            $row_count_pr = $result_count_pr->fetch_assoc();
                            $total_records_pr = $row_count_pr['total'];
                            $total_pages_pr = ceil($total_records_pr / $records_per_page);
                            
                            $sql_resolutions = "SELECT * FROM m9_similaritychecking " . $search_conditions_pr . " LIMIT ? OFFSET ?";
                            $stmt_resolutions = $lgu2_conn->prepare($sql_resolutions);
                            if ($stmt_resolutions === false) {
                                die("Error preparing statement for ordinances: " . $lgu2_conn->error);
                            }
                            if (!empty($search_query_pr)) {
                                $bind_params_data_pr = [];
                                $types_data_pr = '';
                                foreach ($search_terms_pr as $term) {
                                    $types_data_pr .= 's';
                                    $bind_params_data_pr[] = "%" . $term . "%";
                                }
                                $types_data_pr .= 'ii';
                                $bind_params_data_pr[] = $records_per_page;
                                $bind_params_data_pr[] = $offset_pr;
                                $stmt_resolutions->bind_param($types_data_pr, ...$bind_params_data_pr);
                            } else {
                                $stmt_resolutions->bind_param("ii", $records_per_page, $offset_pr);
                            }
                            if (!$stmt_resolutions->execute()) {
                                die("Error executing statement for ordinances: " . $stmt_resolutions->error);
                            }
                            $result_resolutions = $stmt_resolutions->get_result();

                            if ($result_resolutions->num_rows > 0) {
                                while ($row = $result_resolutions->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['docket_no']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['introducers']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['checking_remarks']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['measure_status']) . "</td>";
                                    echo "<td>
                                        <button type='button' class='modal1 btn btn-primary btn-sm btn-action view-task-btn' data-bs-toggle='modal' data-bs-target='#PRViewModal'
                                            data-title='" . htmlspecialchars($row['measure_title']) . "'
                                            data-introducers='" . htmlspecialchars($row['introducers']) . "'
                                            data-content='" . nl2br(htmlspecialchars($row['measure_content'], ENT_QUOTES, 'UTF-8')) . "'>VIEW</button>
                                        
                                        </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No proposed resolutions found.</td></tr>";
                            }
                            $stmt_resolutions->close();
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-container">
                    <span>Page <?php echo $current_page_pr; ?> of <?php echo $total_pages_pr; ?></span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php if($current_page_pr <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_pr=<?php echo $current_page_pr - 1; ?>&search_pr=<?php echo urlencode($search_query_pr); ?>&tab=resolutions" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages_pr; $i++): ?>
                                <li class="page-item <?php if($current_page_pr == $i) echo 'active'; ?>">
                                    <a class="page-link" href="?page_pr=<?php echo $i; ?>&search_pr=<?php echo urlencode($search_query_pr); ?>&tab=resolutions"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php if($current_page_pr >= $total_pages_pr) echo 'disabled'; ?>">
                                <a class="page-link" href="?page_pr=<?php echo $current_page_pr + 1; ?>&search_pr=<?php echo urlencode($search_query_pr); ?>&tab=resolutions" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ONLY CLOSE THE CONNECTION AT THE VERY END OF THE SCRIPT
if (isset($lgu2_conn) && $lgu2_conn->ping()) {
    $lgu2_conn->close();
}
?>


<div class="modal fade" id="ITViewModal" tabindex="+1" aria-labelledby="ITViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg ">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="ITViewModalLabel">Measure Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 text-justify">
                        <div class="mb-2">
                            <label for="modal-title" class="form-label fw-bold">Title:</label>
                            <p id="modal-title" class="text-break"></p>
                        </div>
                    </div>
                    <div class="col-md-6 text-justify">
                        <div class="mb-2">
                            <label for="modal-introducers" class="form-label fw-bold">Introducers:</label>
                            <p id="modal-introducers" class="text-break"></p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-justify">
                    <label for="modal-content" class="form-label fw-bold">Measure Content:</label>
                    <div id="modal-content" class="p-3 border rounded bg-light text-break" style="max-height: 325px; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ITViewModal = document.getElementById('ITViewModal');
        ITViewModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;

            // Extract info from data-bs-* attributes
            var title = button.getAttribute('data-title');
            var introducers = button.getAttribute('data-introducers');
            var content = button.getAttribute('data-content');

            // Update the modal's content.
            var modalTitle = ITViewModal.querySelector('#modal-title');
            var modalIntroducers = ITViewModal.querySelector('#modal-introducers');
            var modalContent = ITViewModal.querySelector('#modal-content');

            modalTitle.textContent = title;
            modalIntroducers.textContent = introducers;
            modalContent.innerHTML = content;
        });
    });
</script>


<div class="modal fade" id="ITSendModal" tabindex="-1" aria-labelledby="ITSendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="ITSendModalLabel">Send Measure to Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6 text-justify">
                            <label class="form-label fw-bold">Title:</label>
                            <p id="modal-send-title-text" class="form-control-plaintext"></p>
                        </div>
                        <div class="col-md-6 text-justify">
                            <label class="form-label fw-bold">Introducers:</label>
                            <p id="modal-send-introducers-text" class="form-control-plaintext"></p>
                        </div>
                    </div>

                    <hr>
                    <div class="text-center">
                    <div class="mb-3">
                        <label for="checking_remarks" class="form-label">Checking Remarks</label>
                        <select class="form-select" id="checking_remarks" name="checking_remarks" required>
                            <option value="" disabled selected>Select an option</option>
                            <option value="No Similar">No Similar</option>
                            <option value="Similar">Similar</option>
                        </select>
                    </div>

                        <div class="mb-3">
                            <label for="checking_notes" class="form-label">Checking Notes</label>
                            <textarea class="form-control" id="checking_notes" name="checking_notes" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                        <label for="checked_by" class="form-label visually-hidden">Checked By</label>
                        <input type="hidden" id="checked_by" name="checked_by" value="Research Section">
                        </div>

                        <div class="mb-3">
                            <label for="datetime_submitted" class="form-label visually-hidden">Date & Time Submitted</label>
                            <input type="hidden" class="form-control" id="datetime_submitted" name="datetime_submitted" required>
                        </div>
                    </div>

                    
                    <input type="hidden" id="modal-send-id-hidden" name="m9_SC_ID">
                    <input type="hidden" id="modal-send-idcode-hidden" name="m9_SC_Code">
                    <input type="hidden" id="modal-send-date-hidden" name="date_created">
                    <input type="hidden" id="modal-send-type-hidden" name="measure_type">
                    <input type="hidden" id="modal-send-title-hidden" name="measure_title">
                    <input type="hidden" id="modal-send-content-hidden" name="measure_content">
                    <input type="hidden" id="modal-send-introducers-hidden" name="introducers">
                    <input type="hidden" id="modal-send-status-hidden" name="measure_status">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="send_task" class="btn btn-success">Send Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ITSendModal = document.getElementById('ITSendModal');
    ITSendModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var idcode = button.getAttribute('data-idcode');
        var date = button.getAttribute('data-date');
        var type = button.getAttribute('data-type');
        var title = button.getAttribute('data-title');
        var content = button.getAttribute('data-content');
        var introducers = button.getAttribute('data-introducers');
        var status = button.getAttribute('data-status');

        // Populate hidden fields for form submission
        ITSendModal.querySelector('#modal-send-id-hidden').value = id;
        ITSendModal.querySelector('#modal-send-idcode-hidden').value = idcode;
        ITSendModal.querySelector('#modal-send-date-hidden').value = date;
        ITSendModal.querySelector('#modal-send-type-hidden').value = type;
        ITSendModal.querySelector('#modal-send-title-hidden').value = title;
        ITSendModal.querySelector('#modal-send-content-hidden').value = content;
        ITSendModal.querySelector('#modal-send-introducers-hidden').value = introducers;
        ITSendModal.querySelector('#modal-send-status-hidden').value = status;
        
        // Display the data in the text labels
        ITSendModal.querySelector('#modal-send-title-text').textContent = title;
        ITSendModal.querySelector('#modal-send-introducers-text').textContent = introducers;
        
        // Populate the Date & Time input field with the CURRENT local date and time
        const now = new Date();
        const year = now.getFullYear();
        const month = (now.getMonth() + 1).toString().padStart(2, '0');
        const day = now.getDate().toString().padStart(2, '0');
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        
        const currentDatetime = `${year}-${month}-${day}T${hours}:${minutes}`;
        ITSendModal.querySelector('#datetime_submitted').value = currentDatetime;
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const alertContainer = document.getElementById('alertContainer');

    // PHP to JS Bridge to check for session message
    const message = <?php echo json_encode(isset($_SESSION['message']) ? $_SESSION['message'] : null); ?>;

    if (message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${message.type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.textContent = message.text;

        const closeBtn = document.createElement('button');
        closeBtn.className = 'btn-close';
        closeBtn.setAttribute('type', 'button');
        closeBtn.setAttribute('data-bs-dismiss', 'alert');
        closeBtn.setAttribute('aria-label', 'Close');

        alertDiv.appendChild(closeBtn);
        alertContainer.appendChild(alertDiv);

        // Remove the alert after a few seconds
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000); // Alert will disappear after 5 seconds
        
        <?php unset($_SESSION['message']); ?>
    }

    // Keep the correct tab active on page reload after form submission or search
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'incoming';
        
        let tabButtonId;
        // Special case for 'pending-measures' to match its tab button ID
        if (activeTab === 'pending-measures') {
            tabButtonId = 'pending-tab';
        } else {
            tabButtonId = `${activeTab}-tab`;
        }

        const tabButton = document.querySelector(`#${tabButtonId}`);
        if (tabButton) {
            const tab = new bootstrap.Tab(tabButton);
            tab.show();
        }
    });
</script>


<div class="modal fade" id="POViewModal" tabindex="-1" aria-labelledby="POViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="POViewModalLabel">Measure Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 text-justify">
                        <div class="mb-2">
                            <label for="modal-title" class="form-label fw-bold">Title:</label>
                            <p id="modal-title-po" class="text-break"></p>
                        </div>
                    </div>
                    <div class="col-md-6 text-justify">
                        <div class="mb-2">
                            <label for="modal-introducers" class="form-label fw-bold">Introducers:</label>
                            <p id="modal-introducers-po" class="text-break"></p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-justify">
                    <label for="modal-content" class="form-label fw-bold">Measure Content:</label>
                    <div id="modal-content-po" class="p-3 border rounded bg-light text-break" style="max-height: 325px; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var POViewModal = document.getElementById('POViewModal');
        POViewModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;

            // Extract info from data-bs-* attributes
            var title = button.getAttribute('data-title');
            var introducers = button.getAttribute('data-introducers');
            var content = button.getAttribute('data-content');

            // Update the modal's content.
            var modalTitle = POViewModal.querySelector('#modal-title-po');
            var modalIntroducers = POViewModal.querySelector('#modal-introducers-po');
            var modalContent = POViewModal.querySelector('#modal-content-po');

            modalTitle.textContent = title;
            modalIntroducers.textContent = introducers;
            modalContent.innerHTML = content;
        });
    });
</script>

<div class="modal fade" id="PRViewModal" tabindex="-1" aria-labelledby="PRViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="PRViewModalLabel">Measure Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 text-justify">
                        <div class="mb-2">
                            <label for="modal-title" class="form-label fw-bold">Title:</label>
                            <p id="modal-title-pr" class="text-break"></p>
                        </div>
                    </div>
                    <div class="col-md-6 text-justify">
                        <div class="mb-2">
                            <label for="modal-introducers" class="form-label fw-bold">Introducers:</label>
                            <p id="modal-introducers-pr" class="text-break"></p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-justify">
                    <label for="modal-content" class="form-label fw-bold">Measure Content:</label>
                    <div id="modal-content-pr" class="p-3 border rounded bg-light text-break" style="max-height: 325px; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var PRViewModal = document.getElementById('PRViewModal');
        PRViewModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            var button = event.relatedTarget;

            // Extract info from data-bs-* attributes
            var title = button.getAttribute('data-title');
            var introducers = button.getAttribute('data-introducers');
            var content = button.getAttribute('data-content');

            // Update the modal's content.
            var modalTitle = PRViewModal.querySelector('#modal-title-pr');
            var modalIntroducers = PRViewModal.querySelector('#modal-introducers-pr');
            var modalContent = PRViewModal.querySelector('#modal-content-pr');

            modalTitle.textContent = title;
            modalIntroducers.textContent = introducers;
            modalContent.innerHTML = content;
        });
    });
</script>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lgu-2-main-main/includes/footer.php';
?>