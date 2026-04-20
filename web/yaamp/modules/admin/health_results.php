<?php

// Thresholds
define('HEALTH_LOW_PEERS',  2);    // < 2 connections = warning
define('HEALTH_BLOCK_LAG',  500);  // >500 blocks behind target = syncing

$now = time();

// Load all enabled coins
$coins = getdbolist('db_coins', "enable=1 ORDER BY algo, name");

// Categorise each coin
$offline = $low_peers = $syncing = $healthy = 0;
$coin_data = [];

foreach ($coins as $coin) {
    $issues = [];
    $severity = 'ok'; // ok | warning | danger

    // Offline: connections == 0 means daemon has no peers (isolated or down).
    // We don't use $coin->errors for this because wallets report non-empty errors
    // for normal warnings (e.g. "Warning: unknown new rules activated") even when
    // running perfectly.
    $conns = (string)$coin->connections;
    $is_offline = ($conns !== '' && (int)$conns === 0);
    if ($is_offline) {
        $issues[] = '<span class="badge bg-danger">No Peers</span>';
        $severity = 'danger';
        $offline++;
    }

    // Wallet warning: non-empty errors field that isn't a connection failure
    if (!empty($coin->errors)) {
        $short_err = htmlspecialchars(substr($coin->errors, 0, 60));
        $issues[] = '<span class="badge bg-warning text-dark" title="'.htmlspecialchars($coin->errors).'">Warn: '.$short_err.(strlen($coin->errors)>60?'…':'').'</span>';
        if ($severity == 'ok') $severity = 'warning';
    }

    // Syncing
    if ($coin->target_height > 0 && ($coin->target_height - $coin->block_height) > HEALTH_BLOCK_LAG) {
        $pct = $coin->block_height > 0 ? round($coin->block_height * 100 / $coin->target_height, 1) : 0;
        $issues[] = '<span class="badge bg-warning text-dark">Syncing '.$pct.'%</span>';
        if ($severity == 'ok') $severity = 'warning';
        $syncing++;
    }

    // Low peers (but not zero — zero is already "No Peers" above)
    if (!$is_offline && $conns !== '' && (int)$conns > 0 && (int)$conns < HEALTH_LOW_PEERS) {
        $issues[] = '<span class="badge bg-secondary">Low Peers ('.(int)$conns.')</span>';
        if ($severity == 'ok') $severity = 'warning';
        $low_peers++;
    }

    // Negative available balance
    if (!$is_offline && $coin->available < 0) {
        $issues[] = '<span class="badge bg-danger">Neg. Balance</span>';
        $severity = 'danger';
    }

    if (empty($issues)) {
        $healthy++;
        $issues[] = '<span class="badge bg-success bg-opacity-75">OK</span>';
    }

    $coin_data[] = [
        'coin'     => $coin,
        'severity' => $severity,
        'issues'   => $issues,
    ];
}

// Sort: danger first, then warning, then ok
usort($coin_data, function($a, $b) {
    $order = ['danger'=>0,'warning'=>1,'ok'=>2];
    return $order[$a['severity']] - $order[$b['severity']];
});

// --- Summary cards ---
$total = count($coins);
$summary  = '<div class="col-6 col-md-3 col-xl">';
$summary .= '  <div class="card border-0 shadow-sm rounded-3 text-center p-3">';
$summary .= '    <div class="fs-2 fw-bold text-dark">'.$total.'</div>';
$summary .= '    <div class="small text-muted text-uppercase">Total Active</div>';
$summary .= '  </div></div>';

$summary .= '<div class="col-6 col-md-3 col-xl">';
$summary .= '  <div class="card border-0 shadow-sm rounded-3 text-center p-3 '.($healthy==$total?'border-success':'').'">';
$summary .= '    <div class="fs-2 fw-bold text-success">'.$healthy.'</div>';
$summary .= '    <div class="small text-muted text-uppercase">Healthy</div>';
$summary .= '  </div></div>';

$summary .= '<div class="col-6 col-md-3 col-xl">';
$summary .= '  <div class="card border-0 shadow-sm rounded-3 text-center p-3">';
$summary .= '    <div class="fs-2 fw-bold '.($offline?'text-danger':'text-muted').'">'.$offline.'</div>';
$summary .= '    <div class="small text-muted text-uppercase">Offline</div>';
$summary .= '  </div></div>';

$summary .= '<div class="col-6 col-md-3 col-xl">';
$summary .= '  <div class="card border-0 shadow-sm rounded-3 text-center p-3">';
$summary .= '    <div class="fs-2 fw-bold '.($syncing?'text-warning':'text-muted').'">'.$syncing.'</div>';
$summary .= '    <div class="small text-muted text-uppercase">Syncing</div>';
$summary .= '  </div></div>';

$summary .= '<div class="col-6 col-md-3 col-xl">';
$summary .= '  <div class="card border-0 shadow-sm rounded-3 text-center p-3">';
$summary .= '    <div class="fs-2 fw-bold '.($low_peers?'text-secondary':'text-muted').'">'.$low_peers.'</div>';
$summary .= '    <div class="small text-muted text-uppercase">Low Peers</div>';
$summary .= '  </div></div>';

// --- Table ---
ob_start();
?>
<div class="card shadow-sm border-0 rounded-4 overflow-hidden">
  <div class="card-header bg-dark text-white py-3 px-4 border-0 d-flex align-items-center justify-content-between">
    <h6 class="mb-0 fw-bold"><i class="fa fa-list me-2 text-primary"></i>Wallet Status</h6>
    <span class="text-muted small">Updated: <?= date('H:i:s') ?></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light text-muted text-uppercase" style="font-size:0.65rem;letter-spacing:1px;">
          <tr>
            <th class="ps-4">Coin</th>
            <th>Algo</th>
            <th>RPC Host</th>
            <th class="text-center">Peers</th>
            <th class="text-end">Height</th>
            <th class="text-end">Balance</th>
            <th>Status</th>
            <th class="pe-4"></th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($coin_data as $entry):
    $coin    = $entry['coin'];
    $issues  = $entry['issues'];
    $sev     = $entry['severity'];
    $row_cls = $sev == 'danger' ? 'table-danger' : ($sev == 'warning' ? 'table-warning' : '');

    $peers    = (int)$coin->connections;
    $peer_cls = $peers === 0 ? 'text-danger fw-bold' : ($peers < HEALTH_LOW_PEERS ? 'text-warning fw-bold' : 'text-success');

    $height_str = number_format($coin->block_height);
    if ($coin->target_height > 0 && ($coin->target_height - $coin->block_height) > HEALTH_BLOCK_LAG) {
        $height_str .= ' <span class="text-muted small">/ '.number_format($coin->target_height).'</span>';
    }
?>
          <tr class="<?= $row_cls ?>">
            <td class="ps-4">
              <div class="d-flex align-items-center gap-2">
                <img src="<?= htmlspecialchars($coin->image) ?>" width="28" class="rounded-circle bg-white shadow-sm p-1">
                <div>
                  <a href="/admin/coin?id=<?= $coin->id ?>" class="fw-bold text-decoration-none text-dark"><?= htmlspecialchars($coin->name) ?></a>
                  <div class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($coin->symbol) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><?= htmlspecialchars($coin->algo) ?></span></td>
            <td class="font-monospace text-muted small"><?= htmlspecialchars($coin->rpchost) ?>:<?= htmlspecialchars($coin->rpcport) ?></td>
            <td class="text-center <?= $peer_cls ?>"><?= $peers ?></td>
            <td class="text-end font-monospace small"><?= $height_str ?></td>
            <td class="text-end"><?= bitcoinvaluetoa($coin->balance) ?></td>
            <td><?= implode(' ', $issues) ?></td>
            <td class="pe-4">
              <div class="btn-group btn-group-sm">
                <a href="/admin/coin?id=<?= $coin->id ?>" class="btn btn-outline-secondary py-0 px-2" title="Wallet"><i class="fa fa-eye"></i></a>
                <a href="/admin/coinupdate?id=<?= $coin->id ?>" class="btn btn-outline-primary py-0 px-2" title="Edit"><i class="fa fa-edit"></i></a>
                <?php if (YAAMP_ADMIN_WEBCONSOLE): ?>
                <a href="/admin/coinconsole?id=<?= $coin->id ?>" class="btn btn-outline-dark py-0 px-2" title="Console"><i class="fa fa-terminal"></i></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$table = ob_get_clean();

header('Content-Type: application/json');
echo json_encode(['summary' => $summary, 'table' => $table]);
