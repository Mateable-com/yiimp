<?php

$mining = getdbosql('db_mining');
$showrental = (bool) YAAMP_RENTAL;

echo '<div class="row g-4">';

// --- Left Column: Algos, Balances, Orders ---
echo '<div class="col-xl-8">';

// Algo Table
echo '<div class="card shadow-sm mb-4">';
echo '  <div class="card-header bg-dark text-white fw-bold py-2"><i class="fa fa-microchip me-2 text-info"></i>Algorithm Performance</div>';
echo '  <div class="card-body p-0 table-responsive">';
echo '<table class="table table-hover table-sm mb-0" id="maintable">';
echo '<thead class="table-light text-muted small text-uppercase"><tr>';
echo '<th class="ps-3">Algo</th><th>Status</th><th class="text-end">C</th><th class="text-end">M</th><th class="text-end">Fee</th><th class="text-end">Rate</th><th class="text-end rental">Rent</th><th class="text-end">Bad</th><th class="text-end">Price</th><th class="text-end rental">RentP</th><th class="text-end">Norm</th><th class="text-end">24E</th><th class="text-end pe-3">24A</th></tr></thead><tbody>';

$total_coins = 0; $total_workers = 0; $total_hashrate = 0; $total_hashrate_bad = 0;
$algos = array();
foreach(yaamp_get_algos() as $algo) {
	$algo_norm = yaamp_get_algo_norm($algo);
	$t = time() - 48*60*60;
	$price = controller()->memcache->get_database_scalar("current_price-$algo", "SELECT price FROM hashrate WHERE algo=:algo AND time>$t ORDER BY time DESC LIMIT 1", array(':algo'=>$algo));
	$norm = take_yaamp_fee($price*$algo_norm, $algo);
	$algos[] = array($norm, $algo);
}
function cmp_algos($a, $b) { return $a[0] < $b[0]; }
usort($algos, 'cmp_algos');

foreach($algos as $item) {
	$norm = $item[0]; $algo = $item[1];
	$algo_color = getAlgoColors($algo);
	$coins = getdbocount('db_coins', "enable AND auto_ready AND algo=:algo", array(':algo'=>$algo));
	$count = getdbocount('db_workers', "algo=:algo", array(':algo'=>$algo));
	$total_coins += $coins; $total_workers += $count;

	$t1 = time() - 24*60*60;
	$total1 = dboscalar("SELECT sum(amount*price) FROM blocks WHERE category!='orphan' AND time>$t1 AND algo=:algo", array(':algo'=>$algo));
	if (!$coins && !$total1) continue;
	$hashrate1 = dboscalar("SELECT avg(hashrate) FROM hashrate WHERE time>$t1 AND algo=:algo", array(':algo'=>$algo));
	$hashrate = controller()->memcache->get_database_scalar("current_hashrate-$algo", "select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo' => $algo));
	$hashrate_bad = dboscalar("SELECT hashrate_bad FROM hashrate WHERE algo=:algo ORDER BY time DESC LIMIT 1", array(':algo'=>$algo));
	$bad_pct = ($hashrate+$hashrate_bad)? round($hashrate_bad * 100 / ($hashrate+$hashrate_bad), 1): 0;
	$total_hashrate += $hashrate; $total_hashrate_bad += $hashrate_bad;

	$hashrate_sfx = $hashrate? Itoa2($hashrate).'h/s': '-';
	$hashrate_jobs = yaamp_rented_rate($algo);
	$hashrate_jobs = $hashrate_jobs>0? Itoa2($hashrate_jobs).'h/s': '';
	$price_val = dboscalar("SELECT price FROM hashrate WHERE algo=:algo ORDER BY time DESC LIMIT 1", array(':algo'=>$algo));
	$rent_val = dboscalar("SELECT rent FROM hashrate WHERE algo=:algo ORDER BY time DESC LIMIT 1", array(':algo'=>$algo));
	$avgprice = dboscalar("SELECT avg(price) FROM hashrate WHERE algo=:algo AND time>$t1", array(':algo'=>$algo));
	$avgprice = $avgprice? mbitcoinvaluetoa(take_yaamp_fee($avgprice, $algo)): '-';
	$btcmhday1 = $hashrate1 != 0? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000 * yaamp_algo_mBTC_factor($algo)): '-';
	
	$stratum = getdbosql('db_stratums', "algo=:algo ORDER BY started DESC", array(':algo'=>$algo));
    $intended_port = getAlgoPort($algo);
	$isup = $stratum ? '<span class="badge bg-success">UP</span>' : '<span class="badge bg-danger">DOWN</span>';
    $port_warning = ($stratum && $stratum->port != $intended_port) ? '<div class="text-danger fw-bold" style="font-size: 0.6rem;"><i class="fa fa-exclamation-triangle"></i> RESTART</div>' : '';
	$uptime = $stratum ? '<div class="small text-muted" style="font-size: 0.6rem;">'.datetoa2($stratum->started).'</div>' : '';

	echo '<tr><td class="ps-3 fw-bold" style="border-left: 4px solid '.$algo_color.'">'.CHtml::link($algo, '/site/gomining?algo='.$algo, ['class'=>'text-decoration-none text-dark']).'</td>';
	echo '<td>'.$isup.$port_warning.$uptime.'</td>';
	echo '<td class="text-end small">'.($coins?:'-').'</td>';
	echo '<td class="text-end small">'.($count?:'-').'</td>';
	echo '<td class="text-end small">'.yaamp_fee($algo).'%</td>';
	echo '<td class="text-end small fw-bold">'.$hashrate_sfx.'</td>';
	echo '<td class="text-end small rental text-info">'.$hashrate_jobs.'</td>';
	echo '<td class="text-end"><span class="badge '.($bad_pct > 10 ? 'bg-danger' : ($bad_pct > 5 ? 'bg-warning text-dark' : 'bg-light text-muted')).'">'.($bad_pct?:'-').'%</span></td>';
	echo '<td class="text-end small">'.($price_val?mbitcoinvaluetoa($price_val):'-').'</td>';
	echo '<td class="text-end small rental">'.($rent_val?mbitcoinvaluetoa($rent_val):'-').'</td>';
	echo '<td class="text-end small text-muted">'.mbitcoinvaluetoa($norm).'</td>';
	echo '<td class="text-end small">'.$avgprice.'</td>';
    
    $act_style = '';
    if ($btcmhday1 != '-' && $avgprice != '-') {
        $v_avg = (double)$avgprice; $v_act = (double)$btcmhday1;
        if ($v_act > $v_avg*1.1) $act_style = 'table-success fw-bold';
        else if ($v_act < $v_avg*0.8) $act_style = 'table-danger fw-bold';
    }
	echo '<td class="text-end pe-3 small '.$act_style.'">'.$btcmhday1.'</td></tr>';
}
echo '</tbody></table></div></div>';

// Market Balances (BTC, Orders, Others, USD)
echo '<div class="card shadow-sm mb-4">';
echo '  <div class="card-header bg-dark text-white fw-bold py-2"><i class="fa fa-university me-2 text-warning"></i>Exchange Balances & USD Totals</div>';
echo '  <div class="card-body p-0 table-responsive">';
$markets = getdbolist('db_balances', "1 order by name");
echo '<table class="table table-sm table-hover mb-0 text-end small">';
echo '<thead class="table-light"><tr><th class="ps-3 text-start">Type</th>';
foreach($markets as $m) echo '<th><a href="/admin/runExchange?id='.$m->id.'" class="text-decoration-none">'.$m->name.'</a></th>';
echo '<th class="pe-3">Total</th></tr></thead><tbody>';

// BTC Row
echo '<tr><td class="ps-3 text-start fw-bold text-primary">BTC</td>';
$tot_btc = 0;
foreach($markets as $m) {
    $b = bitcoinvaluetoa($m->balance); $tot_btc += $b;
    echo '<td class="'.($b>0.2?'text-success fw-bold':'').'">'.($b==0?'-':$b).'</td>';
}
echo '<td class="pe-3 fw-bold bg-light">'.bitcoinvaluetoa($tot_btc).'</td></tr>';

// Orders Row
echo '<tr><td class="ps-3 text-start fw-bold text-info">Orders</td>';
$tot_orders = 0; $salebalances = [];
foreach($markets as $m) {
    $o = YAAMP_ALLOW_EXCHANGE ? dboscalar("SELECT sum(amount*bid) FROM orders WHERE market='{$m->name}'") : $m->onsell;
    $tot_orders += $o; $salebalances[$m->name] = $o;
    echo '<td>'.($o?bitcoinvaluetoa($o):'-').'</td>';
}
echo '<td class="pe-3 fw-bold bg-light">'.bitcoinvaluetoa($tot_orders).'</td></tr>';

// Other (Alt) Row
echo '<tr><td class="ps-3 text-start fw-bold text-warning">Other</td>';
$tot_alt = 0; $alt_balances = [];
foreach($markets as $m) {
    $alt = dboscalar("SELECT SUM((M.balance+M.ontrade)*M.price) FROM markets M INNER JOIN coins C on C.id = M.coinid WHERE M.name='{$m->name}' AND IFNULL(M.deleted,0)=0 AND INSTR(C.symbol,'-')=0");
    $tot_alt += $alt; $alt_balances[$m->name] = $alt;
    echo '<td>'.($alt?bitcoinvaluetoa($alt):'-').'</td>';
}
echo '<td class="pe-3 fw-bold bg-light">'.bitcoinvaluetoa($tot_alt).'</td></tr>';

// Total BTC Row
echo '<tr class="table-secondary"><td class="ps-3 text-start fw-bold">Total BTC</td>';
$grand_total_btc = 0;
foreach($markets as $m) {
    $t = $m->balance + arraySafeVal($salebalances,$m->name,0) + arraySafeVal($alt_balances,$m->name,0);
    $grand_total_btc += $t;
    echo '<td class="fw-bold">'.($t>0?bitcoinvaluetoa($t):'-').'</td>';
}
echo '<td class="pe-3 fw-bold">'.bitcoinvaluetoa($grand_total_btc).'</td></tr>';

// USD Row
echo '<tr class="table-light"><td class="ps-3 text-start fw-bold text-success">USD ($)</td>';
$tot_usd = 0;
foreach($markets as $m) {
    $t = $m->balance + arraySafeVal($salebalances,$m->name,0) + arraySafeVal($alt_balances,$m->name,0);
    $usd = $t * $mining->usdbtc; $tot_usd += $usd;
    echo '<td>'.($usd>0?round($usd,2):'-').'</td>';
}
echo '<td class="pe-3 fw-bold text-success">'.round($tot_usd,2).' $</td></tr>';
echo '</tbody></table></div></div>';

// Stuck Markets (Market list where lastsent < time-2h)
$minsent = time()-2*60*60;
$stuck = getdbolist('db_markets', "lastsent<$minsent and lastsent>lasttraded order by lastsent");
if ($stuck) {
    echo '<div class="card shadow-sm mb-4 border-danger">';
    echo '  <div class="card-header bg-danger text-white fw-bold py-2 small"><i class="fa fa-exclamation-circle me-2"></i>Stuck Markets (Sent > 2h ago but not traded)</div>';
    echo '  <div class="card-body p-0 table-responsive">';
    echo '<table class="table table-sm table-hover mb-0 small text-center">';
    echo '<thead class="table-light"><tr><th>Coin</th><th>Exchange</th><th>Sent</th><th>Traded</th><th>Action</th></tr></thead><tbody>';
    foreach($stuck as $m) {
        $c = getdbo('db_coins', $m->coinid);
        echo '<tr><td><img src="'.$c->image.'" width="14" class="me-1"><b>'.$c->symbol.'</b></td>';
        echo '<td>'.$m->name.'</td>';
        echo '<td>'.datetoa2($m->lastsent).' ago</td>';
        echo '<td>'.datetoa2($m->lasttraded).' ago</td>';
        echo '<td><a href="/admin/clearmarket?id='.$m->id.'" class="btn btn-xs btn-outline-danger py-0 px-1">clear</a></td></tr>';
    }
    echo '</tbody></table></div></div>';
}

echo '</div>'; // End Left Col

// --- Right Column: Status, Graphs, Blocks ---
echo '<div class="col-xl-4">';

// System Info
echo '<div class="card shadow-sm mb-4 border-primary">';
echo '  <div class="card-header bg-primary text-white fw-bold py-2"><i class="fa fa-server me-2"></i>System Status</div>';
echo '  <div class="card-body p-3">';
function get_cron_badge($state) {
    $states = ['new coins', 'trade', 'trade2', 'prices', 'blocks', 'sell', 'find2', 'notify'];
    return '<span class="badge bg-dark border border-secondary text-info">'.(isset($states[$state-1])?$states[$state-1]:'Idle').'</span>';
}
$state_main = (int) $this->memcache->get('cronjob_main_state');

// Detection of multiple running main loops (Security/Stability Check)
$multi_cron_warning = '';
for($i=0; $i<10; $i++) {
    if($i != $state_main-1 && $state_main>0) {
        $state = $this->memcache->get("cronjob_main_state_$i");
        if($state) $multi_cron_warning .= "main $i ";
    }
}
if ($multi_cron_warning) {
    echo '<div class="alert alert-danger py-1 px-2 mb-2 fw-bold small"><i class="fa fa-exclamation-triangle me-2"></i>MULTI-CRON DETECTED: '.$multi_cron_warning.'</div>';
}

$main_time = sectoa($this->memcache->get("cronjob_main_time"));
echo '<div class="d-flex justify-content-between mb-2 small"><span>Cron Main:</span><b>'.$main_time.' '.get_cron_badge($state_main).'</b></div>';
$btc = getdbosql('db_coins', "symbol='BTC'") ?: (object)['balance'=>0];
$topay = dboscalar("select sum(balance) from accounts where coinid={$btc->id} and balance>0.001");
echo '<div class="d-flex justify-content-between mb-2 small"><span>Next Payout:</span><b class="text-success">'.bitcoinvaluetoa($topay).' BTC</b></div>';
echo '<div class="d-flex justify-content-between mb-2 small"><span>Pool Wallet:</span><b class="text-primary">'.$btc->balance.' BTC</b></div>';
echo '<hr class="my-2 opacity-25 small">';
echo '<div class="small text-muted">Bitstamp BTC/USD: <b class="text-dark">'.$mining->usdbtc.'</b></div>';
echo '</div></div>';

// Asset Graphs
echo '<div class="card shadow-sm mb-4">';
echo '  <div class="card-body p-2">';
echo '    <div style="height: 140px;" id="graph_results_negative" class="mb-3"></div>';
echo '    <div style="height: 140px;" id="graph_results_assets"></div>';
echo '  </div></div>';

// Recent Blocks
echo '<div class="card shadow-sm mb-4">';
echo '  <div class="card-header bg-dark text-white fw-bold py-2 small"><i class="fa fa-cubes me-2 text-info"></i>Recent Blocks</div>';
echo '  <div class="card-body p-0 table-responsive">';
$db_blocks = getdbolist('db_blocks', "1 order by time desc limit 15");
echo '<table class="table table-sm table-hover mb-0" style="font-size: 0.75rem;">';
echo '<tbody>';
foreach($db_blocks as $b) {
    if (!$b->coin_id && !$showrental) continue;
    $c = $b->coin_id ? getdbo('db_coins', $b->coin_id) : null;
    $name = $c ? $c->symbol : 'Rental';
    $img = $c ? $c->image : '/images/btc.png';
    $cat_class = ($b->category == 'orphan') ? 'bg-danger' : (($b->category == 'immature') ? 'bg-warning text-dark' : 'bg-success');
    echo '<tr><td class="ps-2"><img src="'.$img.'" width="14" class="me-1"><b>'.$name.'</b></td>';
    echo '<td class="text-end">'.round($b->amount,2).'</td>';
    echo '<td class="text-end text-muted">'.datetoa2($b->time).' ago</td>';
    echo '<td class="text-end pe-2"><span class="badge '.$cat_class.'" style="font-size: 0.6rem;">'.$b->category.'</span></td></tr>';
}
echo '</tbody></table></div></div>';

echo '</div></div>'; // End Row
if (!$showrental) echo '<style>.rental { display: none !important; }</style>';
?>
