<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../api/realtime.php';
$pdo    = getDB();
$assets = $pdo->query('SELECT id, name, stock FROM assets ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $asset_id  = filter_input(INPUT_POST, 'asset_id',  FILTER_VALIDATE_INT);
    $qty       = filter_input(INPUT_POST, 'quantity',  FILTER_VALIDATE_INT);
    $cost      = filter_input(INPUT_POST, 'unit_cost', FILTER_VALIDATE_FLOAT);
    $supplier  = trim(filter_input(INPUT_POST, 'supplier',  FILTER_SANITIZE_SPECIAL_CHARS));
    $reference = trim(filter_input(INPUT_POST, 'reference', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks   = trim(filter_input(INPUT_POST, 'remarks',   FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$asset_id) $errors[] = 'Asset is required.';
    if (!$qty || $qty < 1) $errors[] = 'Quantity must be at least 1.';

    if (!$errors) {
        $stmt = $pdo->prepare('CALL sp_stock_in(?,?,?,?,?,?,?)');
        $stmt->execute([$asset_id, $qty, $cost ?: 0, $supplier, $reference, $remarks, $_SESSION['user_id']]);

        // Fetch updated stock for real-time push
        $assetRow = $pdo->prepare('SELECT a.name, a.stock, a.reorder_qty FROM assets a WHERE a.id = ?');
        $assetRow->execute([$asset_id]);
        $updated = $assetRow->fetch();

        pusherTrigger('inventory-channel', 'stock-in-recorded', [
            'asset_id'   => $asset_id,
            'asset_name' => $updated['name'],
            'quantity'   => $qty,
            'unit_cost'  => $cost ?: 0,
            'supplier'   => $supplier,
            'reference'  => $reference,
            'remarks'    => $remarks,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        pusherTrigger('inventory-channel', 'stock-updated', [
            'asset_id'   => $asset_id,
            'new_stock'  => $updated['stock'],
            'reorder_qty'=> $updated['reorder_qty'],
            'message'    => "Stock-In: {$updated['name']} now has {$updated['stock']} units.",
        ]);

        header('Location: index.php');
        exit;
    }
}
?>
<h4 class="fw-bold mb-3">Record Stock-In</h4>
<?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= h($e) ?></div><?php endforeach; ?>
<form method="POST">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Asset</label>
      <select name="asset_id" class="form-select" required>
        <option value="">Select asset...</option>
        <?php foreach ($assets as $a): ?>
          <option value="<?= $a['id'] ?>"><?= h($a['name']) ?> (stock: <?= $a['stock'] ?>)</option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-3"><label class="form-label">Quantity</label>
      <input type="number" name="quantity" class="form-control" min="1" required></div>
    <div class="col-md-3"><label class="form-label">Unit Cost (₱)</label>
      <input type="number" name="unit_cost" class="form-control" step="0.01" value="0.00"></div>
    <div class="col-md-6"><label class="form-label">Supplier</label>
      <input type="text" name="supplier" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Reference / PO No.</label>
      <input type="text" name="reference" class="form-control"></div>
    <div class="col-12"><label class="form-label">Remarks</label>
      <textarea name="remarks" class="form-control" rows="2"></textarea></div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-success">Confirm Stock-In</button>
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>