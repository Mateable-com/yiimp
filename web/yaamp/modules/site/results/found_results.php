<?php

function WriteBoxHeader($title)
{
	echo "<div class='main-left-box'>";
	echo "<div class='main-left-title'>$title</div>";
	echo "<div class='main-left-inner'>";
}

$showrental = (bool) YAAMP_RENTAL;

// Ability to pass ?algo=all or ?algo=x16s,neoscrypt from URL
$algo_from_query_param = getparam('algo');

if($algo_from_query_param) {
	// Query param is set
	if($algo_from_query_param != 'all') {
		$r_algo = array_map('trim', explode(',', $algo_from_query_param));
		$r_algo = preg_replace('/[^A-Za-z0-9\-]/', '', $r_algo);
	}

} else {
	// Filter out algo from user's preferences
	$algo_from_user_pref = user()->getState('yaamp-algo');
	if($algo_from_user_pref != 'all') {
		$r_algo = array($algo_from_user_pref);
	}
}

$count = getparam('count');
$count = $count? $count: 50;

$algo_header = isset($r_algo) ? implode(',', $r_algo) : 'Global';

echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-cubes me-2 text-warning"></i>Last '.$count.' Blocks: <span class="text-warning small text-uppercase">'.$algo_header.'</span></h5>';
echo '    <span class="badge bg-secondary">Recent Discovery</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="found-blocks-table">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" style="width: 40px;"></th>';
echo '            <th>Asset / Algo</th>';
echo '            <th class="text-end">Amount</th>';
echo '            <th class="text-end">Difficulty</th>';
echo '            <th class="text-end">Height</th>';
echo '            <th class="text-center">Time Ago</th>';
echo '            <th class="text-center">Effort</th>';
echo '            <th class="text-center">Method</th>';
echo '            <th class="text-end pe-4">Status</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$criteria = new CDbCriteria();
$criteria->condition = "t.category NOT IN ('stake','generated')";
// $criteria->condition .= " AND IFNULL(coin.visible,1)=1"; // ifnull for rental
if(isset($r_algo)) {
	$criteria->addInCondition('t.algo', $r_algo);
}
$criteria->limit = $count;
$criteria->order = 't.time DESC';
$db_blocks = getdbolistWith('db_blocks', 'coin', $criteria);

foreach($db_blocks as $db_block)
{
	$d = datetoa2($db_block->time);
	if(!$db_block->coin_id)
	{
		if (!$showrental) continue;
		$reward = bitcoinvaluetoa($db_block->amount);
		echo '<tr>';
		echo '  <td class="ps-4 text-center"><img width="18" src="/images/btc.png" class="rounded-circle shadow-sm"></td>';
		echo '  <td><span class="fw-bold">Rental Power</span> <small class="text-muted text-uppercase">('.$db_block->algo.')</small></td>';
		echo '  <td class="text-end fw-bold text-primary">'.$reward.' BTC</td>';
		echo '  <td colspan="4"></td>';
		echo '  <td class="text-end pe-4"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 w-100">CONFIRMED</span></td>';
		echo '</tr>';
		continue;
	}

	$reward = round($db_block->amount, 3);
	$coin = $db_block->coin ? $db_block->coin : getdbo('db_coins', $db_block->coin_id);
	$difficulty = Itoa2($db_block->difficulty, 3);
	$height = number_format($db_block->height, 0, '.', ' ');
	$link = $coin->createExplorerLink($coin->name, array('hash'=>$db_block->blockhash), ['class'=>'text-decoration-none fw-bold text-dark']);
	$flags = $db_block->segwit ? '&nbsp;<img src="/images/ui/segwit.png" height="8px" title="segwit"/>' : '';

	echo '<tr>';
	echo '  <td class="ps-4 text-center"><img width="18" src="'.$coin->image.'" class="rounded-circle shadow-sm"></td>';
	echo '  <td>'.$link.' <small class="text-muted text-uppercase">('.$db_block->algo.')</small>'.$flags.'</td>';
	echo '  <td class="text-end fw-bold">'.$reward.' <small class="text-muted">'.$coin->symbol_show.'</small></td>';
	echo '  <td class="text-end small font-monospace" title="Found: '.$db_block->difficulty_user.'">'.$difficulty.'</td>';
	echo '  <td class="text-end small">'.$height.'</td>';
	echo '  <td class="text-center small text-muted">'.$d.' ago</td>';
	echo '  <td class="text-center small">'.($db_block->effort ? $db_block->effort.'%' : 'N/A').'</td>';
	
	$methodBadge = ($db_block->solo == '1') ? '<span class="badge bg-info text-uppercase" style="font-size: 0.6rem;">SOLO</span>' : '<span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.6rem;">SHARED</span>';
	echo '  <td class="text-center">'.$methodBadge.'</td>';

	echo '  <td class="text-end pe-4">';
	if($db_block->category == 'orphan') echo '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 w-100">ORPHAN</span>';
	else if($db_block->category == 'immature') {
		$eta = '';
		if ($coin->block_time && $coin->mature_blocks) {
			$t = (int) ($coin->mature_blocks - $db_block->confirmations) * $coin->block_time;
			$eta = "ETA: ".sprintf('%dh %02dmn', ($t/3600), ($t/60)%60);
		}
		$confText = ($coin->mature_blocks) ? '('.$db_block->confirmations.'/'.$coin->mature_blocks.')' : '('.$db_block->confirmations.')';
		echo '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 w-100" title="'.$eta.'">IMMATURE '.$confText.'</span>';
	}
	else if($db_block->category == 'generate') echo '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 w-100">CONFIRMED</span>';
	else if($db_block->category == 'new') echo '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 w-100">NEW</span>';
	echo '  </td>';
	echo '</tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';




