<?php
require_once __DIR__ . '/../../includes/header.php';
$pdo = getDB();
$stmt = $pdo->query('
  SELECT a.*, c.name AS category, b.name AS brand
  FROM assets a
  JOIN categories c ON a.category_id = c.id
  JOIN brands b     ON a.brand_id    = b.id
  ORDER BY a.name
');
$assets = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold mb-0">Assets</h4>
  <a href="create.php" class="btn btn-dark btn-sm">+ Add Asset</a>
</div>
<div id="asset-live-alert" class="alert alert-success d-none"></div>
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>Name</th><th>SKU</th><th>Category</th><th>Brand</th><th>Stock</th><th>Reorder</th><th>Price</th><th>Location</th><th>Actions</th></tr>
  </thead>
  <tbody id="asset-tbody">
  <?php foreach ($assets as $a): ?>
    <tr id="asset-row-<?= $a['id'] ?>">
      <td><?= h($a['name']) ?></td>
      <td><code><?= h($a['sku']) ?></code></td>
      <td><?= h($a['category']) ?></td>
      <td><?= h($a['brand']) ?></td>
      <td class="asset-stock <?= $a['stock'] <= $a['reorder_qty'] ? 'text-danger fw-bold' : '' ?>"><?= $a['stock'] ?></td>
      <td><?= $a['reorder_qty'] ?></td>
      <td>₱<?= number_format($a['unit_price'], 2) ?></td>
      <td><?= h($a['location'] ?? '') ?></td>
      <td>
        <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
        <a href="delete.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Delete this asset?')">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<script>
const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
const channel = pusher.subscribe('inventory-channel');

channel.bind('stock-updated', function(data) {
  const row = document.getElementById('asset-row-' + data.asset_id);
  if (row) {
    const cell = row.querySelector('.asset-stock');
    cell.textContent = data.new_stock;
    cell.className = 'asset-stock ' + (parseInt(data.new_stock) <= parseInt(data.reorder_qty) ? 'text-danger fw-bold' : '');
  }
  const alert = document.getElementById('asset-live-alert');
  alert.textContent = data.message;
  alert.classList.remove('d-none');
  setTimeout(() => alert.classList.add('d-none'), 4000);
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>