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

// Add item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit = trim($_POST['unit']);
    $expiry_date = $_POST['expiry_date'];
    $storage_location = $_POST['storage_location'];
    $notes = trim($_POST['notes']);

    if (empty($item_name) || empty($category) || empty($quantity) || empty($unit) || empty($expiry_date) || empty($storage_location)) {
        $error = 'Please fill in all required fields.';
    } elseif ($quantity <= 0) {
        $error = 'Quantity must be greater than 0.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO food_items (user_id, item_name, category, quantity, unit, expiry_date, storage_location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $item_name, $category, $quantity, $unit, $expiry_date, $storage_location, $notes]);
        $success = 'Item added successfully!';
    }
}

// Edit item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_item'])) {
    $item_id = $_POST['item_id'];
    $item_name = trim($_POST['item_name']);
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit = trim($_POST['unit']);
    $expiry_date = $_POST['expiry_date'];
    $storage_location = $_POST['storage_location'];
    $notes = trim($_POST['notes']);

    $stmt = $pdo->prepare("UPDATE food_items SET item_name=?, category=?, quantity=?, unit=?, expiry_date=?, storage_location=?, notes=? WHERE id=? AND user_id=?");
    $stmt->execute([$item_name, $category, $quantity, $unit, $expiry_date, $storage_location, $notes, $item_id, $user_id]);
    $success = 'Item updated successfully!';
}

// Delete item
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    $success = 'Item deleted.';
    header('Location: ' . BASE_URL . '/pages/inventory.php?msg=deleted');
    exit;
}

// Mark consumed
if (isset($_GET['consume'])) {
    $item_id = $_GET['consume'];
    $stmt = $pdo->prepare("SELECT item_name, category FROM food_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item) {
        $stmt = $pdo->prepare("UPDATE food_items SET status = 'consumed' WHERE id = ?");
        $stmt->execute([$item_id]);
        $stmt = $pdo->prepare("INSERT INTO food_saved_log (user_id, item_name, action, category) VALUES (?, ?, 'consumed', ?)");
        $stmt->execute([$user_id, $item['item_name'], $item['category']]);
        $success = 'Item marked as consumed.';
    }
    header('Location: ' . BASE_URL . '/pages/inventory.php?msg=consumed');
    exit;
}

// Convert to donation
if (isset($_GET['donate'])) {
    $item_id = $_GET['donate'];
    $stmt = $pdo->prepare("SELECT item_name, category FROM food_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item) {
        $stmt = $pdo->prepare("UPDATE food_items SET status = 'donated' WHERE id = ?");
        $stmt->execute([$item_id]);
        $stmt = $pdo->prepare("INSERT INTO donations (donor_id, food_item_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $item_id]);
        $stmt = $pdo->prepare("INSERT INTO food_saved_log (user_id, item_name, action, category) VALUES (?, ?, 'donated', ?)");
        $stmt->execute([$user_id, $item['item_name'], $item['category']]);
        $success = 'Item listed as donation!';
    }
    header('Location: ' . BASE_URL . '/pages/inventory.php?msg=donated');
    exit;
}

// Filters
$category_filter = $_GET['category'] ?? '';
$location_filter = $_GET['location'] ?? '';

$sql = "SELECT * FROM food_items WHERE user_id = ?";
$params = [$user_id];

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}
if (!empty($location_filter)) {
    $sql .= " AND storage_location = ?";
    $params[] = $location_filter;
}
$sql .= " ORDER BY expiry_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/navbar.php';
?>

<h1>Food Inventory</h1>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Item deleted.</div>
    <?php elseif ($_GET['msg'] === 'consumed'): ?>
        <div class="alert alert-success">Item marked as consumed.</div>
    <?php elseif ($_GET['msg'] === 'donated'): ?>
        <div class="alert alert-success">Item listed as donation!</div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="action-bar">
    <h2>Your Items</h2>
    <button class="btn btn-primary" data-modal="addItemModal">+ Add Item</button>
</div>

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
            <a href="<?= BASE_URL ?>/pages/inventory.php" class="btn btn-sm btn-warning">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<?php if (count($items) > 0): ?>
    <div class="card-grid">
        <?php foreach ($items as $item):
            $days_left = floor((strtotime($item['expiry_date']) - time()) / 86400);
            $expiry_class = 'expiry-safe';
            if ($days_left < 0) $expiry_class = 'expiry-danger';
            elseif ($days_left <= 3) $expiry_class = 'expiry-warning';
        ?>
            <div class="card <?= $expiry_class ?>">
                <h3><?= htmlspecialchars($item['item_name']) ?></h3>
                <p><strong>Category:</strong> <?= ucfirst($item['category']) ?></p>
                <p><strong>Quantity:</strong> <?= htmlspecialchars($item['quantity']) ?> <?= htmlspecialchars($item['unit']) ?></p>
                <p><strong>Expiry:</strong> <?= htmlspecialchars($item['expiry_date']) ?>
                    <?php if ($days_left < 0): ?>
                        <span style="color:var(--expiry-danger);font-weight:bold;">(Expired)</span>
                    <?php elseif ($days_left == 0): ?>
                        <span style="color:var(--expiry-warning);font-weight:bold;">(Today)</span>
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
                    <button class="btn btn-sm btn-accent" data-modal="editModal<?= $item['id'] ?>">Edit</button>
                    <a href="?consume=<?= $item['id'] ?>" class="btn btn-sm btn-primary btn-consume">Consume</a>
                    <a href="?donate=<?= $item['id'] ?>" class="btn btn-sm btn-warning btn-donate">Donate</a>
                    <a href="?delete=<?= $item['id'] ?>" class="btn btn-sm btn-danger btn-delete">Delete</a>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal-overlay" id="editModal<?= $item['id'] ?>">
                <div class="modal">
                    <h2>Edit Item</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <div class="form-group">
                            <label for="item_name_<?= $item['id'] ?>">Item Name</label>
                            <input type="text" id="item_name_<?= $item['id'] ?>" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="category_<?= $item['id'] ?>">Category</label>
                            <select id="category_<?= $item['id'] ?>" name="category" required>
                                <option value="fruits" <?= $item['category'] === 'fruits' ? 'selected' : '' ?>>Fruits</option>
                                <option value="vegetables" <?= $item['category'] === 'vegetables' ? 'selected' : '' ?>>Vegetables</option>
                                <option value="dairy" <?= $item['category'] === 'dairy' ? 'selected' : '' ?>>Dairy</option>
                                <option value="meat" <?= $item['category'] === 'meat' ? 'selected' : '' ?>>Meat</option>
                                <option value="grains" <?= $item['category'] === 'grains' ? 'selected' : '' ?>>Grains</option>
                                <option value="other" <?= $item['category'] === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity_<?= $item['id'] ?>">Quantity</label>
                            <input type="number" step="0.01" id="quantity_<?= $item['id'] ?>" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="unit_<?= $item['id'] ?>">Unit</label>
                            <input type="text" id="unit_<?= $item['id'] ?>" name="unit" value="<?= htmlspecialchars($item['unit']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="expiry_date_<?= $item['id'] ?>">Expiry Date</label>
                            <input type="date" id="expiry_date_<?= $item['id'] ?>" name="expiry_date" value="<?= htmlspecialchars($item['expiry_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="storage_location_<?= $item['id'] ?>">Storage Location</label>
                            <select id="storage_location_<?= $item['id'] ?>" name="storage_location" required>
                                <option value="fridge" <?= $item['storage_location'] === 'fridge' ? 'selected' : '' ?>>Fridge</option>
                                <option value="freezer" <?= $item['storage_location'] === 'freezer' ? 'selected' : '' ?>>Freezer</option>
                                <option value="pantry" <?= $item['storage_location'] === 'pantry' ? 'selected' : '' ?>>Pantry</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notes_<?= $item['id'] ?>">Notes</label>
                            <textarea id="notes_<?= $item['id'] ?>" name="notes"><?= htmlspecialchars($item['notes']) ?></textarea>
                        </div>
                        <div class="btn-group">
                            <button type="submit" name="edit_item" class="btn btn-primary">Update</button>
                            <button type="button" class="btn btn-danger close-modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <p>No items found. Add your first food item to get started!</p>
    </div>
<?php endif; ?>

<!-- Add Item Modal -->
<div class="modal-overlay" id="addItemModal">
    <div class="modal">
        <h2>Add Food Item</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select...</option>
                    <option value="fruits">Fruits</option>
                    <option value="vegetables">Vegetables</option>
                    <option value="dairy">Dairy</option>
                    <option value="meat">Meat</option>
                    <option value="grains">Grains</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" step="0.01" id="quantity" name="quantity" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="unit">Unit</label>
                <input type="text" id="unit" name="unit" value="pieces" required>
            </div>
            <div class="form-group">
                <label for="expiry_date">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" required>
            </div>
            <div class="form-group">
                <label for="storage_location">Storage Location</label>
                <select id="storage_location" name="storage_location" required>
                    <option value="">Select...</option>
                    <option value="fridge">Fridge</option>
                    <option value="freezer">Freezer</option>
                    <option value="pantry">Pantry</option>
                </select>
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes"></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                <button type="button" class="btn btn-danger close-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
