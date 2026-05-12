<?php
include_once 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(!isset($_SESSION['admin_logged_in'])){ header("Location: admin_login.php"); exit(); }

$view = $_GET['view'] ?? 'All';

// --- SAVE & UPDATE LOGIC ---
if(isset($_POST['btn_save'])){
    $id       = mysqli_real_escape_string($conn, $_POST['official_id']); // For Editing
    $fname    = mysqli_real_escape_string($conn, $_POST['full_name']);
    $gender   = mysqli_real_escape_string($conn, $_POST['gender']);
    $contact  = mysqli_real_escape_string($conn, $_POST['contact']);
    $status   = mysqli_real_escape_string($conn, $_POST['civil_status']);
    $address  = mysqli_real_escape_string($conn, $_POST['address']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $pos      = mysqli_real_escape_string($conn, $_POST['position']);
    $cat      = mysqli_real_escape_string($conn, $_POST['category']);
    $t_start  = $_POST['term_start'];
    $t_end    = $_POST['term_end'];
    
    // Photo Handling
    $photo_query = "";
    if(!empty($_FILES['photo']['name'])){
        $target_dir = "uploads/officials/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $photo_name = time() . "_" . basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo_name);
        $photo_query = ", photo='$photo_name'";
    }

    if(!empty($id)){
        // UPDATE EXISTING
        $sql = "UPDATE officials SET fullname='$fname', gender='$gender', contact='$contact', civil_status='$status', 
                address='$address', email='$email', position='$pos', category='$cat', 
                term_start='$t_start', term_end='$t_end' $photo_query WHERE id='$id'";
    } else {
        // INSERT NEW
        $def_photo = !empty($photo_name) ? $photo_name : "default_official.png";
        $sql = "INSERT INTO officials (fullname, gender, contact, civil_status, address, email, position, category, term_start, term_end, photo) 
                VALUES ('$fname', '$gender', '$contact', '$status', '$address', '$email', '$pos', '$cat', '$t_start', '$t_end', '$def_photo')";
    }

    if($conn->query($sql)){ echo "<script>window.location='officials.php';</script>"; }
}

if(isset($_GET['delete_id'])){
    $id = $_GET['delete_id'];
    $conn->query("DELETE FROM officials WHERE id = '$id'");
    header("Location: officials.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Hub | BMIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        :root { --primary: #4361ee; --bg: #f8fafc; }
        body { background: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar-container { width: 260px; position: fixed; height: 100vh; z-index: 100; }
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }

        .nav-pills .nav-link { background: white; color: #64748b; font-weight: 600; border-radius: 12px; margin-right: 10px; border: 1px solid #e2e8f0; }
        .nav-pills .nav-link.active { background: var(--primary); color: white; box-shadow: 0 10px 15px rgba(67, 97, 238, 0.2); }

        .personnel-card { background: white; border-radius: 24px; transition: 0.3s; position: relative; overflow: hidden; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .personnel-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        .profile-img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 4px solid #f1f5f9; }
        
        .badge-cat { font-size: 9px; font-weight: 800; padding: 5px 12px; border-radius: 50px; text-transform: uppercase; margin-bottom: 10px; display: inline-block; }
        .bg-official { background: #eff6ff; color: #1e40af; }
        .bg-sk { background: #fae8ff; color: #86198f; }
        .bg-bhw { background: #f0fdf4; color: #166534; }
        .bg-tanod { background: #fffbeb; color: #92400e; }
        
        .modal-content { border-radius: 25px; border: none; }
        .form-label { font-weight: 700; font-size: 0.75rem; color: #64748b; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="sidebar-container"><?php include 'sidebar.php'; ?></div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-800 text-dark mb-1">Personnel Hub</h1>
            <p class="text-muted mb-0">Manage your Barangay workforce effectively.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow" onclick="openEnrollModal()">
            <i class="fas fa-plus-circle me-2"></i> Enroll Personnel
        </button>
    </div>

    <div class="nav nav-pills mb-5">
        <a href="officials.php?view=All" class="nav-link <?= $view == 'All' ? 'active' : '' ?>">All</a>
        <a href="officials.php?view=Official" class="nav-link <?= $view == 'Official' ? 'active' : '' ?>">Council</a>
        <a href="officials.php?view=SK" class="nav-link <?= $view == 'SK' ? 'active' : '' ?>">SK</a>
        <a href="officials.php?view=BHW" class="nav-link <?= $view == 'BHW' ? 'active' : '' ?>">BHW</a>
        <a href="officials.php?view=Tanod" class="nav-link <?= $view == 'Tanod' ? 'active' : '' ?>">Tanods</a>
    </div>

    <div class="row g-4">
        <?php 
        $query = "SELECT * FROM officials";
        if($view != 'All') $query .= " WHERE category = '$view'";
        $query .= " ORDER BY fullname ASC";
        
        $res = $conn->query($query);
        while($row = $res->fetch_assoc()):
            $img = !empty($row['photo']) ? "uploads/officials/".$row['photo'] : "https://ui-avatars.com/api/?name=".urlencode($row['fullname'])."&background=random";
            $c_class = "bg-".strtolower($row['category']);
            // Prepare Data for JS Edit
            $json_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="col-md-3">
            <div class="personnel-card p-4 text-center">
                <span class="badge-cat <?= $c_class ?>"><?= $row['category'] ?></span>
                <div class="mb-3"><img src="<?= $img ?>" class="profile-img"></div>
                <h6 class="fw-800 mb-0"><?= $row['fullname'] ?></h6>
                <p class="text-primary small fw-bold mb-3"><?= $row['position'] ?></p>
                
                <div class="d-flex gap-2 justify-content-center border-top pt-3">
                    <button class="btn btn-sm btn-light rounded-pill px-3 fw-bold" onclick='editOfficial(<?= $json_data ?>)'>Edit</button>
                    <a href="officials.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Remove?')"><i class="fas fa-trash"></i></a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="enrollModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content shadow-lg" method="POST" enctype="multipart/form-data" id="officialForm">
            <input type="hidden" name="official_id" id="official_id">
            <div class="modal-header border-0 px-5 pt-5 pb-0">
                <h4 class="fw-800" id="modalTitle">Enroll Personnel</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="f_name" class="form-control mb-3" required>
                        
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" id="f_gender" class="form-select mb-3"><option>Male</option><option>Female</option></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Status</label>
                                <select name="civil_status" id="f_status" class="form-select mb-3"><option>Single</option><option>Married</option><option>Widowed</option></select>
                            </div>
                        </div>

                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact" id="f_contact" class="form-control mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="f_email" class="form-control mb-3">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Classification</label>
                        <select name="category" id="cat_select" class="form-select mb-3 fw-bold text-primary" onchange="updatePositions()">
                            <option value="Official">Barangay Council</option>
                            <option value="SK">SK Council</option>
                            <option value="BHW">Health Worker (BHW)</option>
                            <option value="Tanod">Barangay Tanod</option>
                        </select>

                        <label class="form-label">Position</label>
                        <select name="position" id="pos_select" class="form-select mb-3" required></select>

                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Term Start</label>
                                <input type="date" name="term_start" id="f_start" class="form-control mb-3">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Term End</label>
                                <input type="date" name="term_end" id="f_end" class="form-control mb-3">
                            </div>
                        </div>
                        <label class="form-label">Residential Address</label>
                        <textarea name="address" id="f_address" class="form-control mb-3" rows="1"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Profile Photo (Leave blank to keep current)</label>
                        <input type="file" name="photo" class="form-control">
                    </div>
                </div>
            </div>
            <div class="px-5 pb-5">
                <button type="submit" name="btn_save" class="btn btn-primary w-100 rounded-pill py-3 fw-800 shadow">SAVE CHANGES</button>
            </div>
        </form>
    </div>
</div>

<script>
const roles = {
    'Official': ['Punong Barangay', 'Barangay Secretary', 'Barangay Treasurer', 'Kagawad (Appropriation)', 'Kagawad (Health)', 'Kagawad (Peace & Order)', 'Kagawad (Education)'],
    'SK': ['SK Chairperson', 'SK Secretary', 'SK Treasurer', 'SK Member'],
    'BHW': ['BHW Coordinator', 'BHW Member', 'BNS Worker'],
    'Tanod': ['Chief Tanod', 'Executive Officer', 'Tanod Member']
};

function updatePositions(selectedPos = '') {
    const cat = document.getElementById('cat_select').value;
    const posSelect = document.getElementById('pos_select');
    posSelect.innerHTML = '';
    roles[cat].forEach(role => {
        let opt = document.createElement('option');
        opt.value = role; opt.innerHTML = role;
        if(role === selectedPos) opt.selected = true;
        posSelect.appendChild(opt);
    });
}

function openEnrollModal() {
    document.getElementById('officialForm').reset();
    document.getElementById('official_id').value = '';
    document.getElementById('modalTitle').innerText = 'Enroll Personnel';
    updatePositions();
    new bootstrap.Modal(document.getElementById('enrollModal')).show();
}

function editOfficial(data) {
    document.getElementById('official_id').value = data.id;
    document.getElementById('f_name').value = data.fullname;
    document.getElementById('f_gender').value = data.gender;
    document.getElementById('f_status').value = data.civil_status;
    document.getElementById('f_contact').value = data.contact;
    document.getElementById('f_email').value = data.email;
    document.getElementById('f_address').value = data.address;
    document.getElementById('f_start').value = data.term_start;
    document.getElementById('f_end').value = data.term_end;
    document.getElementById('cat_select').value = data.category;
    
    updatePositions(data.position);
    document.getElementById('modalTitle').innerText = 'Edit Personnel Profile';
    new bootstrap.Modal(document.getElementById('enrollModal')).show();
}

window.onload = () => updatePositions();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>