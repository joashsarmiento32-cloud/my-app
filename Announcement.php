<?php 
include 'db_connect.php'; 

// 1. Handle Posting New Announcement
if(isset($_POST['btn_post'])){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $sql = "INSERT INTO announcements (title, content, date_posted) VALUES ('$title', '$content', NOW())";
    if($conn->query($sql)){
        echo "<script>alert('Announcement Posted!'); window.location.href='Announcement.php';</script>";
    }
}

// 2. Handle Deleting Old Announcements
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM announcements WHERE id = $id");
    header("Location: Announcement.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap');
        
        :root { --admin-red: #ef4444; --navy: #0f172a; --slate: #64748b; }
        body { background: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
        
        .sidebar-container { width: 280px; position: fixed; height: 100vh; background: #fff; z-index: 100; }
        .main-content { margin-left: 280px; padding: 40px; min-height: 100vh; }
        
        /* Form Styling */
        .card-custom { border: none; border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); overflow: hidden; }
        .header-strip { background: var(--navy); color: white; padding: 20px; }
        .input-app { background: #f1f5f9; border: 2px solid transparent; border-radius: 12px; padding: 12px; transition: all 0.2s; }
        .input-app:focus { background: #fff; border-color: var(--admin-red); box-shadow: none; }
        
        /* News Item Styling */
        .news-item { 
            background: white; 
            border-radius: 16px; 
            padding: 20px; 
            margin-bottom: 16px; 
            border: 1px solid #e2e8f0; 
            transition: transform 0.2s;
            display: flex;
            gap: 15px;
        }
        .news-item:hover { transform: translateY(-2px); border-color: #cbd5e1; }
        .news-icon { 
            width: 48px; height: 48px; background: #fef2f2; color: var(--admin-red); 
            border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        
        .delete-btn {
            width: 35px; height: 35px; border-radius: 10px; display: flex; 
            align-items: center; justify-content: center; color: #94a3b8; transition: all 0.2s;
        }
        .delete-btn:hover { background: #fee2e2; color: #ef4444; }

        @media (max-width: 992px) {
            .sidebar-container { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="sidebar-container"><?php include 'sidebar.php'; ?></div>

<main class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0">Bulletin Board</h3>
                <p class="text-muted small">Manage public notices for your residents</p>
            </div>
        </div>

        <div class="row">
            <!-- POST FORM -->
            <div class="col-lg-5 mb-4">
                <div class="card card-custom">
                    <div class="header-strip d-flex align-items-center">
                        <div class="bg-danger rounded-3 p-2 me-3"><i class="fas fa-bullhorn text-white"></i></div>
                        <h5 class="mb-0 fw-bold">Compose Notice</h5>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted mb-2 text-uppercase">Announcement Title</label>
                                <input type="text" name="title" class="form-control input-app" placeholder="e.g. Mandatory Community Meeting" required>
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold text-muted mb-2 text-uppercase">Full Details</label>
                                <textarea name="content" class="form-control input-app" rows="6" placeholder="Provide complete information here..." required></textarea>
                            </div>
                            <button type="submit" name="btn_post" class="btn btn-danger w-100 fw-bold py-3 rounded-4">
                                <i class="fas fa-paper-plane me-2"></i>Publish to Portal
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- NEWS FEED -->
            <div class="col-lg-7">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0">Live Feed</h5>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">Latest 8 Posts</span>
                </div>
                
                <div id="news-list">
                    <?php 
                    $res = $conn->query("SELECT * FROM announcements ORDER BY id DESC LIMIT 8");
                    if($res->num_rows > 0):
                        while($row = $res->fetch_assoc()): ?>
                        <div class="news-item shadow-sm">
                            <div class="news-icon"><i class="fas fa-bell"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['title']) ?></h6>
                                    <a href="?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Remove this notice?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                                <div class="small text-muted mb-2">
                                    <i class="far fa-calendar-alt me-1"></i><?= date('M d, Y', strtotime($row['date_posted'])) ?> 
                                    <span class="mx-2">•</span> 
                                    <i class="far fa-clock me-1"></i><?= date('g:i A', strtotime($row['date_posted'])) ?>
                                </div>
                                <p class="small text-secondary mb-0"><?= nl2br(htmlspecialchars($row['content'])) ?></p>
                            </div>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="text-center p-5 bg-white rounded-4 border border-dashed">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 80px; opacity: 0.3;" class="mb-3">
                            <p class="text-muted fw-bold">The bulletin board is empty.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>