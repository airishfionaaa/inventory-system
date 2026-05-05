<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../api/realtime.php';
$pdo    = getDB();
$assets = $pdo->query('SELECT id, name, stock FROM assets ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $asset_id  = filter_input(INPUT_POST, 'asset_id',     FILTER_VALIDATE_INT);
    $qty       = filter_input(INPUT_POST, 'quantity',     FILTER_VALIDATE_INT);
    $requested = trim(filter_input(INPUT_POST, 'requested_by', FILTER_SANITIZE_SPECIAL_CHARS));
    $purpose   = trim(filter_input(INPUT_POST, 'purpose',      FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks   = trim(filter_input(INPUT_POST, 'remarks',      FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$asset_id) $errors[] = 'Asset is required.';
    if (!$qty || $qty < 1) $errors[] = 'Quantity must be at least 1.';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('CALL sp_stock_out(?,?,?,?,?,?)');
            $stmt->execute([$asset_id, $qty, $requested, $purpose, $remarks, $_SESSION['user_id']]);

            $assetRow = $pdo->prepare('SELECT a.name, a.stock, a.reorder_qty FROM assets a WHERE a.id = ?');
            $assetRow->execute([$asset_id]);
            $updated = $assetRow->fetch();

            pusherTrigger('inventory-channel', 'stock-out-recorded', [
                'asset_id'     => $asset_id,
                'asset_name'   => $updated['name'],
                'quantity'     => $qty,
                'requested_by' => $requested,
                'purpose'      => $purpose,
                'remarks'      => $remarks,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            pusherTrigger('inventory-channel', 'stock-updated', [
                'asset_id'    => $asset_id,
                'new_stock'   => $updated['stock'],
                'reorder_qty' => $updated['reorder_qty'],
                'message'     => "Stock-Out: {$updated['name']} now has {$updated['stock']} units.",
            ]);

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Transaction failed: ' . $e->getMessage();
        }
    }
}
?>
<h4 class="fw-bold mb-3">Record Stock-Out</h4>
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
    <div class="col-md-3"><label class="form-label">Purpose</label>
      <select name="purpose" class="form-select">
        <option>Use</option><option>Repair</option>
        <option>Transfer</option><option>Disposal</option><option>Sale</option>
      </select></div>
    <div class="col-md-6"><label class="form-label">Requested By</label>
      <input type="text" name="requested_by" class="form-control"></div>
    <div class="col-12"><label class="form-label">Remarks</label>
      <textarea name="remarks" class="form-control" rows="2"></textarea></div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-danger">Confirm Stock-Out</button>
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>