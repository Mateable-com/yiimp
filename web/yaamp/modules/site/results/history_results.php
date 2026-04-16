<?php

$mining = getdbosql('db_mining');
$algo = user()->getState('yaamp-algo');
if($algo == 'all') return;

$algo_unit = 'Mh';
$algo_factor = yaamp_algo_mBTC_factor($algo);
if ($algo_factor == 0.001) $algo_unit = 'Kh';
if ($algo_factor == 1000) $algo_unit = 'Gh';
if ($algo_factor == 1000000) $algo_unit = 'Th';
if ($algo_factor == 1000000000) $algo_unit = 'Ph';

echo '<div class="card shadow-lg border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-history me-2 text-info"></i>Algo Performance History: '.strtoupper($algo).'</h5>';
echo '    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3">Aggregated Data</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0" id="historytable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" width="20"></th>';
echo '            <th>Asset Name</th>';
echo '            <th class="text-center">Symbol</th>';
echo '            <th class="text-end">Last Hour</th>';
echo '            <th class="text-end">Last 24h</th>';
echo '            <th class="text-end">Last 7 Days</th>';
echo '            <th class="text-end pe-4">Last 30 Days</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$t1 = time() - 60*60;
$t2 = time() - 24*60*60;
$t3 = time() - 7*24*60*60;
$t4 = time() - 30*24*60*60;

$total1 = $total2 = $total3 = $total4 = 0;
$main_ids = array();

$list = dbolist("SELECT coin_id FROM blocks WHERE coin_id IN (select id from coins where algo=:algo and enable=1)
	AND time>$t4 AND NOT category IN ('orphan','stake','generated') GROUP BY coin_id ORDER BY coin_id DESC",
	array(':algo'=>$algo)
);

foreach($list as $item)
{
	$coin = getdbo('db_coins', $item['coin_id']);
	if(!$coin || $coin->symbol == 'BTC') continue;

	$id = $coin->id;
	$main_ids[$id] = $coin->symbol;

	$res1 = controller()->memcache->get_database_row("history_item1-$id-$algo",
		"SELECT COUNT(id) as a, SUM(amount*price) as b FROM blocks WHERE coin_id=$id AND NOT category IN ('orphan','stake','generated') AND time>$t1 AND algo=:algo", array(':algo'=>$algo));
	$res2 = controller()->memcache->get_database_row("history_item2-$id-$algo",
		"SELECT COUNT(id) as a, SUM(amount*price) as b FROM blocks WHERE coin_id=$id AND NOT category IN ('orphan','stake','generated') AND time>$t2 AND algo=:algo", array(':algo'=>$algo));
	$res3 = controller()->memcache->get_database_row("history_item3-$id-$algo",
		"SELECT COUNT(id) as a, SUM(amount*price) as b, MIN(time) as t FROM blocks WHERE coin_id=$id AND NOT category IN ('orphan','stake','generated') AND time>$t3 AND algo=:algo", array(':algo'=>$algo));
	$res4 = controller()->memcache->get_database_row("history_item4-$id-$algo",
		"SELECT COUNT(id) as a, SUM(amount*price) as b, MIN(time) as t FROM blocks WHERE coin_id=$id AND NOT category IN ('orphan','stake','generated') AND time>$t4 AND algo=:algo", array(':algo'=>$algo));

	$total1 += $res1['b']; $total2 += $res2['b']; $total3 += $res3['b']; $total4 += $res4['b'];

	echo '<tr>';
	echo '<td class="ps-4 text-center"><img width="18" src="'.$coin->image.'" class="rounded-circle shadow-sm"></td>';
	echo '<td><b>'.CHtml::link($coin->name, '/site/block?id='.$id, ['class'=>'text-decoration-none text-dark']).'</b></td>';
	echo '<td class="text-center small text-muted fw-bold">'.$coin->symbol.'</td>';
	echo '<td class="text-end">'.($res1['a']?:'-').'</td>';
	echo '<td class="text-end">'.($res2['a']?:'-').'</td>';
	echo '<td class="text-end">'.($res3['a']?:'-').'</td>';
	echo '<td class="text-end pe-4">'.($res4['a']?:'-').'</td>';
	echo '</tr>';
}

// --- Aggregate Totals Rows ---
$hashrate1 = max(controller()->memcache->get_database_scalar("history_hashrate1-$algo", "SELECT AVG(hashrate) FROM hashrate WHERE time>$t1 AND algo=:algo", array(':algo'=>$algo)), 1);
$hashrate2 = max(controller()->memcache->get_database_scalar("history_hashrate2-$algo", "SELECT AVG(hashrate) FROM hashrate WHERE time>$t2 AND algo=:algo", array(':algo'=>$algo)), 1);
$hashrate3 = max(controller()->memcache->get_database_scalar("history_hashrate3-$algo", "SELECT AVG(hashrate) FROM hashrate WHERE time>$t3 AND algo=:algo", array(':algo'=>$algo)), 1);
$hashrate4 = max(controller()->memcache->get_database_scalar("history_hashrate4-$algo", "SELECT AVG(hashrate) FROM hashstats WHERE time>$t4 AND algo=:algo", array(':algo'=>$algo)), 1);

$btcmhday1 = mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 24 * 1000);
$btcmhday2 = mbitcoinvaluetoa($total2 / $hashrate2 * 1000000 * 1 * 1000);
$btcmhday3 = mbitcoinvaluetoa($total3 / $hashrate3 * 1000000 / 7 * 1000);
$btcmhday4 = mbitcoinvaluetoa($total4 / $hashrate4 * 1000000 / 30 * 1000);

echo '        </tbody>';
echo '        <tfoot class="table-light small text-uppercase fw-bold">';
// BTC Value Row
echo '          <tr class="border-top border-2 border-dark border-opacity-10">';
echo '            <td class="ps-4 text-center"><img width="16" src="/images/btc.png"></td>';
echo '            <td colspan="2">BTC Combined Value</td>';
echo '            <td class="text-end">'.bitcoinvaluetoa($total1).'</td>';
echo '            <td class="text-end">'.bitcoinvaluetoa($total2).'</td>';
echo '            <td class="text-end">'.bitcoinvaluetoa($total3).'</td>';
echo '            <td class="text-end pe-4">'.bitcoinvaluetoa($total4).'</td>';
echo '          </tr>';
// Avg Hashrate Row
echo '          <tr>';
echo '            <td class="ps-4"></td><td colspan="2">Avg Pool Hashrate</td>';
echo '            <td class="text-end">'.Itoa2($hashrate1).'h/s</td>';
echo '            <td class="text-end">'.Itoa2($hashrate2).'h/s</td>';
echo '            <td class="text-end">'.Itoa2($hashrate3).'h/s</td>';
echo '            <td class="text-end pe-4">'.Itoa2($hashrate4).'h/s</td>';
echo '          </tr>';
// Profitability Row
echo '          <tr class="table-primary bg-opacity-10 text-primary">';
echo '            <td class="ps-4"></td><td colspan="2">mBTC/'.$algo_unit.'/day</td>';
echo '            <td class="text-end">'.$btcmhday1.'</td>';
echo '            <td class="text-end">'.$btcmhday2.'</td>';
echo '            <td class="text-end">'.$btcmhday3.'</td>';
echo '            <td class="text-end pe-4">'.$btcmhday4.'</td>';
echo '          </tr>';
echo '        </tfoot>';
echo '      </table></div></div></div>';
?>