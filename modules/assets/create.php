<?php
require_once __DIR__ . '/../../includes/header.php';
$pdo   = getDB();
$cats  = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT * FROM brands ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name      = trim(filter_input(INPUT_POST, 'name',      FILTER_SANITIZE_SPECIAL_CHARS));
    $sku       = trim(filter_input(INPUT_POST, 'sku',       FILTER_SANITIZE_SPECIAL_CHARS));
    $cat_id    = filter_input(INPUT_POST, 'category_id',  FILTER_VALIDATE_INT);
    $brand_id  = filter_input(INPUT_POST, 'brand_id',     FILTER_VALIDATE_INT);
    $unit      = trim(filter_input(INPUT_POST, 'unit',      FILTER_SANITIZE_SPECIAL_CHARS));
    $stock     = filter_input(INPUT_POST, 'stock',        FILTER_VALIDATE_INT);
    $reorder   = filter_input(INPUT_POST, 'reorder_qty',  FILTER_VALIDATE_INT);
    $price     = filter_input(INPUT_POST, 'unit_price',   FILTER_VALIDATE_FLOAT);
    $location  = trim(filter_input(INPUT_POST, 'location',   FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$name)     $errors[] = 'Name is required.';
    if (!$sku)      $errors[] = 'SKU is required.';
    if (!$cat_id)   $errors[] = 'Category is required.';
    if (!$brand_id) $errors[] = 'Brand is required.';

    if (!$errors) {
        $stmt = $pdo->prepare('
          INSERT INTO assets (name, sku, category_id, brand_id, unit, stock, reorder_qty, unit_price, location)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$name, $sku, $cat_id, $brand_id, $unit ?: 'pcs', $stock ?: 0, $reorder ?: 5, $price ?: 0, $location]);
        header('Location: index.php');
        exit;
    }
}
?>
<h4 class="fw-bold mb-3">Add New Asset</h4>
<?php foreach ($errors as $e): ?>
  <div class="alert alert-danger"><?= h($e) ?></div>
<?php endforeach; ?>
<form method="POST">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">SKU</label>
      <input type="text" name="sku" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Category</label>
      <select name="category_id" class="form-select" required>
        <option value="">Select...</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-4"><label class="form-label">Brand</label>
      <select name="brand_id" class="form-select" required>
        <option value="">Select...</option>
        <?php foreach ($brands as $b): ?>
          <option value="<?= $b['id'] ?>"><?= h($b['name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-4"><label class="form-label">Unit</label>
      <input type="text" name="unit" class="form-control" value="pcs"></div>
    <div class="col-md-3"><label class="form-label">Initial Stock</label>
      <input type="number" name="stock" class="form-control" value="0" min="0"></div>
    <div class="col-md-3"><label class="form-label">Reorder Level</label>
      <input type="number" name="reorder_qty" class="form-control" value="5" min="0"></div>
    <div class="col-md-3"><label class="form-label">Unit Price (₱)</label>
      <input type="number" name="unit_price" class="form-control" step="0.01" value="0.00"></div>
    <div class="col-md-3"><label class="form-label">Location</label>
      <input type="text" name="location" class="form-control"></div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-dark">Save Asset</button>
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>