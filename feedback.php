<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. DELETE LOGIC
if(isset($_GET['delete_id'])){
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM resident_feedback WHERE id = $id");
    header("Location: feedback.php");
    exit();
}

// 2. REPLY LOGIC
if(isset($_POST['btn_reply'])){
    $feedback_id = intval($_POST['feedback_id']);
    $reply = mysqli_real_escape_string($conn, $_POST['admin_reply']);
    $conn->query("UPDATE resident_feedback SET admin_reply = '$reply', status = 'Replied' WHERE id = $feedback_id");
    header("Location: feedback.php");
    exit();
}

$current_page = 'feedback.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management | Barangay Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #3b82f6; 
            --dark: #0f172a; 
            --sidebar: #ffffff;
            --bg: #f8fafc;
        }
        
        body { 
            background-color: var(--bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--dark); 
            overflow-x: hidden;
        }
        
        .main-content { 
            margin-left: 280px; 
            padding: 40px 50px; 
            transition: all 0.3s ease; 
        }

        .header-section {
            margin-bottom: 40px;
        }

        .fw-800 { font-weight: 800; }

        .glass-card { 
            background: #ffffff;
            border-radius: 24px;
            padding: 30px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
            margin-bottom: 25px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.06);
        }

        .profile-container {
            position: relative;
            width: 56px;
            height: 56px;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .status-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .badge-pending { background: #fff7ed; color: #c2410c; }
        .badge-replied { background: #f0fdf4; color: #15803d; }

        .search-container {
            position: relative;
            max-width: 400px;
        }

        .search-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            padding: 12px 20px 12px 45px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-box:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .message-content {
            background: #f8fafc;
            border-radius: 18px;
            padding: 20px;
            margin-top: 15px;
            border-left: 5px solid var(--primary);
        }

        .admin-reply-section {
            background: #eff6ff;
            border-radius: 18px;
            padding: 20px;
            margin-top: 20px;
            position: relative;
        }

        .reply-label {
            font-size: 10px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }

        .btn-reply {
            background: var(--dark);
            color: white;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
        }

        .btn-reply:hover {
            background: #1e293b;
            color: white;
            transform: scale(1.02);
        }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
    <div class="header-section d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h2 class="fw-800 m-0">Resident Feedback</h2>
            <p class="text-muted">Review and respond to submissions from your community</p>
        </div>
        <div class="search-container w-100">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="feedbackSearch" class="form-control search-box" placeholder="Search by resident name or subject...">
        </div>
    </div>

    <div id="feedbackList">
        <?php 
        // Optimized Join: connects feedback to residents through the user table
        $query = "SELECT f.*, r.fullname, r.profile_pic 
                  FROM resident_feedback f 
                  LEFT JOIN users u ON f.resident_id = u.id 
                  LEFT JOIN residents r ON u.resident_id = r.id 
                  ORDER BY f.date_sent DESC";
                  
        $res = $conn->query($query);

        if($res && $res->num_rows > 0):
            while($row = $res->fetch_assoc()): 
                $name = !empty($row['fullname']) ? $row['fullname'] : "Barangay Resident";
                $raw_pic = $row['profile_pic'];
                
                // --- UNIVERSAL PATH DIAGNOSTIC ---
                $final_avatar = "";
                $default_avatar = "https://ui-avatars.com/api/?name=".urlencode($name)."&background=3b82f6&color=fff&size=128";

                if(!empty($raw_pic)) {
                    // Check standard relative paths
                    $paths = [
                        "../portal/" . $raw_pic,
                        "portal/" . $raw_pic,
                        $raw_pic
                    ];

                    foreach($paths as $p) {
                        if(file_exists($p)) {
                            $final_avatar = $p;
                            break;
                        }
                    }
                }
                
                // If no file found, use the Initial Avatar
                $avatar_src = !empty($final_avatar) ? $final_avatar : $default_avatar;
        ?>
        <div class="glass-card feedback-item shadow-sm">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <div class="profile-container me-3">
                        <img src="<?= $avatar_src ?>" class="profile-img" 
                             onerror="this.src='<?= $default_avatar ?>'">
                    </div>
                    <div>
                        <h6 class="fw-bold m-0 sender-name"><?= htmlspecialchars($name) ?></h6>
                        <span class="text-muted" style="font-size: 12px;">
                            <i class="far fa-calendar-alt me-1"></i><?= date('M d, Y • h:i A', strtotime($row['date_sent'])) ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="status-badge <?= $row['status'] == 'Pending' ? 'badge-pending' : 'badge-replied' ?>">
                        <?= $row['status'] ?>
                    </span>
                    <a href="feedback.php?delete_id=<?= $row['id'] ?>" class="btn btn-link text-danger p-2" onclick="return confirm('Remove this feedback entry?')">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
            </div>

            <div class="message-content">
                <div class="fw-bold text-primary small text-uppercase mb-1" style="letter-spacing: 0.5px;">Subject</div>
                <h6 class="fw-bold mb-2 subject-text"><?= htmlspecialchars($row['subject']) ?></h6>
                <p class="text-dark m-0" style="font-size: 14.5px; line-height: 1.6; opacity: 0.9;">
                    <?= nl2br(htmlspecialchars($row['message'])) ?>
                </p>
            </div>

            <?php if(!empty($row['admin_reply'])): ?>
                <div class="admin-reply-section shadow-sm border">
                    <span class="reply-label"><i class="fas fa-check-circle me-1"></i> Your Response</span>
                    <p class="m-0 text-dark" style="font-size: 14px;"><?= nl2br(htmlspecialchars($row['admin_reply'])) ?></p>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-end mt-4">
                <button class="btn btn-reply shadow-sm" data-bs-toggle="collapse" data-bs-target="#replyArea<?= $row['id'] ?>">
                    <i class="fas fa-paper-plane me-2"></i><?= empty($row['admin_reply']) ? 'Reply Now' : 'Update Response' ?>
                </button>
            </div>

            <div class="collapse" id="replyArea<?= $row['id'] ?>">
                <div class="mt-3 p-4 bg-white border rounded-4 shadow-sm">
                    <form method="POST">
                        <input type="hidden" name="feedback_id" value="<?= $row['id'] ?>">
                        <label class="form-label fw-bold small text-muted">Write your response to <?= explode(' ', $name)[0] ?>:</label>
                        <textarea name="admin_reply" class="form-control border-0 bg-light p-3 mb-3" rows="4" style="border-radius: 12px;" placeholder="Type here..." required><?= $row['admin_reply'] ?></textarea>
                        <div class="text-end">
                            <button type="submit" name="btn_reply" class="btn btn-primary px-5 rounded-3 fw-bold">Send Reply</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; else: ?>
            <div class="glass-card text-center py-5">
                <div class="mb-3 opacity-20">
                    <i class="fas fa-comments fa-4x"></i>
                </div>
                <h5 class="fw-bold text-muted">Inbox is empty</h5>
                <p class="text-muted small">New resident feedback will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Advanced Live Filtering
    document.getElementById('feedbackSearch').addEventListener('keyup', function() {
        const term = this.value.toLowerCase();
        const items = document.querySelectorAll('.feedback-item');
        
        items.forEach(item => {
            const name = item.querySelector('.sender-name').textContent.toLowerCase();
            const subject = item.querySelector('.subject-text').textContent.toLowerCase();
            
            if(name.includes(term) || subject.includes(term)) {
                item.style.display = 'block';
                item.style.animation = 'fadeIn 0.3s ease';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>