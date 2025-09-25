<?php
require_once 'config.php';
if (!isLoggedIn() || !isAdmin()) { header('Location: login.php'); exit(); }

// Dapatkan calon mengikut jawatan
$positions = ['pengerusi', 'setiausaha', 'bendahari', 'jawatankuasa'];
$candidates_by_position = [];
foreach ($positions as $position) {
    $stmt = $pdo->prepare("SELECT * FROM calon WHERE jawatan_calon = ? ORDER BY undi_calon DESC");
    $stmt->execute([$position]);
    $candidates_by_position[$position] = $stmt->fetchAll();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM undi");
$stmt->execute();
$total_votes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE peranan = 'pengguna'");
$stmt->execute();
$total_users = $stmt->fetchColumn();

// Kira undi untuk setiap jawatan
$voted_counts = [];
foreach ($positions as $position) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM undi WHERE jawatan_calon = ?");
    $stmt->execute([$position]);
    $voted_counts[$position] = $stmt->fetchColumn();
}

if ($_POST && isset($_POST['reset_votes'])) {
    $pdo->exec("DELETE FROM undi");
    $pdo->exec("UPDATE calon SET undi_calon = 0");
    header('Location: admin.php');
    exit();
}

if ($_POST && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    $pdo->exec("DELETE FROM undi WHERE id_pengguna = $user_id");
    $pdo->exec("DELETE FROM pengguna WHERE id = $user_id AND peranan = 'pengguna'");
    header('Location: admin.php');
    exit();
}

if ($_POST && isset($_POST['update_dates'])) {
    $start_date = $_POST['tarikh_mula'];
    $end_date = $_POST['tarikh_tamat'];
    
    $date_json = json_encode(['mula' => $start_date, 'tamat' => $end_date]);
    $stmt = $pdo->prepare("UPDATE tetapan SET nilai_tetapan = ? WHERE kunci_tetapan = 'tarikh_pengundian'");
    $stmt->execute([$date_json]);
    
    $date_success = "Tarikh pengundian berjaya dikemaskini!";
}

// Dapatkan tarikh pengundian
$stmt = $pdo->prepare("SELECT nilai_tetapan FROM tetapan WHERE kunci_tetapan = 'tarikh_pengundian'");
$stmt->execute();
$date_json = $stmt->fetchColumn();
$dates = json_decode($date_json, true);
$start_date = $dates['mula'];
$end_date = $dates['tamat'];

// Semak sama ada tarikh semasa dalam tempoh pengundian
$current_date = date('Y-m-d');
$voting_open = ($current_date >= $start_date && $current_date <= $end_date);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - SISTEM PENGUNDIAN JAWATANKUASA KELAB BADMINTON</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; }
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
        .card { background: rgba(255,255,255,0.95); border-radius: 16px; padding: 2rem; margin: 1rem 0; box-shadow: 0 8px 32px rgba(0,0,0,0.1); transition: all 0.4s ease; }
        .card:hover { transform: translateY(-5px) rotateX(2deg); box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stats .stat-card { animation: bounceIn 0.8s ease-out; animation-fill-mode: both; }
        @keyframes bounceIn { 0% { transform: scale(0.3); opacity: 0; } 50% { transform: scale(1.05); } 70% { transform: scale(0.9); } 100% { transform: scale(1); opacity: 1; } }
        .stat-card { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1.5rem; border-radius: 12px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; display: block; animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        @keyframes wiggle { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(1deg); } 75% { transform: rotate(-1deg); } }
        .logo:hover { animation: wiggle 0.5s ease-in-out; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-2px); } 75% { transform: translateX(2px); } }
        .btn:active { animation: shake 0.3s ease-in-out; }
        .fas { transition: all 0.3s ease; }
        .fas:hover { transform: scale(1.2) rotate(10deg); color: #ffd700; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin: 0.5rem; text-decoration: none; display: inline-block; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-info { background: #3b82f6; color: white; }
        .btn { margin: 0.25rem; }
        .table { font-size: 0.9rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table tbody tr { transition: all 0.3s ease; animation: fadeInLeft 0.6s ease-out; animation-fill-mode: both; }
        @keyframes fadeInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .table th { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .chart-container { background: white; padding: 2rem; border-radius: 12px; margin: 2rem 0; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <nav class="nav">
                <a href="admin.php" class="logo">
                    <i class="fas fa-shield-alt"></i> Admin Kelab Badminton
                </a>
                <div class="nav-links">
                    <span style="color: white;">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</span>
                    <a href="user_management.php">Pengurusan Pengguna</a>
                    <a href="update_contestants.php">Kemaskini Peserta</a>
                    <a href="logout.php">Log Keluar</a>
                </div>
            </nav>
        </header>

        <div class="stats">
            <div class="stat-card">
                <span class="stat-number"><?php echo $voted_counts['pengerusi']; ?>/<?php echo $total_users; ?></span>
                <span>Pengerusi</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $voted_counts['setiausaha']; ?>/<?php echo $total_users; ?></span>
                <span>Setiausaha</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $voted_counts['bendahari']; ?>/<?php echo $total_users; ?></span>
                <span>Bendahari</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $voted_counts['jawatankuasa']; ?>/<?php echo $total_users; ?></span>
                <span>Jawatankuasa</span>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 1rem;">Kawalan Sistem Pengundian Jawatankuasa Kelab Badminton Admin</h2>
            <div style="margin-bottom: 1rem; padding: 1rem; background: <?php echo $voting_open ? '#10b981' : '#ef4444'; ?>; color: white; border-radius: 8px; text-align: center;">
                <strong>Status Pengundian: <?php echo $voting_open ? 'BUKA' : 'TUTUP'; ?></strong>
            </div>

            <form method="POST" style="display: inline;">
                <button type="submit" name="reset_votes" class="btn btn-danger" style="padding: 0.75rem 1.5rem; font-size: 1rem; min-width: 200px;"
                        onclick="return confirm('Reset all votes?')">
                    <i class="fas fa-redo"></i> Set Semula Semua Undi
                </button>
            </form>
            <a href="export.php" class="btn btn-info" style="padding: 0.75rem 1.5rem; font-size: 1rem; min-width: 200px; text-decoration: none; display: inline-block; text-align: center;">
                <i class="fas fa-download"></i> Eksport Keputusan
            </a>
        </div>

        <?php if (isset($date_success)): ?>
            <div style="background: #10b981; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                <i class="fas fa-check-circle"></i> <?php echo $date_success; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom: 1rem;"><i class="fas fa-calendar-alt"></i> Tetapan Tarikh Pengundian</h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Tarikh Mula:</label>
                        <input type="date" name="tarikh_mula" value="<?php echo $start_date; ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Tarikh Tamat:</label>
                        <input type="date" name="tarikh_tamat" value="<?php echo $end_date; ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" required>
                    </div>
                    <div>
                        <button type="submit" name="update_dates" style="background: #667eea; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            <i class="fas fa-save"></i> Kemaskini
                        </button>
                    </div>
                </div>
            </form>
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
        <div class="card">
            <h3 style="margin-bottom: 1rem;"><?php echo $position_titles[$position]; ?> - Kiraan Undi</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Kedudukan</th>
                        <th>Nama</th>
                        <th>Undi</th>
                        <th>Peratusan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates_by_position[$position] as $index => $candidate): ?>
                        <tr>
                            <td>
                                <?php if ($index === 0 && $candidate['Undi_calon'] > 0): ?>
                                    <i class="fas fa-crown" style="color: #ffd700;"></i> #<?php echo $index + 1; ?>
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
        </div>
        <?php endforeach; ?>
        
        <?php if ($total_votes > 0): ?>
        <div class="card">
            <h3 style="margin-bottom: 1rem;">Carta Keputusan Mengikut Jawatan</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <?php foreach ($positions as $position): ?>
                    <?php if ($voted_counts[$position] > 0): ?>
                    <div style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <h4 style="text-align: center; margin-bottom: 1rem; color: #374151;"><?php echo $position_titles[$position]; ?></h4>
                        <canvas id="chart_<?php echo $position; ?>" width="300" height="300"></canvas>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($total_votes > 0): ?>
    <script>
        <?php foreach ($positions as $position): ?>
            <?php if ($voted_counts[$position] > 0): ?>
            {
                const ctx_<?php echo $position; ?> = document.getElementById('chart_<?php echo $position; ?>').getContext('2d');
                new Chart(ctx_<?php echo $position; ?>, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_map(function($c) { return $c['Nama_calon']; }, $candidates_by_position[$position])); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_map(function($c) { return $c['Undi_calon']; }, $candidates_by_position[$position])); ?>,
                            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#fee140', '#a8edea', '#d299c2']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 10,
                                    usePointStyle: true,
                                    font: { size: 10 }
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
        <?php endforeach; ?>
    </script>
    <?php endif; ?>

</body>
</html>
