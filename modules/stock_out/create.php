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
            $stmt->execute([$asset_id,$qty,$requested,$purpose,$remarks,$_SESSION['user_id']]);
            $assetRow = $pdo->prepare('SELECT name, stock, reorder_qty FROM assets WHERE id = ?');
            $assetRow->execute([$asset_id]);
            $updated = $assetRow->fetch();
            pusherTrigger('inventory-channel', 'stock-out-recorded', [
                'asset_id'=>$asset_id,'asset_name'=>$updated['name'],
                'quantity'=>$qty,'requested_by'=>$requested,
                'purpose'=>$purpose,'remarks'=>$remarks,
                'created_at'=>date('Y-m-d H:i:s'),
            ]);
            pusherTrigger('inventory-channel', 'stock-updated', [
                'asset_id'=>$asset_id,'new_stock'=>$updated['stock'],
                'reorder_qty'=>$updated['reorder_qty'],
                'message'=>"Stock-Out: {$updated['name']} now has {$updated['stock']} units.",
            ]);
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Transaction failed: ' . $e->getMessage();
        }
    }
}
?>

<div class="topbar">
  <div>
    <div class="topbar-title">Record Stock-Out</div>
    <div class="topbar-sub">Remove outgoing inventory</div>
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
        <label class="inv-label">Purpose</label>
        <select name="purpose" class="inv-form-control">
          <option>Use</option>
          <option>Repair</option>
          <option>Transfer</option>
          <option>Disposal</option>
          <option>Sale</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="inv-label">Requested By</label>
        <input type="text" name="requested_by" class="inv-form-control" placeholder="Name or department">
      </div>
      <div class="col-12">
        <label class="inv-label">Remarks</label>
        <textarea name="remarks" class="inv-form-control" rows="3" placeholder="Optional notes..."></textarea>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid #1f2b60">
      <button type="submit" class="btn-inv-danger"><i class="bi bi-check-lg"></i> Confirm Stock-Out</button>
      <a href="index.php" class="btn-inv-ghost">Cancel</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>