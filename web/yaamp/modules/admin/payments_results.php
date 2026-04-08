<?php

$coin_id = getiparam('id');
$sqlFilter = $coin_id ? "AND coinid={$coin_id}" : "";
$limit = $coin_id ? '' : 'LIMIT 100';

$target_coin = $coin_id ? getdbo('db_coins', $coin_id) : null;

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-money-check-alt me-2 text-success"></i>Pending Payments '.($target_coin ? "({$target_coin->symbol})" : "").'</h5>';
echo '    <div class="d-flex gap-2">';
echo '      <input class="search form-control form-control-sm border-secondary bg-dark text-white" type="search" data-column="all" style="width: 200px;" placeholder="Search payments..." />';
if ($coin_id) echo '      <a href="/admin/cancelUsersPayment?id='.$coin_id.'" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold" onclick="return confirm(\'Reset all failed payouts for this coin?\')">Reset All Failed</a>';
echo '    </div>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4">Coin</th>';
echo '            <th>Wallet Address</th>';
echo '            <th>Last Earning</th>';
echo '            <th class="text-end">Pool Balance</th>';
echo '            <th class="text-end">User Balance</th>';
echo '            <th class="text-end">Immature</th>';
echo '            <th class="text-end">Failed</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

// Pre-fetch immature data
$data = dbolist("SELECT coinid, userid, SUM(amount) AS immature FROM earnings WHERE status=0 $sqlFilter GROUP BY coinid, userid");
$immature = array();
if (!empty($data)) foreach ($data as $row) {
	$immkey = $row['coinid']."-".$row['userid'];
	$immature[$immkey] = $row['immature'];
}

// Pre-fetch failed data
$data = dbolist("SELECT account_id, SUM(amount) AS failed FROM payouts WHERE tx IS NULL AND completed=0 GROUP BY account_id");
$failed = array();
if (!empty($data)) foreach ($data as $row) {
	$uid = $row['account_id'];
	$failed[$uid] = $row['failed'];
}

$list = getdbolist('db_accounts', "is_locked != 1 $sqlFilter AND (".
	"balance > 0 OR last_earning > (UNIX_TIMESTAMP()-60*60) OR id IN (SELECT DISTINCT account_id FROM payouts WHERE tx IS NULL)".
	") ORDER BY last_earning DESC $limit");

$total = 0.; $totalimmat = 0.; $totalfailed = 0.;

foreach($list as $user)
{
	$coin = getdbo('db_coins', $user->coinid);
	$d = datetoa2($user->last_earning);
    $immkey = $coin ? "{$coin->id}-{$user->id}" : "0-{$user->id}";
    
    $immbalance = arraySafeVal($immature, $immkey, 0);
    $failbalance = arraySafeVal($failed, $user->id, 0);
    
    $total += (double) $user->balance;
    $totalimmat += (double) $immbalance;
    $totalfailed += (double) $failbalance;

	echo '<tr>';
	echo '<td class="ps-4">';
    if ($coin) {
        echo '<img src="'.$coin->image.'" width="20" class="me-2 rounded-circle shadow-sm">';
        echo '<b>'.CHtml::link($coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b>';
    } else echo '-';
    echo '</td>';

	echo '<td>';
    echo '  <div class="fw-bold font-monospace" style="font-size: 0.8rem;">'.CHtml::link(substr($user->username,0,20).'...', '/?address='.$user->username, ['class'=>'text-primary text-decoration-none', 'target'=>'_blank']).'</div>';
    echo '</td>';

	echo '<td><span class="text-muted small">'.$d.' ago</span></td>';

    // Pool Balance
    $coin_bal = ($coin && $coin->balance) ? bitcoinvaluetoa($coin->balance) : '-';
	echo '<td class="text-end text-muted">'.$coin_bal.'</td>';

    // User Balance
	echo '<td class="text-end fw-bold text-success">'.($user->balance ? bitcoinvaluetoa($user->balance) : '-').'</td>';

    // Immature
	echo '<td class="text-end text-info">'.($immbalance ? bitcoinvaluetoa($immbalance) : '-').'</td>';

    // Failed
	echo '<td class="text-end text-danger fw-bold">'.($failbalance ? bitcoinvaluetoa($failbalance) : '-').'</td>';

	echo '<td class="text-end pe-4">';
	if ($failbalance > 0)
		echo '<a href="/admin/cancelUserPayment?id='.$user->id.'" class="btn btn-xs btn-outline-warning py-0 px-2 fw-bold" title="Add back to balance">RECOVER</a>';
    else echo '<span class="text-muted small">-</span>';
	echo '</td>';

	echo "</tr>";
}

echo '        </tbody>';
echo '      </table>';
echo '    </div>';
echo '  </div>';
echo '</div>';

if ($coin_id && $target_coin) {
    echo '<div class="row g-3 justify-content-end">';
    echo '  <div class="col-md-3">';
    echo '    <div class="card shadow-sm border-0 border-top border-4 border-success">';
    echo '      <div class="card-body p-3 text-center">';
    echo '        <div class="text-muted small fw-bold text-uppercase mb-1">Total Balances</div>';
    echo '        <h5 class="mb-0 fw-bold">'.bitcoinvaluetoa($total).' <small class="text-muted">'.$target_coin->symbol.'</small></h5>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '  <div class="col-md-3">';
    echo '    <div class="card shadow-sm border-0 border-top border-4 border-info">';
    echo '      <div class="card-body p-3 text-center">';
    echo '        <div class="text-muted small fw-bold text-uppercase mb-1">Total Immature</div>';
    echo '        <h5 class="mb-0 fw-bold">'.bitcoinvaluetoa($totalimmat).' <small class="text-muted">'.$target_coin->symbol.'</small></h5>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    if ($totalfailed > 0) {
        echo '  <div class="col-md-3">';
        echo '    <div class="card shadow-sm border-0 border-top border-4 border-danger">';
        echo '      <div class="card-body p-3 text-center">';
        echo '        <div class="text-muted small fw-bold text-uppercase mb-1 text-danger">Total Failed</div>';
        echo '        <h5 class="mb-0 fw-bold text-danger">'.bitcoinvaluetoa($totalfailed).' <small>'.$target_coin->symbol.'</small></h5>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
    }
    echo '</div>';
}
?>