<?php

$orders = getdbolist('db_orders', "1 order by (amount*bid) desc");

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-exchange-alt me-2 text-warning"></i>Active Market Orders</h5>';
echo '    <span class="badge bg-warning text-dark px-3 fw-bold">Open Orders: '.count($orders).'</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="orderstable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="20"></th>';
echo '            <th>Coin</th>';
echo '            <th>Exchange</th>';
echo '            <th>Created</th>';
echo '            <th class="text-end">Quantity</th>';
echo '            <th class="text-end">Ask</th>';
echo '            <th class="text-end">Bid</th>';
echo '            <th class="text-end">Value (BTC)</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$totalvalue = 0; $totalbid = 0;

foreach($orders as $order)
{
	$coin = getdbo('db_coins', $order->coinid);
    if(!$coin) continue;
	$marketurl = getMarketUrl($coin, $order->market);

	$created = datetoa2($order->created). ' ago';
	$price = bitcoinvaluetoa($order->price);
	$bid = bitcoinvaluetoa($order->bid);
	$value = $order->amount * $order->price;
	$bidvalue = $order->amount * $order->bid;
	$totalvalue += $value;
	$totalbid += $bidvalue;
	$bidpercent = $value>0? round(($value-$bidvalue)/$value*100, 1): 0;

	echo '<tr>';
	echo '<td class="ps-4"><img src="'.$coin->image.'" width="18" class="rounded-circle shadow-sm"></td>';
	echo '<td><b>'.CHtml::link($coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b> <small class="text-muted d-none d-md-inline">('.$coin->name.')</small></td>';
	echo '<td><a href="'.$marketurl.'" target="_blank" class="badge bg-secondary text-decoration-none">'.$order->market.' <i class="fa fa-external-link-alt ms-1" style="font-size: 0.6rem;"></i></a></td>';
	echo '<td class="text-muted">'.$created.'</td>';
	echo '<td class="text-end fw-bold">'.bitcoinvaluetoa($order->amount).'</td>';
	echo '<td class="text-end small">'.$price.'</td>';
	echo '<td class="text-end">'.$bid.' <span class="text-danger small" style="font-size: 0.65rem;">(-'.$bidpercent.'%)</span></td>';
	echo '<td class="text-end fw-bold '.($bidvalue > 0.01 ? "text-success" : "text-muted").'">'.bitcoinvaluetoa($bidvalue).'</td>';
	echo '<td class="text-end pe-4"><a href="/admin/cancelorder?id='.$order->id.'" class="btn btn-xs btn-outline-danger py-0 px-2 fw-bold" onclick="return confirm(\'Cancel this order?\')">CANCEL</a></td>';
	echo '</tr>';
}

$total_bid_percent = $totalvalue? round(($totalvalue-$totalbid)/$totalvalue*100, 1): 0;

echo '        </tbody>';
echo '        <tfoot class="table-dark">';
echo '          <tr>';
echo '            <th colspan="4" class="ps-4 small text-uppercase">Market Totals</th>';
echo '            <th></th><th></th>';
echo '            <th class="text-end text-danger small">-'.$total_bid_percent.'%</th>';
echo '            <th class="text-end text-warning">'.bitcoinvaluetoa($totalbid).' BTC</th>';
echo '            <th></th>';
echo '          </tr>';
echo '        </tfoot>';
echo '      </table></div></div></div>';

//////////////////////////////////////////////////////////////////////////////////////////////////////////////

$exchanges_deposits = getdbolist('db_exchange_deposit', "1 order by send_time desc limit 150");

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-history me-2 text-info"></i>Deposit History</h5>';
echo '    <span class="badge bg-info text-dark px-3 fw-bold">Recent: '.count($exchanges_deposits).'</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="depositstable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="20"></th>';
echo '            <th>Coin</th>';
echo '            <th>Market</th>';
echo '            <th>Sent</th>';
echo '            <th class="text-end">Quantity</th>';
echo '            <th class="text-end">Estimate</th>';
echo '            <th class="text-end">Sold Price</th>';
echo '            <th class="text-end">Value (BTC)</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

foreach($exchanges_deposits as $exchange_deposit)
{
	$coin = getdbo('db_coins', $exchange_deposit->coinid);
    if(!$coin) continue;
	$marketurl = getMarketUrl($coin, $exchange_deposit->market);

    $is_waiting = ($exchange_deposit->status == 'waiting');
    $row_class = $is_waiting ? 'table-warning opacity-75' : '';

	$sent = datetoa2($exchange_deposit->send_time). ' ago';
	$price = $exchange_deposit->price? bitcoinvaluetoa($exchange_deposit->price): bitcoinvaluetoa($coin->price);
	$estimate = bitcoinvaluetoa($exchange_deposit->price_estimate);
	$total = $exchange_deposit->price? ($exchange_deposit->quantity*$exchange_deposit->price): ($exchange_deposit->quantity*$coin->price);

	echo '<tr class="'.$row_class.'">';
	echo '<td class="ps-4"><img src="'.$coin->image.'" width="18" class="rounded-circle shadow-sm"></td>';
	echo '<td><b>'.CHtml::link($coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b></td>';
	echo '<td><span class="badge bg-light text-dark border">'.$exchange_deposit->market.'</span></td>';
	echo '<td class="text-muted">'.$sent.'</td>';
	echo '<td class="text-end">'.bitcoinvaluetoa($exchange_deposit->quantity).'</td>';
	echo '<td class="text-end text-muted small">'.$estimate.'</td>';
	echo '<td class="text-end fw-bold">'.$price.'</td>';
	echo '<td class="text-end fw-bold '.($total > 0.01 ? "text-success" : "text-muted").'">'.bitcoinvaluetoa($total).'</td>';
	echo '<td class="text-end pe-4">';
	if($is_waiting) {
		echo '<a href="/admin/deleteexchangedeposit?id='.$exchange_deposit->id.'" class="btn btn-xs btn-outline-danger py-0 px-2" title="Delete"><i class="fa fa-trash"></i></a>';
	} else {
        echo '<span class="badge bg-success opacity-50">SOLD</span>';
    }
	echo '</td>';
	echo '</tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';
?>