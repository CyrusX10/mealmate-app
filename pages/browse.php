<?php
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Claim donation
if (isset($_GET['claim'])) {
    $donation_id = $_GET['claim'];
    $stmt = $pdo->prepare("SELECT donor_id, food_item_id FROM donations WHERE id = ? AND status = 'available'");
    $stmt->execute([$donation_id]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($donation && $donation['donor_id'] != $user_id) {
        $stmt = $pdo->prepare("UPDATE donations SET status = 'claimed', claimer_id = ?, claimed_at = NOW() WHERE id = ?");
        $stmt->execute([$user_id, $donation_id]);

        $stmt = $pdo->prepare("SELECT item_name FROM food_items WHERE id = ?");
        $stmt->execute([$donation['food_item_id']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'donation', ?)");
        $stmt->execute([$donation['donor_id'], $_SESSION['full_name'] . ' has claimed your ' . $item['item_name'] . ' donation.']);

        $success = 'Donation claimed successfully!';
    } else {
        $error = 'You cannot claim your own donation.';
    }
}

// Filters
$category_filter = $_GET['category'] ?? '';
$location_filter = $_GET['location'] ?? '';

$sql = "SELECT d.id as donation_id, d.listed_at, d.donor_id, f.*, u.full_name as donor_name
        FROM donations d
        JOIN food_items f ON d.food_item_id = f.id
        JOIN users u ON d.donor_id = u.id
        WHERE d.status = 'available' AND u.listing_visibility = 'public'";
$params = [];

if (!empty($category_filter)) {
    $sql .= " AND f.category = ?";
    $params[] = $category_filter;
}
if (!empty($location_filter)) {
    $sql .= " AND f.storage_location = ?";
    $params[] = $location_filter;
}
$sql .= " ORDER BY d.listed_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// My claims
$stmt = $pdo->prepare("
    SELECT d.*, f.item_name, f.category, f.quantity, f.unit, f.expiry_date, f.storage_location, f.notes, u.full_name as donor_name
    FROM donations d
    JOIN food_items f ON d.food_item_id = f.id
    JOIN users u ON d.donor_id = u.id
    WHERE d.claimer_id = ?
    ORDER BY d.claimed_at DESC
");
$stmt->execute([$user_id]);
$my_claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/navbar.php';
?>

<h1>Browse Donations</h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="filters">
    <form method="GET" action="">
        <select name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <option value="fruits" <?= $category_filter === 'fruits' ? 'selected' : '' ?>>Fruits</option>
            <option value="vegetables" <?= $category_filter === 'vegetables' ? 'selected' : '' ?>>Vegetables</option>
            <option value="dairy" <?= $category_filter === 'dairy' ? 'selected' : '' ?>>Dairy</option>
            <option value="meat" <?= $category_filter === 'meat' ? 'selected' : '' ?>>Meat</option>
            <option value="grains" <?= $category_filter === 'grains' ? 'selected' : '' ?>>Grains</option>
            <option value="other" <?= $category_filter === 'other' ? 'selected' : '' ?>>Other</option>
        </select>
        <select name="location" onchange="this.form.submit()">
            <option value="">All Locations</option>
            <option value="fridge" <?= $location_filter === 'fridge' ? 'selected' : '' ?>>Fridge</option>
            <option value="freezer" <?= $location_filter === 'freezer' ? 'selected' : '' ?>>Freezer</option>
            <option value="pantry" <?= $location_filter === 'pantry' ? 'selected' : '' ?>>Pantry</option>
        </select>
        <?php if (!empty($category_filter) || !empty($location_filter)): ?>
            <a href="<?= BASE_URL ?>/pages/browse.php" class="btn btn-sm btn-warning">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<h2>Available Donations</h2>

<?php if (count($donations) > 0): ?>
    <div class="card-grid">
        <?php foreach ($donations as $item):
            $days_left = floor((strtotime($item['expiry_date']) - time()) / 86400);
            $expiry_class = 'expiry-safe';
            if ($days_left < 0) $expiry_class = 'expiry-danger';
            elseif ($days_left <= 3) $expiry_class = 'expiry-warning';
        ?>
            <div class="card <?= $expiry_class ?>">
                <h3><?= htmlspecialchars($item['item_name']) ?></h3>
                <p><strong>Donor:</strong> <?= htmlspecialchars($item['donor_name']) ?></p>
                <p><strong>Category:</strong> <?= ucfirst($item['category']) ?></p>
                <p><strong>Quantity:</strong> <?= htmlspecialchars($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?></p>
                <p><strong>Expiry:</strong> <?= htmlspecialchars($item['expiry_date']) ?>
                    <?php if ($days_left < 0): ?>
                        <span style="color:var(--expiry-danger);font-weight:bold;">(Expired)</span>
                    <?php elseif ($days_left <= 3): ?>
                        <span style="color:var(--expiry-warning);">(<?= $days_left ?> days)</span>
                    <?php else: ?>
                        <span>(<?= $days_left ?> days)</span>
                    <?php endif; ?>
                </p>
                <p><strong>Storage:</strong> <?= ucfirst($item['storage_location']) ?></p>
                <?php if (!empty($item['notes'])): ?>
                    <p><strong>Notes:</strong> <?= htmlspecialchars($item['notes']) ?></p>
                <?php endif; ?>
                <div class="btn-group">
                    <button class="btn btn-sm btn-accent" data-modal="detailModal<?= $item['donation_id'] ?>">Details</button>
                    <?php if ($item['donor_id'] != $user_id): ?>
                        <a href="?claim=<?= $item['donation_id'] ?>" class="btn btn-sm btn-primary btn-claim">Claim</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Details Modal -->
            <div class="modal-overlay" id="detailModal<?= $item['donation_id'] ?>">
                <div class="modal">
                    <h2><?= htmlspecialchars($item['item_name']) ?></h2>
                    <p><strong>Donor:</strong> <?= htmlspecialchars($item['donor_name']) ?></p>
                    <p><strong>Category:</strong> <?= ucfirst($item['category']) ?></p>
                    <p><strong>Quantity:</strong> <?= htmlspecialchars($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?></p>
                    <p><strong>Expiry Date:</strong> <?= htmlspecialchars($item['expiry_date']) ?></p>
                    <p><strong>Storage Location:</strong> <?= ucfirst($item['storage_location']) ?></p>
                    <?php if (!empty($item['notes'])): ?>
                        <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($item['notes'])) ?></p>
                    <?php endif; ?>
                    <p><strong>Listed:</strong> <?= htmlspecialchars($item['listed_at']) ?></p>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary close-modal">Close</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <p>No donations available at the moment. Check back later!</p>
    </div>
<?php endif; ?>

<h2 style="margin-top:2rem;">My Claims</h2>

<?php if (count($my_claims) > 0): ?>
    <div class="card-grid">
        <?php foreach ($my_claims as $claim): ?>
            <div class="card">
                <h3><?= htmlspecialchars($claim['item_name']) ?></h3>
                <p><strong>Donor:</strong> <?= htmlspecialchars($claim['donor_name']) ?></p>
                <p><strong>Category:</strong> <?= ucfirst($claim['category']) ?></p>
                <p><strong>Quantity:</strong> <?= htmlspecialchars($claim['quantity']) ?> <?= htmlspecialchars($claim['unit']) ?></p>
                <p><strong>Expiry:</strong> <?= htmlspecialchars($claim['expiry_date']) ?></p>
                <p><strong>Status:</strong> <?= ucfirst($claim['status']) ?></p>
                <p><strong>Claimed:</strong> <?= htmlspecialchars($claim['claimed_at']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <p>You haven't claimed any donations yet.</p>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
