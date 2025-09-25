<?php
require_once 'config.php';
if (!isLoggedIn()) { header('Location: login.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
$stmt->execute([$_SESSION['id_pengguna']]);
$user_data = $stmt->fetch();

if ($_POST && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    
    // Semak sama ada emel sudah digunakan oleh pengguna lain
    $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE emel = ? AND id_pengguna != ?");
    $stmt->execute([$email, $_SESSION['id_pengguna']]);
    
    if ($stmt->fetch()) {
        $error = 'Emel sudah wujud!';
    } else {
        $stmt = $pdo->prepare("UPDATE pengguna SET nama = ?, emel = ? WHERE id_pengguna = ?");
        if ($stmt->execute([$name, $email, $_SESSION['id_pengguna']])) {
            $_SESSION['nama'] = $name;
            $success = 'Profil berjaya dikemaskini!';
            $user_data['nama'] = $name;
            $user_data['emel'] = $email;
        }
    }
}

if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($current_password, $user_data['kata_laluan'])) {
        $password_error = 'Kata laluan semasa tidak betul!';
    } elseif ($new_password !== $confirm_password) {
        $password_error = 'Kata laluan baru tidak sepadan!';
    } elseif (strlen($new_password) < 6) {
        $password_error = 'Kata laluan mestilah sekurang-kurangnya 6 aksara!';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE pengguna SET kata_laluan = ? WHERE id_pengguna = ?");
        if ($stmt->execute([$hashed_password, $_SESSION['id_pengguna']])) {
            $password_success = 'Kata laluan berjaya ditukar!';
        }
    }
}

// Dapatkan sejarah pengundian pengguna
$stmt = $pdo->prepare("SELECT c.nama_calon, u.masa_undi FROM undi u JOIN calon c ON u.id_calon = c.id_calon WHERE u.id_pengguna = ? ORDER BY u.masa_undi DESC");
$stmt->execute([$_SESSION['id_pengguna']]);
$voting_history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengundian Jawatankuasa Kelab Badminton</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .profile-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            margin-bottom: 2rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            margin: 1.5rem 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 3rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border: 1px solid;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border-color: rgba(16, 185, 129, 0.3);
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            display: block;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .history-item {
            padding: 1rem;
            border-left: 4px solid #667eea;
            background: #f8fafc;
            margin-bottom: 0.5rem;
            border-radius: 0 8px 8px 0;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <header class="profile-header">
            <nav class="nav">
                <a href="profile.php" class="logo">
                    <i class="fas fa-user-cog"></i> Tetapan Profil
                </a>
                <div class="nav-links">
                    <a href="dashboard.php"><i class="fas fa-vote-yea"></i> Panel</a>
                    <a href="results.php"><i class="fas fa-chart-bar"></i> Keputusan</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Keluar</a>
                </div>
            </nav>
        </header>

        <div class="profile-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user_data['nama'], 0, 1)); ?>
                </div>
                <h2 style="color: #374151; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($user_data['nama']); ?></h2>
                <p style="color: #6b7280;"><?php echo htmlspecialchars($user_data['emel']); ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php 
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM undi WHERE id_pengguna = ?");
                        $stmt->execute([$_SESSION['id_pengguna']]);
                        echo $stmt->fetchColumn();
                    ?></span>
                    <span class="stat-label">Undi Dibuang</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo ucfirst($user_data['peranan']); ?></span>
                    <span class="stat-label">Jenis Akaun</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo date('M Y'); ?></span>
                    <span class="stat-label">Ahli Sejak</span>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <h3 style="margin-bottom: 1.5rem; color: #374151;">
                <i class="fas fa-edit"></i> Kemaskini Maklumat Profil
            </h3>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nama Penuh</label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user_data['nama']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat Emel</label>
                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user_data['emel']); ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kemaskini Profil
                </button>
            </form>
        </div>

        <div class="profile-card">
            <h3 style="margin-bottom: 1.5rem; color: #374151;">
                <i class="fas fa-key"></i> Tukar Kata Laluan
            </h3>

            <?php if (isset($password_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $password_success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($password_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $password_error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Kata Laluan Semasa</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kata Laluan Baru</label>
                    <input type="password" name="new_password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Sahkan Kata Laluan Baru</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-danger">
                    <i class="fas fa-key"></i> Tukar Kata Laluan
                </button>
            </form>
        </div>

        <?php if (!empty($voting_history)): ?>
        <div class="profile-card">
            <h3 style="margin-bottom: 1.5rem; color: #374151;">
                <i class="fas fa-history"></i> Sejarah Pengundian
            </h3>
            <?php foreach ($voting_history as $vote): ?>
                <div class="history-item">
                    <strong>Mengundi untuk: <?php echo htmlspecialchars($vote['nama_calon']); ?></strong><br>
                    <small style="color: #6b7280;">
                        <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($vote['masa_undi'])); ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="main.js"></script>
</body>
</html>
