<?php

$mining = getdbosql('db_mining');
$showrental = (bool) YAAMP_RENTAL;

echo '<div class="row g-4">';

// --- Top Row: Global Stats Cards ---
$total_workers = getdbocount('db_workers');
$total_coins = getdbocount('db_coins', "enable AND auto_ready");
$total_hashrate = 0;

foreach(yaamp_get_algos() as $algo) {
    $total_hashrate += controller()->memcache->get_database_scalar("current_hashrate-$algo", 
        "select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo' => $algo));
}

$total_renter_debt = 0;
if ($showrental) {
    $total_renter_debt = dboscalar("SELECT sum(balance) FROM accounts WHERE coinid=0");
}

$stat_cards = [
    ['label' => 'Active Miners', 'val' => number_format($total_workers), 'icon' => 'users', 'color' => 'primary'],
    ['label' => 'Enabled Coins', 'val' => $total_coins, 'icon' => 'coins', 'color' => 'success'],
    ['label' => 'Total Hashrate', 'val' => Itoa2($total_hashrate).'h/s', 'icon' => 'microchip', 'color' => 'info'],
];

if ($showrental) {
    $stat_cards[] = ['label' => 'Renter BTC Debt', 'val' => bitcoinvaluetoa($total_renter_debt), 'icon' => 'hand-holding-usd', 'color' => 'danger'];
} else {
    $stat_cards[] = ['label' => 'BTC/USD', 'val' => '$'.number_format($mining->usdbtc, 2), 'icon' => 'chart-line', 'color' => 'warning'];
}

foreach ($stat_cards as $c) {
    echo '<div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 border-start border-4 border-'.$c['color'].' h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">'.$c['label'].'</div>
                            <h3 class="mb-0 fw-bold mt-1">'.$c['val'].'</h3>
                        </div>
                        <div class="bg-'.$c['color'].' bg-opacity-10 p-3 rounded-3 text-'.$c['color'].'">
                            <i class="fa fa-'.$c['icon'].' fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
          </div>';
}

echo '</div><div class="row g-4 mt-1">';

// --- Left Column: Algos & Balances ---
echo '<div class="col-xl-8">';

// Algo Performance Card
echo '<div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-dark text-white d-flex align-items-center py-3">
            <h5 class="mb-0 fw-bold"><i class="fa fa-tachometer-alt me-2 text-info"></i>Algorithm Performance</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="maintable">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th class="ps-4">Algo</th>
                            <th>Status</th>
                            <th>Port</th>
                            <th class="text-end">Miners</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Bad</th>
                            <th class="text-end">Current P.</th>
                            <th class="text-end pe-4">Norm</th>
                        </tr>
                    </thead>
                    <tbody>';

$algos = array();
foreach(yaamp_get_algos() as $algo) {
	$algo_norm = yaamp_get_algo_norm($algo);
	$t = time() - 48*60*60;
	$price = controller()->memcache->get_database_scalar("current_price-$algo", "SELECT price FROM hashrate WHERE algo=:algo AND time>$t ORDER BY time DESC LIMIT 1", array(':algo'=>$algo));
	$algos[] = array(take_yaamp_fee($price*$algo_norm, $algo), $algo);
}
usort($algos, function($a, $b) { return $a[0] < $b[0]; });

foreach($algos as $item) {
	$norm = $item[0]; $algo = $item[1];
	$algo_color = getAlgoColors($algo);
	$count = getdbocount('db_workers', "algo=:algo", array(':algo'=>$algo));
    $hashrate = controller()->memcache->get_database_scalar("current_hashrate-$algo", "select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo' => $algo));
	$hashrate_bad = dboscalar("SELECT hashrate_bad FROM hashrate WHERE algo=:algo ORDER BY time DESC LIMIT 1", array(':algo'=>$algo));
	$bad_pct = ($hashrate+$hashrate_bad)? round($hashrate_bad * 100 / ($hashrate+$hashrate_bad), 1): 0;
	
    // Multi-Server Logic: Check DB first, then local screen
    $t = time() - 120;
    $stratum = getdbosql('db_stratums', "algo=:algo AND time > $t ORDER BY started DESC", array(':algo'=>$algo));
    $is_online = (bool) $stratum;

    if (!$is_online) {
        $check_cmd = "sudo -u yiimpadmin ".YIIMP_STRATUM_CTRL_DIR."/stratum_ctl.sh ".escapeshellarg($algo)." status";
        $check_res = trim((string) shell_exec($check_cmd));
        $is_online = ($check_res == "ONLINE");
    }
    
    if ($is_online) {
        $status_badge = '<span class="badge bg-success me-2">ONLINE</span>';
        $ctl_btn = '<a href="/admin/stopStratum?algo='.$algo.'" class="btn btn-xs btn-outline-danger py-0 px-2" title="Stop Stratum"><i class="fa fa-stop"></i></a>';
    } else {
        $status_badge = '<span class="badge bg-danger me-2">OFFLINE</span>';
        $ctl_btn = '<a href="/admin/startStratum?algo='.$algo.'" class="btn btn-xs btn-outline-success py-0 px-2" title="Start Stratum"><i class="fa fa-play"></i></a>';
    }


    $db_algo = getdbosql('db_algos', "name=:algo", array(':algo'=>$algo));
    $port = $db_algo ? $db_algo->port : '-';
    $port_status = '<span class="text-danger"><i class="fa fa-lock me-1"></i></span>';
    
    if ($port != '-') {
        if ($is_online) {
             $port_status = '<span class="text-success" title="Stratum Active!"><i class="fa fa-unlock me-1"></i></span>';
        } else {
            // Local check fallback for debugging
            $check_cmd = "sudo -u yiimpadmin ".YIIMP_STRATUM_CTRL_DIR."/stratum_ctl.sh ".escapeshellarg($algo)." port-check ".escapeshellarg($port);
            $check_res = trim((string) shell_exec($check_cmd));
            
            if ($check_res == "OPEN") {
                $port_status = '<span class="text-success" title="Port listening!"><i class="fa fa-unlock me-1"></i></span>';
            } else {
                // Only show the unlock link if the port is NOT listening (Red Lock)
                $port_status = CHtml::link('<i class="fa fa-lock me-1"></i>', "/admin/unlockStratum?algo=$algo&port=$port", [
                    'class' => 'text-danger',
                    'title' => 'Port closed! Click to open via firewall...',
                    'onclick' => "return confirm('Are you sure you want to open port $port for $algo in the firewall?')"
                ]);
            }
        }
    }

	echo '<tr>
            <td class="ps-4 fw-bold" style="border-left: 5px solid '.$algo_color.'">'.CHtml::link($algo, '/admin/worker?algo='.$algo, ['class'=>'text-decoration-none text-dark']).'</td>
            <td><div class="d-flex align-items-center">'.$status_badge . $ctl_btn.'</div></td>
            <td class="small fw-bold">'.$port_status . $port.'</td>
            <td class="text-end fw-bold">'.CHtml::link(($count?:'-'), '/admin/worker?algo='.$algo, ['class'=>'text-decoration-none']).'</td>
            <td class="text-end">'.($hashrate?Itoa2($hashrate).'h/s':'-').'</td>
            <td class="text-end"><span class="badge '.($bad_pct > 5 ? 'bg-danger' : 'bg-light text-muted').'">'.CHtml::link($bad_pct.'%', '/admin/version?algo='.$algo, ['class'=>'text-decoration-none text-inherit']).'</span></td>
            <td class="text-end fw-bold text-success">'.mbitcoinvaluetoa(dboscalar("SELECT price FROM hashrate WHERE algo=:algo ORDER BY time DESC LIMIT 1", array(':algo'=>$algo))).'</td>
            <td class="text-end pe-4 text-muted small">'.mbitcoinvaluetoa($norm).'</td>
          </tr>';
}
echo '</tbody></table></div></div></div>';

// Exchange Balances Card
echo '<div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-university me-2 text-warning"></i>Exchange Balances</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 text-end small">
                    <thead class="table-light"><tr><th class="ps-4 text-start">Market</th><th>BTC Balance</th><th>Orders</th><th class="pe-4">USD Value</th></tr></thead><tbody>';
$markets = getdbolist('db_balances', "1 order by name");
$total_btc = 0; $total_usd = 0;
foreach($markets as $m) {
    $onsell = YAAMP_ALLOW_EXCHANGE ? dboscalar("SELECT sum(amount*bid) FROM orders WHERE market='{$m->name}'") : $m->onsell;
    $m_total = $m->balance + $onsell;
    $total_btc += $m_total;
    $total_usd += $m_total * $mining->usdbtc;
    echo '<tr>
            <td class="ps-4 text-start fw-bold">'.$m->name.'</td>
            <td class="text-primary fw-bold">'.bitcoinvaluetoa($m->balance).'</td>
            <td>'.($onsell?bitcoinvaluetoa($onsell):'-').'</td>
            <td class="pe-4 fw-bold text-success">$'.number_format($m_total * $mining->usdbtc, 2).'</td>
          </tr>';
}
echo '</tbody><tfoot class="table-dark"><tr><td class="ps-4 text-start">GRAND TOTAL</td><td class="text-warning fw-bold">'.bitcoinvaluetoa($total_btc).' BTC</td><td></td><td class="pe-4 text-success fw-bold">$'.number_format($total_usd, 2).'</td></tr></tfoot></table></div></div></div>';

echo '</div>'; // End Left Col

// --- Right Column: System Engine & Blocks ---
echo '<div class="col-xl-4">';

// System Engine Card
$state_main = (int) $this->memcache->get('cronjob_main_state');
$main_time = sectoa($this->memcache->get("cronjob_main_time"));
$states = ['New Coins', 'Trading', 'Accounting', 'Prices', 'Block Update', 'Sell Coins', 'Find Blocks', 'Notify'];
$current_action = isset($states[$state_main-1]) ? $states[$state_main-1] : 'Idle';

echo '<div class="card shadow-sm border-0 mb-4 bg-primary text-white">
        <div class="card-body p-4 text-center">
            <i class="fa fa-cogs fa-3x mb-3 opacity-50"></i>
            <h5 class="fw-bold mb-1">Pool Engine Status</h5>
            <div class="fs-4 mb-2">'.$current_action.'</div>
            <div class="badge bg-white text-primary px-3 py-2">Cycle Time: '.$main_time.'</div>
        </div>
      </div>';

// Recent Blocks Card
echo '<div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold small">Recent Blocks</h5>
            <span class="badge bg-info text-dark">Live</span>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush small">';
$db_blocks = getdbolist('db_blocks', "1 order by time desc limit 10");
foreach($db_blocks as $b) {
    if (!$b->coin_id && !$showrental) continue;
    $c = $b->coin_id ? getdbo('db_coins', $b->coin_id) : null;
    $img = $c ? $c->image : '/images/btc.png';
    $cat_class = ($b->category == 'orphan') ? 'bg-danger' : (($b->category == 'immature') ? 'bg-warning text-dark' : 'bg-success');
    echo '<div class="list-group-item d-flex align-items-center py-2 px-3">
            <img src="'.$img.'" width="18" class="me-3">
            <div class="flex-grow-1">
                <div class="fw-bold">'.($c?$c->symbol:'Rental').' <span class="fw-normal text-muted ms-1">'.number_format($b->height).'</span></div>
                <div class="text-muted" style="font-size: 0.7rem;">'.datetoa2($b->time).' ago</div>
            </div>
            <span class="badge '.$cat_class.' rounded-pill">'.$b->category.'</span>
          </div>';
}
echo '</div></div></div>';

echo '</div></div>'; // End Row
?>