<?php
require_once __DIR__ . '/../../includes/header.php';
$pdo    = getDB();
$id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$cats   = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT * FROM brands ORDER BY name')->fetchAll();
$stmt   = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$id]);
$asset  = $stmt->fetch();
if (!$asset) { header('Location: index.php'); exit; }
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name     = trim(filter_input(INPUT_POST, 'name',       FILTER_SANITIZE_SPECIAL_CHARS));
    $sku      = trim(filter_input(INPUT_POST, 'sku',        FILTER_SANITIZE_SPECIAL_CHARS));
    $cat_id   = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $brand_id = filter_input(INPUT_POST, 'brand_id',    FILTER_VALIDATE_INT);
    $unit     = trim(filter_input(INPUT_POST, 'unit',       FILTER_SANITIZE_SPECIAL_CHARS));
    $reorder  = filter_input(INPUT_POST, 'reorder_qty', FILTER_VALIDATE_INT);
    $price    = filter_input(INPUT_POST, 'unit_price',  FILTER_VALIDATE_FLOAT);
    $location = trim(filter_input(INPUT_POST, 'location',   FILTER_SANITIZE_SPECIAL_CHARS));
    if (!$name) $errors[] = 'Name is required.';
    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE assets SET name=?,sku=?,category_id=?,brand_id=?,unit=?,reorder_qty=?,unit_price=?,location=? WHERE id=?');
        $stmt->execute([$name,$sku,$cat_id,$brand_id,$unit,$reorder,$price,$location,$id]);
        header('Location: index.php');
        exit;
    }
}
?>

<div class="topbar">
  <div>
    <div class="topbar-title">Edit Asset</div>
    <div class="topbar-sub"><?= h($asset['name']) ?></div>
  </div>
  <a href="index.php" class="btn-inv-ghost"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php foreach ($errors as $e): ?>
  <div class="inv-alert danger show"><?= h($e) ?></div>
<?php endforeach; ?>

<div class="inv-card">
  <form method="POST">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="inv-label">Asset Name</label>
        <input type="text" name="name" class="inv-form-control" value="<?= h($asset['name']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="inv-label">SKU / Code</label>
        <input type="text" name="sku" class="inv-form-control" value="<?= h($asset['sku']) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="inv-label">Category</label>
        <select name="category_id" class="inv-form-control">
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$asset['category_id']?'selected':'' ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="inv-label">Brand</label>
        <select name="brand_id" class="inv-form-control">
          <?php foreach ($brands as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $b['id']==$asset['brand_id']?'selected':'' ?>><?= h($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="inv-label">Unit</label>
        <input type="text" name="unit" class="inv-form-control" value="<?= h($asset['unit']) ?>">
      </div>
      <div class="col-md-4">
        <label class="inv-label">Reorder Level</label>
        <input type="number" name="reorder_qty" class="inv-form-control" value="<?= $asset['reorder_qty'] ?>">
      </div>
      <div class="col-md-4">
        <label class="inv-label">Unit Price (₱)</label>
        <input type="number" name="unit_price" class="inv-form-control" step="0.01" value="<?= $asset['unit_price'] ?>">
      </div>
      <div class="col-md-4">
        <label class="inv-label">Location</label>
        <input type="text" name="location" class="inv-form-control" value="<?= h($asset['location'] ?? '') ?>">
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid #1f2b60">
      <button type="submit" class="btn-inv-primary"><i class="bi bi-check-lg"></i> Update Asset</button>
      <a href="index.php" class="btn-inv-ghost">Cancel</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>