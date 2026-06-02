<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'fee';
$success = $error = '';

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $amount       = (float) $_POST['amount'];
        $payment_date = $conn->real_escape_string($_POST['payment_date']);
        $due_date     = $conn->real_escape_string($_POST['due_date']);
        $student_id   = intval($_POST['student_id']);

        $sql = "INSERT INTO fee (amount, payment_date, due_date, student_id)
                VALUES ($amount, '$payment_date', '$due_date', $student_id)";
        if ($conn->query($sql))
            $success = "Fee record added successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'edit') {
        $id           = intval($_POST['fee_id']);
        $amount       = (float) $_POST['amount'];
        $payment_date = $conn->real_escape_string($_POST['payment_date']);
        $due_date     = $conn->real_escape_string($_POST['due_date']);
        $student_id   = intval($_POST['student_id']);

        $sql = "UPDATE fee SET amount=$amount, payment_date='$payment_date',
                due_date='$due_date', student_id=$student_id
                WHERE fee_id=$id";
        if ($conn->query($sql))
            $success = "Fee record updated successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['fee_id']);
        if ($conn->query("DELETE FROM fee WHERE fee_id=$id"))
            $success = "Fee record deleted.";
        else
            $error = "Error: " . $conn->error;
    }
}

// ── Fetch ──
$fees = $conn->query("
    SELECT f.*, CONCAT(s.first_name,' ',s.last_name) AS student_name,
           r.room_number
    FROM fee f
    LEFT JOIN student s ON f.student_id = s.student_id
    LEFT JOIN room r ON s.room_id = r.room_id
    ORDER BY f.payment_date DESC
");
$students    = $conn->query("SELECT student_id, CONCAT(first_name,' ',last_name) AS full_name FROM student ORDER BY first_name");
$total_fee   = $conn->query("SELECT COALESCE(SUM(amount),0) c FROM fee")->fetch_assoc()['c'];
$this_month  = $conn->query("SELECT COALESCE(SUM(amount),0) c FROM fee WHERE MONTH(payment_date)=MONTH(NOW()) AND YEAR(payment_date)=YEAR(NOW())")->fetch_assoc()['c'];
$total_count = $conn->query("SELECT COUNT(*) c FROM fee")->fetch_assoc()['c'];
$overdue     = $conn->query("SELECT COUNT(*) c FROM fee WHERE due_date < CURDATE() AND payment_date IS NULL")->fetch_assoc()['c'];

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-file-invoice-dollar"></i> Fee Management</h1>
        <p>Track and manage all student fee payments</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addFeeModal')">
        <i class="fa-solid fa-plus"></i> Add Fee Record
    </button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#1e40af"><i class="fas fa-coins"></i></div>
        </div>
        <label>Total Collected</label>
        <h2>৳<?= number_format($total_fee) ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#10b981"><i class="fas fa-calendar-check"></i></div>
        </div>
        <label>This Month</label>
        <h2>৳<?= number_format($this_month) ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#8b5cf6"><i class="fas fa-receipt"></i></div>
        </div>
        <label>Total Records</label>
        <h2><?= $total_count ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#ef4444"><i class="fas fa-exclamation-circle"></i></div>
        </div>
        <label>Overdue</label>
        <h2><?= $overdue ?></h2>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3>All Fee Records</h3>
        <input type="text" id="feeSearch"
               onkeyup="filterTable('feeSearch','feeTable')"
               placeholder="Search..." class="search-input">
    </div>
    <div class="table-wrapper">
        <table id="feeTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Room</th>
                    <th>Amount (৳)</th>
                    <th>Payment Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($fees->num_rows === 0): ?>
                <tr><td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">No fee records yet.</td></tr>
            <?php else:
                $i = 1;
                while ($row = $fees->fetch_assoc()):
                    $is_overdue = strtotime($row['due_date']) < time();
                    $status_badge = $is_overdue ? 'badge-red' : 'badge-green';
                    $status_label = $is_overdue ? 'Overdue' : 'Paid';
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <span style="font-weight:600;">
                            <?= htmlspecialchars($row['student_name'] ?? '—') ?>
                        </span>
                    </td>
                    <td><?= $row['room_number'] ?? '—' ?></td>
                    <td style="color:#10b981;font-weight:700;">
                        ৳<?= number_format($row['amount'], 2) ?>
                    </td>
                    <td><?= $row['payment_date'] ? date('d M Y', strtotime($row['payment_date'])) : '—' ?></td>
                    <td><?= $row['due_date'] ? date('d M Y', strtotime($row['due_date'])) : '—' ?></td>
                    <td><span class="badge <?= $status_badge ?>"><?= $status_label ?></span></td>
                    <td>
                        <button class="btn-icon btn-edit" onclick="openEditFee(
                            <?= $row['fee_id'] ?>,
                            <?= $row['amount'] ?>,
                            '<?= $row['payment_date'] ?>',
                            '<?= $row['due_date'] ?>',
                            <?= $row['student_id'] ?>
                        )"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-delete"
                                onclick="confirmDelete(<?= $row['fee_id'] ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD MODAL -->
<div id="addFeeModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> Add Fee Record</h3>
            <button onclick="closeModal('addFeeModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($s = $students->fetch_assoc()): ?>
                            <option value="<?= $s['student_id'] ?>">
                                <?= htmlspecialchars($s['full_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (৳)</label>
                    <input type="number" name="amount" step="0.01"
                           required placeholder="e.g. 15000">
                </div>
                <div class="form-group">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date"
                           required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addFeeModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editFeeModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Fee Record</h3>
            <button onclick="closeModal('editFeeModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="fee_id" id="edit_fid">
            <div class="form-grid">
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" id="edit_fstudent" required>
                        <?php
                        mysqli_data_seek($students, 0);
                        while ($s = $students->fetch_assoc()): ?>
                            <option value="<?= $s['student_id'] ?>">
                                <?= htmlspecialchars($s['full_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (৳)</label>
                    <input type="number" name="amount" id="edit_famount"
                           step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" id="edit_fpaydate" required>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" id="edit_fduedate" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editFeeModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Record</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="fee_id" id="delete_fid">
</form>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openEditFee(id, amount, payment_date, due_date, student_id) {
    document.getElementById('edit_fid').value       = id;
    document.getElementById('edit_famount').value   = amount;
    document.getElementById('edit_fpaydate').value  = payment_date;
    document.getElementById('edit_fduedate').value  = due_date;
    document.getElementById('edit_fstudent').value  = student_id;
    openModal('editFeeModal');
}

function confirmDelete(id) {
    if (confirm('Delete this fee record?')) {
        document.getElementById('delete_fid').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function filterTable(inputId, tableId) {
    const filter = document.getElementById(inputId).value.toLowerCase();
    const rows   = document.getElementById(tableId).getElementsByTagName('tr');
    for (let i = 1; i < rows.length; i++) {
        rows[i].style.display =
            rows[i].textContent.toLowerCase().includes(filter) ? '' : 'none';
    }
}

window.onclick = e => {
    ['addFeeModal','editFeeModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>