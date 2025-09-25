<?php
require_once 'config.php';

// Dapatkan calon mengikut jawatan
$positions = ['pengerusi', 'setiausaha', 'bendahari', 'jawatankuasa'];
$candidates_by_position = [];
foreach ($positions as $position) {
    $stmt = $pdo->prepare("SELECT * FROM calon WHERE jawatan_calon = ? ORDER BY undi_calon DESC, nama_calon");
    $stmt->execute([$position]);
    $candidates_by_position[$position] = $stmt->fetchAll();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM undi");
$stmt->execute();
$total_votes = $stmt->fetchColumn();

// Kira undi untuk setiap jawatan
$voted_counts = [];
foreach ($positions as $position) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM undi WHERE jawatan_calon = ?");
    $stmt->execute([$position]);
    $voted_counts[$position] = $stmt->fetchColumn();
}

// Dapatkan tarikh pengundian untuk semak status
$stmt = $pdo->prepare("SELECT nilai_tetapan FROM tetapan WHERE kunci_tetapan = 'tarikh_pengundian'");
$stmt->execute();
$date_json = $stmt->fetchColumn();
$dates = json_decode($date_json, true);
$start_date = $dates['mula'];
$end_date = $dates['tamat'];

$current_date = date('Y-m-d');
$voting_open = ($current_date >= $start_date && $current_date <= $end_date);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .results-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 15px;
        }
        .results-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 1rem 0;
            margin-bottom: 1.5rem;
            border-radius: 12px;
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
        .hero-section {
            text-align: center;
            color: white;
            margin: 1rem 0;
            padding: 1.5rem;
        }
        .hero-title {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
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
            font-size: 1rem;
            font-weight: 500;
        }
        .results-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .results-table th,
        .results-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .results-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            border-radius: 8px 8px 0 0;
        }
        .results-table tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
        }
        .winner-row {
            background: linear-gradient(135deg, #fef3c7, #fde68a) !important;
            font-weight: bold;
        }
        .rank-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .rank-1 {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #b45309;
        }
        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0, #e5e5e5);
            color: #374151;
        }
        .rank-3 {
            background: linear-gradient(135deg, #cd7f32, #daa520);
            color: white;
        }
        .no-votes {
            text-align: center;
            padding: 4rem;
            color: #6b7280;
        }
        .no-votes i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>
    <div class="results-container">
        <header class="results-header">
            <nav class="nav">
                <a href="index.html" class="logo">
                    <i class="fas fa-trophy"></i> Kelab Badminton
                </a>
                <div class="nav-links">
                    <?php if (isLoggedIn()): ?>
                        <span style="color: white; margin-right: 1rem;">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</span>
                        <a href="dashboard.php">Panel</a>
                        <a href="logout.php">Log Keluar</a>
                    <?php else: ?>
                        <a href="dashboard.php">Panel</a>
                        <a href="login.php">Log Masuk</a>
                    <?php endif; ?>
                </div>
            </nav>
        </header>

        <div class="hero-section">
            <h1 class="hero-title">🏆 Keputusan Pengundian</h1>
            <p class="hero-subtitle">SISTEM PENGUNDIAN JAWATANKUASA KELAB BADMINTON SMJK Chung Ling</p>
            <div style="margin-top: 1rem; padding: 1rem; background: <?php echo $voting_open ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; border-radius: 8px; border: 1px solid <?php echo $voting_open ? 'rgba(16, 185, 129, 0.5)' : 'rgba(239, 68, 68, 0.5)'; ?>;">
                <strong><?php echo $voting_open ? '✅ Pengundian Masih BUKA' : '❌ Pengundian Telah DITUTUP'; ?></strong>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $voted_counts['pengerusi']; ?></span>
                <span class="stat-label">Undi Pengerusi</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $voted_counts['setiausaha']; ?></span>
                <span class="stat-label">Undi Setiausaha</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $voted_counts['bendahari']; ?></span>
                <span class="stat-label">Undi Bendahari</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $voted_counts['jawatankuasa']; ?></span>
                <span class="stat-label">Undi Jawatankuasa</span>
            </div>
        </div>



        <?php 
        $position_titles = [
            'pengerusi' => 'Pengerusi',
            'setiausaha' => 'Setiausaha', 
            'bendahari' => 'Bendahari',
            'jawatankuasa' => 'Jawatankuasa Lain'
        ];
        
        foreach ($positions as $position): 
        ?>
        <div class="results-card">
            <h3 style="margin-bottom: 1.5rem; color: #374151; font-size: 1.5rem;">
                <i class="fas fa-list-ol"></i> Kedudukan <?php echo $position_titles[$position]; ?>
            </h3>
            
            <?php if ($voted_counts[$position] == 0): ?>
                <div class="no-votes">
                    <i class="fas fa-vote-yea"></i>
                    <h4>Tiada undi untuk <?php echo $position_titles[$position]; ?> lagi</h4>
                    <p>Jadilah yang pertama mengundi!</p>
                </div>
            <?php else: ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Kedudukan</th>
                            <th>Calon</th>
                            <th>Undi</th>
                            <th>Peratusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates_by_position[$position] as $index => $candidate): ?>
                            <tr <?php echo $index === 0 && $candidate['Undi_calon'] > 0 ? 'class="winner-row"' : ''; ?>>
                                <td>
                                    <?php if ($index === 0 && $candidate['Undi_calon'] > 0): ?>
                                        <span class="rank-badge rank-1">
                                            <i class="fas fa-crown"></i> #<?php echo $index + 1; ?>
                                        </span>
                                    <?php elseif ($index === 1 && $candidate['Undi_calon'] > 0): ?>
                                        <span class="rank-badge rank-2">
                                            <i class="fas fa-medal"></i> #<?php echo $index + 1; ?>
                                        </span>
                                    <?php elseif ($index === 2 && $candidate['Undi_calon'] > 0): ?>
                                        <span class="rank-badge rank-3">
                                            <i class="fas fa-award"></i> #<?php echo $index + 1; ?>
                                        </span>
                                    <?php else: ?>
                                        #<?php echo $index + 1; ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($candidate['Nama_calon']); ?></strong></td>
                                <td><?php echo $candidate['Undi_calon']; ?></td>
                                <td>
                                    <?php 
                                    $position_total = $voted_counts[$position];
                                    $percentage = $position_total > 0 ? round(($candidate['Undi_calon'] / $position_total) * 100, 1) : 0;
                                    echo $percentage . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>



    <script src="main.js"></script>
</body>
</html>
