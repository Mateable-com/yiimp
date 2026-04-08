<?php

if (isset($_GET['algo']))
    user()->setState('yaamp-algo', $_GET['algo']);

$algo = user()->getState('yaamp-algo');

$workers = getdbolist('db_workers', "algo=:algo order by name", array(':algo' => $algo));

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-microchip me-2 text-info"></i>Active Workers: '.strtoupper($algo).'</h5>';
echo '    <div class="d-flex gap-2">';
echo '      <input class="search form-control form-control-sm border-secondary bg-dark text-white" type="search" data-column="all" style="width: 200px;" placeholder="Search rigs..." />';
echo '      <span class="badge bg-primary d-flex align-items-center px-3 fw-bold">Total: '.count($workers).'</span>';
echo '    </div>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="20"></th>';
echo '            <th>Coin</th>';
echo '            <th>Wallet / Rig Name</th>';
echo '            <th>Client / Version</th>';
echo '            <th>IP / DNS</th>';
echo '            <th class="text-end">Hashrate</th>';
echo '            <th class="text-end">Difficulty</th>';
echo '            <th class="text-end">Bad %</th>';
echo '            <th class="text-center">Found Blocks</th>';
echo '            <th class="text-end pe-4">Share %</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$total_rate = 0.0;
foreach ($workers as $worker) {
    $total_rate += yaamp_worker_rate($worker->id);
}

foreach ($workers as $worker) {
    $user_rate = yaamp_worker_rate($worker->id);
    $percent = $total_rate ? (100.0 * $user_rate) / $total_rate : 0.0;
    $user_bad = yaamp_worker_rate_bad($worker->id);
    $pct_bad = ($user_rate + $user_bad) ? round($user_bad * 100 / ($user_rate + $user_bad), 1) : 0;
    
    $user = $coin = NULL;
    if ($worker->userid) {
        $user = getdbo('db_accounts', $worker->userid);
        if ($user) $coin = getdbo('db_coins', $user->coinid);
    }

    $dns = !empty($worker->dns) ? $worker->dns : $worker->ip;
    if (strlen($dns) > 25) $dns = substr($dns, 0, 22).'...';

	echo '<tr>';
	echo '<td class="ps-4">';
    if ($coin) echo '<img src="'.$coin->image.'" width="18" class="rounded-circle shadow-sm">';
    else echo '<i class="fa fa-question-circle text-muted"></i>';
    echo '</td>';

	echo '<td>';
    if ($coin) echo '<b>'.CHtml::link($coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b>';
    else echo '-';
    echo '</td>';

	echo '<td>';
    echo '  <div class="fw-bold font-monospace" style="font-size: 0.8rem;">'.CHtml::link(substr($worker->name,0,12).'...', '/?address='.$worker->name, ['class'=>'text-primary text-decoration-none', 'target'=>'_blank']).'</div>';
    echo '  <div class="text-muted small">ID: '.$worker->worker.'</div>';
    echo '</td>';

	echo '<td>';
    echo '  <div class="fw-bold">'.$worker->password.'</div>';
    echo '  <div class="small text-muted">'.$worker->version.'</div>';
    echo '</td>';

    echo '<td><span class="badge bg-light text-dark border font-monospace" title="'.$worker->ip.'">'.$dns.'</span></td>';

	echo '<td class="text-end fw-bold text-dark">'.($user_rate ? Itoa2($user_rate).'h/s' : '-').'</td>';
	echo '<td class="text-end">'.round($worker->difficulty, 2).'</td>';

	echo '<td class="text-end">';
	if ($pct_bad > 0) {
        $b_class = $pct_bad > 10 ? 'bg-danger' : ($pct_bad > 5 ? 'bg-warning text-dark' : 'bg-light text-muted');
        echo '<span class="badge '.$b_class.'">'.$pct_bad.'%</span>';
    } else echo '<span class="text-muted">-</span>';
	echo '</td>';

    $worker_blocs = (int)dboscalar("SELECT COUNT(id) FROM blocks WHERE workerid=:worker AND algo=:algo", array(':worker'=>$worker->id, ':algo'=>$algo));
	echo '<td class="text-center fw-bold">'.($worker_blocs?:'-').'</td>';

	echo '<td class="text-end pe-4 text-muted">'.number_format($percent, 1).'%</td>';
	echo '</tr>';
}

echo "        </tbody>";
echo '        <tfoot class="table-dark">';
echo '          <tr>';
echo '            <th colspan="5" class="ps-4 small text-uppercase">Total Algo Hashrate</th>';
echo '            <th class="text-end text-warning">'.Itoa2($total_rate).'h/s</th>';
echo '            <th colspan="4"></th>';
echo '          </tr>';
echo '        </tfoot>';
echo "      </table></div></div></div>";
?>