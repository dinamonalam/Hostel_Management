<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'report';
$success = $error = '';

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $report_type = $conn->real_escape_string(trim($_POST['report_type']));
        $title       = $conn->real_escape_string(trim($_POST['title']));
        $description = $conn->real_escape_string(trim($_POST['description']));
        $date        = $conn->real_escape_string($_POST['date']);
        $status      = $conn->real_escape_string($_POST['status']);
        $student_id  = !empty($_POST['student_id'])  ? intval($_POST['student_id'])  : 'NULL';
        $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : 'NULL';

        $sql = "INSERT INTO report (report_type, title, description, date, status, student_id, employee_id)
                VALUES ('$report_type','$title','$description','$date','$status',$student_id,$employee_id)";
        if ($conn->query($sql))
            $success = "Report added successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'edit') {
        $id          = intval($_POST['report_id']);
        $report_type = $conn->real_escape_string(trim($_POST['report_type']));
        $title       = $conn->real_escape_string(trim($_POST['title']));
        $description = $conn->real_escape_string(trim($_POST['description']));
        $date        = $conn->real_escape_string($_POST['date']);
        $status      = $conn->real_escape_string($_POST['status']);
        $student_id  = !empty($_POST['student_id'])  ? intval($_POST['student_id'])  : 'NULL';
        $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : 'NULL';

        $sql = "UPDATE report SET report_type='$report_type', title='$title',
                description='$description', date='$date', status='$status',
                student_id=$student_id, employee_id=$employee_id
                WHERE report_id=$id";
        if ($conn->query($sql))
            $success = "Report updated successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['report_id']);
        if ($conn->query("DELETE FROM report WHERE report_id=$id"))
            $success = "Report deleted.";
        else
            $error = "Error: " . $conn->error;
    }
}

// ── Fetch ──
$reports   = $conn->query("
    SELECT r.*,
           CONCAT(s.first_name,' ',s.last_name) AS student_name,
           e.name AS employee_name
    FROM report r
    LEFT JOIN student s ON r.student_id = s.student_id
    LEFT JOIN employee e ON r.employee_id = e.employee_id
    ORDER BY r.date DESC
");
$students  = $conn->query("SELECT student_id, CONCAT(first_name,' ',last_name) AS full_name FROM student ORDER BY first_name");
$employees = $conn->query("SELECT employee_id, name FROM employee ORDER BY name");

$total     = $conn->query("SELECT COUNT(*) c FROM report")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) c FROM report WHERE status='Pending'")->fetch_assoc()['c'];
$resolved  = $conn->query("SELECT COUNT(*) c FROM report WHERE status='Resolved'")->fetch_assoc()['c'];
$this_month= $conn->query("SELECT COUNT(*) c FROM report WHERE MONTH(date)=MONTH(NOW()) AND YEAR(date)=YEAR(NOW())")->fetch_assoc()['c'];

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-chart-bar"></i> Report Management</h1>
        <p>Track and manage all hostel reports</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addReportModal')">
        <i class="fa-solid fa-plus"></i> Add Report
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
            <div class="stat-card-icon" style="background:#1e40af">
                <i class="fas fa-flag"></i>
            </div>
        </div>
        <label>Total Reports</label>
        <h2><?= $total ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#f59e0b">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <label>Pending</label>
        <h2><?= $pending ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#10b981">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
        <label>Resolved</label>
        <h2><?= $resolved ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#8b5cf6">
                <i class="fas fa-calendar"></i>
            </div>
        </div>
        <label>This Month</label>
        <h2><?= $this_month ?></h2>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3>All Reports</h3>
        <input type="text" id="repSearch"
               onkeyup="filterTable('repSearch','repTable')"
               placeholder="Search..." class="search-input">
    </div>
    <div class="table-wrapper">
        <table id="repTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Employee</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($reports->num_rows === 0): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">
                        No reports yet.
                    </td>
                </tr>
            <?php else:
                $i = 1;
                while ($row = $reports->fetch_assoc()):
                    $status_badge = match($row['status']) {
                        'Resolved'   => 'badge-green',
                        'Pending'    => 'badge-yellow',
                        'Rejected'   => 'badge-red',
                        'InProgress' => 'badge-blue',
                        default      => 'badge-gray'
                    };
                    $type_badge = match($row['report_type']) {
                        'Complaint'   => 'badge-red',
                        'Maintenance' => 'badge-yellow',
                        'Suggestion'  => 'badge-blue',
                        'Incident'    => 'badge-red',
                        default       => 'badge-gray'
                    };
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><span class="badge <?= $type_badge ?>"><?= htmlspecialchars($row['report_type']) ?></span></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= $row['date'] ? date('d M Y', strtotime($row['date'])) : '—' ?></td>
                    <td><?= htmlspecialchars($row['student_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['employee_name'] ?? '—') ?></td>
                    <td><span class="badge <?= $status_badge ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                        <button class="btn-icon btn-edit" onclick="openEditReport(
                            <?= $row['report_id'] ?>,
                            '<?= addslashes($row['report_type']) ?>',
                            '<?= addslashes($row['title']) ?>',
                            '<?= addslashes($row['description']) ?>',
                            '<?= $row['date'] ?>',
                            '<?= addslashes($row['status']) ?>',
                            '<?= $row['student_id'] ?? '' ?>',
                            '<?= $row['employee_id'] ?? '' ?>'
                        )"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-delete"
                                onclick="confirmDelete(<?= $row['report_id'] ?>)">
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
<div id="addReportModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> Add Report</h3>
            <button onclick="closeModal('addReportModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Report Type</label>
                    <select name="report_type" required>
                        <option value="">-- Select --</option>
                        <option>Complaint</option>
                        <option>Maintenance</option>
                        <option>Suggestion</option>
                        <option>Incident</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required placeholder="Report title">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="InProgress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Student <small style="color:#94a3b8">(optional)</small></label>
                    <select name="student_id">
                        <option value="">-- None --</option>
                        <?php while ($s = $students->fetch_assoc()): ?>
                            <option value="<?= $s['student_id'] ?>">
                                <?= htmlspecialchars($s['full_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Employee <small style="color:#94a3b8">(optional)</small></label>
                    <select name="employee_id">
                        <option value="">-- None --</option>
                        <?php while ($e = $employees->fetch_assoc()): ?>
                            <option value="<?= $e['employee_id'] ?>">
                                <?= htmlspecialchars($e['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Description</label>
                    <textarea name="description" rows="3"
                              placeholder="Describe the report..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addReportModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Report</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editReportModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Report</h3>
            <button onclick="closeModal('editReportModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="report_id" id="edit_rid">
            <div class="form-grid">
                <div class="form-group">
                    <label>Report Type</label>
                    <select name="report_type" id="edit_rtype" required>
                        <option>Complaint</option>
                        <option>Maintenance</option>
                        <option>Suggestion</option>
                        <option>Incident</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="edit_rtitle" required>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" id="edit_rdate" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_rstatus" required>
                        <option value="Pending">Pending</option>
                        <option value="InProgress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" id="edit_rstudent">
                        <option value="">-- None --</option>
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
                    <label>Employee</label>
                    <select name="employee_id" id="edit_remployee">
                        <option value="">-- None --</option>
                        <?php
                        mysqli_data_seek($employees, 0);
                        while ($e = $employees->fetch_assoc()): ?>
                            <option value="<?= $e['employee_id'] ?>">
                                <?= htmlspecialchars($e['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Description</label>
                    <textarea name="description" id="edit_rdesc" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editReportModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Report</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="report_id" id="delete_rid">
</form>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openEditReport(id, type, title, desc, date, status, student_id, employee_id) {
    document.getElementById('edit_rid').value       = id;
    document.getElementById('edit_rtype').value     = type;
    document.getElementById('edit_rtitle').value    = title;
    document.getElementById('edit_rdesc').value     = desc;
    document.getElementById('edit_rdate').value     = date;
    document.getElementById('edit_rstatus').value   = status;
    document.getElementById('edit_rstudent').value  = student_id;
    document.getElementById('edit_remployee').value = employee_id;
    openModal('editReportModal');
}

function confirmDelete(id) {
    if (confirm('Delete this report?')) {
        document.getElementById('delete_rid').value = id;
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
    ['addReportModal','editReportModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>