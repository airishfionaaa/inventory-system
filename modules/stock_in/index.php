<?php
require_once __DIR__ . '/../../includes/header.php';
$pdo  = getDB();
$rows = $pdo->query('
  SELECT si.*, a.name AS asset_name
  FROM stock_in si
  JOIN assets a ON si.asset_id = a.id
  ORDER BY si.created_at DESC LIMIT 100
')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold mb-0">Stock-In Records</h4>
  <a href="create.php" class="btn btn-success btn-sm">+ New Stock-In</a>
</div>
<div id="si-live-alert" class="alert alert-success d-none"></div>
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>Date</th><th>Asset</th><th>Qty</th><th>Cost</th><th>Supplier</th><th>Reference</th><th>Remarks</th></tr>
  </thead>
  <tbody id="si-tbody">
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['created_at']) ?></td>
      <td><?= h($r['asset_name']) ?></td>
      <td class="text-success fw-bold">+<?= $r['quantity'] ?></td>
      <td>₱<?= number_format($r['unit_cost'], 2) ?></td>
      <td><?= h($r['supplier'] ?? '') ?></td>
      <td><?= h($r['reference'] ?? '') ?></td>
      <td><?= h($r['remarks'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
const channel = pusher.subscribe('inventory-channel');
channel.bind('stock-in-recorded', function(data) {
  const tbody = document.getElementById('si-tbody');
  const row   = document.createElement('tr');
  row.innerHTML = `<td>${data.created_at}</td><td>${data.asset_name}</td>
    <td class="text-success fw-bold">+${data.quantity}</td>
    <td>₱${parseFloat(data.unit_cost).toFixed(2)}</td>
    <td>${data.supplier||''}</td><td>${data.reference||''}</td><td>${data.remarks||''}</td>`;
  tbody.prepend(row);
  const alert = document.getElementById('si-live-alert');
  alert.textContent = 'New stock-in recorded: ' + data.asset_name + ' +' + data.quantity;
  alert.classList.remove('d-none');
  setTimeout(() => alert.classList.add('d-none'), 4000);
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>