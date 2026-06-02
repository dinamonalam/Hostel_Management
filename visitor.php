<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'visitor';
$success = $error = '';

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $name       = $conn->real_escape_string(trim($_POST['visitor_name']));
        $phone      = $conn->real_escape_string(trim($_POST['phone']));
        $purpose    = $conn->real_escape_string(trim($_POST['purpose']));
        $visit_date = $conn->real_escape_string($_POST['visit_date']);
        $in_time    = $conn->real_escape_string($_POST['in_time']);
        $out_time   = !empty($_POST['out_time']) ? "'".$conn->real_escape_string($_POST['out_time'])."'" : 'NULL';
        $student_id = intval($_POST['student_id']);

        $sql = "INSERT INTO visitor (visitor_name, phone, purpose, visit_date, in_time, out_time, student_id)
                VALUES ('$name','$phone','$purpose','$visit_date','$in_time',$out_time,$student_id)";
        if ($conn->query($sql))
            $success = "Visitor logged successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'checkout') {
        $vid      = intval($_POST['visitor_id']);
        $out_time = $conn->real_escape_string($_POST['out_time']);
        if ($conn->query("UPDATE visitor SET out_time='$out_time' WHERE visitor_id=$vid"))
            $success = "Checkout time recorded.";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'delete') {
        $vid = intval($_POST['visitor_id']);
        if ($conn->query("DELETE FROM visitor WHERE visitor_id=$vid"))
            $success = "Visitor entry deleted.";
        else
            $error = "Error: " . $conn->error;
    }
}

// ── Fetch ──
$visitors = $conn->query("
    SELECT v.*, CONCAT(s.first_name,' ',s.last_name) AS student_name,
           r.room_number
    FROM visitor v
    LEFT JOIN student s ON v.student_id = s.student_id
    LEFT JOIN room r ON s.room_id = r.room_id
    ORDER BY v.visit_date DESC, v.in_time DESC
");
$students     = $conn->query("
    SELECT s.student_id, CONCAT(s.first_name,' ',s.last_name) AS full_name, r.room_number
    FROM student s LEFT JOIN room r ON s.room_id = r.room_id
    ORDER BY s.first_name
");
$total_all    = $conn->query("SELECT COUNT(*) c FROM visitor")->fetch_assoc()['c'];
$total_today  = $conn->query("SELECT COUNT(*) c FROM visitor WHERE visit_date=CURDATE()")->fetch_assoc()['c'];
$total_inside = $conn->query("SELECT COUNT(*) c FROM visitor WHERE visit_date=CURDATE() AND out_time IS NULL")->fetch_assoc()['c'];

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-person-walking-luggage"></i> Visitor Management</h1>
        <p>Log and monitor hostel visitor entry & exit</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addVisitorModal')">
        <i class="fa-solid fa-plus"></i> Log Visitor
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
                <i class="fas fa-users"></i>
            </div>
        </div>
        <label>Total Visitors</label>
        <h2><?= $total_all ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#10b981">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <label>Today's Visitors</label>
        <h2><?= $total_today ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#f59e0b">
                <i class="fas fa-person-walking-arrow-right"></i>
            </div>
        </div>
        <label>Currently Inside</label>
        <h2><?= $total_inside ?></h2>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3>Visitor Log</h3>
        <input type="text" id="visSearch"
               onkeyup="filterTable('visSearch','visTable')"
               placeholder="Search..." class="search-input">
    </div>
    <div class="table-wrapper">
        <table id="visTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Visitor Name</th>
                    <th>Phone</th>
                    <th>Purpose</th>
                    <th>Date</th>
                    <th>In Time</th>
                    <th>Out Time</th>
                    <th>Student Host</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($visitors->num_rows === 0): ?>
                <tr>
                    <td colspan="10" style="text-align:center;padding:24px;color:#94a3b8;">
                        No visitors logged yet.
                    </td>
                </tr>
            <?php else:
                $i = 1;
                while ($row = $visitors->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($row['visitor_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td><?= date('d M Y', strtotime($row['visit_date'])) ?></td>
                    <td><?= $row['in_time'] ?></td>
                    <td>
                        <?php if ($row['out_time']): ?>
                            <?= $row['out_time'] ?>
                        <?php else: ?>
                            <form method="POST"
                                  style="display:flex;gap:4px;align-items:center;">
                                <input type="hidden" name="action" value="checkout">
                                <input type="hidden" name="visitor_id"
                                       value="<?= $row['visitor_id'] ?>">
                                <input type="time" name="out_time"
                                       value="<?= date('H:i') ?>"
                                       style="font-size:0.78rem;padding:3px 6px;
                                              border-radius:6px;border:1px solid #e2e8f0;">
                                <button type="submit" class="btn-icon btn-edit"
                                        title="Checkout">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['student_name'] ?? '—') ?>
                        <?php if ($row['room_number']): ?>
                            <small style="color:#94a3b8;">(Room <?= $row['room_number'] ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$row['out_time']): ?>
                            <span class="badge badge-blue">Inside</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Left</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Delete this visitor entry?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="visitor_id"
                                   value="<?= $row['visitor_id'] ?>">
                            <button type="submit" class="btn-icon btn-delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD MODAL -->
<div id="addVisitorModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> Log New Visitor</h3>
            <button onclick="closeModal('addVisitorModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Visitor Name</label>
                    <input type="text" name="visitor_name" required
                           placeholder="Full name">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone"
                           placeholder="017XXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Purpose</label>
                    <input type="text" name="purpose"
                           placeholder="e.g. Family visit, Delivery">
                </div>
                <div class="form-group">
                    <label>Visit Date</label>
                    <input type="date" name="visit_date"
                           required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>In Time</label>
                    <input type="time" name="in_time"
                           required value="<?= date('H:i') ?>">
                </div>
                <div class="form-group">
                    <label>Out Time <small style="color:#94a3b8">(optional)</small></label>
                    <input type="time" name="out_time">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Visiting Student (Host)</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($s = $students->fetch_assoc()): ?>
                            <option value="<?= $s['student_id'] ?>">
                                <?= htmlspecialchars($s['full_name']) ?>
                                (Room <?= $s['room_number'] ?? '—' ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addVisitorModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Log Visitor
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function filterTable(inputId, tableId) {
    const filter = document.getElementById(inputId).value.toLowerCase();
    const rows   = document.getElementById(tableId).getElementsByTagName('tr');
    for (let i = 1; i < rows.length; i++) {
        rows[i].style.display =
            rows[i].textContent.toLowerCase().includes(filter) ? '' : 'none';
    }
}

window.onclick = e => {
    const m = document.getElementById('addVisitorModal');
    if (e.target === m) m.style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>