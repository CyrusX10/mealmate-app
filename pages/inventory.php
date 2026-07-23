<?php
$page_title = 'Inventory';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/helpers.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

function resolve_unit(array $post): string {
    $selected = $post['unit_select'] ?? '';
    if ($selected === 'other') {
        return trim($post['unit_custom'] ?? '');
    }
    return trim($selected);
}

// Add item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit = resolve_unit($_POST);
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
    $unit = resolve_unit($_POST);
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
    header('Location: ' . BASE_URL . '/pages/inventory.php?msg=deleted');
    exit;
}

// Mark consumed — removes 1 unit; the item vanishes once it hits 0.
if (isset($_GET['consume'])) {
    $item_id = (int) $_GET['consume'];
    $stmt = $pdo->prepare("SELECT item_name, category, quantity FROM food_items WHERE id = ? AND user_id = ? AND status = 'available'");
    $stmt->execute([$item_id, $user_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $remaining = $item['quantity'] - 1;

        $stmt = $pdo->prepare("INSERT INTO food_saved_log (user_id, item_name, action, category) VALUES (?, ?, 'consumed', ?)");
        $stmt->execute([$user_id, $item['item_name'], $item['category']]);

        if ($remaining <= 0) {
            $pdo->prepare("DELETE FROM food_items WHERE id = ?")->execute([$item_id]);
            header('Location: ' . BASE_URL . '/pages/inventory.php?msg=consumed_done');
        } else {
            $pdo->prepare("UPDATE food_items SET quantity = ? WHERE id = ?")->execute([$remaining, $item_id]);
            header('Location: ' . BASE_URL . '/pages/inventory.php?msg=consumed_partial');
        }
    } else {
        header('Location: ' . BASE_URL . '/pages/inventory.php');
    }
    exit;
}

// Convert to donation — donates the item's full remaining quantity as one
// listing (Browse Donations shows this as a single claimable listing, so
// splitting it unit-by-unit isn't meaningful the way Consume's is).
if (isset($_GET['donate'])) {
    $item_id = (int) $_GET['donate'];
    $stmt = $pdo->prepare("SELECT item_name, category FROM food_items WHERE id = ? AND user_id = ? AND status = 'available'");
    $stmt->execute([$item_id, $user_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item) {
        $stmt = $pdo->prepare("UPDATE food_items SET status = 'donated' WHERE id = ?");
        $stmt->execute([$item_id]);
        $stmt = $pdo->prepare("INSERT INTO donations (donor_id, food_item_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $item_id]);
        $stmt = $pdo->prepare("INSERT INTO food_saved_log (user_id, item_name, action, category) VALUES (?, ?, 'donated', ?)");
        $stmt->execute([$user_id, $item['item_name'], $item['category']]);
    }
    header('Location: ' . BASE_URL . '/pages/inventory.php?msg=donated');
    exit;
}

// Filters
$category_filter = $_GET['category'] ?? '';
$location_filter = $_GET['location'] ?? '';

$sql = "SELECT * FROM food_items WHERE user_id = ? AND status = 'available'";
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

$units = common_units();

function unit_select_html(string $id_prefix, string $current_unit, array $units): void {
    $is_preset = in_array($current_unit, $units, true);
?>
    <select id="<?= $id_prefix ?>_select" name="unit_select" class="unit-select" data-custom-target="<?= $id_prefix ?>_custom_wrap" required>
        <?php foreach ($units as $u): ?>
            <option value="<?= htmlspecialchars($u) ?>" <?= ($current_unit === $u) ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
        <?php endforeach; ?>
        <option value="other" <?= (!$is_preset && $current_unit !== '') ? 'selected' : '' ?>>Other…</option>
    </select>
    <div class="unit-custom-wrap" id="<?= $id_prefix ?>_custom_wrap" style="display:<?= (!$is_preset && $current_unit !== '') ? 'block' : 'none' ?>;">
        <input type="text" name="unit_custom" placeholder="Custom unit" value="<?= (!$is_preset) ? htmlspecialchars($current_unit) : '' ?>">
    </div>
<?php
}
?>

<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-boxes-stacked"></i> Food Inventory</h1>
        <p class="page-subtitle">Everything currently in your fridge, freezer, and pantry.</p>
    </div>
    <button class="btn btn-primary" data-modal="addItemModal"><i class="fa-solid fa-plus"></i> Add Item</button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php
        $messages = [
            'deleted'          => ['Item deleted.', 'fa-trash'],
            'consumed_partial' => ['1 unit consumed — updated your quantity.', 'fa-utensils'],
            'consumed_done'    => ['Item fully consumed and removed from your inventory.', 'fa-utensils'],
            'donated'          => ['Item listed as a donation!', 'fa-hand-holding-heart'],
        ];
        $m = $messages[$_GET['msg']] ?? null;
    ?>
    <?php if ($m): ?>
        <div class="alert alert-success alert-toast"><i class="fa-solid <?= $m[1] ?>"></i> <?= htmlspecialchars($m[0]) ?></div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error alert-toast"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success alert-toast"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
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
            <a href="<?= BASE_URL ?>/pages/inventory.php" class="btn btn-sm btn-warning">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<?php if (count($items) > 0): ?>
    <div class="card-grid">
        <?php foreach ($items as $item):
            $days_left = (int) floor((strtotime($item['expiry_date']) - time()) / 86400);
            $badge = expiry_badge($days_left);
            $qty_display = rtrim(rtrim(number_format((float) $item['quantity'], 2), '0'), '.');
        ?>
            <div class="card <?= $badge['class'] ?>">
                <div class="item-card-header">
                    <span class="item-icon"><i class="fa-solid <?= category_icon($item['category']) ?>"></i></span>
                    <div>
                        <h3><?= htmlspecialchars($item['item_name']) ?></h3>
                        <span class="item-category"><?= ucfirst($item['category']) ?></span>
                    </div>
                </div>
                <div class="item-pills">
                    <span class="pill"><i class="fa-solid fa-scale-balanced"></i> <?= htmlspecialchars($qty_display) ?> <?= htmlspecialchars($item['unit']) ?></span>
                    <span class="pill"><i class="fa-solid <?= storage_icon($item['storage_location']) ?>"></i> <?= ucfirst($item['storage_location']) ?></span>
                    <span class="pill pill-<?= $badge['class'] ?>"><i class="fa-solid <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?></span>
                </div>
                <?php if (!empty($item['notes'])): ?>
                    <p class="item-notes"><?= htmlspecialchars($item['notes']) ?></p>
                <?php endif; ?>
                <div class="btn-group">
                    <button class="btn btn-sm btn-accent" data-modal="editModal<?= $item['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</button>
                    <a href="?consume=<?= $item['id'] ?>" class="btn btn-sm btn-primary"
                       data-confirm-title="Consume 1 <?= htmlspecialchars($item['unit'], ENT_QUOTES) ?>?"
                       data-confirm-message="This uses up 1 <?= htmlspecialchars($item['unit'], ENT_QUOTES) ?> of <?= htmlspecialchars($item['item_name'], ENT_QUOTES) ?><?= $qty_display <= 1 ? ' and will remove it from your inventory' : '' ?>."
                       data-confirm-icon="fa-utensils" data-confirm-variant="primary" data-confirm-label="Consume 1">
                        <i class="fa-solid fa-utensils"></i> Consume
                    </a>
                    <a href="?donate=<?= $item['id'] ?>" class="btn btn-sm btn-warning"
                       data-confirm-title="Donate all <?= htmlspecialchars($qty_display, ENT_QUOTES) ?> <?= htmlspecialchars($item['unit'], ENT_QUOTES) ?>?"
                       data-confirm-message="This lists <?= htmlspecialchars($item['item_name'], ENT_QUOTES) ?> on Browse Donations for others to claim, and removes it from your own inventory."
                       data-confirm-icon="fa-hand-holding-heart" data-confirm-variant="warning" data-confirm-label="Donate it all">
                        <i class="fa-solid fa-hand-holding-heart"></i> Donate
                    </a>
                    <a href="?delete=<?= $item['id'] ?>" class="btn btn-sm btn-danger"
                       data-confirm-title="Delete this item?"
                       data-confirm-message="This permanently removes <?= htmlspecialchars($item['item_name'], ENT_QUOTES) ?> from your inventory. This can't be undone."
                       data-confirm-icon="fa-trash" data-confirm-variant="danger" data-confirm-label="Delete">
                        <i class="fa-solid fa-trash"></i> Delete
                    </a>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal-overlay" id="editModal<?= $item['id'] ?>">
                <div class="modal">
                    <h2><i class="fa-solid fa-pen"></i> Edit Item</h2>
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
                            <label for="<?= $item['id'] ?>_unit_select">Unit</label>
                            <?php unit_select_html((string) $item['id'] . '_unit', $item['unit'], $units); ?>
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
                            <button type="button" class="btn btn-ghost close-modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card empty-state">
        <div class="empty-state-icon"><i class="fa-solid fa-basket-shopping"></i></div>
        <h3>Your inventory is empty</h3>
        <p>Add your first food item to start tracking what's in your kitchen.</p>
        <button class="btn btn-primary" data-modal="addItemModal"><i class="fa-solid fa-plus"></i> Add Item</button>
    </div>
<?php endif; ?>

<!-- Add Item Modal -->
<div class="modal-overlay" id="addItemModal">
    <div class="modal">
        <h2><i class="fa-solid fa-plus"></i> Add Food Item</h2>
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
                <label for="new_unit_select">Unit</label>
                <?php unit_select_html('new_unit', 'pieces', $units); ?>
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
                <button type="button" class="btn btn-ghost close-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
