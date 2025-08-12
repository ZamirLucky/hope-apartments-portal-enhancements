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
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$start = ($page - 1) * $perPage;

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
$smartlocksPage = array_slice($smartlocks, $start, $perPage);

// Define mapping for battery type values (for display)
$batteryTypeMapping = [
    0 => 'Alkali',
    1 => 'Accumulator',
    2 => 'Lithium'
];
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
                    <!-- Sort by percentages -->
                    <div class="col-md-6 text-end">
                        <button class="btn btn-secondary ms-2" id="sortButtonByPerc" type="button">
                            <i class="fas fa-sort-numeric-down"></i> Sort %
                        </button>
                    </div> 
                </div>
                <?php if (isset($smartlocks['error'])): ?>
                    <div class="alert alert-danger text-center">
                        <?= htmlspecialchars($smartlocks['error']); ?>
                    </div>
                <?php else: ?>
                    <?php if (count($smartlocksPage) > 0): ?>
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
                                        <?php foreach ($smartlocksPage as $smartlock): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($smartlock['name']) ?></td>
                                                <td>
                                                    <?php if (isset($smartlock['state']['batteryCritical']) && $smartlock['state']['batteryCritical']): ?>
                                                        <span class="badge bg-danger">Critical</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Normal</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= isset($smartlock['state']['batteryCharge']) 
                                                        ? htmlspecialchars($smartlock['state']['batteryCharge']) . '%' 
                                                        : 'Not available'; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (isset($smartlock['advancedConfig']['batteryType'])) {
                                                        $bt = $smartlock['advancedConfig']['batteryType'];
                                                        echo isset($batteryTypeMapping[$bt]) ? $batteryTypeMapping[$bt] : 'Unknown';
                                                    } else {
                                                        echo 'Not available';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
        const batteryData = <?= json_encode($smartlocks) ?>;
    </script>

    <script src="../../public/js/battery.js"></script>
</body>
</html>
