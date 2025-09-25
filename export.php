<?php
require_once 'config.php';
if (!isLoggedIn() || !isAdmin()) { 
    header('Location: login.php'); 
    exit(); 
}

// Dapatkan calon mengikut jawatan
$positions = ['pengerusi', 'setiausaha', 'bendahari', 'jawatankuasa'];
$position_titles = [
    'pengerusi' => 'Pengerusi',
    'setiausaha' => 'Setiausaha', 
    'bendahari' => 'Bendahari',
    'jawatankuasa' => 'Jawatankuasa Lain'
];

$candidates_by_position = [];
$voted_counts = [];
foreach ($positions as $position) {
    $stmt = $pdo->prepare("SELECT * FROM calon WHERE jawatan_calon = ? ORDER BY undi_calon DESC");
    $stmt->execute([$position]);
    $candidates_by_position[$position] = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM undi WHERE jawatan_calon = ?");
    $stmt->execute([$position]);
    $voted_counts[$position] = $stmt->fetchColumn();
}

// Tetapkan header untuk muat turun CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="keputusan_jawatankuasa_badminton_' . date('Y-m-d') . '.csv"');

// Cipta kandungan CSV
$output = fopen('php://output', 'w');

// Eksport keputusan mengikut jawatan
foreach ($positions as $position) {
    // Header jawatan
    fputcsv($output, []);
    fputcsv($output, [$position_titles[$position]]);
    fputcsv($output, ['Kedudukan', 'Nama Calon', 'Undi', 'Peratusan']);
    
    // Data jawatan
    foreach ($candidates_by_position[$position] as $index => $candidate) {
        $position_total = $voted_counts[$position];
        $percentage = $position_total > 0 ? round(($candidate['Undi_calon'] / $position_total) * 100, 1) : 0;
        fputcsv($output, [
            $index + 1,
            $candidate['Nama_calon'],
            $candidate['Undi_calon'],
            $percentage . '%'
        ]);
    }
}

// Tambah ringkasan
fputcsv($output, []);
fputcsv($output, ['RINGKASAN']);
fputcsv($output, ['Jawatan', 'Jumlah Undi']);
foreach ($positions as $position) {
    fputcsv($output, [$position_titles[$position], $voted_counts[$position]]);
}

fclose($output);
exit();
?>
