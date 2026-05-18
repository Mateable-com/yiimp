<?php
$defaultalgo = user()->getState('yaamp-algo');

echo '<div class="card shadow-lg border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-broadcast-tower me-2 text-primary"></i>Live Stratum Performance</h5>';
echo '    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">Real-time Data</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0" id="maintable1">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4">Algorithm / Coin</th>';
echo '            <th class="text-center">Exchange</th>';
echo '            <th class="text-center">Min Payout</th>';
echo '            <th class="text-center">Port</th>';
echo '            <th class="text-center">Users</th>';
echo '            <th class="text-center">Workers <small>(S/L)</small></th>';
echo '            <th class="text-center">Pool Hashrate <small>(S/L/T)</small></th>';
echo '            <th class="text-center">Network Hash</th>';
echo '            <th class="text-center">Fees</th>';
echo '            <th class="text-end pe-4">24h Actual*</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

// Fetch screen list once outside the loop — running shell_exec per algo is very slow
$screen_list = (string) shell_exec("sudo -u yiimpadmin /usr/bin/screen -list");

$best_algo = ''; $best_norm = 0; $algos = array();
foreach (yaamp_get_algos() as $algo) {
    $algo_norm = yaamp_get_algo_norm($algo);
    $price = controller()->memcache->get_database_scalar("current_price-$algo", "select price from hashrate where algo=:algo order by time desc limit 1", array(':algo' => $algo));
    $norm = take_yaamp_fee($price * $algo_norm, $algo);
    $algos[] = [$norm, $algo];
    if ($norm > $best_norm) { $best_norm = $norm; $best_algo = $algo; }
}
usort($algos, function($a,$b){ return $a[0] < $b[0]; });

$total_coins = $total_workers = $total_solo_workers = 0;

foreach ($algos as $item) {
    $norm = $item[0]; $algo = $item[1];
    $coins_count = getdbocount('db_coins', "enable and auto_ready and algo=:algo", array(':algo' => $algo));
    if (!$coins_count) continue;

    $workers = getdbocount('db_workers', "algo=:algo and not password like '%m=solo%'", array(':algo' => $algo));
    $solo_workers = getdbocount('db_workers',"algo=:algo and password like '%m=solo%'", array(':algo'=>$algo));
    
    $t = time() - 24 * 60 * 60;
    $total1 = controller()->memcache->get_database_scalar("current_total-$algo", "SELECT SUM(amount*price) AS total FROM blocks WHERE time>$t AND algo=:algo AND NOT category IN ('orphan','stake','generated')", array(':algo' => $algo));
    $hashrate1 = controller()->memcache->get_database_scalar("current_hashrate1-$algo", "select avg(hashrate) from hashrate where time>$t and algo=:algo", array(':algo' => $algo));
    $algo_unit_factor = yaamp_algo_mBTC_factor($algo);
    $btcmhday1 = $hashrate1 != 0 ? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000 * $algo_unit_factor) : '0.000';
    
    $fees = yaamp_fee($algo); $fees_solo = yaamp_fee_solo($algo); $port = getAlgoPort($algo);
    $rowClass = ($defaultalgo == $algo) ? 'table-primary bg-opacity-10' : '';
    
    echo '<tr class="'.$rowClass.'" style="cursor: pointer;" onclick="javascript:select_algo(\''.$algo.'\')">';
    echo '<td class="ps-4 fw-bold fs-6 text-primary"><i class="fa fa-caret-right me-2 opacity-50"></i>'.strtoupper($algo).'</td>';
    echo '<td colspan="8"></td>';
    $bestBadge = ($algo == $best_algo) ? '<span class="badge bg-success shadow-sm ms-2" style="font-size: 0.6rem;">BEST PROFIT</span>' : '';
    echo '<td class="text-end pe-4 fw-bold text-dark">'.$btcmhday1.' '.$bestBadge.'</td>';
    echo '</tr>';

    $t = time() - 120; // 2 minute threshold for DB status
    
    // Total users for this specific algorithm
    $users_algo = getdbocount('db_accounts', "id IN (SELECT DISTINCT userid FROM workers WHERE algo=:algo)", array(':algo' => $algo));

    $list = getdbolist('db_coins', "enable and auto_ready and algo=:algo order by auxpow asc, index_avg desc", array(':algo' => $algo));
    foreach ($list as $coin) {
        $symbol = $coin->getOfficialSymbol();

        // Check Database first (Supports Remote Servers)
        $stratum_db = getdbosql('db_stratums', "algo=:algo and (symbol=:symbol OR symbol IS NULL) AND time > $t", array(':algo' => $algo, ':symbol' => $coin->symbol));
        $is_online = (bool) $stratum_db;

        // Local Screen Check as Fallback
        if (!$is_online) {
            if ($screen_list && preg_match("/\.(stratum-)?".preg_quote($algo, "/")."[[:space:]]/i", $screen_list)) {
                $is_online = true;
            }
        }

        $port_val = $stratum_db ? $stratum_db->port : $port;
        $status_color = $is_online ? 'text-success' : 'text-danger';
        $status_icon = $is_online ? 'fa-check-circle' : 'fa-times-circle';

        $min_payout = max(floatval(YAAMP_PAYMENTS_MINI), floatval($coin->payout_min));
        $auxBadge = $coin->auxpow ? '<span class="badge bg-info text-dark ms-2" style="font-size: 0.5rem; vertical-align: middle;">AUX</span>' : '';

        echo '<tr class="small border-start border-4" style="border-left-color: '.getAlgoColors($algo).' !important;">';
        echo '<td class="ps-5"><div class="d-flex align-items-center"><img width="18" src="'.$coin->image.'" class="me-2 rounded-circle shadow-sm"><b>'.$coin->name.'</b> <span class="text-muted ms-1">('.$symbol.')</span>'.$auxBadge.'</div></td>';
        echo '<td class="text-center">'.($coin->auto_exchange ? '<i class="fa fa-check-circle text-success fs-6"></i>' : '<i class="fa fa-times-circle text-danger fs-6"></i>').'</td>';
        echo '<td class="text-center fw-bold">'.$min_payout.' <small class="text-muted">'.$symbol.'</small></td>';
        echo '<td class="text-center fw-bold '.$status_color.'"><i class="fa '.$status_icon.' me-1"></i>'.$port_val.'</td>';
        $interval = yaamp_hashrate_step();
        $delay = time() - $interval;
        $users_coin = (int) dboscalar("SELECT COUNT(DISTINCT userid) FROM shares WHERE coinid=:cid AND time>:delay", array(':cid' => $coin->id, ':delay' => $delay));
        $workers_coins = $users_coin;
        $solo_workers_coins = (int) dboscalar("SELECT COUNT(DISTINCT userid) FROM shares WHERE coinid=:cid AND solo=1 AND time>:delay", array(':cid' => $coin->id, ':delay' => $delay));
        echo '<td class="text-center">'.$users_coin.'</td>';
        echo '<td class="text-center text-muted">'.$workers_coins.' / '.$solo_workers_coins.'</td>';

        $pool_hash = Itoa2(yaamp_coin_rate($coin->id));
        $pool_shared_hash = Itoa2(yaamp_coin_shared_rate($coin->id));
        $pool_solo_hash = Itoa2(yaamp_coin_solo_rate($coin->id));
        echo '<td class="text-center fw-bold text-nowrap">'.$pool_shared_hash.' / '.$pool_solo_hash.' / '.$pool_hash.'</td>';
        
        $network_hash = yaamp_coin_nethash($coin);
        echo '<td class="text-center text-muted">'.($network_hash ? Itoa2($network_hash) : '-').'</td>';
        echo '<td class="text-center">'.$fees.'% / '.$fees_solo.'%</td>';
        echo '<td class="text-end pe-4 text-muted">'.mbitcoinvaluetoa(yaamp_profitability($coin)).'</td>';
        echo '</tr>';
    }
    $total_coins += $coins_count; $total_workers += $workers; $total_solo_workers += $solo_workers;
}

echo '        </tbody>';
$total_users = getdbocount('db_accounts', "id IN (SELECT DISTINCT userid FROM workers)");
echo '        <tfoot class="table-dark small text-uppercase fw-bold">';
echo '          <tr><td class="ps-4">Infrastructure Totals</td><td></td>';
echo '            <td class="text-center">'.$total_coins.' Assets</td><td></td>';
echo '            <td class="text-center">'.$total_users.' Miners</td>';
echo '            <td class="text-center">'.$total_workers.' / '.$total_solo_workers.'</td>';
echo '            <td colspan="4"></td></tr>';
echo '        </tfoot>';
echo '      </table></div></div>';
echo '  <div class="card-footer bg-light py-2 small text-muted"><i class="fa fa-info-circle me-1"></i> * Profitability values are estimated in mBTC/MH/day (or GH/day for SHA/Blake algos).</div>';
echo '</div>';
?>