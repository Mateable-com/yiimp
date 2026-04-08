<?php

function WriteBoxHeader($title)
{
	echo "<div class='main-left-box'>";
	echo "<div class='main-left-title'>$title</div>";
	echo "<div class='main-left-inner'>";
}

$algo = user()->getState('yaamp-algo');

$user = getuserparam(getparam('address'));
if(!$user || $user->is_locked) return;

$count = getparam('count');
$count = $count? $count: 50;

echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-hand-holding-usd me-2 text-success"></i>Last '.$count.' Earnings</h5>';
echo '    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">Miner: <small class="font-monospace">'.$user->username.'</small></span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="user-earnings-table">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" style="width: 40px;"></th>';
echo '            <th>Asset / Algo</th>';
echo '            <th class="text-end">Height</th>';
echo '            <th class="text-end">Amount</th>';
echo '            <th class="text-end">Percent</th>';
echo '            <th class="text-end">mBTC</th>';
echo '            <th class="text-center">Time Ago</th>';
echo '            <th class="text-end pe-4">Status</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$showrental = (bool) YAAMP_RENTAL;
$earnings = getdbolist('db_earnings', "userid=$user->id order by create_time desc limit :count", array(':count'=>$count));

foreach($earnings as $earning)
{
	$coin = getdbo('db_coins', $earning->coinid);
	$block = getdbo('db_blocks', $earning->blockid);
	if (!$block) continue;

	$d = datetoa2($earning->create_time);
	if(!$coin)
	{
		if (!$showrental) continue;
		$reward = bitcoinvaluetoa($earning->amount);
		$value = mbitcoinvaluetoa($earning->amount*1000);
		$percent = $block->amount ? percentvaluetoa($earning->amount * 100/$block->amount) : 0;

		echo '<tr>';
		echo '  <td class="ps-4 text-center"><img width="18" src="/images/btc.png" class="rounded-circle shadow-sm"></td>';
		echo '  <td><span class="fw-bold text-primary">Rental Power</span> <small class="text-muted text-uppercase">('.$block->algo.')</small></td>';
		echo '  <td class="text-end">-</td>';
		echo '  <td class="text-end fw-bold text-primary">'.$reward.' BTC</td>';
		echo '  <td class="text-end small">'.$percent.'%</td>';
		echo '  <td class="text-end small">'.$value.'</td>';
		echo '  <td class="text-center small text-muted">'.$d.' ago</td>';
		echo '  <td class="text-end pe-4"><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 w-100">CLEARED</span></td>';
		echo '</tr>';
		continue;
	}

	$height = number_format($block->height, 0, '.', ' ');
	$reward = altcoinvaluetoa($earning->amount);
	$percent = $block->amount ? percentvaluetoa($earning->amount * 100/$block->amount) : 0;
	$value = mbitcoinvaluetoa($earning->amount*$earning->price*1000);
	$blockUrl = $coin->createExplorerLink($coin->name, array('height'=>$block->height), ['class'=>'text-decoration-none fw-bold text-dark']);

	echo '<tr>';
	echo '  <td class="ps-4 text-center"><img width="18" src="'.$coin->image.'" class="rounded-circle shadow-sm"></td>';
	echo '  <td>'.$blockUrl.' <small class="text-muted text-uppercase">('.$coin->algo.')</small></td>';
	echo '  <td class="text-end small">'.$height.'</td>';
	echo '  <td class="text-end fw-bold">'.$reward.' <small class="text-muted">'.$coin->symbol_show.'</small></td>';
	echo '  <td class="text-end small">'.$percent.'%</td>';
	echo '  <td class="text-end small font-monospace">'.$value.'</td>';
	echo '  <td class="text-center small text-muted">'.$d.' ago</td>';
	echo '  <td class="text-end pe-4">';

	if($earning->status == 0) {
		$eta = '';
		if ($coin->block_time && $coin->mature_blocks) {
			$t = (int) ($coin->mature_blocks - $block->confirmations) * $coin->block_time;
			$eta = "ETA: ".sprintf('%dh %02dmn', ($t/3600), ($t/60)%60);
		}
		echo '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 w-100" title="'.$eta.'">IMMATURE ('.$block->confirmations.'/'.$coin->mature_blocks.')</span>';
	}
	else if($earning->status == 1) echo '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 w-100">'.(YAAMP_ALLOW_EXCHANGE ? 'EXCHANGE' : 'CONFIRMED').'</span>';
	else if($earning->status == 2) echo '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 w-100">CLEARED</span>';
	else if($earning->status == -1) echo '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 w-100">INVALID</span>';

	echo '  </td>';
	echo '</tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';
