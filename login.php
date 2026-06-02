<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin  = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']    = $admin['admin_id'];
            $_SESSION['admin_name']  = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['admin_email'] = $admin['email'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hostel Management — Sign In</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --accent:  #1e40af;
  --accent-light: #3b82f6;
  --gold:    #f59e0b;
  --font-main: 'Plus Jakarta Sans', sans-serif;
  --font-body: 'DM Sans', sans-serif;
}

html, body {
  height: 100%;
  font-family: var(--font-body);
}

/* ── Full-screen background image ── */
.login-page {
  min-height: 100vh;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 40px 60px;
  background:
    linear-gradient(135deg, rgba(10,20,50,0.82) 0%, rgba(15,31,61,0.70) 100%),
    url('https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=1600&q=85') center/cover no-repeat;
}

/* ── Left text (over background) ── */
.left-text {
  position: absolute;
  left: 60px;
  top: 50%;
  transform: translateY(-50%);
  max-width: 420px;
  z-index: 1;
}

.hostel-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.18);
  border-radius: 30px;
  padding: 6px 16px;
  color: rgba(255,255,255,0.80);
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 28px;
}

.left-text h1 {
  font-family: var(--font-main);
  font-size: 48px;
  font-weight: 800;
  color: #fff;
  line-height: 1.1;
  letter-spacing: -0.5px;
  margin-bottom: 18px;
}

.left-text p {
  font-size: 15px;
  color: rgba(255,255,255,0.60);
  line-height: 1.75;
  margin-bottom: 44px;
}

.stats-row {
  display: flex;
  gap: 40px;
}
.stat-item .num {
  font-family: var(--font-main);
  font-size: 30px;
  font-weight: 800;
  color: var(--gold);
  line-height: 1;
}
.stat-item .lbl {
  font-size: 12px;
  color: rgba(255,255,255,0.45);
  margin-top: 4px;
}

/* ── Floating login card ── */
.login-card {
  position: relative;
  z-index: 2;
  background: #fff;
  border-radius: 20px;
  padding: 40px 44px;
  width: 420px;
  box-shadow: 0 25px 60px rgba(0,0,0,0.35);
}

.card-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 28px;
}
.card-logo-icon {
  width: 50px; height: 50px;
  background: var(--accent);
  border-radius: 13px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
  color: #fff;
}
.card-logo-text h2 {
  font-family: var(--font-main);
  font-size: 15px;
  font-weight: 800;
  color: #0f172a;
}
.card-logo-text p { font-size: 12px; color: #94a3b8; }

.login-card h3 {
  font-family: var(--font-main);
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.3px;
  margin-bottom: 6px;
}
.login-card > p {
  font-size: 14px;
  color: #64748b;
  margin-bottom: 26px;
}

.error-box {
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  color: #b91c1c;
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.field-group { margin-bottom: 16px; }
.field-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 6px;
  font-family: var(--font-main);
}
.field-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.field-icon {
  position: absolute;
  left: 13px;
  color: #94a3b8;
  font-size: 14px;
  pointer-events: none;
}
.field-wrap input {
  width: 100%;
  padding: 11px 40px 11px 38px;
  border: 1.5px solid #e2e8f0;
  border-radius: 9px;
  font-size: 14px;
  font-family: var(--font-body);
  color: #0f172a;
  outline: none;
  background: #f8fafc;
  transition: border-color .15s, box-shadow .15s;
}
.field-wrap input:focus {
  border-color: var(--accent-light);
  box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
  background: #fff;
}
.field-wrap input::placeholder { color: #cbd5e1; }
.toggle-pw {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 14px;
  padding: 0;
}

.login-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 6px 0 22px;
}
.remember {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  color: #475569;
  cursor: pointer;
}
.remember input[type="checkbox"] {
  width: 15px; height: 15px;
  accent-color: var(--accent);
  cursor: pointer;
}
.forgot { font-size: 13px; color: var(--accent-light); font-weight: 600; text-decoration: none; }
.forgot:hover { text-decoration: underline; }

.btn-signin {
  width: 100%;
  padding: 13px;
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 9px;
  font-size: 15px;
  font-weight: 700;
  font-family: var(--font-main);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background .15s, transform .1s;
  letter-spacing: 0.1px;
}
.btn-signin:hover { background: #1d3db8; }
.btn-signin:active { transform: scale(0.99); }

.card-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 12px;
  color: #94a3b8;
}

@media (max-width: 900px) {
  .left-text { display: none; }
  .login-page { justify-content: center; padding: 24px; }
  .login-card { width: 100%; max-width: 400px; }
}
</style>
</head>
<body>

<div class="login-page">

  <!-- Left text over background -->
  <div class="left-text">
    <div class="hostel-tag">
      <i class="fas fa-university"></i>
      Est. 2020 &middot; University Hostel
    </div>
    <h1>Smart Hostel<br>Management<br>System</h1>
    <p>Efficiently manage students, rooms, fees, and staff — all from one powerful, easy-to-use dashboard.</p>
    <div class="stats-row">
      <div class="stat-item">
        <div class="num">120+</div>
        <div class="lbl">Students</div>
      </div>
      <div class="stat-item">
        <div class="num">45</div>
        <div class="lbl">Rooms</div>
      </div>
      <div class="stat-item">
        <div class="num">15</div>
        <div class="lbl">Employees</div>
      </div>
    </div>
  </div>

  <!-- Floating card -->
  <div class="login-card">

    <div class="card-logo">
      <div class="card-logo-icon"><i class="fas fa-hotel"></i></div>
      <div class="card-logo-text">
        <h2>Hostel Management</h2>
        <p>System v2.0</p>
      </div>
    </div>

    <h3>Welcome back!</h3>
    <p>Sign in to your admin account to continue.</p>

    <?php if ($error): ?>
    <div class="error-box">
      <i class="fas fa-exclamation-circle"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="field-group">
        <label>Email Address</label>
        <div class="field-wrap">
          <i class="fas fa-envelope field-icon"></i>
          <input type="email" name="email" placeholder="admin@hostel.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
      </div>

      <div class="field-group">
        <label>Password</label>
        <div class="field-wrap">
          <i class="fas fa-lock field-icon"></i>
          <input type="password" id="pw" name="password" placeholder="Enter your password" required>
          <button type="button" class="toggle-pw" onclick="togglePw()">
            <i class="fas fa-eye" id="pw-eye"></i>
          </button>
        </div>
      </div>

      <div class="login-meta">
        <label class="remember">
          <input type="checkbox" name="remember"> Remember me
        </label>
        <a href="#" class="forgot">Forgot Password?</a>
      </div>

      <button type="submit" class="btn-signin">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div class="card-footer">
      &copy; 2026 Hostel Management System. All rights reserved.
    </div>
  </div>

</div>

<script>
function togglePw() {
  const input = document.getElementById('pw');
  const icon  = document.getElementById('pw-eye');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fas fa-eye';
  }
}
</script>
</body>
</html>