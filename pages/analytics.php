<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

$date_filter = $_GET['period'] ?? 'all';

$date_condition = '';
if ($date_filter === 'week') {
    $date_condition = "AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'month') {
    $date_condition = "AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_saved_log WHERE user_id = ? $date_condition");
$stmt->execute([$user_id]);
$total_saved = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_saved_log WHERE user_id = ? AND action = 'donated' $date_condition");
$stmt->execute([$user_id]);
$total_donated = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_saved_log WHERE user_id = ? AND action = 'consumed' $date_condition");
$stmt->execute([$user_id]);
$total_consumed = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM food_saved_log WHERE user_id = ? $date_condition GROUP BY category ORDER BY count DESC");
$stmt->execute([$user_id]);
$category_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT action, COUNT(*) as count FROM food_saved_log WHERE user_id = ? $date_condition GROUP BY action");
$stmt->execute([$user_id]);
$action_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE user_id = ? AND status = 'available'");
$stmt->execute([$user_id]);
$current_inventory = $stmt->fetchColumn();

require_once '../includes/navbar.php';
?>

<h1><i class="fa-solid fa-chart-simple"></i> Food Analytics</h1>

<div class="filters">
    <form method="GET" action="">
        <label for="period">Time Period:</label>
        <select name="period" id="period" onchange="this.form.submit()">
            <option value="all" <?= $date_filter === 'all' ? 'selected' : '' ?>>All Time</option>
            <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>This Month</option>
            <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>This Week</option>
        </select>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-leaf"></i></span>
        <div>
            <h3><?= $total_saved ?></h3>
            <p>Total Saved from Waste</p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-hand-holding-heart"></i></span>
        <div>
            <h3><?= $total_donated ?></h3>
            <p>Donations Made</p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-utensils"></i></span>
        <div>
            <h3><?= $total_consumed ?></h3>
            <p>Items Consumed</p>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-card-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
        <div>
            <h3><?= $current_inventory ?></h3>
            <p>Current Inventory</p>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-container">
        <h2>Food Saved by Category</h2>
        <div class="chart-wrapper">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    <div class="chart-container">
        <h2>Consumed vs Donated</h2>
        <div class="chart-wrapper">
            <canvas id="actionChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category bar chart
    var catCtx = document.getElementById('categoryChart');
    if (catCtx) {
        new Chart(catCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($category_data as $c): ?>'<?= ucfirst($c['category']) ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Items Saved',
                    data: [<?php foreach ($category_data as $c): ?><?= $c['count'] ?>,<?php endforeach; ?>],
                    backgroundColor: ['#2d6a4f','#52b788','#95d5b2','#74c69d','#40916c','#d8f3dc'],
                    borderColor: ['#1b4332','#40916c','#74c69d','#52b788','#2d6a4f','#95d5b2'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // Action pie chart
    var actCtx = document.getElementById('actionChart');
    if (actCtx) {
        var consumed = <?= $total_consumed ?>;
        var donated = <?= $total_donated ?>;
        new Chart(actCtx, {
            type: 'pie',
            data: {
                labels: ['Consumed', 'Donated'],
                datasets: [{
                    data: [consumed, donated],
                    backgroundColor: ['#2d6a4f', '#52b788'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
