<?php
require_once 'config.php';

if ($_POST && isset($_POST['reset_all'])) {
    // Kosongkan semua undi dan set semula kiraan
    $pdo->exec("DELETE FROM votes");
    $pdo->exec("UPDATE candidates SET votes = 0");
    $pdo->exec("UPDATE users SET has_voted = 0");
    
    echo "✅ All votes reset! <a href='results.php'>Check results</a>";
} else {
?>
<h3>Reset All Votes</h3>
<form method="POST">
    <button type="submit" name="reset_all" style="background:red;color:white;padding:15px;font-size:16px;" 
            onclick="return confirm('Reset ALL votes and counts?')">
        Reset Everything
    </button>
</form>
<?php } ?>
