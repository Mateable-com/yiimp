<?php

require dirname(__FILE__).'/../../ui/lib/pageheader.php';

$user = getuserparam(getparam('address'));
if(!$user) return;

$this->pageTitle = $user->username.' | '.YAAMP_SITE_NAME;

$bitcoin = getdbosql('db_coins', "symbol='BTC'");

echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-history me-2 text-primary"></i>Transactions History</h5>';
echo '    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">Miner: <small class="font-monospace">'.htmlspecialchars($user->username).'</small></span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="tx-history-table">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" style="width: 40px;"></th>';
echo '            <th>Time Ago</th>';
echo '            <th class="text-end">Amount Paid</th>';
echo '            <th class="text-end pe-4">Transaction ID</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$coin = ($user->coinid == $bitcoin->id) ? $bitcoin : getdbo('db_coins', $user->coinid);

$total = 0;
foreach($list as $payout)
{
	$d = datetoa2($payout->time);
	$amount = bitcoinvaluetoa($payout->amount);

	echo '<tr>';
	echo '  <td class="ps-4 text-center">'.($coin ? '<img width="18" src="'.$coin->image.'" class="rounded-circle shadow-sm">' : '').'</td>';
	echo '  <td class="fw-bold">'.$d.' ago</td>';
	echo '  <td class="text-end fw-bold text-success">'.$amount.'</td>';
	
	$url = $coin->createExplorerLink(substr($payout->tx, 0, 48).'...', array('txid'=>$payout->tx), array('target'=>'_blank', 'class'=>'text-decoration-none font-monospace'));
	echo '  <td class="text-end pe-4">'.$url.'</td>';
	echo '</tr>';
	$total += $payout->amount;
}

$total_str = bitcoinvaluetoa($total);

echo '        </tbody>';
echo '        <tfoot class="table-dark small text-uppercase fw-bold">';
echo '          <tr>';
echo '            <td class="ps-4" colspan="2">Total Payouts</td>';
echo '            <td class="text-end text-warning">'.$total_str.'</td>';
echo '            <td></td>';
echo '          </tr>';
echo '        </tfoot>';
echo '      </table></div></div></div>';


