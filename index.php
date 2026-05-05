<?php
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

$totalAssets = $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
$totalStock  = $pdo->query('SELECT SUM(stock) FROM assets')->fetchColumn();
$lowStock    = $pdo->query('SELECT COUNT(*) FROM assets WHERE stock <= reorder_qty')->fetchColumn();
$totalIn     = $pdo->query('SELECT COUNT(*) FROM stock_in')->fetchColumn();
$totalOut    = $pdo->query('SELECT COUNT(*) FROM stock_out')->fetchColumn();

$recent = $pdo->query("
  SELECT 'IN' AS type, si.created_at, a.name, si.quantity
  FROM stock_in si JOIN assets a ON si.asset_id = a.id
  UNION ALL
  SELECT 'OUT', so.created_at, a.name, so.quantity
  FROM stock_out so JOIN assets a ON so.asset_id = a.id
  ORDER BY created_at DESC LIMIT 10
")->fetchAll();
?>
<h4 class="fw-bold mb-4">Dashboard</h4>
<div id="dash-live-alert" class="alert alert-info d-none"></div>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card text-center p-3 bg-light"><div class="text-muted small">Total Assets</div><div class="fs-3 fw-bold"><?= $totalAssets ?></div></div></div>
  <div class="col-md-3"><div class="card text-center p-3 bg-light"><div class="text-muted small">Total Units</div><div class="fs-3 fw-bold"><?= number_format($totalStock ?? 0) ?></div></div></div></div>
  <div class="col-md-3"><div class="card text-center p-3 <?= $lowStock > 0 ? 'bg-warning' : 'bg-light' ?>"><div class="text-muted small">Low Stock Alerts</div><div class="fs-3 fw-bold"><?= $lowStock ?></div></div></div>
  <div class="col-md-3"><div class="card text-center p-3 bg-light"><div class="text-muted small">Transactions</div><div class="fs-3 fw-bold"><?= $totalIn + $totalOut ?></div></div></div>
</div>
<h6 class="fw-bold mb-2">Recent Activity</h6>
<table class="table table-sm table-bordered">
  <thead class="table-dark"><tr><th>Type</th><th>Asset</th><th>Qty</th><th>Date</th></tr></thead>
  <tbody id="dash-tbody">
  <?php foreach ($recent as $r): ?>
    <tr>
      <td><span class="badge <?= $r['type']==='IN'?'bg-success':'bg-danger' ?>"><?= $r['type'] ?></span></td>
      <td><?= h($r['name']) ?></td>
      <td><?= $r['type']==='IN'?'+':'-' ?><?= $r['quantity'] ?></td>
      <td><?= h($r['created_at']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
const channel = pusher.subscribe('inventory-channel');
channel.bind('stock-in-recorded', function(data) { prependRow('IN', data); });
channel.bind('stock-out-recorded', function(data) { prependRow('OUT', data); });
function prependRow(type, data) {
  const tbody = document.getElementById('dash-tbody');
  const row   = document.createElement('tr');
  const color = type === 'IN' ? 'bg-success' : 'bg-danger';
  const sign  = type === 'IN' ? '+' : '-';
  row.innerHTML = `<td><span class="badge ${color}">${type}</span></td>
    <td>${data.asset_name}</td><td>${sign}${data.quantity}</td><td>${data.created_at}</td>`;
  tbody.prepend(row);
  const alert = document.getElementById('dash-live-alert');
  alert.textContent = (type==='IN'?'Stock-In':'Stock-Out') + ': ' + data.asset_name + ' ' + sign + data.quantity + ' units';
  alert.classList.remove('d-none');
  setTimeout(() => alert.classList.add('d-none'), 5000);
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>