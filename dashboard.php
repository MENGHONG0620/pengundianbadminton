<?php
require_once 'config.php';
if (!isLoggedIn()) { header('Location: login.php'); exit(); }

// Semak sama ada pengguna telah mengundi untuk setiap jawatan
$user_votes = [];
foreach (['pengerusi', 'setiausaha', 'bendahari', 'jawatankuasa'] as $pos) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM undi WHERE id_pengguna = ? AND jawatan_calon = ?");
    $stmt->execute([$_SESSION['id_pengguna'], $pos]);
    $user_votes[$pos] = $stmt->fetchColumn() > 0;
}

// Dapatkan calon mengikut jawatan
$positions = ['pengerusi', 'setiausaha', 'bendahari', 'jawatankuasa'];
$candidates_by_position = [];
foreach ($positions as $position) {
    $stmt = $pdo->prepare("SELECT * FROM calon WHERE jawatan_calon = ? ORDER BY nama_calon");
    $stmt->execute([$position]);
    $candidates_by_position[$position] = $stmt->fetchAll();
}

// Semak julat tarikh pengundian
$current_date = date('Y-m-d');

$stmt = $pdo->prepare("SELECT nilai_tetapan FROM tetapan WHERE kunci_tetapan = 'tarikh_pengundian'");
$stmt->execute();
$date_json = $stmt->fetchColumn();
$dates = json_decode($date_json, true);
$start_date = $dates['mula'];
$end_date = $dates['tamat'];

$date_valid = ($current_date >= $start_date && $current_date <= $end_date);
$can_vote = $date_valid;

if ($_POST && isset($_POST['vote'])) {
    $candidate_id = (int)$_POST['candidate_id'];
    $position = $_POST['position'];
    
    if (!$can_vote) {
        $error = "Pengundian hanya boleh dibuat dari $start_date hingga $end_date!";
    } elseif (!$user_votes[$position]) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO undi (id_pengguna, id_calon, jawatan_calon) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['id_pengguna'], $candidate_id, $position]);
        
        $stmt = $pdo->prepare("UPDATE calon SET undi_calon = undi_calon + 1 WHERE id_calon = ?");
        $stmt->execute([$candidate_id]);
        
        $pdo->commit();
        
        $success = "Undi untuk jawatan $position berjaya direkodkan!";
        $user_votes[$position] = true;
    }
}

if ($_POST && isset($_POST['reset_vote'])) {
    $position = $_POST['position'];
    
    if ($user_votes[$position]) {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT id_calon FROM undi WHERE id_pengguna = ? AND jawatan_calon = ?");
        $stmt->execute([$_SESSION['id_pengguna'], $position]);
        $vote = $stmt->fetch();
        
        if ($vote) {
            $stmt = $pdo->prepare("DELETE FROM undi WHERE id_pengguna = ? AND jawatan_calon = ?");
            $stmt->execute([$_SESSION['id_pengguna'], $position]);
            
            $stmt = $pdo->prepare("UPDATE calon SET undi_calon = undi_calon - 1 WHERE id_calon = ?");
            $stmt->execute([$vote['id_calon']]);
            
            $pdo->commit();
            
            $success = "Undi untuk jawatan $position berjaya di set semula!";
            $user_votes[$position] = false;
        }
    }
}

// Dapatkan calon yang telah diundi oleh pengguna
$user_voted_candidates = [];
foreach ($positions as $position) {
    if ($user_votes[$position]) {
        $stmt = $pdo->prepare("SELECT c.nama_calon FROM undi u JOIN calon c ON u.id_calon = c.id_calon WHERE u.id_pengguna = ? AND u.jawatan_calon = ?");
        $stmt->execute([$_SESSION['id_pengguna'], $position]);
        $user_voted_candidates[$position] = $stmt->fetchColumn();
    }
}
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
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .dashboard-header {
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
            max-width: 1200px;
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
        .welcome-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            color: white;
        }
        .welcome-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: white;
            display: block;
            margin-bottom: 0.3rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            font-weight: 500;
        }
        .voting-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .candidate-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border-color: rgba(102, 126, 234, 0.3);
        }
        .candidate-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        .candidate-name {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        .candidate-votes {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .vote-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            width: 100%;
        }
        .vote-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .vote-btn:disabled {
            background: #10b981;
            cursor: not-allowed;
            transform: none;
        }
        .reset-section {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        .reset-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: 1px solid rgba(16, 185, 129, 0.5);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .user-vote-info {
            background: linear-gradient(135deg, #10b981, #059669);
            border: 1px solid rgba(16, 185, 129, 0.5);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .quick-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }
        .action-btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            animation: pulse 2s ease-in-out infinite;
        }
        .action-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            animation: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .action-btn:active {
            transform: translateY(-1px) scale(1.02);
            transition: all 0.1s ease;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <nav class="nav">
                <a href="dashboard.php" class="logo">
                    <i class="fas fa-vote-yea"></i> Kelab Badminton
                </a>
                <div class="nav-links">
                    <span style="color: white;">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</span>
                    <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                    <a href="results.php"><i class="fas fa-chart-bar"></i> Keputusan</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Keluar</a>
                </div>
            </nav>
        </header>

        <div class="welcome-section">
            <h1 class="welcome-title">🏸 SISTEM PENGUNDIAN JAWATANKUASA KELAB BADMINTON</h1>
            <p style="opacity: 0.9; font-size: 1.1rem;">Mengundi pengerusi, setiausaha, bendahari dan jawatankuasa lain yang utama dalam Kelab Badminton SMJK Chung Ling</p>
            <div style="margin: 1rem 0; padding: 1rem; background: <?php echo $can_vote ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; border-radius: 8px; border: 1px solid <?php echo $can_vote ? 'rgba(16, 185, 129, 0.5)' : 'rgba(239, 68, 68, 0.5)'; ?>;">
                <strong>Status: <?php echo $can_vote ? '✅ Pengundian BUKA' : '❌ Pengundian TUTUP'; ?></strong><br>
                <small>Tempoh: <?php echo $start_date; ?> hingga <?php echo $end_date; ?></small>
            </div>
            
            <div class="quick-actions">
                <a href="results.php" class="action-btn btn-primary">
                    <i class="fas fa-chart-line"></i> Lihat Keputusan Langsung
                </a>
                <a href="profile.php" class="action-btn btn-secondary">
                    <i class="fas fa-user-cog"></i> Urus Profil
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $user_votes['pengerusi'] ? '✅' : '❌'; ?></span>
                <span class="stat-label">Pengerusi</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $user_votes['setiausaha'] ? '✅' : '❌'; ?></span>
                <span class="stat-label">Setiausaha</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $user_votes['bendahari'] ? '✅' : '❌'; ?></span>
                <span class="stat-label">Bendahari</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $user_votes['jawatankuasa'] ? '✅' : '❌'; ?></span>
                <span class="stat-label">Jawatankuasa</span>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($user_voted_candidates)): ?>
            <div class="user-vote-info">
                <h4><i class="fas fa-check-circle"></i> Terima kasih kerana mengundi!</h4>
                <?php foreach ($user_voted_candidates as $position => $vote_name): ?>
                    <p><strong><?php echo ucfirst($position); ?>:</strong> <?php echo htmlspecialchars($vote_name); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php 
        $position_titles = [
            'pengerusi' => 'Pengerusi',
            'setiausaha' => 'Setiausaha', 
            'bendahari' => 'Bendahari',
            'jawatankuasa' => 'Jawatankuasa Lain'
        ];
        
        foreach ($positions as $position): 
        ?>
        <div class="voting-card">
            <h2 style="margin-bottom: 1.5rem; color: #374151; text-align: center;">
                <i class="fas fa-users"></i> <?php echo $position_titles[$position]; ?>
            </h2>
            
            <div class="candidates-grid">
                <?php foreach ($candidates_by_position[$position] as $candidate): ?>
                    <div class="candidate-card">
                        <?php if (!empty($candidate['gambar_calon'])): ?>
                            <img src="images/candidates/<?php echo htmlspecialchars($candidate['gambar_calon']); ?>" 
                                 alt="<?php echo htmlspecialchars($candidate['nama_calon']); ?>" 
                                 class="candidate-avatar" style="object-fit: cover; width: 80px; height: 80px; border-radius: 50%;" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="candidate-avatar" style="display: none;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php else: ?>
                            <div class="candidate-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div class="candidate-name"><?php echo htmlspecialchars($candidate['nama_calon']); ?></div>
                        <?php if (!empty($candidate['penerangan_calon'])): ?>
                            <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($candidate['penerangan_calon']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (!$user_votes[$position]): ?>
                            <?php if ($can_vote): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['id_calon']; ?>">
                                    <input type="hidden" name="position" value="<?php echo $position; ?>">
                                    <button type="submit" name="vote" class="vote-btn" 
                                            onclick="return confirm('Undi untuk <?php echo htmlspecialchars($candidate['nama_calon']); ?>?')">
                                        <i class="fas fa-vote-yea"></i> Undi Sekarang
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="vote-btn" disabled style="background: #6b7280;">
                                    <i class="fas fa-lock"></i> Pengundian Tutup
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="vote-btn" disabled>
                                <i class="fas fa-check"></i> 
                                <?php echo isset($user_voted_candidates[$position]) && $user_voted_candidates[$position] === $candidate['nama_calon'] ? 'Pilihan Anda' : 'Telah Mengundi'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($user_votes[$position]): ?>
                <div class="reset-section">
                    <h4 style="margin-bottom: 1rem; color: #374151;">Ingin tukar undi untuk <?php echo $position_titles[$position]; ?>?</h4>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="position" value="<?php echo $position; ?>">
                        <button type="submit" name="reset_vote" class="reset-btn"
                                onclick="return confirm('Set semula undi untuk <?php echo $position_titles[$position]; ?>?')">
                            <i class="fas fa-redo"></i> Set Semula Undi
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script src="main.js"></script>
</body>
</html>
