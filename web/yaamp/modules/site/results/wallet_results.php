<?php

$mining = getdbosql('db_mining');
$defaultalgo = user()->getState('yaamp-algo');
$show_details = getparam('showdetails');

$user = getuserparam(getparam('address'));
if(!$user) return;

$refcoin = getdbo('db_coins', $user->coinid);
if(!$refcoin) {
	$refcoin = getdbosql('db_coins', "symbol='BTC'");
}

echo '<div class="container-fluid py-4">';

// --- Wallet Hero Header ---
echo '<div class="card shadow-sm border-0 mb-4 bg-dark text-white rounded-3 overflow-hidden">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4 shadow-sm">';
echo '      <i class="fa fa-wallet fa-2x text-primary"></i>';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <div class="small text-muted text-uppercase fw-bold mb-1">Miner Wallet Details</div>';
echo '      <h3 class="mb-0 fw-bold font-monospace text-break">'.$user->username.'</h3>';
echo '    </div>';
echo '    <div class="text-end d-none d-md-block border-start border-secondary border-opacity-25 ps-4 ms-4">';
echo '      <div class="small text-muted mb-1 text-uppercase fw-bold">Reference Coin</div>';
echo '      <div class="d-flex align-items-center justify-content-end">';
echo '        <img src="'.$refcoin->image.'" width="24" class="me-2 rounded-circle shadow-sm">';
echo '        <h4 class="mb-0 fw-bold">'.$refcoin->symbol.'</h4>';
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

// --- Earnings Overview Card ---
echo '<div class="card shadow-sm border-0 mb-4 rounded-3">';
echo '  <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-chart-pie me-2 text-info"></i>Pending Earnings</h5>';
if(!$show_details) {
    echo '    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="showDetailsSwitch" onclick="javascript:main_wallet_refresh_details()"><label class="form-check-label small fw-bold text-muted" for="showDetailsSwitch">Show Details</label></div>';
}
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem;">';
echo '          <tr><th class="ps-4">Coin</th><th class="text-end">Immature</th><th class="text-end">Confirmed</th><th class="text-end">Total</th><th class="text-end pe-4">Value ('.$refcoin->symbol.')</th></tr>';
echo '        </thead>';
echo '        <tbody>';

if($show_details) {
	$list = dbolist("select coinid from earnings where userid=$user->id group by coinid");
	if(!count($list)) {
		echo '<tr><td colspan="5" class="py-4 text-center text-muted">No pending earnings records.</td></tr>';
	} else {
		foreach($list as $item) {
			$coin = getdbo('db_coins', $item['coinid']);
			if(!$coin) continue;
			$confirmed = (double)controller()->memcache->get_database_scalar("wallet_confirmed-$user->id-$coin->id", "select sum(amount) from earnings where status=1 and userid=$user->id and coinid=$coin->id");
			$unconfirmed = (double)controller()->memcache->get_database_scalar("wallet_unconfirmed-$user->id-$coin->id", "select sum(amount) from earnings where status=0 and userid=$user->id and coinid=$coin->id");
			$total = $confirmed + $unconfirmed;
			$value = yaamp_convert_amount_user($coin, $total, $user);

			echo '<tr>';
			echo '  <td class="ps-4"><img src="'.$coin->image.'" width="18" class="me-2 rounded-circle shadow-sm"><b>'.$coin->symbol.'</b> <span class="text-muted small">('.$coin->algo.')</span></td>';
			echo '  <td class="text-end text-muted">'.altcoinvaluetoa($unconfirmed).'</td>';
			echo '  <td class="text-end text-muted">'.altcoinvaluetoa($confirmed).'</td>';
			echo '  <td class="text-end fw-bold">'.altcoinvaluetoa($total).'</td>';
			echo '  <td class="text-end pe-4 text-primary fw-bold">'.bitcoinvaluetoa($value).'</td>';
			echo '</tr>';
		}
	}
}

$total_confirmed = yaamp_convert_earnings_user($user, "status=1");
$total_unconfirmed = yaamp_convert_earnings_user($user, "status=0");
$total_pending_value = $total_confirmed + $total_unconfirmed;

echo '        </tbody>';
echo '        <tfoot class="table-dark">';
echo '          <tr>';
echo '            <th class="ps-4 text-uppercase small">Total Pending Value</th>';
echo '            <th class="text-end small opacity-75">'.bitcoinvaluetoa($total_unconfirmed).'</th>';
echo '            <th class="text-end small opacity-75">'.bitcoinvaluetoa($total_confirmed).'</th>';
echo '            <th class="text-end"></th>';
echo '            <th class="text-end pe-4 text-warning">'.bitcoinvaluetoa($total_pending_value).' '.$refcoin->symbol.'</th>';
echo '          </tr>';
echo '        </tfoot>';
echo '      </table></div></div></div>';

// --- Multi-Metric Summary Cards ---
$balance = (double)$user->balance;
$total_paid = (double)controller()->memcache->get_database_scalar("wallet_total_paid-$user->id", "select sum(amount) from payouts where account_id=$user->id");
$total_earned = $total_pending_value + $balance + $total_paid;

echo '<div class="row g-3 mb-4">';
$summary = [
    ['Current Balance', bitcoinvaluetoa($balance), 'success', 'university'],
    ['Total Unpaid', bitcoinvaluetoa($balance + $total_pending_value), 'info', 'hand-holding-usd'],
    ['Total Paid Out', bitcoinvaluetoa($total_paid), 'primary', 'paper-plane'],
    ['Lifetime Earned', bitcoinvaluetoa($total_earned), 'warning', 'trophy']
];
foreach ($summary as $s) {
    echo '<div class="col-md-6 col-lg-3"><div class="card shadow-sm border-0 border-bottom border-3 border-'.$s[2].' h-100 rounded-3"><div class="card-body p-3">';
    echo '  <div class="d-flex justify-content-between align-items-center mb-2"><span class="text-muted small fw-bold text-uppercase">'.$s[0].'</span><i class="fa fa-'.$s[3].' text-'.$s[2].' opacity-25"></i></div>';
    echo '  <h4 class="mb-0 fw-bold">'.$s[1].' <small class="fs-6 text-muted">'.$refcoin->symbol.'</small></h4>';
    echo '</div></div></div>';
}
echo '</div>';

// --- Payouts History Card ---
echo '<div class="card shadow-sm border-0 rounded-3">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold small"><i class="fa fa-history me-2 text-warning"></i>Recent Payouts (Last 24h)</h5>';
echo '    <span class="badge bg-secondary">Live Ledger</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover table-sm align-middle mb-0 small">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem;">';
echo '          <tr><th class="ps-4">Time Ago</th><th class="text-end">Amount ('.$refcoin->symbol.')</th><th class="pe-4">Transaction ID</th></tr>';
echo '        </thead>';
echo '        <tbody>';

$t24 = time()-24*60*60;
$payouts = getdbolist('db_payouts', "account_id={$user->id} AND time>$t24 ORDER BY time DESC");
$total_24h = 0;

if(empty($payouts)) {
    echo '<tr><td colspan="3" class="py-4 text-center text-muted">No payouts in the last 24 hours.</td></tr>';
} else {
    foreach($payouts as $p) {
        $total_24h += $p->amount;
        echo '<tr>';
        echo '  <td class="ps-4 fw-bold">'.datetoa2($p->time).' ago</td>';
        echo '  <td class="text-end text-success fw-bold">'.bitcoinvaluetoa($p->amount).'</td>';
        echo '  <td class="pe-4 font-monospace" style="font-size: 0.75rem;">'.$refcoin->createExplorerLink(substr($p->tx, 0, 48).'...', ['txid'=>$p->tx], ['class'=>'text-primary text-decoration-none']).'</td>';
        echo '</tr>';
    }
}

echo '        </tbody>';
if($total_24h > 0) {
    echo '        <tfoot class="table-light text-dark fw-bold border-top">';
    echo '          <tr><td class="ps-4">24h TOTAL</td><td class="text-end text-success">'.bitcoinvaluetoa($total_24h).'</td><td></td></tr>';
    echo '        </tfoot>';
}
echo '      </table></div></div></div>';

echo '</div>'; // close main container
?>