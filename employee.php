<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'employee';
$success = $error = '';

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $name        = $conn->real_escape_string(trim($_POST['name']));
        $designation = $conn->real_escape_string(trim($_POST['designation']));
        $phone       = $conn->real_escape_string(trim($_POST['phone']));
        $salary      = (float) $_POST['salary'];
        $hostel_id   = intval($_POST['hostel_id']);

        $sql = "INSERT INTO employee (name, designation, phone, salary, hostel_id)
                VALUES ('$name','$designation','$phone',$salary,$hostel_id)";
        if ($conn->query($sql))
            $success = "Employee added successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'edit') {
        $id          = intval($_POST['employee_id']);
        $name        = $conn->real_escape_string(trim($_POST['name']));
        $designation = $conn->real_escape_string(trim($_POST['designation']));
        $phone       = $conn->real_escape_string(trim($_POST['phone']));
        $salary      = (float) $_POST['salary'];
        $hostel_id   = intval($_POST['hostel_id']);

        $sql = "UPDATE employee SET name='$name', designation='$designation',
                phone='$phone', salary=$salary, hostel_id=$hostel_id
                WHERE employee_id=$id";
        if ($conn->query($sql))
            $success = "Employee updated successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['employee_id']);
        if ($conn->query("DELETE FROM employee WHERE employee_id=$id"))
            $success = "Employee deleted.";
        else
            $error = "Error: " . $conn->error;
    }
}

// ── Fetch ──
$employees  = $conn->query("
    SELECT e.*, h.hostel_name
    FROM employee e
    LEFT JOIN hostel h ON e.hostel_id = h.hostel_id
    ORDER BY e.employee_id DESC
");
$hostels    = $conn->query("SELECT hostel_id, hostel_name FROM hostel ORDER BY hostel_name");
$total      = $conn->query("SELECT COUNT(*) c FROM employee")->fetch_assoc()['c'];
$managers   = $conn->query("SELECT COUNT(*) c FROM employee WHERE designation='Manager'")->fetch_assoc()['c'];
$guards     = $conn->query("SELECT COUNT(*) c FROM employee WHERE designation='Guard'")->fetch_assoc()['c'];
$avg_salary = $conn->query("SELECT COALESCE(AVG(salary),0) c FROM employee")->fetch_assoc()['c'];

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-users-gear"></i> Employee Management</h1>
        <p>Manage all hostel employees</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addEmployeeModal')">
        <i class="fa-solid fa-plus"></i> Add Employee
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
            <div class="stat-card-icon" style="background:#1e40af"><i class="fas fa-users"></i></div>
        </div>
        <label>Total Employees</label>
        <h2><?= $total ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#8b5cf6"><i class="fas fa-user-tie"></i></div>
        </div>
        <label>Managers</label>
        <h2><?= $managers ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#f59e0b"><i class="fas fa-shield-halved"></i></div>
        </div>
        <label>Guards</label>
        <h2><?= $guards ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#10b981"><i class="fas fa-coins"></i></div>
        </div>
        <label>Avg Salary</label>
        <h2>৳<?= number_format($avg_salary, 0) ?></h2>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3>All Employees</h3>
        <input type="text" id="empSearch"
               onkeyup="filterTable('empSearch','empTable')"
               placeholder="Search..." class="search-input">
    </div>
    <div class="table-wrapper">
        <table id="empTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Phone</th>
                    <th>Salary (৳)</th>
                    <th>Hostel</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($employees->num_rows === 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;">No employees yet.</td></tr>
            <?php else:
                $i = 1;
                $colors = ['#1e40af','#0891b2','#059669','#7c3aed','#b45309'];
                while ($row = $employees->fetch_assoc()):
                    $ini = strtoupper(substr($row['name'], 0, 2));
                    $clr = $colors[($i-1) % count($colors)];
                    $badge = match($row['designation']) {
                        'Manager'     => 'badge-blue',
                        'Guard'       => 'badge-yellow',
                        'Cleaner'     => 'badge-green',
                        'Cook'        => 'badge-green',
                        'Electrician' => 'badge-red',
                        default       => 'badge-gray'
                    };
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="avatar" style="background:<?= $clr ?>"><?= $ini ?></div>
                            <span style="font-weight:600;"><?= htmlspecialchars($row['name']) ?></span>
                        </div>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($row['designation']) ?></span></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td>৳<?= number_format($row['salary'], 2) ?></td>
                    <td><?= htmlspecialchars($row['hostel_name'] ?? '—') ?></td>
                    <td>
                        <button class="btn-icon btn-edit" onclick="openEditEmployee(
                            <?= $row['employee_id'] ?>,
                            '<?= addslashes($row['name']) ?>',
                            '<?= addslashes($row['designation']) ?>',
                            '<?= addslashes($row['phone']) ?>',
                            <?= $row['salary'] ?>,
                            <?= $row['hostel_id'] ?>
                        )"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-delete"
                                onclick="confirmDelete(<?= $row['employee_id'] ?>)">
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
<div id="addEmployeeModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> Add Employee</h3>
            <button onclick="closeModal('addEmployeeModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. Karim Uddin">
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <select name="designation" required>
                        <option value="">-- Select --</option>
                        <option>Manager</option>
                        <option>Guard</option>
                        <option>Cleaner</option>
                        <option>Cook</option>
                        <option>Electrician</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="017XXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Salary (৳)</label>
                    <input type="number" name="salary" step="0.01" required placeholder="e.g. 12000">
                </div>
                <div class="form-group">
                    <label>Hostel</label>
                    <select name="hostel_id" required>
                        <option value="">-- Select Hostel --</option>
                        <?php while ($h = $hostels->fetch_assoc()): ?>
                            <option value="<?= $h['hostel_id'] ?>">
                                <?= htmlspecialchars($h['hostel_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addEmployeeModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editEmployeeModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Employee</h3>
            <button onclick="closeModal('editEmployeeModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="employee_id" id="edit_eid">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit_ename" required>
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <select name="designation" id="edit_edesig" required>
                        <option>Manager</option>
                        <option>Guard</option>
                        <option>Cleaner</option>
                        <option>Cook</option>
                        <option>Electrician</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="edit_ephone">
                </div>
                <div class="form-group">
                    <label>Salary (৳)</label>
                    <input type="number" name="salary" id="edit_esalary" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Hostel</label>
                    <select name="hostel_id" id="edit_ehostel">
                        <?php
                        mysqli_data_seek($hostels, 0);
                        while ($h = $hostels->fetch_assoc()): ?>
                            <option value="<?= $h['hostel_id'] ?>">
                                <?= htmlspecialchars($h['hostel_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editEmployeeModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="employee_id" id="delete_eid">
</form>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openEditEmployee(id, name, desig, phone, salary, hostel_id) {
    document.getElementById('edit_eid').value     = id;
    document.getElementById('edit_ename').value   = name;
    document.getElementById('edit_edesig').value  = desig;
    document.getElementById('edit_ephone').value  = phone;
    document.getElementById('edit_esalary').value = salary;
    document.getElementById('edit_ehostel').value = hostel_id;
    openModal('editEmployeeModal');
}

function confirmDelete(id) {
    if (confirm('Delete this employee?')) {
        document.getElementById('delete_eid').value = id;
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
    ['addEmployeeModal','editEmployeeModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>