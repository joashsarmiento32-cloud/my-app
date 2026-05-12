<?php 
// 1. Database Connection
include 'db_connect.php'; 

// 2. PRE-DEFINE VARIABLES (Prevents "Undefined variable" errors)
$chairman_name = "HON. BARANGAY CHAIRMAN"; // Default fallback
$name = "Resident Name";
$purok = "";
$civil_status = "";
$type = "Document";
$purpose = "ANY LEGAL PURPOSES";
$date = date('F j, Y');

// 3. FETCH THE CHAIRMAN NAME IMMEDIATELY
$chairman_query = $conn->query("SELECT fullname FROM officials WHERE position LIKE '%Chairman%' OR position LIKE '%Punong Barangay%' LIMIT 1");
if ($chairman_query && $chairman_query->num_rows > 0) {
    $chairman = $chairman_query->fetch_assoc();
    $chairman_name = strtoupper($chairman['fullname']);
}

// 4. FETCH REQUEST DATA
if(isset($_GET['id'])){
    $request_id = intval($_GET['id']);
    
    // JOIN document_requests with residents to get all info
    $query = "SELECT r.*, dr.document_type, dr.purpose, dr.status 
              FROM residents r 
              JOIN document_requests dr ON r.id = dr.resident_id 
              WHERE dr.id = $request_id";
              
    $res = $conn->query($query);
    
    if($res && $res->num_rows > 0){
        $row = $res->fetch_assoc();
        
        // Security Gate: Only print if Approved
        if($row['status'] !== 'Approved') {
            die("<div style='text-align:center; padding-top:50px; font-family:sans-serif;'>
                    <h2>Wait!</h2>
                    <p>This document is still pending approval. Please wait for the admin to approve your request.</p>
                    <button onclick='window.close()'>Close Window</button>
                 </div>");
        }

        $name = $row['fullname'];
        $purok = $row['purok'];
        $civil_status = $row['civil_status'];
        $type = $row['document_type']; 
        $purpose = !empty($row['purpose']) ? strtoupper($row['purpose']) : 'ANY LEGAL PURPOSES';
    } else {
        die("Record not found in database.");
    }
} else {
    die("No Request ID provided.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print - <?= htmlspecialchars($name) ?></title>
    <style>
        @media print { 
            .no-print { display: none; } 
            body { background: none; padding: 0; }
            .certificate-container { border: 4px solid #000; box-shadow: none; margin: 0; }
        }
        body { font-family: 'Times New Roman', serif; background-color: #f0f0f0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .certificate-container { background-color: white; width: 8.5in; height: 11in; padding: 0.5in; box-shadow: 0 0 10px rgba(0,0,0,0.2); display: flex; position: relative; box-sizing: border-box; border: 1px solid #ddd; overflow: hidden;}
        
        .sidebar { width: 30%; border-right: 2px solid #2c3e50; padding-right: 15px; font-size: 11px; text-align: center; z-index: 1; }
        .sidebar h4 { margin-bottom: 5px; color: #c0392b; font-size: 12px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        
        .official-name { font-weight: bold; margin-top: 10px; text-transform: uppercase; line-height: 1.1; }
        .official-pos { font-style: italic; font-size: 10px; margin-bottom: 8px; color: #555; }
        
        .main-body { width: 70%; padding-left: 25px; z-index: 1; }
        .header-logos { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .header-logos img { width: 80px; height: 80px; object-fit: contain; }
        
        .header-text { text-align: center; flex-grow: 1; }
        .header-text h3 { margin: 0; font-size: 14px; font-weight: normal; }
        .header-text h2 { margin: 5px 0; color: #2c3e50; font-size: 16px; font-weight: bold; }
        
        .title-box { text-align: center; margin: 40px 0; border-top: 2px solid #2c3e50; border-bottom: 2px solid #2c3e50; padding: 15px 0; }
        .title-box h1 { margin: 0; font-size: 28px; letter-spacing: 3px; font-weight: bold; }
        
        .content { text-align: justify; line-height: 1.8; font-size: 16px; margin-top: 30px; }
        .thumbmark-box { width: 110px; height: 110px; border: 1px solid #000; float: left; margin-top: 60px; font-size: 10px; display: flex; align-items: center; justify-content: center; text-align: center; }
        
        .footer-sig { margin-top: 110px; text-align: center; float: right; width: 280px; }
        .sig-line { border-bottom: 2px solid #000; font-weight: bold; padding-bottom: 5px; text-transform: uppercase; font-size: 18px; margin-bottom: 5px;}
        
        .watermark { position: absolute; top: 50%; left: 55%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 90px; color: rgba(0,0,0,0.03); z-index: 0; pointer-events: none; }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding:15px 40px; background:#27ae60; color:white; border:none; cursor:pointer; font-weight:bold; border-radius:30px; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            🖨️ PRINT OFFICIAL DOCUMENT
        </button>
        <button onclick="window.close()" style="padding:15px 20px; background:#7f8c8d; color:white; border:none; cursor:pointer; border-radius:30px; margin-left:10px;">
            Close
        </button>
    </div>

    <div class="certificate-container">
        <div class="watermark">HICMING</div>

        <div class="sidebar">
            <img src="logo.jpg" style="width: 100px; height: 100px; margin-bottom: 15px;" alt="Barangay Logo">
            <h4>BARANGAY OFFICIALS</h4>
            <?php
            $list = $conn->query("SELECT * FROM officials ORDER BY id ASC");
            if ($list && $list->num_rows > 0):
                while($off = $list->fetch_assoc()): ?>
                    <div class="official-name"><?= htmlspecialchars($off['fullname']) ?></div>
                    <div class="official-pos"><?= htmlspecialchars($off['position']) ?></div>
            <?php endwhile; 
            else: ?>
                <div class="official-pos">No officials listed</div>
            <?php endif; ?>
        </div>

        <div class="main-body">
            <div class="header-logos">
                <img src="virac1.jfif" alt="Municipality Logo">
                <div class="header-text">
                    <h3>Republic of the Philippines</h3>
                    <h3>Province of Catanduanes</h3>
                    <h3>Municipality of Virac</h3>
                    <h2>BARANGAY HICMING</h2>
                </div>
                <div style="width: 80px;"></div> 
            </div>

            <div class="title-box">
                <h1><?= strtoupper(htmlspecialchars($type)) ?></h1>
            </div>

            <div class="content">
                <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
                
                <p style="text-indent: 50px;">
                    This is to certify that <strong><?= strtoupper(htmlspecialchars($name)) ?></strong>, 
                    of legal age, <strong><?= htmlspecialchars($civil_status) ?></strong>, Filipino Citizen, is a bonafide resident of <strong><?= htmlspecialchars($purok) ?></strong>, Barangay Hicming, Virac, Catanduanes.
                </p>

                <p style="text-indent: 50px;">
                    The above-named person is known to me to be of good moral character and a law-abiding citizen of this community with no derogatory record on file in this office as of this date.
                </p>

                <p style="text-indent: 50px;">
                    This certification is issued upon the request of the interested party for 
                    <strong><?= ($type == 'Indigency') ? 'FINANCIAL ASSISTANCE / SCHOLARSHIP' : htmlspecialchars($purpose) ?></strong>.
                </p>

                <p>Issued this <strong><?= $date ?></strong> at the Office of the Punong Barangay, Hicming, Virac, Catanduanes.</p>
            </div>

            <div class="thumbmark-box">RIGHT<br>THUMBMARK</div>

            <div class="footer-sig">
                <div class="sig-line"><?= $chairman_name ?></div>
                <div>Punong Barangay</div>
            </div>
            
            <div style="position: absolute; bottom: 30px; left: 0; width: 100%; text-align: center;">
                <small style="color: #bbb; font-style: italic;">Note: Official only with Barangay Dry Seal</small>
            </div>
        </div>
    </div>
</body>
</html>