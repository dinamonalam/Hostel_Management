<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$current_page = 'dashboard';

$totalStudents  = $conn->query("SELECT COUNT(*) as c FROM student")->fetch_assoc()['c'] ?? 0;
$totalRooms     = $conn->query("SELECT COUNT(*) as c FROM room")->fetch_assoc()['c'] ?? 0;
$occupiedRooms  = $conn->query("SELECT COUNT(*) as c FROM room WHERE status='occupied'")->fetch_assoc()['c'] ?? 0;
$availableRooms = $conn->query("SELECT COUNT(*) as c FROM room WHERE status='available'")->fetch_assoc()['c'] ?? 0;
$maintRooms     = $conn->query("SELECT COUNT(*) as c FROM room WHERE status='maintenance'")->fetch_assoc()['c'] ?? 0;
$totalEmployees = $conn->query("SELECT COUNT(*) as c FROM employee")->fetch_assoc()['c'] ?? 0;
$totalFee       = $conn->query("SELECT COALESCE(SUM(amount),0) as c FROM fee")->fetch_assoc()['c'] ?? 0;

$recentStudents = $conn->query("SELECT s.*, r.room_number FROM student s LEFT JOIN room r ON s.room_id = r.room_id ORDER BY s.student_id DESC LIMIT 5");
$recentFees     = $conn->query("SELECT f.*, CONCAT(s.first_name,' ',s.last_name) as student_name FROM fee f LEFT JOIN student s ON f.student_id = s.student_id ORDER BY f.payment_date DESC LIMIT 5");

$monthlyFees = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $res = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM fee WHERE DATE_FORMAT(payment_date,'%Y-%m')='$month'");
    $monthlyFees[] = ['label' => $label, 'total' => (float)$res->fetch_assoc()['total']];
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';

require_once 'includes/header.php';
?>

<style>
/* ── Dashboard specific overrides ── */
.dash-stat-cards {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 16px;
    margin-bottom: 20px;
}
.dash-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
}
.dash-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.dash-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #fff;
    flex-shrink: 0;
}
.dash-stat-link {
    font-size: 0.78rem;
    color: #3b82f6;
    font-weight: 600;
    text-decoration: none;
    align-self: flex-start;
}
.dash-stat-label {
    font-size: 0.82rem;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 4px;
    display: block;
}
.dash-stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 6px;
    line-height: 1;
}
.dash-stat-sub {
    font-size: 0.78rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
}
.dash-stat-sub.up { color: #10b981; }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars(explode(' ',$adminName)[0]) ?>! Here's what's happening today.</p>
    </div>
    <div style="font-size:13px;color:#64748b;display:flex;align-items:center;gap:6px;">
        <i class="far fa-calendar"></i> <?= date('F Y') ?>
    </div>
</div>

<!-- Stat Cards -->
<div class="dash-stat-cards">
    <div class="dash-stat-card">
        <div class="dash-stat-top">
            <div class="dash-stat-icon" style="background:#1e40af">
                <i class="fas fa-user-graduate"></i>
            </div>
            <a href="student.php" class="dash-stat-link">View all</a>
        </div>
        <span class="dash-stat-label">Total Students</span>
        <div class="dash-stat-value"><?= $totalStudents ?></div>
        <div class="dash-stat-sub up"><i class="fas fa-arrow-up"></i> Active residents</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-top">
            <div class="dash-stat-icon" style="background:#10b981">
                <i class="fas fa-door-open"></i>
            </div>
            <a href="room.php" class="dash-stat-link">View all</a>
        </div>
        <span class="dash-stat-label">Total Rooms</span>
        <div class="dash-stat-value"><?= $totalRooms ?></div>
        <div class="dash-stat-sub"><?= $occupiedRooms ?> occupied</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-top">
            <div class="dash-stat-icon" style="background:#8b5cf6">
                <i class="fas fa-id-badge"></i>
            </div>
            <a href="employee.php" class="dash-stat-link">View all</a>
        </div>
        <span class="dash-stat-label">Total Employees</span>
        <div class="dash-stat-value"><?= $totalEmployees ?></div>
        <div class="dash-stat-sub up"><i class="fas fa-check-circle"></i> All active</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-top">
            <div class="dash-stat-icon" style="background:#f59e0b">
                <i class="fas fa-coins"></i>
            </div>
            <a href="fee.php" class="dash-stat-link">View all</a>
        </div>
        <span class="dash-stat-label">Total Fee Collected</span>
        <div class="dash-stat-value">৳<?= number_format($totalFee) ?></div>
        <div class="dash-stat-sub up"><i class="fas fa-arrow-up"></i> This month</div>
    </div>
</div>

<!-- Middle Row -->
<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:16px;margin-bottom:16px;">

    <!-- Room Donut -->
    <div class="card">
        <div class="card-header">
            <h3>Room Status</h3>
            <a href="room.php" style="font-size:12px;color:#3b82f6;font-weight:600;">Details</a>
        </div>
        <div class="card-body" style="display:flex;align-items:center;gap:24px;">
            <div style="position:relative;width:140px;height:140px;flex-shrink:0;">
                <canvas id="roomChart"></canvas>
                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                    <div style="font-size:24px;font-weight:800;color:#1e293b;"><?= $totalRooms ?></div>
                    <div style="font-size:11px;color:#94a3b8;">Total</div>
                </div>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;gap:12px;">
                <?php foreach([
                    ['Total Rooms','#3b82f6',$totalRooms],
                    ['Occupied','#10b981',$occupiedRooms],
                    ['Available','#f59e0b',$availableRooms],
                    ['Maintenance','#ef4444',$maintRooms]
                ] as [$lbl,$clr,$val]): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
                        <span style="width:10px;height:10px;border-radius:50%;background:<?= $clr ?>;display:inline-block;flex-shrink:0;"></span>
                        <?= $lbl ?>
                    </div>
                    <strong style="color:#1e293b;"><?= $val ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Fee -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Fee Collection</h3>
            <a href="fee.php" style="font-size:12px;color:#3b82f6;font-weight:600;">View all</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>STUDENT</th>
                        <th>AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recentFees && $recentFees->num_rows > 0):
                    while ($fee = $recentFees->fetch_assoc()): ?>
                    <tr>
                        <td><?= $fee['payment_date'] ? date('d M Y', strtotime($fee['payment_date'])) : '—' ?></td>
                        <td><?= htmlspecialchars($fee['student_name'] ?? 'N/A') ?></td>
                        <td style="color:#10b981;font-weight:700;">৳<?= number_format($fee['amount']) ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center;padding:24px;color:#94a3b8;">
                            No fee records yet
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h3>Recent Students</h3>
        <a href="student.php" class="btn btn-primary btn-sm">
            <i class="fas fa-arrow-right"></i> View All
        </a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>NAME</th>
                    <th>DEPARTMENT</th>
                    <th>ROOM NO.</th>
                    <th>PHONE</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recentStudents && $recentStudents->num_rows > 0):
                $i=1;
                $colors=['#1e40af','#0891b2','#059669','#7c3aed','#b45309'];
                while ($s = $recentStudents->fetch_assoc()):
                    $ini = strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1));
                    $clr = $colors[($i-1)%count($colors)];
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="avatar" style="background:<?= $clr ?>"><?= $ini ?></div>
                            <span style="font-weight:600;">
                                <?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?>
                            </span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($s['department'] ?? '—') ?></td>
                    <td><?= $s['room_number'] ?? '—' ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                    <td><span class="badge badge-green">Active</span></td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">
                        No students yet
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Monthly Chart -->
<div class="card">
    <div class="card-header">
        <h3>Monthly Fee Collection (৳)</h3>
    </div>
    <div class="card-body">
        <canvas id="feeChart" height="80"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('roomChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Occupied','Available','Maintenance'],
        datasets: [{
            data: [<?= $occupiedRooms ?>,<?= $availableRooms ?>,<?= $maintRooms ?>],
            backgroundColor: ['#10b981','#f59e0b','#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        cutout: '72%',
        plugins: { legend: { display: false } },
        animation: { animateScale: true }
    }
});

new Chart(document.getElementById('feeChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthlyFees,'label')) ?>,
        datasets: [{
            label: 'Fee (৳)',
            data: <?= json_encode(array_column($monthlyFees,'total')) ?>,
            backgroundColor: 'rgba(30,64,175,0.15)',
            borderColor: '#1e40af',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: { callback: v => '৳'+v.toLocaleString(), font: { size: 11 } }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>