<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- PRECISE TERMINATION LOGIC ---
if(isset($_GET['delete_user_id'])){
    $user_id = intval($_GET['delete_user_id']);
    
    // Clear dependencies first
    $conn->query("DELETE FROM portal_activity WHERE user_id = $user_id");
    $delete = $conn->query("DELETE FROM users WHERE id = $user_id");
    
    if($delete){
        header("Location: account.php?status=success_purge");
        exit();
    }
}

// Live Uplink Stats
$active_query = $conn->query("SELECT COUNT(*) as active_total FROM portal_activity WHERE last_seen > NOW() - INTERVAL 5 MINUTE");
$active_total = $active_query->fetch_assoc()['active_total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="30"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Console | Barangay E-Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --brand-primary: #0f172a;
            --brand-accent: #3b82f6;
            --success-glow: #10b981;
            --danger-soft: #fff1f2;
            --danger-bold: #e11d48;
        }

        body { 
            background-color: #f1f5f9;
            background-image: radial-gradient(#cbd5e1 0.5px, transparent 0.5px);
            background-size: 24px 24px; /* Subtle grid for that technical look */
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--brand-primary);
        }

        .sidebar-container { width: 280px; position: fixed; height: 100vh; z-index: 1000; }
        .main-wrapper { margin-left: 280px; padding: 50px; }

        /* The "Glow" Card */
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.03);
        }

        /* Profile Image logic */
        .avatar-frame {
            width: 54px; height: 54px;
            border-radius: 16px;
            padding: 3px;
            background: linear-gradient(135deg, #3b82f6, #10b981);
            position: relative;
        }
        .avatar-frame img {
            width: 100%; height: 100%;
            border-radius: 13px;
            object-fit: cover;
            border: 2px solid white;
        }

        /* Live Radar Pulse */
        .radar-pulse {
            height: 10px; width: 10px;
            background: var(--success-glow);
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            position: relative;
        }
        .radar-pulse::after {
            content: '';
            position: absolute;
            width: 100%; height: 100%;
            background: var(--success-glow);
            border-radius: 50%;
            animation: pulseWave 2s infinite;
        }
        @keyframes pulseWave {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(4); opacity: 0; }
        }

        /* Table Design */
        .table thead th {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1.5px;
            font-weight: 800;
            color: #64748b;
            padding: 25px;
            border: none;
        }
        .user-row { 
            transition: all 0.3s; 
            border-bottom: 1px solid #f1f5f9;
        }
        .user-row:hover { 
            background: rgba(59, 130, 246, 0.03); 
            transform: scale(1.005);
        }

        /* Delete Button */
        .action-btn-delete {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--danger-soft);
            color: var(--danger-bold);
            border: none;
            transition: 0.2s;
        }
        .action-btn-delete:hover {
            background: var(--danger-bold);
            color: white;
            box-shadow: 0 8px 15px rgba(225, 29, 72, 0.2);
        }

        /* Staggered Loading */
        .user-row { opacity: 0; animation: fadeInUp 0.5s forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="sidebar-container"><?php include 'sidebar.php'; ?></div>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-5 animate__animated animate__fadeIn">
        <div>
            <h1 class="fw-800 text-dark mb-1">Account Management</h1>
            <p class="text-muted fw-500">Live Resident Monitoring System & Database Control</p>
        </div>
        
        <div class="glass-panel py-2 px-4 d-flex align-items-center border">
            <span class="radar-pulse"></span>
            <span class="fw-800 small"><?= $active_total ?> LIVE SESSIONS</span>
        </div>
    </div>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'success_purge'): ?>
    <div class="alert bg-white border-start border-danger border-4 shadow-sm py-3 px-4 mb-4 animate__animated animate__lightSpeedInLeft">
        <div class="d-flex align-items-center">
            <i class="fas fa-user-check text-danger me-3 fa-lg"></i>
            <div>
                <strong class="d-block">Account Terminated</strong>
                <span class="text-muted small">Resident records have been securely removed from the database.</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="glass-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-5">RESIDENT PROFILE</th>
                        <th>CONNECTIVITY</th>
                        <th>LAST SYNC</th>
                        <th class="pe-5 text-end">MANAGEMENT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sql = "SELECT users.id, users.username, portal_activity.last_seen, r.fullname, r.profile_pic,
                            TIMESTAMPDIFF(SECOND, portal_activity.last_seen, NOW()) as seconds_ago
                            FROM users 
                            LEFT JOIN portal_activity ON users.id = portal_activity.user_id 
                            LEFT JOIN residents r ON users.resident_id = r.id
                            WHERE users.role = 'Resident'
                            ORDER BY (portal_activity.last_seen IS NULL), portal_activity.last_seen DESC";
                    
                    $res = mysqli_query($conn, $sql);
                    $delay = 0.1;

                    while($row = mysqli_fetch_assoc($res)): 
                        $is_online = ($row['seconds_ago'] !== null && $row['seconds_ago'] < 300);
                        $name = !empty($row['fullname']) ? $row['fullname'] : $row['username'];
                        
                        // Image Pathing
                        $default = "https://ui-avatars.com/api/?name=".urlencode($name)."&background=3b82f6&color=fff&bold=true";
                        $img_path = $default;
                        if(!empty($row['profile_pic'])){
                            $paths = ["../portal/".$row['profile_pic'], "portal/".$row['profile_pic'], $row['profile_pic']];
                            foreach($paths as $p) { if(file_exists($p)) { $img_path = $p; break; } }
                        }
                    ?>
                    <tr class="user-row" style="animation-delay: <?= $delay ?>s">
                        <td class="ps-5 py-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-frame me-3">
                                    <img src="<?= $img_path ?>" onerror="this.src='<?= $default ?>'">
                                </div>
                                <div>
                                    <div class="fw-800 text-dark mb-0"><?= htmlspecialchars($name) ?></div>
                                    <div class="text-muted small">ID: #RES-<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if($is_online): ?>
                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-3 fw-800">
                                    <i class="fas fa-link me-1"></i> CONNECTED
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted px-3 py-2 rounded-3 fw-700">
                                    DISCONNECTED
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-600 small text-dark">
                            <?= (!$row['last_seen']) ? "No Data" : ($is_online ? "Active Now" : date("M d, Y | h:i A", strtotime($row['last_seen']))) ?>
                        </td>
                        <td class="pe-5 text-end">
                            <div class="d-flex justify-content-end">
                                <a href="account.php?delete_user_id=<?= $row['id'] ?>" 
                                   class="action-btn-delete" 
                                   onclick="return confirm('Confirm permanent deletion of resident account: <?= $name ?>?')">
                                    <i class="fas fa-user-minus"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php $delay += 0.05; endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>