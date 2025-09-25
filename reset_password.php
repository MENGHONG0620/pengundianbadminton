<?php
require_once 'config.php';

if ($_POST) {
    $email = sanitize($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "Kata laluan tidak sepadan!";
    } else {
        $stmt = $pdo->prepare("SELECT id_pengguna, nama FROM pengguna WHERE emel = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE pengguna SET kata_laluan = ? WHERE emel = ?");
            $stmt->execute([$hashed_password, $email]);
            $success = "Kata laluan berjaya di set semula! Anda boleh log masuk dengan kata laluan baru.";
        } else {
            $error = "Emel tidak dijumpai!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Semula Kata Laluan - Sistem Pengundian Jawatankuasa Kelab Badminton</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .reset-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }
        .reset-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: rotate 4s linear infinite;
            opacity: 0.5;
            pointer-events: none;
            z-index: 1;
        }
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }
        .reset-icon {
            font-size: 4rem;
            color: white;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }
        .reset-title {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .reset-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }
        .form-input {
            width: 100%;
            padding: 0.8rem 0.8rem 0.8rem 2.5rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        .form-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        .input-icon {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 1rem;
            pointer-events: none;
        }
        .reset-btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.2rem;
        }
        .reset-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #6ee7b7;
            border-color: rgba(16, 185, 129, 0.3);
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #fecaca;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .home-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        .home-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes rotate {
            to { transform: rotate(360deg); }
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <i class="fas fa-key reset-icon"></i>
            <h2 class="reset-title">Set Semula Kata Laluan</h2>
            <p class="reset-subtitle">Cipta kata laluan baru untuk akaun anda</p>
        </div>

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
                <label class="form-label">Alamat Emel</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" class="form-input" required placeholder="Masukkan emel anda">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kata Laluan Baru</label>
                <div style="position: relative;">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="new_password" class="form-input" required placeholder="Masukkan kata laluan baru" minlength="6">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Sahkan Kata Laluan</label>
                <div style="position: relative;">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="confirm_password" class="form-input" required placeholder="Sahkan kata laluan baru">
                </div>
            </div>
            <button type="submit" class="reset-btn">
                <i class="fas fa-key"></i> Set Semula Kata Laluan
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="login.php" class="home-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Log Masuk
            </a>
        </div>
    </div>

    <script src="main.js"></script>
</body>
</html>
