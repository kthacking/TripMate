<?php
ob_start();
require_once 'db.php';
require_once 'auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkAdmin(); // Ensure only admins can access

// Helper function to log actions
function logActivity($conn, $user_id, $action, $details = "") {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
}

// Fetch settings with error handling
$settings = [];
$default_settings = [
    'registration_enabled' => '1',
    'media_uploads_enabled' => '1',
    'zip_downloads_enabled' => '1',
    'max_upload_size_mb' => '50',
    'maintenance_mode' => '0',
    'site_title' => 'TripMate',
    'auto_approval' => '0'
];

try {
    $s_res = $conn->query("SELECT * FROM settings");
    if ($s_res) {
        while($s = $s_res->fetch_assoc()) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }
    } else {
        $settings = $default_settings;
    }
} catch (Exception $e) {
    // If table doesn't exist, use defaults to prevent crash
    $settings = $default_settings;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin - Command Center</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="script.js" defer></script>
    <style>
        :root {
            --admin-primary: #6366F1;
            --admin-primary-dark: #4F46E5;
            --admin-bg: #F8FAFC;
            --admin-nav-bg: #0F172A;
            --admin-card-bg: #FFFFFF;
            --admin-text-main: #0F172A;
            --admin-text-muted: #64748B;
            --admin-border: #E2E8F0;
            --radius-2xl: 20px;
            --radius-xl: 16px;
            --radius-lg: 10px;
            --shadow-premium: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-active: 0 10px 15px -3px rgba(99, 102, 241, 0.12), 0 4px 6px -2px rgba(99, 102, 241, 0.05);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            background-color: var(--admin-bg);
            margin: 0;
            color: var(--admin-text-main);
            -webkit-font-smoothing: antialiased;
        }

        /* Top Navigation Bar */
        .admin-nav {
            background: var(--admin-nav-bg);
            height: 75px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            position: sticky;
            top: 0;
            z-index: 1001;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            box-sizing: border-box;
        }

        .admin-logo {
            font-size: 1.3rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            letter-spacing: -0.6px;
            flex-shrink: 0;
        }

        .admin-logo i {
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 1.1rem;
            color: white;
        }

        .admin-logo span { 
            background: linear-gradient(to right, #818CF8, #C084FC);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 8px;
            align-items: center;
        }

        .admin-menu-link {
            display: flex;
            align-items: center;
            padding: 10px 18px;
            border-radius: 12px;
            color: #94A3B8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            gap: 10px;
            white-space: nowrap;
        }

        .admin-menu-link i {
            font-size: 1.2rem;
        }

        .admin-menu-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        .admin-menu-link.active {
            background: var(--admin-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        /* Top Bar Profile & Status */
        .admin-nav-right {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-shrink: 0;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 5px 5px 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.2s;
            cursor: pointer;
        }

        .admin-profile:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .profile-info {
            text-align: right;
        }

        .profile-name {
            font-weight: 700;
            color: white;
            font-size: 0.88rem;
            display: block;
        }

        .profile-role {
            font-size: 0.7rem;
            color: #94A3B8;
            font-weight: 600;
        }

        .profile-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 0.9rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Main Content Container */
        .admin-main {
            min-height: calc(100vh - 75px);
            display: flex;
            flex-direction: column;
        }

        .admin-content {
            padding: 40px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Global UI Elements */
        /* Main Card System */
        .admin-card {
            background: var(--admin-card-bg);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid var(--admin-border);
            box-shadow: var(--shadow-premium);
            margin-bottom: 30px;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 16px;
            border: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .stat-card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08); 
            border-color: var(--admin-primary);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: inset 0 -4px 0 rgba(0,0,0,0.05);
        }

        .stat-label {
            font-size: 0.82rem;
            color: var(--admin-text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--admin-text-main);
            line-height: 1.2;
            margin: 0; /* Ensure margin is reset if needed */
            letter-spacing: -1.5px; /* Keep original letter spacing */
        }

        .stat-footer {
            font-size: 0.85rem;
            color: var(--admin-text-muted);
            border-top: 1px solid #F1F5F9;
            padding-top: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .admin-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .admin-table th {
            font-weight: 700;
            color: var(--admin-text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 18px 25px;
            background: #F8FAFC;
        }

        .admin-table td {
            padding: 18px 25px;
            font-size: 0.9rem;
            border-bottom: 1px solid #F1F5F9;
        }

        .admin-badge {
            padding: 6px 16px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-premium {
            background: var(--admin-primary);
            color: white;
            padding: 10px 22px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .btn-premium:hover {
            background: var(--admin-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
        }
    </style>
</head>
<body>

<nav class="admin-nav">
    <a href="admin_dashboard.php" class="admin-logo">
        <i class="ri-shield-user-fill"></i> Trip<span>Mate</span> Admin
    </a>

    <ul class="admin-menu">
        <li>
            <a href="admin_dashboard.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="ri-dashboard-2-line"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="admin_users.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">
                <i class="ri-user-settings-line"></i> Users
            </a>
        </li>
        <li>
            <a href="admin_trips.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_trips.php' ? 'active' : ''; ?>">
                <i class="ri-compass-3-line"></i> Trips
            </a>
        </li>
        <li>
            <a href="admin_requests.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_requests.php' ? 'active' : ''; ?>">
                <i class="ri-mail-check-line"></i> Requests
            </a>
        </li>
        <li>
            <a href="admin_media.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_media.php' ? 'active' : ''; ?>">
                <i class="ri-gallery-line"></i> Media
            </a>
        </li>
        <li>
            <a href="admin_analytics.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_analytics.php' ? 'active' : ''; ?>">
                <i class="ri-bar-chart-2-line"></i> Analytics
            </a>
        </li>
        <li>
            <a href="admin_logs.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_logs.php' ? 'active' : ''; ?>">
                <i class="ri-history-line"></i> Logs
            </a>
        </li>
        <li>
            <a href="admin_settings.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : ''; ?>">
                <i class="ri-settings-4-line"></i> Settings
            </a>
        </li>
    </ul>

    <div class="admin-nav-right">
        <a href="dashboard.php" style="color: #94A3B8; text-decoration: none; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;">
            <i class="ri-external-link-line"></i> View Site
        </a>
        <div class="admin-profile" onclick="location.href='admin_settings.php'">
            <div class="profile-info">
                <span class="profile-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <span class="profile-role">Master Administrator</span>
            </div>
            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
            </div>
        </div>
        <a href="logout.php" style="color: #EF4444; font-size: 1.3rem; margin-left: 5px;" title="Secure Logout">
            <i class="ri-logout-box-r-line"></i>
        </a>
    </div>
</nav>

<div class="admin-main">
    <div class="admin-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 style="font-size: 1.4rem; font-weight: 850; color: var(--admin-text-main); margin: 0; letter-spacing: -0.8px; display: flex; align-items: center; gap: 12px;">
                <?php 
                    $titles = [
                        'admin_dashboard.php' => 'Dashboard Overview',
                        'admin_users.php' => 'User Directory',
                        'admin_trips.php' => 'Trip Manifest',
                        'admin_media.php' => 'Digital Assets Library',
                        'admin_settings.php' => 'System Core Settings',
                        'admin_requests.php' => 'Enrollment Requests',
                        'admin_logs.php' => 'System Audit Trails',
                        'admin_analytics.php' => 'Global Platform Analytics'
                    ];
                    echo $titles[basename($_SERVER['PHP_SELF'])] ?? 'Administration';
                ?>
                <span style="background: #EEF2FF; color: #6366F1; padding: 4px 12px; border-radius: 40px; font-size: 0.65rem; font-weight: 800; letter-spacing: 0.5px; border: 1px solid #E0E7FF; display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; background: #6366F1; border-radius: 50%; display: inline-block;"></span>
                    LIVE MONITORING
                </span>
            </h2>
        </div>
