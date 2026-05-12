<?php 
include 'db_connect.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['admin_logged_in'])){ header("Location: admin_login.php"); exit(); }

// --- YOUR ORIGINAL LOGIC (UNTOUCHED) ---
if(isset($_GET['delete_id'])){
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM residents WHERE id = $id");
    header("Location: residents.php?msg=deleted"); exit();
}

if(isset($_POST['save_profile'])){
    $fname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $mname = mysqli_real_escape_string($conn, $_POST['middlename']);
    $lname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $fullname = trim("$fname $mname $lname"); 
    
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $civil_status = $_POST['civil_status'];
    $purok = mysqli_real_escape_string($conn, $_POST['purok']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $religion = mysqli_real_escape_string($conn, $_POST['religion']);
    $nationality = mysqli_real_escape_string($conn, $_POST['nationality']);
    
    $age = date_diff(date_create($birthdate), date_create('today'))->y;
    $category = ($age >= 60) ? 'Senior' : (($age >= 18) ? 'Adult' : 'Minor');

    if(!empty($_POST['resident_id'])){
        $id = intval($_POST['resident_id']);
        $sql = "UPDATE residents SET firstname='$fname', middlename='$mname', lastname='$lname', fullname='$fullname', birthdate='$birthdate', age='$age', gender='$gender', purok='$purok', contact='$contact', category='$category', civil_status='$civil_status', religion='$religion', nationality='$nationality' WHERE id=$id";
    } else {
        $username = strtolower(str_replace(' ', '', $lname)) . rand(100,999);
        $sql = "INSERT INTO residents (firstname, middlename, lastname, fullname, username, password, birthdate, age, gender, purok, contact, category, civil_status, religion, nationality, status) 
                VALUES ('$fname', '$mname', '$lname', '$fullname', '$username', '123', '$birthdate', '$age', '$gender', '$purok', '$contact', '$category', '$civil_status', '$religion', '$nationality', 'Active')";
    }
    $conn->query($sql);
    header("Location: residents.php?msg=success"); exit();
}

if(isset($_POST['confirm_death'])){
    $id = intval($_POST['resident_id']);
    $dod = $_POST['date_of_death'];
    $cert_name = "";
    if(!empty($_FILES['death_cert']['name'])){
        $cert_name = time() . '_' . $_FILES['death_cert']['name'];
        if(!is_dir('uploads/certs')) { mkdir('uploads/certs', 0777, true); }
        move_uploaded_file($_FILES['death_cert']['tmp_name'], "uploads/certs/" . $cert_name);
    }
    $conn->query("UPDATE residents SET status = 'Deceased', date_deceased = '$dod', death_certificate_file = '$cert_name', username = NULL, password = NULL WHERE id = $id");
    header("Location: residents.php?msg=archived"); exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resident Masterlist | BMIS Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary: #6366f1; --primary-hover: #4f46e5; --bg: #f8fafc; --text-dark: #0f172a; --text-muted: #64748b; }
        body { background-color: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .sidebar-container { width: 280px; position: fixed; height: 100vh; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 40px; position: relative; z-index: 1; }
        .nav-pills .nav-link { color: var(--text-muted); font-weight: 600; padding: 10px 20px; border-radius: 12px; transition: 0.3s; border: 1px solid transparent; }
        .nav-pills .nav-link.active { background: #fff !important; color: var(--primary) !important; border-color: #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .registry-card { background: #fff; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); overflow: hidden; }
        .table thead th { background: #fcfcfd; padding: 20px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); border-bottom: 1px solid #f1f5f9; }
        .table td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        /* NEW PHOTO STYLING */
        .avatar-container { width: 50px; height: 50px; border-radius: 14px; overflow: hidden; border: 2px solid #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .avatar-img { width: 100%; height: 100%; object-fit: cover; }
        
        .bg-avatar-active { background: #eef2ff; color: var(--primary); }
        .bg-avatar-deceased { background: #f1f5f9; color: #94a3b8; }
        .btn-action { width: 38px; height: 38px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; background: #fff; transition: 0.2s; text-decoration: none; cursor: pointer; }
        .btn-action:hover { background: var(--primary); color: #fff !important; transform: translateY(-2px); }
        .badge-cat { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; }
        .bg-senior { background: #fff1f2; color: #e11d48; }
        .bg-adult { background: #f0fdf4; color: #16a34a; }
        .bg-minor { background: #fefce8; color: #ca8a04; }       
        .modal-content { border-radius: 30px; border: none; }
        .input-custom { border-radius: 14px; padding: 12px 18px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.95rem; }
        .input-custom:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
    </style>
</head>
<body>

<div class="sidebar-container"><?php include 'sidebar.php'; ?></div>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold text-dark m-0">Resident Masterlist</h2>
            <p class="text-muted m-0">Comprehensive population tracking & profiling</p>
        </div>
        <button class="btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm" onclick="openAddModal()">
            <i class="fas fa-plus me-2"></i> New Registration
        </button>
    </div>

    <ul class="nav nav-pills mb-4 gap-2" id="registryTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-active">
                <i class="fas fa-users me-2"></i> Active Residents
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-deceased">
                <i class="fas fa-dove me-2"></i> Deceased Records
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-active">
            <div class="registry-card">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Resident Profile</th>
                            <th>Contact / Info</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM residents WHERE status = 'Active' ORDER BY fullname ASC");
                        while($row = $res->fetch_assoc()): 
                            $cat_class = ($row['category'] == 'Minor') ? 'bg-minor' : (($row['category'] == 'Senior') ? 'bg-senior' : 'bg-adult');
                            
                            // PROFILE PIC RESOLUTION LOGIC
                            $fname_encoded = urlencode($row['fullname']);
                            $default_ui = "https://ui-avatars.com/api/?name=$fname_encoded&background=6366f1&color=fff&bold=true";
                            $final_pic = $default_ui;

                            if(!empty($row['profile_pic'])){
                                $check_paths = ["../portal/".$row['profile_pic'], "portal/".$row['profile_pic'], $row['profile_pic']];
                                foreach($check_paths as $p) { if(file_exists($p)) { $final_pic = $p; break; } }
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-container">
                                        <img src="<?= $final_pic ?>" class="avatar-img" onerror="this.src='<?= $default_ui ?>'">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['fullname']) ?></div>
                                        <div class="small text-muted"><?= $row['gender'] ?> • <?= $row['age'] ?> yrs old</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= $row['contact'] ?: 'No Contact' ?></div>
                                <div class="small text-muted"><?= $row['civil_status'] ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border px-2 py-1">Purok <?= $row['purok'] ?></span></td>
                            <td><span class="badge-cat <?= $cat_class ?>"><?= $row['category'] ?></span></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn-action text-primary" onclick='openEditModal(<?= json_encode($row) ?>)' title="Edit Profile"><i class="fas fa-pen-to-square"></i></button>
                                    <button class="btn-action text-secondary" onclick="openDeceasedModal(<?= $row['id'] ?>, '<?= addslashes($row['fullname']) ?>')" title="Record Death"><i class="fas fa-cross"></i></button>
                                    <a href="residents.php?delete_id=<?= $row['id'] ?>" class="btn-action text-danger" onclick="return confirm('Delete permanently?')" title="Delete"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-deceased">
            <div class="registry-card">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Deceased Profile</th>
                            <th>Archived Date</th>
                            <th>Reason/Cert</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dec = $conn->query("SELECT * FROM residents WHERE status = 'Deceased' ORDER BY date_deceased DESC");
                        if($dec->num_rows == 0) echo "<tr><td colspan='4' class='text-center py-5 text-muted'>No deceased records found.</td></tr>";
                        while($row = $dec->fetch_assoc()): 
                            // PROFILE PIC FOR DECEASED (Fallback to dove if no pic)
                            $dec_pic = "https://ui-avatars.com/api/?name=".urlencode($row['fullname'])."&background=f1f5f9&color=94a3b8";
                            if(!empty($row['profile_pic'])){
                                $check_paths = ["../portal/".$row['profile_pic'], "portal/".$row['profile_pic'], $row['profile_pic']];
                                foreach($check_paths as $p) { if(file_exists($p)) { $dec_pic = $p; break; } }
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-container" style="filter: grayscale(1);">
                                        <img src="<?= $dec_pic ?>" class="avatar-img">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark text-decoration-line-through"><?= htmlspecialchars($row['fullname']) ?></div>
                                        <div class="small text-muted">Passed away at age <?= $row['age'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-danger"><?= date("M d, Y", strtotime($row['date_deceased'])) ?></div>
                                <div class="small text-muted">Date of Death</div>
                            </td>
                            <td>
                                <?php if($row['death_certificate_file']): ?>
                                    <a href="uploads/certs/<?= $row['death_certificate_file'] ?>" target="_blank" class="btn btn-sm btn-light border text-primary">
                                        <i class="fas fa-file-pdf me-1"></i> View Certificate
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small italic">No File Uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="residents.php?delete_id=<?= $row['id'] ?>" class="btn-action text-danger" onclick="return confirm('Delete record permanently?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="resModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 px-4 pt-4">
                <h4 class="fw-bold m-0" id="mTitle">Register Resident</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="resident_id" id="mId">
                <div class="row g-3">
    <div class="col-md-4">
        <label class="small fw-bold text-muted mb-1">FIRST NAME</label>
        <input type="text" name="firstname" id="mFname" class="form-control input-custom" placeholder="e.g. Juan" required>
    </div>
    <div class="col-md-4">
        <label class="small fw-bold text-muted mb-1">MIDDLE NAME</label>
        <input type="text" name="middlename" id="mMname" class="form-control input-custom" placeholder="e.g. Santos">
    </div>
    <div class="col-md-4">
        <label class="small fw-bold text-muted mb-1">SURNAME</label>
        <input type="text" name="lastname" id="mLname" class="form-control input-custom" placeholder="e.g. Dela Cruz" required>
    </div>

    <div class="col-md-4">
        <label class="small fw-bold text-muted mb-1">BIRTHDATE</label>
        <input type="date" name="birthdate" id="mBday" class="form-control input-custom" required>
    </div>
                
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">GENDER</label>
                        <select name="gender" id="mGender" class="form-select input-custom">
                            <option>Male</option><option>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">PUROK</label>
                        <input type="text" name="purok" id="mPurok" class="form-control input-custom" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">CIVIL STATUS</label>
                        <select name="civil_status" id="mStatus" class="form-select input-custom">
                            <option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">NATIONALITY</label>
                        <input type="text" name="nationality" id="mNation" class="form-control input-custom" value="Filipino">
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">CONTACT</label>
                        <input type="text" name="contact" id="mContact" class="form-control input-custom">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" name="save_profile" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">Save Profile Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deceasedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-body p-5 text-center">
                <div class="avatar bg-danger-subtle text-danger mx-auto mb-3" style="width: 70px; height: 70px; border-radius: 50%;">
                    <i class="fas fa-cross fa-2x"></i>
                </div>
                <h4 class="fw-bold">Archive Deceased</h4>
                <p class="text-muted small">You are archiving <strong id="dec_name"></strong></p>
                <input type="hidden" name="resident_id" id="dec_id">
                
                <div class="text-start mt-4">
                    <label class="small fw-bold text-muted">DATE OF DEATH</label>
                    <input type="date" name="date_of_death" class="form-control input-custom mb-3" required>
                    <label class="small fw-bold text-muted">UPLOAD DEATH CERTIFICATE (PDF/IMAGE)</label>
                    <input type="file" name="death_cert" class="form-control input-custom mb-4">
                </div>

                <button type="submit" name="confirm_death" class="btn btn-danger w-100 py-3 rounded-pill fw-bold">Archive Permanently</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const resModal = new bootstrap.Modal(document.getElementById('resModal'));
    const decModal = new bootstrap.Modal(document.getElementById('deceasedModal'));

    function openAddModal() {
        document.getElementById('mTitle').innerText = "Register Resident";
        document.getElementById('mId').value = "";
        document.querySelectorAll('#resModal input').forEach(i => i.value = "");
        document.getElementById('mNation').value = "Filipino";
        resModal.show();
    }

    function openEditModal(data) {
    document.getElementById('mTitle').innerText = "Edit Resident Profile";
    document.getElementById('mId').value = data.id;
    
    document.getElementById('mFname').value = data.firstname || "";
    document.getElementById('mMname').value = data.middlename || "";
    document.getElementById('mLname').value = data.lastname || "";
    
    document.getElementById('mBday').value = data.birthdate;
    document.getElementById('mGender').value = data.gender;
    document.getElementById('mPurok').value = data.purok;
    document.getElementById('mContact').value = data.contact || "";
    document.getElementById('mStatus').value = data.civil_status || "Single";
    document.getElementById('mNation').value = data.nationality || "Filipino";
    resModal.show();
}

function openAddModal() {
    document.getElementById('mTitle').innerText = "Register Resident";
    document.getElementById('mId').value = "";
    document.getElementById('mFname').value = "";
    document.getElementById('mMname').value = "";
    document.getElementById('mLname').value = "";
    
    document.querySelectorAll('#resModal input:not([name="nationality"])').forEach(i => i.value = "");
    document.getElementById('mNation').value = "Filipino";
    resModal.show();
}
</script>
</body>
</html>