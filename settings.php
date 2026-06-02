<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';

$current_page = 'settings';
$success = $error = '';

// ── Fetch current admin ──
$admin_id = $_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM admin WHERE admin_id=$admin_id")->fetch_assoc();

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Update Profile
    if ($_POST['action'] === 'update_profile') {
        $first_name = $conn->real_escape_string(trim($_POST['first_name']));
        $last_name  = $conn->real_escape_string(trim($_POST['last_name']));
        $email      = $conn->real_escape_string(trim($_POST['email']));
        $phone      = $conn->real_escape_string(trim($_POST['phone']));

        $sql = "UPDATE admin SET first_name='$first_name', last_name='$last_name',
                email='$email', phone='$phone'
                WHERE admin_id=$admin_id";
        if ($conn->query($sql)) {
            $_SESSION['admin_name'] = $first_name . ' ' . $last_name;
            $success = "Profile updated successfully!";
            // Refresh admin data
            $admin = $conn->query("SELECT * FROM admin WHERE admin_id=$admin_id")->fetch_assoc();
        } else {
            $error = "Error: " . $conn->error;
        }
    }

    // Change Password
    if ($_POST['action'] === 'change_password') {
        $current  = $_POST['current_password'];
        $new      = $_POST['new_password'];
        $confirm  = $_POST['confirm_password'];

        if (!password_verify($current, $admin['password'])) {
            $error = "Current password is incorrect!";
        } elseif (strlen($new) < 6) {
            $error = "New password must be at least 6 characters!";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match!";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $hash_escaped = $conn->real_escape_string($hash);
            if ($conn->query("UPDATE admin SET password='$hash_escaped' WHERE admin_id=$admin_id"))
                $success = "Password changed successfully!";
            else
                $error = "Error: " . $conn->error;
        }
    }
}

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-gear"></i> Settings</h1>
        <p>Manage your admin profile and account settings</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <!-- Profile Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-user-pen"></i> Update Profile</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required
                           value="<?= htmlspecialchars($admin['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required
                           value="<?= htmlspecialchars($admin['last_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required
                           value="<?= htmlspecialchars($admin['email']) ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone"
                           value="<?= htmlspecialchars($admin['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Password Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-lock"></i> Change Password</h3>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div class="form-group">
                    <label>Current Password</label>
                    <div style="position:relative;">
                        <input type="password" name="current_password"
                               id="cur_pass" required
                               placeholder="Enter current password">
                        <button type="button" onclick="togglePass('cur_pass','eye1')"
                                style="position:absolute;right:10px;top:50%;
                                       transform:translateY(-50%);background:none;
                                       border:none;cursor:pointer;color:#94a3b8;">
                            <i class="fa-regular fa-eye" id="eye1"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="new_password"
                               id="new_pass" required
                               placeholder="Min 6 characters">
                        <button type="button" onclick="togglePass('new_pass','eye2')"
                                style="position:absolute;right:10px;top:50%;
                                       transform:translateY(-50%);background:none;
                                       border:none;cursor:pointer;color:#94a3b8;">
                            <i class="fa-regular fa-eye" id="eye2"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="confirm_password"
                               id="con_pass" required
                               placeholder="Repeat new password">
                        <button type="button" onclick="togglePass('con_pass','eye3')"
                                style="position:absolute;right:10px;top:50%;
                                       transform:translateY(-50%);background:none;
                                       border:none;cursor:pointer;color:#94a3b8;">
                            <i class="fa-regular fa-eye" id="eye3"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-key"></i> Change Password
                </button>
            </div>
        </form>
    </div>

</div>

<!-- System Info Card -->
<div class="card">
    <div class="card-header">
        <h3><i class="fa-solid fa-circle-info"></i> System Information</h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;">
            <?php
            $total_students  = $conn->query("SELECT COUNT(*) c FROM student")->fetch_assoc()['c'];
            $total_rooms     = $conn->query("SELECT COUNT(*) c FROM room")->fetch_assoc()['c'];
            $total_employees = $conn->query("SELECT COUNT(*) c FROM employee")->fetch_assoc()['c'];
            $total_hostels   = $conn->query("SELECT COUNT(*) c FROM hostel")->fetch_assoc()['c'];
            foreach ([
                ['Total Students',  $total_students,  '#1e40af', 'fa-user-graduate'],
                ['Total Rooms',     $total_rooms,     '#10b981', 'fa-door-open'],
                ['Total Employees', $total_employees, '#8b5cf6', 'fa-users-gear'],
                ['Total Hostels',   $total_hostels,   '#f59e0b', 'fa-hotel'],
            ] as [$label, $val, $clr, $icon]):
            ?>
            <div style="text-align:center;padding:20px;background:#f8fafc;
                        border-radius:12px;">
                <div style="width:48px;height:48px;background:<?= $clr ?>;
                            border-radius:12px;display:flex;align-items:center;
                            justify-content:center;margin:0 auto 12px;">
                    <i class="fas <?= $icon ?>" style="color:#fff;font-size:1.2rem;"></i>
                </div>
                <div style="font-size:1.8rem;font-weight:800;color:#1e293b;">
                    <?= $val ?>
                </div>
                <div style="font-size:0.82rem;color:#64748b;margin-top:4px;">
                    <?= $label ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Admin Info Card -->
<div class="card">
    <div class="card-header">
        <h3><i class="fa-solid fa-id-card"></i> Admin Account Info</h3>
    </div>
    <div class="card-body">
        <div style="display:flex;align-items:center;gap:20px;">
            <div style="width:70px;height:70px;background:#1e40af;border-radius:50%;
                        display:flex;align-items:center;justify-content:center;
                        font-size:1.8rem;font-weight:800;color:#fff;flex-shrink:0;">
                <?= strtoupper(substr($admin['first_name'],0,1)) ?>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 40px;flex:1;">
                <div>
                    <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;">
                        FULL NAME
                    </div>
                    <div style="font-weight:700;color:#1e293b;margin-top:2px;">
                        <?= htmlspecialchars($admin['first_name'].' '.$admin['last_name']) ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;">
                        EMAIL
                    </div>
                    <div style="font-weight:600;color:#1e293b;margin-top:2px;">
                        <?= htmlspecialchars($admin['email']) ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;">
                        PHONE
                    </div>
                    <div style="font-weight:600;color:#1e293b;margin-top:2px;">
                        <?= htmlspecialchars($admin['phone'] ?? '—') ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:0.78rem;color:#94a3b8;font-weight:600;">
                        ROLE
                    </div>
                    <div style="margin-top:2px;">
                        <span class="badge badge-blue">Administrator</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>