<div class="modal fade" id="revModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-primary py-3 border-0">
                <h5 class="modal-title fw-800 text-white"><i class="fas fa-cog me-2"></i>Finance Settings (<?= $y ?>)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="finance.php" method="POST" class="mb-4">
                    <input type="hidden" name="year" value="<?= $y ?>">
                    <h6 class="stat-label text-primary mb-3">Annual IRA Configuration</h6>
                    <div class="mb-3">
                        <label class="small fw-bold">Internal Revenue Allotment (IRA)</label>
                        <input type="number" name="annual_ira" class="form-control" value="<?= $annual_ira ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Monthly Budget Target</label>
                        <input type="number" name="monthly_target" class="form-control" value="<?= $monthly_budget ?>" required>
                    </div>
                    <button type="submit" name="save_ira_settings" class="btn btn-primary w-100 fw-bold">Update IRA Settings</button>
                </form>
                <hr>
                <form action="finance.php" method="POST">
                    <input type="hidden" name="month" value="<?= $m ?>">
                    <input type="hidden" name="year" value="<?= $y ?>">
                    <h6 class="stat-label text-success mb-3">Monthly Fund Allotment</h6>
                    <?php foreach(['General Fund', '20% Development Fund', 'SK Fund (10%)', 'LDRRMF (Calamity)', 'BDRRM Fund'] as $f): 
                        $cur = $conn->query("SELECT amount FROM budget_allotments WHERE category='$f' AND month='$m' AND year='$y'")->fetch_assoc()['amount'] ?? 0;
                    ?>
                    <div class="mb-2">
                        <label class="small fw-bold"><?= $f ?></label>
                        <input type="number" name="amounts[<?= $f ?>]" class="form-control form-control-sm" value="<?= $cur ?>">
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" name="save_allotments" class="btn btn-success w-100 fw-bold mt-2">Save Allotments</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="payrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <form action="finance.php" method="POST">
                <div class="modal-header bg-warning py-3 border-0">
                    <h5 class="modal-title fw-800 text-dark"><i class="fas fa-coins me-2"></i>Personnel Payroll Disbursement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="stat-label">Date</label><input type="date" name="pay_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-8"><label class="stat-label">Remarks</label><input type="text" name="pay_remarks" class="form-control" placeholder="e.g. Month of Jan Honoraria" required></div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4 border-end">
                            <h6 class="fw-800 text-primary small mb-3">GENERAL FUND OFFICIALS</h6>
                            <div class="mb-2"><label class="small fw-bold">Punong Brgy</label><input type="number" step="0.01" name="gen_staff[Captain]" class="form-control form-control-sm"></div>
                            <div class="mb-2"><label class="small fw-bold">Kagawads (Total)</label><input type="number" step="0.01" name="gen_staff[Kagawads]" class="form-control form-control-sm"></div>
                            <div class="mb-2"><label class="small fw-bold">Secretary/Treasurer</label><input type="number" step="0.01" name="gen_staff[Sec-Treas]" class="form-control form-control-sm"></div>
                        </div>
                        <div class="col-md-4 border-end">
                            <h6 class="fw-800 text-warning small mb-3">SK COUNCIL (SK FUND)</h6>
                            <div class="mb-2"><label class="small fw-bold">SK Chairperson</label><input type="number" step="0.01" name="sk_staff[SK-Chair]" class="form-control form-control-sm"></div>
                            <div class="mb-2"><label class="small fw-bold">SK Kagawads</label><input type="number" step="0.01" name="sk_staff[SK-Kagawads]" class="form-control form-control-sm"></div>
                            <div class="mb-2"><label class="small fw-bold">SK Sec/Treas</label><input type="number" step="0.01" name="sk_staff[SK-Sec-Treas]" class="form-control form-control-sm"></div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-800 text-success small mb-3">OTHER WORKERS</h6>
                            <div class="mb-2"><label class="small fw-bold">Tanods</label><input type="number" step="0.01" name="gen_staff[Tanods]" class="form-control form-control-sm"></div>
                            <div class="mb-2"><label class="small fw-bold">BHWs</label><input type="number" step="0.01" name="gen_staff[BHW]" class="form-control form-control-sm"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="submit" name="btn_save_payroll" class="btn btn-dark w-100 fw-800 py-3 rounded-pill shadow">CONFIRM & RECORD PAYROLL</button>
                </div>
            </form>
        </div>
    </div>
</div>