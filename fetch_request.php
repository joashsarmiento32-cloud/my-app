<?php
// Ensure this file is named EXACTLY fetch_requests.php
include 'db_connect.php';

// Check if connection exists
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "SELECT dr.*, r.fullname FROM document_requests dr 
        JOIN residents r ON dr.resident_id = r.id 
        ORDER BY dr.id DESC";
$result = $conn->query($sql);

if($result && $result->num_rows > 0):
    while($row = $result->fetch_assoc()):
        $status = $row['status'];
        $badge = ($status == 'Pending') ? 'bg-warning text-dark' : 'bg-success';
?>
<tr>
    <td class="ps-4">
        <div class="fw-bold"><?= htmlspecialchars($row['fullname']) ?></div>
    </td>
    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['document_type']) ?></span></td>
    <td><small><?= htmlspecialchars($row['purpose']) ?></small></td>
    <td><span class="badge <?= $badge ?> rounded-pill px-3"><?= $status ?></span></td>
    <td class="text-center">
        <?php if($status == 'Pending'): ?>
            <div class="btn-group">
                <a href="clearance.php?action=approve&req_id=<?= $row['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                <a href="clearance.php?action=decline&req_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger ms-1">Decline</a>
            </div>
        <?php else: ?>
            <span class="text-muted small">Processed</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; else: ?>
<tr>
    <td colspan="5" class="text-center py-5 text-muted">No requests found.</td>
</tr>
<?php endif; ?>