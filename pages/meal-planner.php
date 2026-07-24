<?php
$page_title = 'Meal Planner';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

function meal_type_icon(string $type): string {
    $icons = ['breakfast' => 'fa-egg', 'lunch' => 'fa-bowl-food', 'dinner' => 'fa-utensils'];
    return $icons[$type] ?? 'fa-utensils';
}

// Week navigation
$week_offset = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$monday = strtotime('monday this week + ' . $week_offset . ' weeks');
$sunday = strtotime('sunday this week + ' . $week_offset . ' weeks');

// Add meal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_meal'])) {
    $meal_date = $_POST['meal_date'];
    $meal_type = $_POST['meal_type'];
    $meal_name = trim($_POST['meal_name']);
    $notes = trim($_POST['notes']);

    if (empty($meal_date) || empty($meal_type) || empty($meal_name)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO meal_plans (user_id, meal_date, meal_type, meal_name, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $meal_date, $meal_type, $meal_name, $notes]);
        $success = 'Meal added to your plan!';
    }
}

// Edit meal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_meal'])) {
    $meal_id = $_POST['meal_id'];
    $meal_name = trim($_POST['meal_name']);
    $meal_type = $_POST['meal_type'];
    $notes = trim($_POST['notes']);

    $stmt = $pdo->prepare("UPDATE meal_plans SET meal_name=?, meal_type=?, notes=? WHERE id=? AND user_id=?");
    $stmt->execute([$meal_name, $meal_type, $notes, $meal_id, $user_id]);
    $success = 'Meal updated!';
}

// Delete meal
if (isset($_GET['delete_meal'])) {
    $stmt = $pdo->prepare("DELETE FROM meal_plans WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete_meal'], $user_id]);
    header('Location: ' . BASE_URL . '/pages/meal-planner.php?week=' . $week_offset);
    exit;
}

// Get meals for the week
$week_start = date('Y-m-d', $monday);
$week_end = date('Y-m-d', $sunday);
$stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE user_id = ? AND meal_date BETWEEN ? AND ? ORDER BY meal_date, FIELD(meal_type,'breakfast','lunch','dinner')");
$stmt->execute([$user_id, $week_start, $week_end]);
$meals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$meals_by_date = [];
foreach ($meals as $meal) {
    $meals_by_date[$meal['meal_date']][] = $meal;
}

// Suggest expiring items
$stmt = $pdo->prepare("SELECT * FROM food_items WHERE user_id = ? AND status = 'available' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY) ORDER BY expiry_date ASC");
$stmt->execute([$user_id]);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/navbar.php';

$day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$today_str = date('Y-m-d');
?>

<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-utensils"></i> Meal Planner</h1>
        <p class="page-subtitle">Plan meals around what's about to expire.</p>
    </div>
    <button class="btn btn-primary" data-modal="addMealModal"><i class="fa-solid fa-plus"></i> Add Meal</button>
</div>

<?php if ($error): ?>
    <div class="alert alert-error alert-toast"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success alert-toast"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="week-nav">
    <a href="?week=<?= $week_offset - 1 ?>" class="btn btn-accent">&larr; Previous Week</a>
    <h2><?= date('M j', $monday) ?> &ndash; <?= date('M j, Y', $sunday) ?></h2>
    <a href="?week=<?= $week_offset + 1 ?>" class="btn btn-accent">Next Week &rarr;</a>
</div>

<span class="week-summary"><i class="fa-solid fa-calendar-check"></i> <?= count($meals) ?> meal<?= count($meals) !== 1 ? 's' : '' ?> planned this week</span>

<?php if (count($suggestions) > 0): ?>
    <div class="suggestions">
        <h3><i class="fa-solid fa-lightbulb"></i> Suggestions: Items Expiring Soon</h3>
        <?php foreach ($suggestions as $s): ?>
            <div class="suggestion-item">
                <span><?= htmlspecialchars($s['item_name']) ?> &ndash; expires <?= htmlspecialchars($s['expiry_date']) ?></span>
                <button class="btn btn-sm btn-accent" onclick="document.getElementById('meal_name').value='<?= htmlspecialchars($s['item_name'], ENT_QUOTES) ?>';document.getElementById('addMealModal').classList.add('active');">Use in Meal</button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="week-grid">
    <?php for ($i = 0; $i < 7; $i++):
        $date_ts = strtotime("+$i days", $monday);
        $date_str = date('Y-m-d', $date_ts);
        $day_name = $day_names[$i];
        $day_meals = $meals_by_date[$date_str] ?? [];
        $is_today = $date_str === $today_str;
    ?>
        <div class="week-day <?= $is_today ? 'today' : '' ?>">
            <div class="day-header"><?= $day_name ?><br><?= date('M j', $date_ts) ?></div>
            <?php foreach ($day_meals as $meal): ?>
                <div class="meal-entry meal-<?= htmlspecialchars($meal['meal_type']) ?>">
                    <div>
                        <span class="meal-type"><i class="fa-solid <?= meal_type_icon($meal['meal_type']) ?>"></i> <?= htmlspecialchars($meal['meal_type']) ?>:</span>
                        <?= htmlspecialchars($meal['meal_name']) ?>
                    </div>
                    <div class="meal-actions">
                        <button class="btn-edit-meal" data-id="<?= $meal['id'] ?>" data-name="<?= htmlspecialchars($meal['meal_name'], ENT_QUOTES) ?>" data-type="<?= $meal['meal_type'] ?>" data-notes="<?= htmlspecialchars($meal['notes'] ?? '', ENT_QUOTES) ?>" title="Edit" aria-label="Edit <?= htmlspecialchars($meal['meal_name']) ?>">&#9998;</button>
                        <a href="?delete_meal=<?= $meal['id'] ?>&week=<?= $week_offset ?>" title="Delete" aria-label="Delete <?= htmlspecialchars($meal['meal_name']) ?>"
                           data-confirm-title="Delete this meal?"
                           data-confirm-message="Remove &quot;<?= htmlspecialchars($meal['meal_name'], ENT_QUOTES) ?>&quot; from your plan?"
                           data-confirm-icon="fa-trash" data-confirm-variant="danger" data-confirm-label="Delete">&times;</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="button" class="day-add-btn" data-modal="addMealModal" data-prefill-date="<?= $date_str ?>" aria-label="Add meal to <?= $day_name ?>">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
    <?php endfor; ?>
</div>

<!-- Add Meal Modal -->
<div class="modal-overlay" id="addMealModal">
    <div class="modal">
        <h2><i class="fa-solid fa-plus"></i> Add Meal</h2>
        <form method="POST" action="?week=<?= $week_offset ?>">
            <div class="form-group">
                <label for="meal_date">Date</label>
                <input type="date" id="meal_date" name="meal_date" min="<?= $week_start ?>" max="<?= $week_end ?>" required>
            </div>
            <div class="form-group">
                <label for="meal_type">Meal Type</label>
                <select id="meal_type" name="meal_type" required>
                    <option value="">Select...</option>
                    <option value="breakfast">Breakfast</option>
                    <option value="lunch">Lunch</option>
                    <option value="dinner">Dinner</option>
                </select>
            </div>
            <div class="form-group">
                <label for="meal_name">Meal Name</label>
                <input type="text" id="meal_name" name="meal_name" required>
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes"></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" name="add_meal" class="btn btn-primary">Add Meal</button>
                <button type="button" class="btn btn-ghost close-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Meal Modal -->
<div class="modal-overlay" id="editMealModal">
    <div class="modal">
        <h2><i class="fa-solid fa-pen"></i> Edit Meal</h2>
        <form method="POST" action="?week=<?= $week_offset ?>">
            <input type="hidden" name="meal_id" id="edit_meal_id">
            <div class="form-group">
                <label for="edit_meal_name">Meal Name</label>
                <input type="text" id="edit_meal_name" name="meal_name" required>
            </div>
            <div class="form-group">
                <label for="edit_meal_type">Meal Type</label>
                <select id="edit_meal_type" name="meal_type" required>
                    <option value="breakfast">Breakfast</option>
                    <option value="lunch">Lunch</option>
                    <option value="dinner">Dinner</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_notes">Notes</label>
                <textarea id="edit_notes" name="notes"></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" name="edit_meal" class="btn btn-primary">Update Meal</button>
                <button type="button" class="btn btn-ghost close-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editBtns = document.querySelectorAll('.btn-edit-meal');
    editBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit_meal_id').value = this.dataset.id;
            document.getElementById('edit_meal_name').value = this.dataset.name;
            document.getElementById('edit_meal_type').value = this.dataset.type;
            document.getElementById('edit_notes').value = this.dataset.notes;
            document.getElementById('editMealModal').classList.add('active');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
