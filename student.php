<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'student';
$success = $error = '';

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $first_name = $conn->real_escape_string(trim($_POST['first_name']));
        $last_name  = $conn->real_escape_string(trim($_POST['last_name']));
        $email      = $conn->real_escape_string(trim($_POST['email']));
        $phone      = $conn->real_escape_string(trim($_POST['phone']));
        $year       = intval($_POST['year_of_study']);
        $dept       = $conn->real_escape_string(trim($_POST['department']));
        $room_id    = intval($_POST['room_id']);
        $hostel_id  = intval($_POST['hostel_id']);

        $sql = "INSERT INTO student (first_name, last_name, email, phone, year_of_study, department, room_id, hostel_id)
                VALUES ('$first_name','$last_name','$email','$phone',$year,'$dept',$room_id,$hostel_id)";
        if ($conn->query($sql)) {
            $conn->query("UPDATE room SET status='occupied' WHERE room_id=$room_id");
            $success = "Student added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }

    if ($_POST['action'] === 'edit') {
        $id         = intval($_POST['student_id']);
        $first_name = $conn->real_escape_string(trim($_POST['first_name']));
        $last_name  = $conn->real_escape_string(trim($_POST['last_name']));
        $email      = $conn->real_escape_string(trim($_POST['email']));
        $phone      = $conn->real_escape_string(trim($_POST['phone']));
        $year       = intval($_POST['year_of_study']);
        $dept       = $conn->real_escape_string(trim($_POST['department']));
        $room_id    = intval($_POST['room_id']);
        $hostel_id  = intval($_POST['hostel_id']);

        $sql = "UPDATE student SET first_name='$first_name', last_name='$last_name',
                email='$email', phone='$phone', year_of_study=$year,
                department='$dept', room_id=$room_id, hostel_id=$hostel_id
                WHERE student_id=$id";
        if ($conn->query($sql))
            $success = "Student updated successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['student_id']);
        $r  = $conn->query("SELECT room_id FROM student WHERE student_id=$id")->fetch_assoc();
        if ($conn->query("DELETE FROM student WHERE student_id=$id")) {
            if ($r['room_id'])
                $conn->query("UPDATE room SET status='available' WHERE room_id=".$r['room_id']);
            $success = "Student deleted.";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// ── Fetch ──
$students  = $conn->query("
    SELECT s.*, r.room_number, h.hostel_name
    FROM student s
    LEFT JOIN room r ON s.room_id = r.room_id
    LEFT JOIN hostel h ON s.hostel_id = h.hostel_id
    ORDER BY s.student_id DESC
");
$rooms     = $conn->query("SELECT room_id, room_number, floor_no FROM room ORDER BY room_number");
$hostels   = $conn->query("SELECT hostel_id, hostel_name FROM hostel ORDER BY hostel_name");
$total     = $conn->query("SELECT COUNT(*) c FROM student")->fetch_assoc()['c'];
$depts     = $conn->query("SELECT COUNT(DISTINCT department) c FROM student")->fetch_assoc()['c'];
$year1     = $conn->query("SELECT COUNT(*) c FROM student WHERE year_of_study=1")->fetch_assoc()['c'];

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-user-graduate"></i> Student Management</h1>
        <p>Manage all hostel students</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addStudentModal')">
        <i class="fa-solid fa-plus"></i> Add Student
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
        <label>Total Students</label>
        <h2><?= $total ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#8b5cf6"><i class="fas fa-building-columns"></i></div>
        </div>
        <label>Departments</label>
        <h2><?= $depts ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#10b981"><i class="fas fa-star"></i></div>
        </div>
        <label>1st Year Students</label>
        <h2><?= $year1 ?></h2>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3>All Students</h3>
        <input type="text" id="stuSearch"
               onkeyup="filterTable('stuSearch','stuTable')"
               placeholder="Search..." class="search-input">
    </div>
    <div class="table-wrapper">
        <table id="stuTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Room</th>
                    <th>Hostel</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($students->num_rows === 0): ?>
                <tr><td colspan="10" style="text-align:center;padding:24px;color:#94a3b8;">No students yet.</td></tr>
            <?php else:
                $i = 1;
                $colors = ['#1e40af','#0891b2','#059669','#7c3aed','#b45309'];
                while ($row = $students->fetch_assoc()):
                    $ini = strtoupper(substr($row['first_name'],0,1).substr($row['last_name'],0,1));
                    $clr = $colors[($i-1) % count($colors)];
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="avatar" style="background:<?= $clr ?>"><?= $ini ?></div>
                            <span style="font-weight:600;">
                                <?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?>
                            </span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['email'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['phone'] ?? '—') ?></td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars($row['department']) ?></span></td>
                    <td>Year <?= $row['year_of_study'] ?></td>
                    <td><?= $row['room_number'] ?? '—' ?></td>
                    <td><?= htmlspecialchars($row['hostel_name'] ?? '—') ?></td>
                    <td><span class="badge badge-green">Active</span></td>
                    <td>
                        <button class="btn-icon btn-edit" onclick="openEditStudent(
                            <?= $row['student_id'] ?>,
                            '<?= addslashes($row['first_name']) ?>',
                            '<?= addslashes($row['last_name']) ?>',
                            '<?= addslashes($row['email'] ?? '') ?>',
                            '<?= addslashes($row['phone'] ?? '') ?>',
                            <?= $row['year_of_study'] ?>,
                            '<?= addslashes($row['department']) ?>',
                            <?= $row['room_id'] ?? 0 ?>,
                            <?= $row['hostel_id'] ?? 0 ?>
                        )"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-delete"
                                onclick="confirmDelete(<?= $row['student_id'] ?>)">
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
<div id="addStudentModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> Add Student</h3>
            <button onclick="closeModal('addStudentModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required placeholder="e.g. Ayesha">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required placeholder="e.g. Khan">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="017XXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Year of Study</label>
                    <select name="year_of_study" required>
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                        <option value="4">Year 4</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department" required>
                        <option value="">-- Select --</option>
                        <option>CSE</option>
                        <option>EEE</option>
                        <option>BBA</option>
                        <option>Civil</option>
                        <option>Mechanical</option>
                        <option>English</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <select name="room_id" required>
                        <option value="">-- Select Room --</option>
                        <?php while ($rm = $rooms->fetch_assoc()): ?>
                            <option value="<?= $rm['room_id'] ?>">
                                Room <?= $rm['room_number'] ?> (Floor <?= $rm['floor_no'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hostel</label>
                    <select name="hostel_id" required>
                        <option value="">-- Select Hostel --</option>
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
                <button type="button" onclick="closeModal('addStudentModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Student</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editStudentModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Student</h3>
            <button onclick="closeModal('editStudentModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" id="edit_sid">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" id="edit_sfirst" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" id="edit_slast" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_semail">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="edit_sphone">
                </div>
                <div class="form-group">
                    <label>Year of Study</label>
                    <select name="year_of_study" id="edit_syear">
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                        <option value="4">Year 4</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department" id="edit_sdept">
                        <option>CSE</option>
                        <option>EEE</option>
                        <option>BBA</option>
                        <option>Civil</option>
                        <option>Mechanical</option>
                        <option>English</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <select name="room_id" id="edit_sroom">
                        <?php
                        mysqli_data_seek($rooms, 0);
                        while ($rm = $rooms->fetch_assoc()): ?>
                            <option value="<?= $rm['room_id'] ?>">
                                Room <?= $rm['room_number'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hostel</label>
                    <select name="hostel_id" id="edit_shostel">
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
                <button type="button" onclick="closeModal('editStudentModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Student</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="student_id" id="delete_sid">
</form>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openEditStudent(id, first, last, email, phone, year, dept, room_id, hostel_id) {
    document.getElementById('edit_sid').value     = id;
    document.getElementById('edit_sfirst').value  = first;
    document.getElementById('edit_slast').value   = last;
    document.getElementById('edit_semail').value  = email;
    document.getElementById('edit_sphone').value  = phone;
    document.getElementById('edit_syear').value   = year;
    document.getElementById('edit_sdept').value   = dept;
    document.getElementById('edit_sroom').value   = room_id;
    document.getElementById('edit_shostel').value = hostel_id;
    openModal('editStudentModal');
}

function confirmDelete(id) {
    if (confirm('Delete this student?')) {
        document.getElementById('delete_sid').value = id;
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
    ['addStudentModal','editStudentModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>