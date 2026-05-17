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
echo '      <h3 class="mb-0 fw-bold font-monospace text-break">'.htmlspecialchars($user->username).'</h3>';
echo '    </div>';
echo '    <div class="text-end d-none d-md-block border-start border-secondary border-opacity-25 ps-4 ms-4">';
echo '      <div class="small text-muted mb-1 text-uppercase fw-bold">Reference Coin</div>';
echo '      <div class="d-flex align-items-center justify-content-end">';
echo '        <img src="'.htmlspecialchars($refcoin->image).'" width="24" class="me-2 rounded-circle shadow-sm">';
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

// --- Aux Coin Balances (Merge Mining) ---
$aux_accounts = [];
$worker_passwords = dbolist("SELECT DISTINCT password FROM workers WHERE userid=$user->id AND password!=''");
foreach ($worker_passwords as $wp) {
	$parts = explode(',', $wp['password']);
	foreach ($parts as $part) {
		if (strncmp($part, 'm=', 2) === 0) {
			$val = substr($part, 2);
			if ($val === 'solo') continue;
			$colon = strpos($val, ':');
			if ($colon === false) continue;
			$sym = strtoupper(substr($val, 0, $colon));
			$addr = substr($val, $colon + 1);
			if (!$addr || isset($aux_accounts[$sym])) continue;
			$aux_coin = getdbosql('db_coins', "symbol=:s", array(':s' => $sym));
			if (!$aux_coin) continue;
			$aux_user = getdbosql('db_accounts', "username=:a AND coinid=:c", array(':a' => $addr, ':c' => $aux_coin->id));
			if (!$aux_user) continue;
			$aux_pending = (double)controller()->memcache->get_database_scalar(
				"wallet_aux_pending-$aux_user->id",
				"SELECT SUM(amount) FROM earnings WHERE userid=$aux_user->id"
			);
			$aux_paid = (double)controller()->memcache->get_database_scalar(
				"wallet_aux_paid-$aux_user->id",
				"SELECT SUM(amount) FROM payouts WHERE account_id=$aux_user->id"
			);
			$aux_accounts[$sym] = [
				'coin'    => $aux_coin,
				'address' => $addr,
				'balance' => (double)$aux_user->balance,
				'pending' => $aux_pending,
				'paid'    => $aux_paid,
			];
		}
	}
}

if (!empty($aux_accounts)) {
	echo '<div class="card shadow-sm border-0 mb-4 rounded-3">';
	echo '  <div class="card-header bg-dark text-white py-3 border-0">';
	echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-link me-2 text-success"></i>Merge Mining Balances</h5>';
	echo '  </div>';
	echo '  <div class="card-body p-0"><div class="table-responsive">';
	echo '    <table class="table table-hover align-middle mb-0 small">';
	echo '      <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem;">';
	echo '        <tr><th class="ps-4">Coin</th><th class="font-monospace">Address</th><th class="text-end">Balance</th><th class="text-end">Pending Earnings</th><th class="text-end pe-4">Total Paid Out</th></tr>';
	echo '      </thead><tbody>';
	foreach ($aux_accounts as $sym => $a) {
		$coin = $a['coin'];
		echo '<tr>';
		echo '  <td class="ps-4"><img src="'.htmlspecialchars($coin->image).'" width="18" class="me-2 rounded-circle shadow-sm"><b>'.htmlspecialchars($sym).'</b></td>';
		echo '  <td class="font-monospace small text-muted">'.htmlspecialchars(substr($a['address'], 0, 20)).'&hellip;</td>';
		echo '  <td class="text-end fw-bold text-success">'.altcoinvaluetoa($a['balance']).'</td>';
		echo '  <td class="text-end text-info">'.altcoinvaluetoa($a['pending']).'</td>';
		echo '  <td class="text-end pe-4 text-muted">'.altcoinvaluetoa($a['paid']).'</td>';
		echo '</tr>';
	}
	echo '      </tbody></table></div></div></div>';
}

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

// --- Payout Threshold Setting ---
$current_threshold = !empty($user->payout_threshold) ? floatval($user->payout_threshold) : null;
$min_payout = floatval(YAAMP_PAYMENTS_MINI);

echo '<div class="card shadow-sm border-0 rounded-3 mt-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex align-items-center">';
echo '    <i class="fa fa-sliders-h me-2 text-warning"></i>';
echo '    <h5 class="mb-0 fw-bold">Payout Threshold</h5>';
echo '  </div>';
echo '  <div class="card-body">';
echo '    <p class="text-muted small mb-3">Set a custom minimum balance before a payout is triggered. Leave blank to use the pool default (<strong>'.number_format($min_payout, 8).'</strong> in coin units). Must be at least the pool minimum.</p>';
echo '    <form method="post" action="/site/setpayout" class="d-flex align-items-center gap-3 flex-wrap">';
echo '      <input type="hidden" name="address" value="'.htmlspecialchars($user->username).'">';
echo '      <div class="input-group" style="max-width: 280px;">';
echo '        <input type="number" name="threshold" class="form-control" step="0.00000001" min="0" ';
echo           'value="'.($current_threshold !== null ? number_format($current_threshold, 8, '.', '') : '').'" ';
echo           'placeholder="e.g. 0.01">';
echo '        <span class="input-group-text text-muted small">'.htmlspecialchars($refcoin->symbol).'</span>';
echo '      </div>';
echo '      <button type="submit" class="btn btn-warning fw-bold px-4"><i class="fa fa-save me-1"></i>Save</button>';
if ($current_threshold !== null) {
    echo '      <a href="/site/setpayout?address='.urlencode($user->username).'&threshold=0" class="btn btn-outline-secondary btn-sm">Reset to Default</a>';
}
echo '    </form>';
if ($current_threshold !== null) {
    echo '    <div class="mt-2 text-success small"><i class="fa fa-check-circle me-1"></i>Custom threshold active: <strong>'.number_format($current_threshold, 8).'</strong></div>';
} else {
    echo '    <div class="mt-2 text-muted small"><i class="fa fa-info-circle me-1"></i>Using pool default threshold.</div>';
}
echo '  </div>';
echo '</div>';

echo '</div>'; // close main container
?>