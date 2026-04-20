<?php

$user = getuserparam(getparam('address'));
if (!$user) return;

$userid = intval($user->id);

$workers = getdbolist('db_workers', "userid=:userid ORDER BY algo, name", array(':userid' => $userid));

if (empty($workers)) {
    echo '<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">';
    echo '  <div class="card-body text-center py-5 text-muted">';
    echo '    <i class="fa fa-microchip fa-2x mb-3 opacity-50"></i>';
    echo '    <p class="mb-0">No active workers connected.</p>';
    echo '  </div>';
    echo '</div>';
    return;
}

$now = time();
$interval = yaamp_hashrate_step();
$delay = $now - $interval;

echo '<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-microchip me-2 text-primary"></i>Your Workers</h5>';
echo '    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">'.count($workers).' connected</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size:0.65rem;letter-spacing:1px;">';
echo '          <tr>';
echo '            <th class="ps-4">Worker Name</th>';
echo '            <th>Algo</th>';
echo '            <th>Client</th>';
echo '            <th class="text-end">Hashrate</th>';
echo '            <th class="text-end">Difficulty</th>';
echo '            <th class="text-center">Bad %</th>';
echo '            <th class="text-end">Blocks</th>';
echo '            <th class="text-end pe-4">Last Share</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$total_rate = 0;
foreach ($workers as $worker) {
    $rate     = yaamp_worker_rate($worker->id);
    $rate_bad = yaamp_worker_rate_bad($worker->id);
    $total_rate += $rate;

    $pct_bad = ($rate + $rate_bad) > 0 ? round($rate_bad * 100 / ($rate + $rate_bad), 1) : 0;
    $bad_badge = '';
    if ($pct_bad > 10)      $bad_badge = '<span class="badge bg-danger">'.$pct_bad.'%</span>';
    elseif ($pct_bad > 5)   $bad_badge = '<span class="badge bg-warning text-dark">'.$pct_bad.'%</span>';
    elseif ($pct_bad > 0)   $bad_badge = '<span class="badge bg-secondary">'.$pct_bad.'%</span>';
    else                    $bad_badge = '<span class="text-muted">—</span>';

    $blocks_found = (int) dboscalar(
        "SELECT COUNT(id) FROM blocks WHERE workerid=:wid AND category != 'orphan'",
        array(':wid' => $worker->id)
    );

    // Last share time
    $last_share = dboscalar(
        "SELECT MAX(time) FROM shares WHERE workerid=:wid AND time > :delay",
        array(':wid' => $worker->id, ':delay' => $now - 24*3600)
    );
    if ($last_share) {
        $age = $now - (int)$last_share;
        if ($age < 60)         $last_str = $age.'s ago';
        elseif ($age < 3600)   $last_str = round($age/60).'m ago';
        elseif ($age < 86400)  $last_str = round($age/3600, 1).'h ago';
        else                   $last_str = date('M j H:i', (int)$last_share);
        $last_cls = $age > 1800 ? 'text-warning' : 'text-success';
    } else {
        $last_str = '<span class="text-muted">—</span>';
        $last_cls = 'text-muted';
    }

    // Worker display name: strip wallet prefix (e.g. "addr.rigname" → "rigname")
    $display_name = $worker->name;
    if (strpos($display_name, '.') !== false)
        $display_name = substr($display_name, strpos($display_name, '.') + 1);

    $rate_str = $rate ? '<span class="fw-bold">'.Itoa2($rate).'h/s</span>' : '<span class="text-muted">—</span>';

    echo '<tr>';
    echo '  <td class="ps-4 font-monospace fw-bold" style="font-size:0.8rem;">'.htmlspecialchars($display_name).'</td>';
    echo '  <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">'.htmlspecialchars($worker->algo).'</span></td>';
    echo '  <td class="text-muted small">'.htmlspecialchars(substr($worker->version, 0, 20)).'</td>';
    echo '  <td class="text-end">'.$rate_str.'</td>';
    echo '  <td class="text-end text-muted">'.round($worker->difficulty, 2).'</td>';
    echo '  <td class="text-center">'.$bad_badge.'</td>';
    echo '  <td class="text-end fw-bold">'.($blocks_found ?: '<span class="text-muted">—</span>').'</td>';
    echo '  <td class="text-end pe-4 '.$last_cls.' small">'.$last_str.'</td>';
    echo '</tr>';
}

echo '        </tbody>';
if ($total_rate > 0) {
    echo '        <tfoot class="table-light">';
    echo '          <tr>';
    echo '            <td colspan="3" class="ps-4 small text-muted fw-bold text-uppercase">Total</td>';
    echo '            <td class="text-end fw-bold text-primary">'.Itoa2($total_rate).'h/s</td>';
    echo '            <td colspan="4"></td>';
    echo '          </tr>';
    echo '        </tfoot>';
}
echo '      </table>';
echo '    </div>';
echo '  </div>';
echo '</div>';
