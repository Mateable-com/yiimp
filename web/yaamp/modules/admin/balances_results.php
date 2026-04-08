<?php

$exch = getparam('exch');
$mining = getdbosql('db_mining');

$markets = getdbolist('db_markets', "name=:exch ORDER BY ((balance+ontrade)*price) DESC", array(':exch'=>$exch));

require_once('yaamp/ui/misc.php');
showFlashMessage();

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-university me-2 text-warning"></i>Market Balances: '.strtoupper($exch).'</h5>';
echo '    <span class="badge bg-warning text-dark px-3 fw-bold">Active Markets: '.count($markets).'</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="20"></th>';
echo '            <th>Coin</th>';
echo '            <th class="text-end">Bid</th>';
echo '            <th class="text-end">Ask</th>';
echo '            <th class="text-end">On Trade</th>';
echo '            <th class="text-end">Balance</th>';
echo '            <th class="text-end">Total BTC</th>';
echo '            <th class="text-end">USD Value</th>';
echo '            <th>Last Update</th>';
echo '            <th class="text-center">Status</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$totals_trade = $totals = $totals_usd = 0;
$symbols = array();

foreach($markets as $market)
{
	if ($market->pricetime == 0) continue;
	$balance = $market->balance + $market->ontrade;
	if ($balance*$market->price2 < 200*1e-8) continue;

	$coin = getdbo('db_coins', $market->coinid);
    if(!$coin) continue;
	$symbol = !empty($coin->symbol2) ? $coin->symbol2 : $coin->symbol;

	if (arraySafeVal($symbols, $symbol)) continue; // prevent dups
	$symbols[$symbol] = 1;

	$btime = $market->balancetime ? datetoa2($market->balancetime). ' ago' : 'never';
	$price = $market->price? bitcoinvaluetoa($market->price): bitcoinvaluetoa($coin->price);
	$price2 = $market->price2? bitcoinvaluetoa($market->price2): bitcoinvaluetoa($coin->price2);
	$total_btc = $balance * ($market->price ? : $coin->price);
	$total_usd = $total_btc * $mining->usdbtc;

    $is_disabled = ($market->disabled || !$coin->enable);
	$row_class = $is_disabled ? 'table-light opacity-50' : '';

	echo '<tr class="'.$row_class.'">';
	echo '<td class="ps-4 text-center"><img src="'.$coin->image.'" width="18" class="rounded-circle shadow-sm"></td>';
	echo '<td><b>'.CHtml::link($symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b></td>';
	echo '<td class="text-end fw-bold text-success">'.$price.'</td>';
	echo '<td class="text-end text-muted small">'.$price2.'</td>';
	echo '<td class="text-end text-info">'.($market->ontrade ? bitcoinvaluetoa($market->ontrade) : '-').'</td>';
	echo '<td class="text-end fw-bold">'.bitcoinvaluetoa($market->balance).'</td>';
	echo '<td class="text-end fw-bold '.($total_btc > 0.01 ? "text-primary" : "text-muted").'">'.bitcoinvaluetoa($total_btc).'</td>';
	echo '<td class="text-end text-success fw-bold">$'.number_format($total_usd, 2).'</td>';
	echo '<td class="text-muted small">'.$btime.'</td>';
    
    echo '<td class="text-center">';
    if ($is_disabled) echo '<span class="badge bg-danger">DISABLED</span>';
    else echo '<span class="badge bg-success">OK</span>';
    echo '</td>';

	echo '<td class="text-end pe-4">';
    echo '  <a href="/admin/balanceUpdate?market='.$market->id.'" class="btn btn-xs btn-outline-primary py-0 px-2" title="Force Update"><i class="fa fa-sync-alt"></i></a>';
	echo '</td>';

	$totals_trade += $market->ontrade * ($market->price ? : $coin->price);
	$totals += $total_btc; 
    $totals_usd += $total_usd;
	echo "</tr>";
}

echo '        </tbody>';
echo '        <tfoot class="table-dark">';
echo '          <tr>';
echo '            <th colspan="6" class="ps-4 small text-uppercase">Consolidated Portfolio Total</th>';
echo '            <th class="text-end text-warning">'.bitcoinvaluetoa($totals).' BTC</th>';
echo '            <th class="text-end text-success">$'.number_format($totals_usd, 2).'</th>';
echo '            <th colspan="3"></th>';
echo '          </tr>';
echo '        </tfoot>';
echo '      </table></div></div></div>';
?>