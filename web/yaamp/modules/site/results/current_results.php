<?php
$defaultalgo = user()->getState('yaamp-algo');

echo '<div class="card mb-4 shadow-sm">';
echo '  <div class="card-header bg-dark text-white fw-bold py-2"><i class="fa fa-chart-line me-2"></i>Pool Status</div>';
echo '  <div class="card-body p-0 table-responsive">';

echo '<table class="table table-hover table-sm mb-0" id="maintable1">';
echo <<<END
<thead class="table-light">
<tr>
<th class="ps-3">Algorithm / Coin</th>
<th class="text-center">Exchange</th>
<th class="text-center">Min Payout</th>
<th class="text-center">Port</th>
<th class="text-center">Users</th>
<th class="text-center">Workers<br/><small class="text-muted">(Shared/Solo)</small></th>
<th class="text-center">Pool HashRate<br/><small class="text-muted">(Shared/Solo/Total)</small></th>
<th class="text-center">Network Hash</th>
<th class="text-center">Fees</th>
<th class="text-end pe-3">24h Actual*</th>
</tr>
</thead>
<tbody>
END;

$best_algo = '';
$best_norm = 0;
$algos = array();

foreach (yaamp_get_algos() as $algo)
{
    $algo_norm = yaamp_get_algo_norm($algo);
    $price = controller()
        ->memcache
        ->get_database_scalar("current_price-$algo", "select price from hashrate where algo=:algo order by time desc limit 1", array(
        ':algo' => $algo
    ));
    $norm = $price * $algo_norm;
    $norm = take_yaamp_fee($norm, $algo);
    $algos[] = array(
        $norm,
        $algo
    );
    if ($norm > $best_norm)
    {
        $best_norm = $norm;
        $best_algo = $algo;
    }
}

function cmp($a, $b)
{
    return $a[0] < $b[0];
}

usort($algos, 'cmp');

$total_coins = 0;
$total_users = 0;
$total_workers = 0;
$total_solo_workers = 0;
$showestimates = false;

foreach ($algos as $item)
{
    $norm = $item[0];
    $algo = $item[1];
    
    $coins = getdbocount('db_coins', "enable and visible and auto_ready and algo=:algo", array(
        ':algo' => $algo
    ));
    
    if (!$coins) continue;

    $workers = getdbocount('db_workers', "algo=:algo and not password like '%m=solo%'", array(':algo' => $algo));
    $solo_workers = getdbocount('db_workers',"algo=:algo and password like '%m=solo%'", array(':algo'=>$algo));
    
    $hashrate = controller()->memcache->get_database_scalar("current_hashrate-$algo", "select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo' => $algo));
    $price = controller()->memcache->get_database_scalar("current_price-$algo", "select price from hashrate where algo=:algo order by time desc limit 1", array(':algo' => $algo));
    $price = $price ? mbitcoinvaluetoa(take_yaamp_fee($price, $algo)) : '-';
    $norm = mbitcoinvaluetoa($norm);
    
    $t = time() - 24 * 60 * 60;
    $total1 = controller()->memcache->get_database_scalar("current_total-$algo", "SELECT SUM(amount*price) AS total FROM blocks WHERE time>$t AND algo=:algo AND NOT category IN ('orphan','stake','generated')", array(':algo' => $algo));
    $hashrate1 = controller()->memcache->get_database_scalar("current_hashrate1-$algo", "select avg(hashrate) from hashrate where time>$t and algo=:algo", array(':algo' => $algo));
    
    $algo_unit_factor = yaamp_algo_mBTC_factor($algo);
    $btcmhday1 = $hashrate1 != 0 ? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000 * $algo_unit_factor) : '0.000';
    
    $fees = yaamp_fee($algo);
    $fees_solo = yaamp_fee_solo($algo);
    $port = getAlgoPort($algo);

    $rowClass = ($defaultalgo == $algo) ? 'table-primary' : '';
    
    echo "<tr class='$rowClass' style='cursor: pointer;' onclick='javascript:select_algo(\"$algo\")'>";
    echo "<td class='ps-3 fw-bold'>$algo</td>";
    echo "<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
    
    $bestBadge = ($algo == $best_algo) ? '<span class="badge bg-success ms-1">Best</span>' : '';
    echo "<td class='text-end pe-3 fw-bold' data='$btcmhday1'>$btcmhday1 $bestBadge</td>";
    echo "</tr>";

    $list = getdbolist('db_coins', "enable and visible and auto_ready and algo=:algo order by index_avg desc", array(':algo' => $algo));

    foreach ($list as $coin)
    {
        $name = substr($coin->name, 0, 20);
        $symbol = $coin->getOfficialSymbol();
        
        echo "<tr>";
        echo "<td class='ps-4 small'><img width='16' src='" . $coin->image . "' class='me-2'><b>$name</b> <span class='text-muted'>($symbol)</span></td>";
        
        $port_db = getdbosql('db_stratums', "algo=:algo and symbol=:symbol", array(':algo' => $algo,':symbol' => $coin->symbol));
        $port_count = $port_db ? 1 : 0;

        echo "<td class='text-center'>";
        echo ($coin->auto_exchange == 1) ? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>';
        echo "</td>";
        
        $min_payout = max(floatval(YAAMP_PAYMENTS_MINI), floatval($coin->payout_min));
        echo "<td class='text-center small fw-bold'>$min_payout $symbol</td>";

        $displayPort = $port_db ? $port_db->port : $port;
        echo "<td class='text-center small fw-bold text-primary'>$displayPort</td>";
        
        $users_total = getdbocount('db_accounts', "id IN (SELECT DISTINCT userid FROM workers)");
        echo "<td class='text-center small'>$users_total</td>";
        
        $workers_coins = getdbocount('db_workers', "algo=:algo and pid=:pid and not password like '%m=solo%'", array(':algo' => $algo,':pid' => ($port_db ? $port_db->pid : 0)));
        $solo_workers_coins = getdbocount('db_workers', "algo=:algo and pid=:pid and password like '%m=solo%'", array(':algo' => $algo,':pid' => ($port_db ? $port_db->pid : 0)));
        echo "<td class='text-center small text-muted'>$workers_coins / $solo_workers_coins</td>";
        
        $pool_hash = yaamp_coin_rate($coin->id);
        $pool_hash_sfx = $pool_hash ? Itoa2($pool_hash) : '0';
        $pool_shared_hash = yaamp_coin_shared_rate($coin->id);
        $pool_shared_hash_sfx = $pool_shared_hash ? Itoa2($pool_shared_hash) : '0';
        $pool_solo_hash = yaamp_coin_solo_rate($coin->id);
        $pool_solo_hash_sfx = $pool_solo_hash ? Itoa2($pool_solo_hash) : '0';
        echo "<td class='text-center small fw-bold text-nowrap'>$pool_shared_hash_sfx / $pool_solo_hash_sfx / $pool_hash_sfx</td>";
        
        $network_hash = yaamp_coin_nethash($coin);
        $network_hash_sfx = $network_hash ? Itoa2($network_hash) : '-';
        echo "<td class='text-center small text-muted'>$network_hash_sfx</td>";
        echo "<td class='text-center small'>{$fees}% / {$fees_solo}%</td>";
        
        $btcmhd = mbitcoinvaluetoa(yaamp_profitability($coin));
        echo "<td class='text-end pe-3 small text-muted'>$btcmhd</td>";
        echo "</tr>";
    }

    $total_coins += $coins;
    $total_workers += $workers;
    $total_solo_workers += $solo_workers;
}

// Final row with totals
$total_users = getdbocount('db_accounts', "id IN (SELECT DISTINCT userid FROM workers)");
echo "</tbody>";
echo "<tfoot class='table-light fw-bold'>";
echo "<tr>";
echo "<td class='ps-3'>Totals</td>";
echo "<td></td>";
echo "<td class='text-center small'>$total_coins Coins</td>";
echo "<td></td>";
echo "<td class='text-center small'>$total_users Users</td>";
echo "<td class='text-center small text-muted'>$total_workers / $total_solo_workers</td>";
echo "<td></td><td></td><td></td><td></td>";
echo "</tr>";
echo "</tfoot>";

echo "</table>";
echo "</div>"; // card-body
echo '<div class="card-footer bg-light py-2 small text-muted">';
echo '* values in mBTC/MH/day (or GH/day for sha/blake algos)';
echo '</div>';
echo "</div>"; // card

showTableSorter('maintable1', "{
    tableClass: 'table table-hover table-sm mb-0',
    textExtraction: {
        4: function(node, table, n) { return $(node).attr('data'); },
        8: function(node, table, n) { return $(node).attr('data'); }
    }
}");
?>
