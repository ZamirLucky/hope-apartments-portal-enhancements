<?php
// Enable error reporting for debugging (remove these lines in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
session_start();

require_once '../controllers/BatteryController.php';
require_once '../controllers/LoginController.php';

// Check if user is logged in; if not, redirect to login page
$loginController = new LoginController();
if (!$loginController->isLoggedIn()) {
    header('Location: LoginView.php');
    exit();
}

$batteryController = new BatteryController();
$smartlocks = $batteryController->getSortedSmartlockData();

// Get search term from query parameters
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';


// Filter smartlocks if search term is provided
if (!empty($searchTerm)) {
    $searchTermLower = strtolower($searchTerm);
    $smartlocks = array_filter($smartlocks, function ($smartlock) use ($searchTermLower) {
        $nameMatch = strpos(strtolower($smartlock['name']), $searchTermLower) !== false;
        $batteryStatus = (isset($smartlock['state']['batteryCritical']) && $smartlock['state']['batteryCritical']) ? 'critical' : 'normal';
        $batteryMatch = strpos($batteryStatus, $searchTermLower) !== false;
        $batteryTypeMapping = [
            0 => 'alkali',
            1 => 'accumulator',
            2 => 'lithium'
        ];
        $batteryType = 'not available';
        if (isset($smartlock['advancedConfig']['batteryType'])) {
            $bt = $smartlock['advancedConfig']['batteryType'];
            $batteryType = isset($batteryTypeMapping[$bt]) ? $batteryTypeMapping[$bt] : 'unknown';
        }
        $typeMatch = strpos($batteryType, $searchTermLower) !== false;
        return $nameMatch || $batteryMatch || $typeMatch;
    });
}

$totalSmartlocks = count($smartlocks);


// Sort by status if set
if (isset($_GET['status']) && in_array($_GET['status'], ['normal', 'critical'])) {
    $status = $_GET['status'];
    usort($smartlocks, function ($a, $b) use ($status) {
        $aCritical = isset($a['state']['batteryCritical']) && $a['state']['batteryCritical'];
        $bCritical = isset($b['state']['batteryCritical']) && $b['state']['batteryCritical'];
        if ($status === 'critical') {
            return $bCritical <=> $aCritical;
        } else {
            return $aCritical <=> $bCritical;
        }
    });
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Battery Status of Nuki Devices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../views/nav.php'; ?>
    <div class="container mt-5">
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header text-white">
                <h2 class="card-title mb-0">Battery Status of Nuki Devices</h2>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="get" action="" class="d-flex">
                            <input type="text" name="search" id="searchInput" class="form-control"
                                   placeholder="Search by Name, Battery Status, or Battery Type"
                                   value="<?= htmlspecialchars($searchTerm) ?>">
                            <button type="submit" id="searchButton" class="btn btn-primary ms-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </form>
                    </div>
                    <!-- Sort controls -->
                    <div class="col-md-6 d-flex">
                        <!-- Percentage sort -->
                        <button class="btn btn-primary ms-2" id="sortButtonByPerc" type="button">
                            <i class="fas fa-sort-numeric-down"></i> Sort %
                        </button>

                        <!-- Status dropdown sort -->
                        <div class="btn-group ms-1" role="group">
                            <button class="btn btn-primary dropdown-toggle" 
                                id="sortStatusDropdown" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false"
                            >

                                <span id="sortStatusLabel"
                                    <?php if (isset($_GET['status']) && $_GET['status'] === 'critical'):
                                    echo 'class="text-danger"'; ?>
                                    <?php endif; ?>>
                                    
                                    <?= isset($_GET['status']) && in_array($_GET['status'], ['normal', 'critical']) 
                                        ? ucfirst($_GET['status']) 
                                        : 'Sort by status' ?>
                                </span>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="sortStatusDropdown">
                                <li><a class="dropdown-item" href="?<?= http_build_query(array_merge($_GET, ['status' => 'normal'])) ?>">Normal</a></li>
                                <li><a class="dropdown-item text-danger" href="?<?= http_build_query(array_merge($_GET, ['status' => 'critical'])) ?>">Critical</a></li>
                                <li><a class="dropdown-item" href="?<?= http_build_query(array_diff_key($_GET, ['status' => ''])) ?>">Clear</a></li>
                            </ul>
                        </div>
                    </div> 
                </div>
                <?php if (isset($smartlocks['error'])): ?>
                    <div class="alert alert-danger text-center">
                        <?= htmlspecialchars($smartlocks['error']); ?>
                    </div>
                <?php else: ?>
                    <?php if ($totalSmartlocks > 0): ?>
                        <!-- Battery table -->
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Device Name</th>
                                        <th>Battery Status</th>
                                        <th>Battery Charge (%)</th>
                                        <th>Battery Type</th>
                                    </tr>
                                </thead>
                                <tbody id="smartlockTableBody">
                                    <!-- battery.js will render rows -->
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination Controls -->
                        <div id="paginationControls" class="mt-3"></div>

                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            No smartlocks found.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Pass PHP data to JavaScript -->
    <script>
        //const batteryData = <?= json_encode($smartlocks) ?>;
        const batteryData = <?= json_encode(array_values($smartlocks)) ?>;
    </script>

    <script src="../../public/js/battery.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
