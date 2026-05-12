<?php 
include 'db_connect.php'; // Resolves the Fatal Error by connecting to your DB

// 1. Fetch Total Residents
$total_res_query = $conn->query("SELECT COUNT(*) as total FROM residents");
$total_res = $total_res_query->fetch_assoc()['total'] ?? 0;

// 2. Fetch Children (Age 0-12)
$child_query = $conn->query("SELECT COUNT(*) as total FROM residents WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) <= 12");
$child_count = $child_query->fetch_assoc()['total'] ?? 0;

// 3. Fetch Adults (Age 13+)
$adult_query = $conn->query("SELECT COUNT(*) as total FROM residents WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) > 12");
$adult_count = $adult_query->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?> <main class="col-md-10 ms-sm-auto p-4">
            <h2 class="text-center mb-4">Resident Profiling Dashboard</h2>
            
            <div class="row g-3">
                <div class="col-md-4"><div class="stat-card bg-total"><h1><?= $total_res ?></h1><p>Total Residents</p></div></div>
                <div class="col-md-4"><div class="stat-card bg-infants"><h1>0</h1><p>Infants</p></div></div>
                <div class="col-md-4"><div class="stat-card bg-children"><h1><?= $child_count ?></h1><p>Children</p></div></div>
                <div class="col-md-4"><div class="stat-card bg-teens"><h1>0</h1><p>Teens</p></div></div>
                <div class="col-md-4"><div class="stat-card bg-adult"><h1><?= $adult_count ?></h1><p>Adult</p></div></div>
                <div class="col-md-4"><div class="stat-card bg-senior"><h1>0</h1><p>Senior Citizen</p></div></div>
            </div>
            
            <div class="mt-5 text-center">
                <h5>Resident Population Graph</h5>
                <div style="max-width: 400px; margin: auto;">
                    <canvas id="popChart"></canvas>
                </div>
            </div>
        </main>
    </div>
</div>

<footer id="liveClock" class="footer-clock"></footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    // These variables send PHP data to your script.js
    const childrenVal = <?= $child_count ?>;
    const adultVal = <?= $adult_count ?>;
</script>
<script src="script.js"></script>
</body>
</html>