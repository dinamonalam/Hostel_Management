<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /Hostel_Management/login.php");
    exit();
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$initials  = strtoupper(substr(trim($adminName), 0, 1));

$page_labels = [
    'dashboard' => 'Dashboard',
    'hostel'    => 'Hostel',
    'rooms'     => 'Room',
    'room'      => 'Room',
    'students'  => 'Student',
    'student'   => 'Student',
    'employees' => 'Employee',
    'employee'  => 'Employee',
    'visitors'  => 'Visitor',
    'visitor'   => 'Visitor',
    'fees'      => 'Fee',
    'fee'       => 'Fee',
    'reports'   => 'Report',
    'report'    => 'Report',
    'settings'  => 'Settings',
];
$current_label = $page_labels[$current_page ?? ''] ?? 'Dashboard';

if (!function_exists('nav_active')) {
    function nav_active($current_page, array $pages) {
        return in_array($current_page, $pages, true) ? ' active' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($current_label) ?> &mdash; Hostel Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/Hostel_Management/assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-wrapper">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <i class="fa-solid fa-building-columns"></i>
      </div>
      <div>
        <span class="logo-title">Hostel Manager</span>
        <span class="logo-sub">Admin Panel</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <p class="nav-section-label">MAIN</p>

      <a href="/Hostel_Management/dashboard.php" class="nav-item<?= nav_active($current_page ?? '', ['dashboard']) ?>">
        <i class="fa-solid fa-gauge"></i> Dashboard
      </a>

      <a href="/Hostel_Management/hostel.php" class="nav-item<?= nav_active($current_page ?? '', ['hostel']) ?>">
        <i class="fa-solid fa-hotel"></i> Hostel
      </a>

      <a href="/Hostel_Management/room.php" class="nav-item<?= nav_active($current_page ?? '', ['rooms', 'room']) ?>">
        <i class="fa-solid fa-door-open"></i> Room
      </a>

      <a href="/Hostel_Management/student.php" class="nav-item<?= nav_active($current_page ?? '', ['students', 'student']) ?>">
        <i class="fa-solid fa-user-graduate"></i>
        <span>Student</span>
        <?php if (isset($studentBadge) && (int)$studentBadge > 0): ?>
          <span class="nav-badge"><?= (int)$studentBadge ?></span>
        <?php endif; ?>
      </a>

      <a href="/Hostel_Management/employee.php" class="nav-item<?= nav_active($current_page ?? '', ['employees', 'employee']) ?>">
        <i class="fa-solid fa-users-gear"></i> Employee
      </a>

      <a href="/Hostel_Management/visitor.php" class="nav-item<?= nav_active($current_page ?? '', ['visitors', 'visitor']) ?>">
        <i class="fa-solid fa-person-walking-luggage"></i> Visitor
      </a>

      <a href="/Hostel_Management/fee.php" class="nav-item<?= nav_active($current_page ?? '', ['fees', 'fee']) ?>">
        <i class="fa-solid fa-file-invoice-dollar"></i> Fee
      </a>

      <a href="/Hostel_Management/report.php" class="nav-item<?= nav_active($current_page ?? '', ['reports', 'report']) ?>">
        <i class="fa-solid fa-chart-bar"></i> Report
      </a>

      <p class="nav-section-label">OTHER</p>

      <a href="/Hostel_Management/settings.php" class="nav-item<?= nav_active($current_page ?? '', ['settings']) ?>">
        <i class="fa-solid fa-gear"></i> Settings
      </a>

      <a href="/Hostel_Management/logout.php" class="nav-item nav-logout">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    </nav>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <button type="button" class="topbar-menu" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
      </button>

      <div class="topbar-breadcrumb">
        <a href="/Hostel_Management/dashboard.php" 
   style="color:#94a3b8;text-decoration:none;">Home</a>
        <span class="separator">/</span>
        <span class="current"><?= htmlspecialchars($current_label) ?></span>
      </div>

      <div class="topbar-actions">
        <button type="button" class="topbar-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell"></i>
          <span class="dot"></span>
        </button>
        <button type="button" class="topbar-btn" aria-label="Messages">
          <i class="fa-regular fa-envelope"></i>
        </button>
        <div class="admin-pill">
          <span class="admin-avatar"><?= htmlspecialchars($initials) ?></span>
          <span><?= htmlspecialchars($adminName) ?></span>
          <i class="fa-solid fa-chevron-down"></i>
        </div>
      </div>
    </header>

    <main class="page-content">