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

<div class="topbar">
  <div>
    <div class="topbar-title">Stock-In Records</div>
    <div class="topbar-sub">Incoming inventory transactions</div>
  </div>
  <a href="create.php" class="btn-inv-success"><i class="bi bi-plus-lg"></i> New Stock-In</a>
</div>

<div id="si-live-alert" class="inv-alert success"></div>

<div class="inv-card">
  <table class="inv-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Asset</th>
        <th>Quantity</th>
        <th>Unit Cost</th>
        <th>Supplier</th>
        <th>Reference</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody id="si-tbody">
    <?php foreach ($rows as $r): ?>
      <tr>
        <td style="color:#8892b0"><?= h($r['created_at']) ?></td>
        <td style="font-weight:600"><?= h($r['asset_name']) ?></td>
        <td style="color:#22c55e;font-weight:700">+<?= $r['quantity'] ?></td>
        <td>₱<?= number_format($r['unit_cost'], 2) ?></td>
        <td><?= h($r['supplier'] ?? '—') ?></td>
        <td><code><?= h($r['reference'] ?? '—') ?></code></td>
        <td style="color:#8892b0"><?= h($r['remarks'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
      <tr><td colspan="7" style="text-align:center;color:#8892b0;padding:32px">No stock-in records yet</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
const channel = pusher.subscribe('inventory-channel');
channel.bind('stock-in-recorded', function(data) {
  const tbody = document.getElementById('si-tbody');
  const row   = document.createElement('tr');
  row.innerHTML = `
    <td style="color:#8892b0">${data.created_at}</td>
    <td style="font-weight:600">${data.asset_name}</td>
    <td style="color:#22c55e;font-weight:700">+${data.quantity}</td>
    <td>₱${parseFloat(data.unit_cost).toFixed(2)}</td>
    <td>${data.supplier||'—'}</td>
    <td><code>${data.reference||'—'}</code></td>
    <td style="color:#8892b0">${data.remarks||'—'}</td>`;
  tbody.prepend(row);
  const alert = document.getElementById('si-live-alert');
  alert.textContent = 'New stock-in recorded: ' + data.asset_name + ' +' + data.quantity;
  alert.classList.add('show');
  setTimeout(() => alert.classList.remove('show'), 4000);
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>