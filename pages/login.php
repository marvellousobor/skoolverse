<?php
session_start();
include_once '../config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$error = '';
$selected_login_type = $_POST['login_type'] ?? ROLE_STUDENT;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_type = $selected_login_type;
    $identifier = trim($_POST['identifier'] ?? '');
    $secret = trim($_POST['secret'] ?? '');
    $user = null;

    if ($login_type == ROLE_ADMIN || $login_type == ROLE_PARENT || $login_type == ROLE_TEACHER) {
        $email = filter_var($identifier, FILTER_SANITIZE_EMAIL);
        $sql = "SELECT * FROM users WHERE email = ? AND role = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $role = $login_type;
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $account = $result->fetch_assoc();
            if (password_verify($secret, $account['password_hash'])) {
                $user = $account;
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    } elseif ($login_type == ROLE_STUDENT) {
        $sql = "SELECT users.*
                FROM students
                INNER JOIN users ON students.user_id = users.id
                WHERE LOWER(students.last_name) = LOWER(?)
                AND students.admission_no = ?
                AND students.is_active = 1
                AND users.role = ?
                AND users.status = 'active'";
        $stmt = $conn->prepare($sql);
        $role = ROLE_STUDENT;
        $stmt->bind_param("sss", $identifier, $secret, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        } else {
            $error = "Invalid student last name or admission number";
        }
    } else {
        $error = "Invalid login type";
    }

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_time'] = time();
        header("Location: " . BASE_URL . "pages/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPMS — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #0F2B5B;
            --navy-d:  #081A3A;
            --blue:    #1A56DB;
            --blue-l:  #2563EB;
            --sky:     #EFF6FF;
            --red:     #DC2626;
            --red-l:   #FEE2E2;
            --white:   #FFFFFF;
            --gray-50: #F8FAFC;
            --gray-100:#F1F5F9;
            --gray-300:#CBD5E1;
            --gray-500:#64748B;
            --gray-700:#334155;
            --crystal1: rgba(255,255,255,0.18);
            --crystal2: rgba(99,179,237,0.25);
            --crystal3: rgba(255,255,255,0.07);
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-50);
            min-height: 100vh;
            display: flex;
            align-items: stretch;
        }

        /* ── LEFT PANEL ── */
        .left-panel {
            width: 420px;
            flex-shrink: 0;
            background: linear-gradient(155deg, var(--navy-d) 0%, var(--navy) 50%, #1A3A6E 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 48px 40px;
        }

        /* Crystal SVG decorations */
        .crystals {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .left-logo {
            position: relative;
            z-index: 2;
            margin-bottom: 48px;
        }
        .left-logo .logo-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        .left-logo .logo-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #3B82F6, #1E40AF);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: #fff; font-size: 18px;
            box-shadow: 0 4px 16px rgba(59,130,246,0.4);
        }
        .left-logo .logo-text {
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .left-logo .logo-sub {
            color: rgba(255,255,255,0.55);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .left-headline {
            position: relative; z-index: 2;
            margin-bottom: 36px;
        }
        .left-headline h2 {
            color: #fff;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }
        .left-headline p {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            line-height: 1.6;
        }

        .nav-features {
            position: relative; z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 14px;
            flex: 1;
        }
        .nav-feature {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: var(--crystal1);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            backdrop-filter: blur(8px);
            transition: background 0.2s;
        }
        .nav-feature:hover { background: rgba(255,255,255,0.22); }
        .nav-feature .feat-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .feat-blue  { background: rgba(59,130,246,0.35); }
        .feat-red   { background: rgba(220,38,38,0.35); }
        .feat-teal  { background: rgba(20,184,166,0.35); }
        .feat-gold  { background: rgba(245,158,11,0.35); }
        .nav-feature .feat-text strong {
            display: block;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
        }
        .nav-feature .feat-text span {
            color: rgba(255,255,255,0.5);
            font-size: 11.5px;
        }

        .left-footer {
            position: relative; z-index: 2;
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .left-footer p {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
        }

        /* ── RIGHT PANEL ── */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background: var(--white);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        .login-card h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .login-card .subtitle {
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 32px;
        }

        /* Error alert */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--red-l);
            border: 1px solid #FECACA;
            border-left: 4px solid var(--red);
            color: #991B1B;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
        }
        .alert-error .alert-icon { font-size: 16px; flex-shrink:0; }

        /* Role tabs */
        .role-tabs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            background: var(--gray-100);
            border-radius: 10px;
            padding: 4px;
            gap: 3px;
            margin-bottom: 24px;
        }
        .role-tab {
            padding: 8px 4px;
            border: none;
            background: transparent;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
            font-family: 'Inter', sans-serif;
        }
        .role-tab .tab-icon { font-size: 16px; line-height: 1; }
        .role-tab.active {
            background: var(--blue);
            color: #fff;
            box-shadow: 0 2px 8px rgba(26,86,219,0.35);
        }
        .role-tab:hover:not(.active) {
            background: var(--gray-300);
            color: var(--gray-700);
        }

        /* Hidden native select (still submitted in form) */
        #loginType { display: none; }

        /* Form fields */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }
        .form-group .input-wrap {
            position: relative;
        }
        .form-group .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--gray-500);
            pointer-events: none;
            width: 16px;
            text-align: center;
        }
        .form-group input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid var(--gray-300);
            border-radius: 9px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--gray-700);
            background: var(--white);
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }
        .form-group input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(26,86,219,0.12);
        }
        .form-group input::placeholder { color: var(--gray-300); }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--blue-l), var(--blue));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            letter-spacing: 0.2px;
            transition: opacity 0.15s, transform 0.1s;
            box-shadow: 0 4px 14px rgba(26,86,219,0.35);
        }
        .btn-login:hover { opacity: 0.92; transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        /* Demo box */
        .demo-box {
            margin-top: 24px;
            background: var(--sky);
            border: 1px solid #BFDBFE;
            border-radius: 10px;
            padding: 14px 16px;
        }
        .demo-box .demo-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--blue);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .demo-box ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .demo-box ul li {
            font-size: 12px;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .demo-box ul li::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--blue);
            flex-shrink: 0;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 860px) {
            body { flex-direction: column; }
            .left-panel {
                width: 100%;
                padding: 32px 24px;
                min-height: auto;
            }
            .nav-features {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }
            .left-headline h2 { font-size: 22px; }
        }
        @media (max-width: 520px) {
            .nav-features { grid-template-columns: 1fr; }
            .right-panel { padding: 32px 16px; }
            .role-tabs { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

    <!-- ═══════════════════════════ LEFT PANEL ═══════════════════════════ -->
    <div class="left-panel">

        <!-- Crystal SVG decorations -->
        <svg class="crystals" viewBox="0 0 420 900" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <!-- Large top-right crystal cluster -->
            <polygon points="320,0 420,0 420,140 370,80" fill="rgba(255,255,255,0.07)"/>
            <polygon points="350,0 420,0 420,90" fill="rgba(99,179,237,0.12)"/>
            <polygon points="280,0 420,0 390,60 310,40" fill="rgba(255,255,255,0.05)"/>
            <polygon points="380,20 420,0 420,60 400,70" fill="rgba(147,197,253,0.15)"/>
            <!-- Mid-left shard -->
            <polygon points="0,300 60,260 80,340 20,370" fill="rgba(255,255,255,0.06)"/>
            <polygon points="0,260 50,240 55,295 0,305" fill="rgba(99,179,237,0.10)"/>
            <!-- Bottom-right large crystal -->
            <polygon points="300,720 420,680 420,900 260,900" fill="rgba(255,255,255,0.05)"/>
            <polygon points="360,760 420,740 420,860 380,900 310,870" fill="rgba(147,197,253,0.09)"/>
            <polygon points="400,800 420,790 420,900 395,900" fill="rgba(255,255,255,0.08)"/>
            <!-- Accent: small top-left -->
            <polygon points="0,0 80,0 50,50 0,60" fill="rgba(255,255,255,0.06)"/>
            <polygon points="0,0 40,0 20,30 0,25" fill="rgba(99,179,237,0.10)"/>
            <!-- Center subtle -->
            <polygon points="160,440 220,410 230,490 175,495" fill="rgba(255,255,255,0.04)"/>
            <!-- Red crystal accent (top right echo) -->
            <polygon points="390,100 420,90 420,160 395,155" fill="rgba(220,38,38,0.12)"/>
        </svg>

        <!-- Logo -->
        <div class="left-logo">
            <div class="logo-badge">
                <div class="logo-icon">SP</div>
                <div>
                    <div class="logo-text">SPMS</div>
                    <div class="logo-sub">School Portal Management</div>
                </div>
            </div>
        </div>

        <!-- Headline -->
        <div class="left-headline">
            <h2>Everything your school needs, in one place.</h2>
            <p>Manage students, fees, results, and communication — securely and efficiently.</p>
        </div>

        <!-- Feature nav cards -->
        <div class="nav-features">
            <div class="nav-feature">
                <div class="feat-icon feat-blue"><i class="fas fa-graduation-cap"></i></div>
                <div class="feat-text">
                    <strong>Student Records</strong>
                    <span>Profiles, classes & admissions</span>
                </div>
            </div>
            <div class="nav-feature">
                <div class="feat-icon feat-gold"><i class="fas fa-credit-card"></i></div>
                <div class="feat-text">
                    <strong>Fee Management</strong>
                    <span>Payments & outstanding tracking</span>
                </div>
            </div>
            <div class="nav-feature">
                <div class="feat-icon feat-teal"><i class="fas fa-chart-bar"></i></div>
                <div class="feat-text">
                    <strong>Results &amp; Reports</strong>
                    <span>Upload, publish &amp; download</span>
                </div>
            </div>
            <div class="nav-feature">
                <div class="feat-icon feat-red"><i class="fas fa-people-roof"></i></div>
                <div class="feat-text">
                    <strong>Parent Portal</strong>
                    <span>Stay updated on your child</span>
                </div>
            </div>
        </div>

        <div class="left-footer">
            <p>© <?php echo date('Y'); ?> SPMS · Secure School Portal · All rights reserved</p>
        </div>
    </div>

    <!-- ═══════════════════════════ RIGHT PANEL ══════════════════════════ -->
    <div class="right-panel">
        <div class="login-card">
            <h1>Welcome back <i class="fas fa-hand-wave" style="color:var(--blue);font-size:22px;vertical-align:middle;"></i></h1>
            <p class="subtitle">Sign in to your school portal account</p>

            <?php if ($error): ?>
                <div class="alert-error">
                    <span class="alert-icon"><i class="fas fa-triangle-exclamation"></i></span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Hidden select that gets updated by JS -->
                <select id="loginType" name="login_type" aria-hidden="true">
                    <option value="<?php echo ROLE_STUDENT; ?>" <?php echo $selected_login_type == ROLE_STUDENT ? 'selected' : ''; ?>>Student</option>
                    <option value="<?php echo ROLE_ADMIN; ?>"   <?php echo $selected_login_type == ROLE_ADMIN   ? 'selected' : ''; ?>>Admin</option>
                    <option value="<?php echo ROLE_PARENT; ?>"  <?php echo $selected_login_type == ROLE_PARENT  ? 'selected' : ''; ?>>Parent</option>
                    <option value="<?php echo ROLE_TEACHER; ?>" <?php echo $selected_login_type == ROLE_TEACHER ? 'selected' : ''; ?>>Teacher</option>
                </select>

                <!-- Visual role tabs -->
                <div class="role-tabs" role="tablist">
                    <button type="button" class="role-tab <?php echo $selected_login_type == ROLE_STUDENT ? 'active' : ''; ?>"
                            data-role="<?php echo ROLE_STUDENT; ?>" role="tab">
                    <span class="tab-icon"><i class="fas fa-graduation-cap"></i></span> Student
                    </button>
                    <button type="button" class="role-tab <?php echo $selected_login_type == ROLE_ADMIN ? 'active' : ''; ?>"
                            data-role="<?php echo ROLE_ADMIN; ?>" role="tab">
                        <span class="tab-icon"><i class="fas fa-shield-halved"></i></span> Admin
                    </button>
                    <button type="button" class="role-tab <?php echo $selected_login_type == ROLE_PARENT ? 'active' : ''; ?>"
                            data-role="<?php echo ROLE_PARENT; ?>" role="tab">
                        <span class="tab-icon"><i class="fas fa-people-roof"></i></span> Parent
                    </button>
                    <button type="button" class="role-tab <?php echo $selected_login_type == ROLE_TEACHER ? 'active' : ''; ?>"
                            data-role="<?php echo ROLE_TEACHER; ?>" role="tab">
                        <span class="tab-icon"><i class="fas fa-chalkboard-user"></i></span> Teacher
                    </button>
                </div>

                <!-- Identifier field -->
                <div class="form-group">
                    <label id="identifierLabel">Last Name</label>
                    <div class="input-wrap">
                        <span class="input-icon" id="identifierIcon"><i class="fas fa-user"></i></span>
                        <input id="identifierInput" type="text" name="identifier"
                               placeholder="Student last name"
                               autocomplete="username" required>
                    </div>
                </div>

                <!-- Secret field -->
                <div class="form-group">
                    <label id="secretLabel">Admission Number</label>
                    <div class="input-wrap">
                        <span class="input-icon" id="secretIcon"><i class="fas fa-hashtag"></i></span>
                        <input id="secretInput" type="text" name="secret"
                               placeholder="Admission number"
                               autocomplete="off" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">Sign In &nbsp;<i class="fas fa-arrow-right"></i></button>
            </form>

            <!-- Demo credentials -->
            <div class="demo-box">
                <div class="demo-title"><i class="fas fa-key"></i> Demo Credentials</div>
                <ul>
                    <li><strong>Admin:</strong>&nbsp; admin@123 / admin@123</li>
                    <li><strong>Student:</strong>&nbsp; Last name + Admission number</li>
                    <li><strong>Parent:</strong>&nbsp; Email + Generated password</li>
                    <li><strong>Teacher:</strong>&nbsp; Email + Password</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        const loginTypeSelect  = document.getElementById('loginType');
        const identifierLabel  = document.getElementById('identifierLabel');
        const identifierInput  = document.getElementById('identifierInput');
        const identifierIcon   = document.getElementById('identifierIcon');
        const secretLabel      = document.getElementById('secretLabel');
        const secretInput      = document.getElementById('secretInput');
        const secretIcon       = document.getElementById('secretIcon');
        const roleTabs         = document.querySelectorAll('.role-tab');

        const STUDENT_ROLE = '<?php echo ROLE_STUDENT; ?>';
        const ADMIN_ROLE   = '<?php echo ROLE_ADMIN; ?>';
        const TEACHER_ROLE = '<?php echo ROLE_TEACHER; ?>';

        function updateLoginFields() {
            const v = loginTypeSelect.value;
            const isStudent = v === STUDENT_ROLE;
            const isAdmin   = v === ADMIN_ROLE;
            const isTeacher = v === TEACHER_ROLE;

            if (isStudent) {
                identifierLabel.textContent = 'Last Name';
                identifierInput.type        = 'text';
                identifierInput.placeholder = 'Student last name';
                identifierInput.autocomplete= 'username';
                identifierIcon.innerHTML    = '<i class="fas fa-user"></i>';

                secretLabel.textContent     = 'Admission Number';
                secretInput.type            = 'text';
                secretInput.placeholder     = 'e.g. ADM-2024-001';
                secretInput.autocomplete    = 'off';
                secretIcon.innerHTML        = '<i class="fas fa-hashtag"></i>';
            } else if (isAdmin) {
                identifierLabel.textContent = 'Gmail Address';
                identifierInput.type        = 'email';
                identifierInput.placeholder = 'admin@gmail.com';
                identifierInput.autocomplete= 'email';
                identifierIcon.innerHTML    = '<i class="fas fa-envelope"></i>';

                secretLabel.textContent     = 'Password';
                secretInput.type            = 'password';
                secretInput.placeholder     = 'Your password';
                secretInput.autocomplete    = 'current-password';
                secretIcon.innerHTML        = '<i class="fas fa-lock"></i>';
            } else if (isTeacher) {
                identifierLabel.textContent = 'Email Address';
                identifierInput.type        = 'email';
                identifierInput.placeholder = 'teacher@example.com';
                identifierInput.autocomplete= 'email';
                identifierIcon.innerHTML    = '<i class="fas fa-envelope"></i>';

                secretLabel.textContent     = 'Password';
                secretInput.type            = 'password';
                secretInput.placeholder     = 'Your password';
                secretInput.autocomplete    = 'current-password';
                secretIcon.innerHTML        = '<i class="fas fa-lock"></i>';
            } else {
                // Parent
                identifierLabel.textContent = 'Email Address';
                identifierInput.type        = 'email';
                identifierInput.placeholder = 'parent@example.com';
                identifierInput.autocomplete= 'email';
                identifierIcon.innerHTML    = '<i class="fas fa-envelope"></i>';

                secretLabel.textContent     = 'Password';
                secretInput.type            = 'password';
                secretInput.placeholder     = 'Your password';
                secretInput.autocomplete    = 'current-password';
                secretIcon.innerHTML        = '<i class="fas fa-lock"></i>';
            }
        }

        roleTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                roleTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                loginTypeSelect.value = tab.dataset.role;
                updateLoginFields();
                identifierInput.value = '';
                secretInput.value = '';
                identifierInput.focus();
            });
        });

        updateLoginFields();
    </script>
</body>
</html>