<?php
// Always start the session at the top of the sidebar to access user data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'db_connect.php'; 

// Helper function to handle active class
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .sidebar {
        width: 260px; 
        height: 100vh; 
        position: fixed; 
        left: 0; 
        top: 0;
        background: #1a222b; 
        color: white; 
        z-index: 1000;
        display: flex; 
        flex-direction: column; 
        box-shadow: 4px 0 15px rgba(0,0,0,0.3);
        /* FIX: Ensure no border-right is fighting with the background */
        border-right: none !important;
    }
    
    .sidebar-header { 
        padding: 20px 15px; 
        text-align: center; 
        border-bottom: 1px solid rgba(255,255,255,0.1); 
        background: rgba(255,255,255,0.02);
        flex-shrink: 0;
    }

    /* LOGO WITH GLOW EFFECT */
    .sidebar-header img { 
        width: 65px; height: 65px; border-radius: 50%; 
        margin-bottom: 10px; 
        border: 2px solid rgba(255,255,255,0.2); 
        box-shadow: 0 0 10px rgba(52, 152, 219, 0.5); 
        animation: logo-pulse 3s infinite ease-in-out; 
    }

    @keyframes logo-pulse {
        0% { box-shadow: 0 0 8px rgba(52, 152, 219, 0.4); border-color: rgba(255,255,255,0.2); }
        50% { 
            box-shadow: 0 0 20px rgba(52, 152, 219, 0.8), 0 0 30px rgba(52, 152, 219, 0.3);
            border-color: rgba(52, 152, 219, 0.5);
            transform: scale(1.02);
        }
        100% { box-shadow: 0 0 8px rgba(52, 152, 219, 0.4); border-color: rgba(255,255,255,0.2); }
    }

    .off-text-1 { font-size: 0.55rem; letter-spacing: 1px; color: #94a3b8; text-transform: uppercase; display: block; }
    .off-text-2 { font-size: 0.75rem; font-weight: 600; color: #f1f5f9; margin: 0; }
    .off-text-3 { font-size: 1rem; font-weight: 800; color: #ffffff; margin-bottom: 0; }

    .user-status { font-size: 0.65rem; color: #22c55e; font-weight: 700; margin-top: 5px; display: block; }
    
    .nav-container {
        flex-grow: 1;
        overflow-y: auto; 
        /* FIX: Strictly hide horizontal overflow to remove that arrow-pointed line */
        overflow-x: hidden !important; 
        padding-bottom: 30px;
    }

    .nav-link { 
        color: #94a3b8 !important; 
        padding: 12px 20px !important; 
        display: flex; 
        align-items: center; 
        text-decoration: none !important; 
        font-size: 0.85rem; 
        transition: 0.3s;
        border-left: 4px solid transparent;
        /* FIX: Prevent any right-side border or outline from appearing */
        outline: none !important;
    }
    .nav-link:hover, .nav-link.active { 
        background: rgba(52, 152, 219, 0.1); 
        color: #3498db !important; 
        border-left: 4px solid #3498db; 
    }
    .nav-link i { margin-right: 12px; width: 20px; text-align: center; }
    
    .section-label { 
        font-size: 0.65rem; 
        font-weight: 700; 
        color: #475569; 
        padding: 25px 20px 10px; 
        text-transform: uppercase; 
        letter-spacing: 1.2px; 
    }

    /* ANNOUNCEMENT GLOW EFFECT */
    .glow-icon {
        color: #f1c40f !important; 
        text-shadow: 0 0 8px rgba(241, 196, 15, 0.5);
        animation: pulse-yellow 2s infinite;
    }

    @keyframes pulse-yellow {
        0% { transform: scale(1); text-shadow: 0 0 5px rgba(241, 196, 15, 0.5); }
        50% { transform: scale(1.1); text-shadow: 0 0 15px rgba(241, 196, 15, 0.9); }
        100% { transform: scale(1); text-shadow: 0 0 5px rgba(241, 196, 15, 0.5); }
    }

    /* Clean Scrollbar Styling */
    .nav-container::-webkit-scrollbar { width: 4px; }
    .nav-container::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
    .nav-container:hover::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); }

    .logout-section {
        flex-shrink: 0;
        border-top: 1px solid rgba(255,255,255,0.1);
        padding: 10px;
        background: rgba(0,0,0,0.1);
    }
</style>

<nav class="sidebar">
    <div class="sidebar-header">
        <img src="logo.jpg" alt="Official Seal">
        <span class="off-text-1">Republic of the Philippines</span>
        <p class="off-text-2">Barangay Hicming</p>
        
        <h4 class="off-text-3">
            <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'OFFICIAL ADMIN'; ?>
        </h4>
        
        <span class="user-status">
            <i class="fas fa-circle me-1" style="font-size: 7px;"></i> 
            ONLINE: <?php echo isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'GUEST'; ?>
        </span>
    </div>
    
    <div class="nav-container">
    <div class="section-label">Main Navigation</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                <i class="fas fa-columns"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="announcement.php" class="nav-link <?= ($current_page == 'announcement.php') ? 'active' : '' ?>">
                <i class="fas fa-bullhorn glow-icon"></i> Announcements
            </a>
        </li>
        <li class="nav-item">
            <a href="account.php" class="nav-link <?= ($current_page == 'account.php') ? 'active' : '' ?>">
                <i class="fas fa-users-cog"></i> User Accounts
            </a>
        </li>
        <li class="nav-item">
            <a href="clearance.php" class="nav-link <?= ($current_page == 'clearance.php') ? 'active' : '' ?>">
                <i class="fas fa-file-signature text-info"></i> Clearance Requests
            </a>
        </li>
    </ul>

    <div class="section-label">Governance</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="officials.php" class="nav-link <?= ($current_page == 'officials.php') ? 'active' : '' ?>">
                <i class="fas fa-user-tie text-warning"></i> Barangay Officials
            </a>
        </li>
        <li class="nav-item">
            <a href="residents.php" class="nav-link <?= ($current_page == 'residents.php') ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i> Resident Profiling
            </a>
        </li>
    </ul>

    <div class="section-label">Management & Reports</div>
    <ul class="nav flex-column">
        <!-- ADDED FEEDBACK MODULE HERE -->
        <li class="nav-item">
            <a href="feedback.php" class="nav-link <?= ($current_page == 'feedback.php') ? 'active' : '' ?>">
                <i class="fas fa-comment-dots" style="color: #a855f7;"></i> Feedback & Inquiries
            </a>
        </li>
        <li class="nav-item">
            <a href="health.php" class="nav-link <?= ($current_page == 'health.php') ? 'active' : '' ?>">
                <i class="fas fa-heartbeat text-danger"></i> Health Reports
            </a>
        </li>
        <li class="nav-item">
            <a href="projects.php" class="nav-link <?= ($current_page == 'projects.php') ? 'active' : '' ?>">
                <i class="fas fa-project-diagram"></i> Special Projects
            </a>
        </li>
        <li class="nav-item">
            <a href="peace.php" class="nav-link <?= ($current_page == 'peace.php') ? 'active' : '' ?>">
                <i class="fas fa-shield-alt"></i> Peace & Order
            </a>
        </li>
        <li class="nav-item">
            <a href="finance.php" class="nav-link <?= ($current_page == 'finance.php') ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar text-success"></i> Finance & Records
            </a>
        </li>
    </ul>

    <div class="section-label">System Configuration</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?= ($current_page == 'settings.php') ? 'active' : '' ?>">
                <i class="fas fa-wallet text-primary"></i> GCash Settings
            </a>
        </li>
    </ul>
</div>

    <div class="logout-section">
        <a href="logout.php" class="nav-link text-danger fw-bold" onclick="return confirm('Logout?')">
            <i class="fas fa-power-off"></i> Logout
        </a>
    </div>
</nav>