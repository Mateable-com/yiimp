<?php

$symbol = getparam('symbol');
$coin = null;

if($symbol == 'all')
	$users = getdbolist('db_accounts', "balance>.001 OR id IN (SELECT DISTINCT userid FROM workers) ORDER BY balance DESC");
else
{
	$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
	if(!$coin) return;
	$users = getdbolist('db_accounts', "coinid={$coin->id} AND (balance>.001 OR id IN (SELECT DISTINCT userid FROM workers)) ORDER BY balance DESC");
}

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-users me-2 text-primary"></i>User Management '.($coin ? "($coin->symbol)" : "(All)").'</h5>';
echo '    <div class="d-flex gap-2">';
echo '      <input class="search form-control form-control-sm border-secondary bg-dark text-white" type="search" data-column="all" style="width: 200px;" placeholder="Search miners..." />';
echo '      <span class="badge bg-primary d-flex align-items-center px-3">Total: '.count($users).'</span>';
echo '    </div>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="50">UID</th>';
echo '            <th>Coin</th>';
echo '            <th>Wallet Address</th>';
if (YAAMP_RENTAL) echo '            <th>Linked Renter BTC</th>';
echo '            <th class="text-center">Workers</th>';
echo '            <th class="text-end">Hashrate</th>';
echo '            <th class="text-end">Bad %</th>';
echo '            <th class="text-end">Blocks</th>';
echo '            <th class="text-end">Balance</th>';
echo '            <th class="text-end">Total Paid</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$total_balance = 0;
$total_paid = 0;

foreach($users as $user)
{
	$target = yaamp_hashrate_constant();
	$interval = yaamp_hashrate_step(); 
	$delay = time()-$interval;

	$user_rate = dboscalar("SELECT (sum(difficulty) * $target / $interval / 1000) FROM shares WHERE valid AND time>$delay AND userid=".$user->id);
	$user_bad = yaamp_user_rate_bad($user->id);
	$pct_bad = $user_rate? round($user_bad*100/$user_rate, 1): 0;

	$balance = bitcoinvaluetoa($user->balance);
    $paid_raw = dboscalar("SELECT sum(amount) FROM payouts WHERE account_id=".$user->id);
	$paid = bitcoinvaluetoa($paid_raw);
	$d = datetoa2($user->last_earning);

	$miner_count = getdbocount('db_workers', "userid=".$user->id);
	$block_count = getdbocount('db_blocks', "userid=".$user->id);

	$user_coin = null;
	if ($user->coinid > 0) {
		$user_coin = getdbosql('db_coins', "id=:id", array(':id'=>$user->coinid));
	}

	echo '<tr>';
	echo '<td class="ps-4 text-muted">'.$user->id.'</td>';
	echo '<td>';
    if ($user_coin) {
        echo '<img src="'.$user_coin->image.'" width="20" class="me-1 shadow-sm rounded-circle">';
        echo '<b>'.CHtml::link($user_coin->symbol, '/admin/coin?id='.$user_coin->id, ['class'=>'text-decoration-none text-dark']).'</b>';
    } else echo '-';
    echo '</td>';
	echo '<td>';
    echo '  <div class="fw-bold font-monospace" style="font-size: 0.8rem;">'.CHtml::link(substr($user->username,0,20).'...', '/?address='.$user->username, ['class'=>'text-primary text-decoration-none', 'target'=>'_blank']).'</div>';
    echo '  <div class="text-muted" style="font-size: 0.65rem;">Last: '.$d.' ago</div>';
    echo '</td>';

    if (YAAMP_RENTAL) {
        echo '<td>';
        if (!empty($user->rent_address)) {
            $rent_user = getdbosql('db_accounts', "username=:address", array(':address'=>$user->rent_address));
            $rent_balance = $rent_user ? bitcoinvaluetoa($rent_user->balance) : '0.00000000';
            echo '<div class="fw-bold font-monospace small text-dark">'.substr($user->rent_address,0,12).'...</div>';
            echo '<div class="text-success small fw-bold">'.$rent_balance.' BTC</div>';
        } else echo '<span class="text-muted small">None</span>';
        echo '</td>';
    }

	echo '<td class="text-center"><span class="badge bg-secondary rounded-pill">'.$miner_count.'</span></td>';

	echo '<td class="text-end fw-bold text-dark">'.($user_rate ? Itoa2($user_rate).'h/s' : '-').'</td>';
	echo '<td class="text-end">';
	if ($pct_bad > 0) {
        $b_class = $pct_bad > 10 ? 'bg-danger' : ($pct_bad > 5 ? 'bg-warning text-dark' : 'bg-light text-muted');
        echo '<span class="badge '.$b_class.'">'.$pct_bad.'%</span>';
    } else echo '<span class="text-muted">-</span>';
	echo '</td>';

	echo '<td class="text-end fw-bold">'.($block_count?:'-').'</td>';
	echo '<td class="text-end text-success fw-bold">'.$balance.'</td>';
	echo '<td class="text-end fw-bold">'.$paid.'</td>';

	echo '<td class="text-end pe-4">';
    echo '  <div class="btn-group">';
    
	if ($user->logtraffic)
		echo '    <a href="/admin/loguser?id='.$user->id.'&en=0" class="btn btn-xs btn-outline-info py-0 px-2" title="Unwatch"><i class="fa fa-eye-slash"></i></a>';
	else
		echo '    <a href="/admin/loguser?id='.$user->id.'&en=1" class="btn btn-xs btn-outline-secondary py-0 px-2" title="Watch"><i class="fa fa-eye"></i></a>';

	if ($user->is_locked)
		echo '    <a href="/admin/unblockuser?wallet='.$user->username.'" class="btn btn-xs btn-outline-warning py-0 px-2" title="Unblock"><i class="fa fa-unlock"></i></a>';
	else
		echo '    <a href="/admin/blockuser?wallet='.$user->username.'" class="btn btn-xs btn-outline-dark py-0 px-2" title="Block"><i class="fa fa-lock"></i></a>';

	echo '    <a href="/admin/banuser?id='.$user->id.'" class="btn btn-xs btn-outline-danger py-0 px-2" onclick="return confirm(\'BAN this user?\')" title="BAN"><i class="fa fa-ban"></i></a>';
    echo '  </div>';
	echo '</td>';
	echo '</tr>';

	$total_balance += $user->balance;
	$total_paid += $paid_raw;
}

echo "        </tbody>";
echo '        <tfoot class="table-dark">';
echo '          <tr>';
echo '            <th colspan="7" class="ps-4 text-uppercase small">Combined User Totals</th>';
echo '            <th class="text-end text-warning">'.bitcoinvaluetoa($total_balance).' BTC</th>';
echo '            <th class="text-end">'.bitcoinvaluetoa($total_paid).' BTC</th>';
echo '            <th></th>';
echo '          </tr>';
echo '        </tfoot>';
echo "      </table>";
echo "    </div>";
echo "  </div>";
echo "</div>";

if($coin)
{
    $wallet_balance = $coin->balance;
    $profit = $wallet_balance - $total_balance;
    $p_class = $profit >= 0 ? 'text-success' : 'text-danger';

    echo '<div class="row g-3">';
    echo '  <div class="col-md-6">';
    echo '    <div class="card shadow-sm border-0 border-start border-4 border-info h-100">';
    echo '      <div class="card-body py-3 d-flex justify-content-between align-items-center">';
    echo '        <div><div class="small text-muted text-uppercase fw-bold">Wallet Balance</div><h4 class="mb-0 fw-bold">'.bitcoinvaluetoa($wallet_balance).' <small class="fs-6">BTC</small></h4></div>';
    echo '        <i class="fa fa-wallet fa-2x opacity-25 text-info"></i>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '  <div class="col-md-6">';
    echo '    <div class="card shadow-sm border-0 border-start border-4 border-'.($profit >= 0 ? 'success' : 'danger').' h-100">';
    echo '      <div class="card-body py-3 d-flex justify-content-between align-items-center">';
    echo '        <div><div class="small text-muted text-uppercase fw-bold">Pool Profit</div><h4 class="mb-0 fw-bold '.$p_class.'">'.bitcoinvaluetoa($profit).' <small class="fs-6">BTC</small></h4></div>';
    echo '        <i class="fa fa-chart-line fa-2x opacity-25 '.($profit >= 0 ? 'text-success' : 'text-danger').'"></i>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}
?>