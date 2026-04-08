<?php

$algo = user()->getState('yaamp-algo');

$user = getuserparam(getparam('address'));
if(!$user || $user->is_locked) return;

$count = getparam('count');
$count = $count? $count: 20;

echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-trophy me-2 text-warning"></i>Last '.$count.' Blocks Found</h5>';
echo '    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3">Miner: <small class="font-monospace">'.$user->username.'</small></span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="wallet-blocks-table">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" style="width: 40px;"></th>';
echo '            <th>Asset / Algo</th>';
echo '            <th class="text-end">Height</th>';
echo '            <th class="text-end">Amount</th>';
echo '            <th class="text-end">Difficulty</th>';
echo '            <th class="text-center">Time Ago</th>';
echo '            <th class="text-center">Effort</th>';
echo '            <th class="text-center">Method</th>';
echo '            <th class="text-end pe-4">Status</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$db_blocks = getdbolist('db_blocks', "userid=$user->id order by time desc limit :count", array(':count'=>$count));

foreach($db_blocks as $db_block)
{
	$coin = getdbo('db_coins', $db_block->coinid);
	if(!$coin) continue;

	if($db_block->category == 'stake' && !$this->admin) continue;
	if($db_block->category == 'generated' && !$this->admin) continue;

	$activeClass = ($db_block->category == 'immature') ? 'table-warning bg-opacity-10' : '';
	echo '<tr class="'.$activeClass.'">';

	echo '  <td class="ps-4 text-center"><img width="18" src="'.$coin->image.'" class="rounded-circle shadow-sm"></td>';

	$flags = $db_block->segwit ? '&nbsp;<img src="/images/ui/segwit.png" height="8px" title="segwit"/>' : '';

	echo '  <td>';
	if ($this->admin) echo '<a href="/site/coin?id='.$coin->id.'" class="text-decoration-none fw-bold">'.$coin->name.'</a>';
	else echo '<span class="fw-bold">'.$coin->name.'</span>';
	echo ' <small class="text-muted text-uppercase">('.$coin->algo.')</small>'.$flags.'</td>';

	$d = datetoa2($db_block->time);
	echo '  <td class="text-end fw-bold">'.$coin->createExplorerLink($db_block->height, array('height'=>$db_block->height), ['class'=>'text-decoration-none']).'</td>';
	echo '  <td class="text-end fw-bold text-primary">'.$db_block->amount.'</td>';
	echo '  <td class="text-end font-monospace small">'.round_difficulty($db_block->difficulty).'</td>';
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
		echo '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 w-100" title="'.$eta.'">IMMATURE ('.$db_block->confirmations.'/'.$coin->mature_blocks.')</span>';
	}
	else if($db_block->category == 'generate') echo '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 w-100">CONFIRMED</span>';
	echo '  </td>';

	echo '</tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';
