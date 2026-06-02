<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'room';
$success = $error = '';

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $room_number = intval($_POST['room_number']);
        $floor_no    = intval($_POST['floor_no']);
        $capacity    = intval($_POST['capacity']);
        $status      = $conn->real_escape_string($_POST['status']);
        $hostel_id   = intval($_POST['hostel_id']);

        $sql = "INSERT INTO room (room_number, floor_no, capacity, status, hostel_id)
                VALUES ($room_number, $floor_no, $capacity, '$status', $hostel_id)";
        if ($conn->query($sql))
            $success = "Room added successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'edit') {
        $id          = intval($_POST['room_id']);
        $room_number = intval($_POST['room_number']);
        $floor_no    = intval($_POST['floor_no']);
        $capacity    = intval($_POST['capacity']);
        $status      = $conn->real_escape_string($_POST['status']);
        $hostel_id   = intval($_POST['hostel_id']);

        $sql = "UPDATE room SET room_number=$room_number, floor_no=$floor_no,
                capacity=$capacity, status='$status', hostel_id=$hostel_id
                WHERE room_id=$id";
        if ($conn->query($sql))
            $success = "Room updated successfully!";
        else
            $error = "Error: " . $conn->error;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['room_id']);
        if ($conn->query("DELETE FROM room WHERE room_id=$id"))
            $success = "Room deleted.";
        else
            $error = "Error: " . $conn->error;
    }
}

// ── Fetch ──
$rooms   = $conn->query("
    SELECT r.*, h.hostel_name,
           COUNT(s.student_id) as students_count
    FROM room r
    LEFT JOIN hostel h ON r.hostel_id = h.hostel_id
    LEFT JOIN student s ON r.room_id = s.room_id
    GROUP BY r.room_id
    ORDER BY r.room_number ASC
");
$hostels  = $conn->query("SELECT hostel_id, hostel_name FROM hostel ORDER BY hostel_name");
$total    = $conn->query("SELECT COUNT(*) c FROM room")->fetch_assoc()['c'];
$occupied = $conn->query("SELECT COUNT(*) c FROM room WHERE status='occupied'")->fetch_assoc()['c'];
$available= $conn->query("SELECT COUNT(*) c FROM room WHERE status='available'")->fetch_assoc()['c'];
$maint    = $conn->query("SELECT COUNT(*) c FROM room WHERE status='maintenance'")->fetch_assoc()['c'];

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-door-open"></i> Room Management</h1>
        <p>Manage all hostel rooms</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addRoomModal')">
        <i class="fa-solid fa-plus"></i> Add Room
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
            <div class="stat-card-icon" style="background:#1e40af"><i class="fas fa-door-open"></i></div>
        </div>
        <label>Total Rooms</label>
        <h2><?= $total ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#10b981"><i class="fas fa-check-circle"></i></div>
        </div>
        <label>Occupied</label>
        <h2><?= $occupied ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#f59e0b"><i class="fas fa-clock"></i></div>
        </div>
        <label>Available</label>
        <h2><?= $available ?></h2>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-card-icon" style="background:#ef4444"><i class="fas fa-tools"></i></div>
        </div>
        <label>Maintenance</label>
        <h2><?= $maint ?></h2>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3>All Rooms</h3>
        <input type="text" id="roomSearch"
               onkeyup="filterTable('roomSearch','roomTable')"
               placeholder="Search..." class="search-input">
    </div>
    <div class="table-wrapper">
        <table id="roomTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Room No.</th>
                    <th>Floor</th>
                    <th>Capacity</th>
                    <th>Students</th>
                    <th>Hostel</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rooms->num_rows === 0): ?>
                <tr><td colspan="8" style="text-align:center;padding:24px;">No rooms yet.</td></tr>
            <?php else: $i = 1; while ($row = $rooms->fetch_assoc()):
                $badge = match($row['status']) {
                    'occupied'    => 'badge-blue',
                    'available'   => 'badge-green',
                    'maintenance' => 'badge-red',
                    default       => 'badge-gray'
                };
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong>Room <?= $row['room_number'] ?></strong></td>
                    <td>Floor <?= $row['floor_no'] ?></td>
                    <td><?= $row['capacity'] ?> persons</td>
                    <td><?= $row['students_count'] ?> / <?= $row['capacity'] ?></td>
                    <td><?= htmlspecialchars($row['hostel_name'] ?? '—') ?></td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                    <td>
                        <button class="btn-icon btn-edit" onclick="openEditRoom(
                            <?= $row['room_id'] ?>,
                            <?= $row['room_number'] ?>,
                            <?= $row['floor_no'] ?>,
                            <?= $row['capacity'] ?>,
                            '<?= $row['status'] ?>',
                            <?= $row['hostel_id'] ?>
                        )"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-icon btn-delete"
                                onclick="confirmDelete(<?= $row['room_id'] ?>)">
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
<div id="addRoomModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> Add Room</h3>
            <button onclick="closeModal('addRoomModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="number" name="room_number" required placeholder="e.g. 101">
                </div>
                <div class="form-group">
                    <label>Floor No.</label>
                    <input type="number" name="floor_no" required placeholder="e.g. 1">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" required placeholder="e.g. 2">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
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
                <button type="button" onclick="closeModal('addRoomModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Room</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editRoomModal" class="modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen-to-square"></i> Edit Room</h3>
            <button onclick="closeModal('editRoomModal')" class="modal-close">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="room_id" id="edit_rid">
            <div class="form-grid">
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="number" name="room_number" id="edit_rnum" required>
                </div>
                <div class="form-group">
                    <label>Floor No.</label>
                    <input type="number" name="floor_no" id="edit_rfloor" required>
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" id="edit_rcap" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_rstatus">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hostel</label>
                    <select name="hostel_id" id="edit_rhostel">
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
                <button type="button" onclick="closeModal('editRoomModal')"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Room</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="room_id" id="delete_rid">
</form>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openEditRoom(id, num, floor, cap, status, hostel_id) {
    document.getElementById('edit_rid').value    = id;
    document.getElementById('edit_rnum').value   = num;
    document.getElementById('edit_rfloor').value = floor;
    document.getElementById('edit_rcap').value   = cap;
    document.getElementById('edit_rstatus').value  = status;
    document.getElementById('edit_rhostel').value  = hostel_id;
    openModal('editRoomModal');
}

function confirmDelete(id) {
    if (confirm('Delete this room? Students in this room will lose their room assignment!')) {
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
    ['addRoomModal','editRoomModal'].forEach(id => {
        const m = document.getElementById(id);
        if (e.target === m) m.style.display = 'none';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>