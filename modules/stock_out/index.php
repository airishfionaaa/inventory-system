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
        $stmt->execute([$asset_id,$qty,$cost?:0,$supplier,$reference,$remarks,$_SESSION['user_id']]);
        $assetRow = $pdo->prepare('SELECT name, stock, reorder_qty FROM assets WHERE id = ?');
        $assetRow->execute([$asset_id]);
        $updated = $assetRow->fetch();
        pusherTrigger('inventory-channel', 'stock-in-recorded', [
            'asset_id'=>$asset_id,'asset_name'=>$updated['name'],
            'quantity'=>$qty,'unit_cost'=>$cost?:0,
            'supplier'=>$supplier,'reference'=>$reference,
            'remarks'=>$remarks,'created_at'=>date('Y-m-d H:i:s'),
        ]);
        pusherTrigger('inventory-channel', 'stock-updated', [
            'asset_id'=>$asset_id,'new_stock'=>$updated['stock'],
            'reorder_qty'=>$updated['reorder_qty'],
            'message'=>"Stock-In: {$updated['name']} now has {$updated['stock']} units.",
        ]);
        header('Location: index.php');
        exit;
    }
}
?>

<div class="topbar">
  <div>
    <div class="topbar-title">Record Stock-In</div>
    <div class="topbar-sub">Add incoming inventory</div>
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
        <label class="inv-label">Asset</label>
        <select name="asset_id" class="inv-form-control" required>
          <option value="">Select asset...</option>
          <?php foreach ($assets as $a): ?>
            <option value="<?= $a['id'] ?>"><?= h($a['name']) ?> (stock: <?= $a['stock'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="inv-label">Quantity</label>
        <input type="number" name="quantity" class="inv-form-control" min="1" placeholder="0" required>
      </div>
      <div class="col-md-3">
        <label class="inv-label">Unit Cost (₱)</label>
        <input type="number" name="unit_cost" class="inv-form-control" step="0.01" value="0.00">
      </div>
      <div class="col-md-6">
        <label class="inv-label">Supplier</label>
        <input type="text" name="supplier" class="inv-form-control" placeholder="Supplier name">
      </div>
      <div class="col-md-6">
        <label class="inv-label">Reference / PO No.</label>
        <input type="text" name="reference" class="inv-form-control" placeholder="e.g. PO-2025-001">
      </div>
      <div class="col-12">
        <label class="inv-label">Remarks</label>
        <textarea name="remarks" class="inv-form-control" rows="3" placeholder="Optional notes..."></textarea>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid #1f2b60">
      <button type="submit" class="btn-inv-success"><i class="bi bi-check-lg"></i> Confirm Stock-In</button>
      <a href="index.php" class="btn-inv-ghost">Cancel</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>