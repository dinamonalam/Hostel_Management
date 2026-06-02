<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'hostel';
$success = $error = '';

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $name      = $conn->real_escape_string(trim($_POST['hostel_name']));
        $address   = $conn->real_escape_string(trim($_POST['address']));
        $rooms     = intval($_POST['total_rooms']);
        $admin_id  = $_SESSION['admin_id'];

        $sql = "INSERT INTO hostel (hostel_name, address, total_rooms, admin_id)
                VALUES ('$name','$address',$rooms,$admin_id)";
        if ($conn->query($sql))
            $success = "Hostel added successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'edit') {
        $id      = intval($_POST['hostel_id']);
        $name    = $conn->real_escape_string(trim($_POST['hostel_name']));
        $address = $conn->real_escape_string(trim($_POST['address']));
        $rooms   = intval($_POST['total_rooms']);

        $sql = "UPDATE hostel SET hostel_name='$name', address='$address',
                total_rooms=$rooms WHERE hostel_id=$id";
        if ($conn->query($sql))
            $success = "Hostel updated successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['hostel_id']);
        if ($conn->query("DELETE FROM hostel WHERE hostel_id=$id"))
            $success = "Hostel deleted.";
        else
            $error = "Error: " . $conn->error;
    }
}

// ── Fetch ──
$hostels      = $conn->query("SELECT h.*, COUNT(r.room_id) as room_count
                               FROM hostel h
                               LEFT JOIN room r ON h.hostel_id = r.hostel_id
                               GROUP BY h.hostel_id
                               ORDER BY h.hostel_id DESC");
$total_hostels = $conn->query("SELECT COUNT(*) c FROM hostel")->fetch_assoc()['c'];
$total_rooms   = $conn->query("SELECT COUNT(*) c FROM room")->fetch_assoc()['c'];

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-hotel"></i> Hostel Management</h1>
        <p>Manage all hostels</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addHostelModal')">
        <i class="fa-solid fa-plus"></i> Add Hostel
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
                <i class="fas fa-hotel"></i>
            </div>
        </div>
        <label>Total Hostels</label>
        <h2><?= $total_hostels ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#10b981">
                <i class="fas fa-door-open"></i>
            </div>
        </div>
        <label>Total Rooms</label>
        <h2><?= $total_rooms ?></h2>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3>All Hostels</h3>
        <input type="text" id="hostelSearch"
               onkeyup="filterTable('hostelSearch','hostelTable')"
               placeholder="Search..." class="search-input">
    </div>
    <div class="table-wrapper">
        <table id="hostelTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Hostel Name</th>
                    <th>Address</th>
                    <th>Total Rooms</th>
                    <th>Rooms Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($hostels->num_rows === 0): ?>
                <tr><td colspan="6" style="text-align:center;padding:24px;">No hostels yet.</td></tr>
            <?php else: $i = 1; while ($row = $hostels->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($row['hostel_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><?= $row['total_rooms'] ?></td>
                    <td><?= $row['room_count'] ?></td>
                    <td>
                        <button class="btn-icon btn-edit" onclick="openEditHostel(
                            <?= $row['hostel_id'] ?>,
                            '<?= addslashes($row['hostel_name']) ?>',
                            '<?= addslashes($row['address']) ?>',
                            <?= $row['total_rooms'] ?>
                        )"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-delete"
                                onclick="confirmDelete(<?= $row['hostel_id'] ?>)">
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
<div id="addHostelModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> Add Hostel</h3>
            <button onclick="closeModal('addHostelModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Hostel Name</label>
                    <input type="text" name="hostel_name" required
                           placeholder="e.g. North Block Hostel">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" required
                           placeholder="e.g. Chittagong University Campus">
                </div>
                <div class="form-group">
                    <label>Total Rooms</label>
                    <input type="number" name="total_rooms" required
                           placeholder="e.g. 50" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addHostelModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Hostel</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editHostelModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Hostel</h3>
            <button onclick="closeModal('editHostelModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="hostel_id" id="edit_hid">
            <div class="form-grid">
                <div class="form-group">
                    <label>Hostel Name</label>
                    <input type="text" name="hostel_name" id="edit_hname" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" id="edit_haddress" required>
                </div>
                <div class="form-group">
                    <label>Total Rooms</label>
                    <input type="number" name="total_rooms" id="edit_hrooms" required min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editHostelModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Hostel</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="hostel_id" id="delete_hid">
</form>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openEditHostel(id, name, address, rooms) {
    document.getElementById('edit_hid').value      = id;
    document.getElementById('edit_hname').value    = name;
    document.getElementById('edit_haddress').value = address;
    document.getElementById('edit_hrooms').value   = rooms;
    openModal('editHostelModal');
}

function confirmDelete(id) {
    if (confirm('Delete this hostel? All related rooms and students will be affected!')) {
        document.getElementById('delete_hid').value = id;
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
    ['addHostelModal','editHostelModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>