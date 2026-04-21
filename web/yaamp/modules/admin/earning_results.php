<?php

$coin_id = getiparam('id');
$sqlFilter = $coin_id ? "AND coinid={$coin_id}": '';
$limit = $coin_id ? '' : 'LIMIT 1500';

$target_coin = $coin_id ? getdbo('db_coins', $coin_id) : null;

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-hand-holding-usd me-2 text-info"></i>Miner Earnings '.($target_coin ? "({$target_coin->symbol})" : "").'</h5>';
echo '    <div class="d-flex gap-2">';
echo '      <input class="search form-control form-control-sm border-secondary bg-dark text-white" type="search" data-column="all" style="width: 200px;" placeholder="Search earnings..." />';
echo '      <span class="badge bg-primary d-flex align-items-center px-3">Records: '.count(getdbolist('db_earnings', "status!=2 $sqlFilter ORDER BY create_time DESC $limit")).'</span>';
echo '    </div>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4">Coin</th>';
echo '            <th>Wallet Address</th>';
echo '            <th class="text-end">Quantity</th>';
echo '            <th class="text-end">BTC Value</th>';
echo '            <th class="text-end">Block</th>';
echo '            <th class="text-center">Status</th>';
echo '            <th>Created / Mature</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$earnings = getdbolist('db_earnings', "status!=2 $sqlFilter ORDER BY create_time DESC $limit");

$total = 0.; $total_btc = 0.; $totalimmat = 0.; $totalfees = 0.; $totalstake = 0.;

foreach($earnings as $earning)
{
	$coin = getdbo('db_coins', $earning->coinid);
	if(!$coin) continue;

	$user = getdbo('db_accounts', $earning->userid);
	if(!$user) continue;

	$block = getdbo('db_blocks', $earning->blockid);
	if(!$block) continue;

	$t1 = datetoa2($earning->create_time). ' ago';
	$t2 = datetoa2($earning->mature_time);
	if ($t2) $t2 = '+'.$t2;

	echo '<tr>';
	echo '<td class="ps-4">';
    echo '  <img src="'.$coin->image.'" width="20" class="me-2 rounded-circle shadow-sm">';
    echo '  <b>'.CHtml::link($coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b>';
    echo '</td>';

	echo '<td>';
    echo '  <div class="fw-bold font-monospace" style="font-size: 0.8rem;">'.CHtml::link(htmlspecialchars(substr($user->username,0,20)).'...', '/?address='.urlencode($user->username), ['class'=>'text-primary text-decoration-none', 'target'=>'_blank']).'</div>';
    echo '</td>';

	echo '<td class="text-end fw-bold">'.bitcoinvaluetoa($earning->amount).'</td>';
	echo '<td class="text-end text-muted">'.bitcoinvaluetoa($earning->amount * $earning->price).'</td>';
	echo '<td class="text-end">'.number_format($block->height).'</td>';

	echo '<td class="text-center">';
    $cat_class = ($block->category == 'orphan') ? 'bg-danger' : (($block->category == 'immature') ? 'bg-warning text-dark' : 'bg-success');
    echo '<span class="badge '.$cat_class.' text-uppercase" style="font-size: 0.6rem;">'.$block->category.'</span>';
    echo '<div class="small text-muted" style="font-size: 0.65rem;">'.$block->confirmations.' confs</div>';
    echo '</td>';

	echo '<td>';
    echo '  <div class="small">'.$t1.'</div>';
    if ($earning->mature_time > time()) echo '  <div class="text-info" style="font-size: 0.65rem;"><i class="fa fa-clock me-1"></i>'.$t2.'</div>';
    echo '</td>';

	echo '<td class="text-end pe-4">';
    echo '  <div class="btn-group">';
	echo '    <a href="/admin/clearearning?id='.$earning->id.'" class="btn btn-xs btn-outline-success py-0 px-2" title="Clear"><i class="fa fa-check"></i></a>';
	echo '    <a href="/admin/deleteearning?id='.$earning->id.'" class="btn btn-xs btn-outline-danger py-0 px-2" onclick="return confirm(\'Delete this earning record?\')" title="Delete"><i class="fa fa-trash"></i></a>';
    echo '  </div>';
	echo '</td>';
	echo "</tr>";

	if($block->category == 'immature') {
		$total += (double) $earning->amount;
		$total_btc += (double) $earning->amount * $earning->price;
		$totalimmat += (double) $earning->amount;
	}
	if($block->category == 'generate') {
		$total += (double) $earning->amount; 
		$total_btc += (double) $earning->amount * $earning->price;
	}
	else if($block->category == 'stake' || $block->category == 'generated') {
		$totalstake += (double) $earning->amount;
	}
}

echo '        </tbody>';
echo '      </table>';
echo '    </div>';
echo '  </div>';
echo '</div>';

if ($coin_id && $target_coin) {
	$feepct = yaamp_fee($target_coin->algo);
	$totalfees = ($total / ((100 - $feepct) / 100.)) - $total;
	$cleared = dboscalar("SELECT SUM(balance) FROM accounts WHERE coinid={$target_coin->id}");
    $exchange = $total - $totalimmat;
    $available = $target_coin->balance - $exchange - $cleared;

    echo '<div class="row g-3 justify-content-end">';
    
    $stats = [
        ['Immature', bitcoinvaluetoa($totalimmat), 'info'],
        ['Total Owed', bitcoinvaluetoa($total), 'warning'],
        ['Fees ('.round($feepct,1).'%)', bitcoinvaluetoa($totalfees), 'secondary'],
        ['Balance', bitcoinvaluetoa($target_coin->balance), 'success'],
        ['Cleared', bitcoinvaluetoa($cleared), 'primary'],
        ['Available', bitcoinvaluetoa($available), 'dark']
    ];

    foreach($stats as $s) {
        echo '<div class="col-md-2">';
        echo '  <div class="card shadow-sm border-0 border-top border-3 border-'.$s[2].'">';
        echo '    <div class="card-body p-2 text-center">';
        echo '      <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.6rem;">'.$s[0].'</div>';
        echo '      <div class="fw-bold small">'.$s[1].'</div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    echo '</div>';
}
?>