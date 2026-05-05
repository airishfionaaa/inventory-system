<?php
require_once __DIR__ . '/../../includes/header.php';
$pdo  = getDB();
$rows = $pdo->query('
  SELECT so.*, a.name AS asset_name
  FROM stock_out so
  JOIN assets a ON so.asset_id = a.id
  ORDER BY so.created_at DESC LIMIT 100
')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold mb-0">Stock-Out Records</h4>
  <a href="create.php" class="btn btn-danger btn-sm">+ New Stock-Out</a>
</div>
<div id="so-live-alert" class="alert alert-warning d-none"></div>
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>Date</th><th>Asset</th><th>Qty</th><th>Requested By</th><th>Purpose</th><th>Remarks</th></tr>
  </thead>
  <tbody id="so-tbody">
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['created_at']) ?></td>
      <td><?= h($r['asset_name']) ?></td>
      <td class="text-danger fw-bold">-<?= $r['quantity'] ?></td>
      <td><?= h($r['requested_by'] ?? '') ?></td>
      <td><?= h($r['purpose']) ?></td>
      <td><?= h($r['remarks'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
const channel = pusher.subscribe('inventory-channel');
channel.bind('stock-out-recorded', function(data) {
  const tbody = document.getElementById('so-tbody');
  const row   = document.createElement('tr');
  row.innerHTML = `<td>${data.created_at}</td><td>${data.asset_name}</td>
    <td class="text-danger fw-bold">-${data.quantity}</td>
    <td>${data.requested_by||''}</td><td>${data.purpose}</td><td>${data.remarks||''}</td>`;
  tbody.prepend(row);
  const alert = document.getElementById('so-live-alert');
  alert.textContent = 'New stock-out recorded: ' + data.asset_name + ' -' + data.quantity;
  alert.classList.remove('d-none');
  setTimeout(() => alert.classList.add('d-none'), 4000);
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>