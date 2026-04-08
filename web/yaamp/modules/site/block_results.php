<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

$id = (int) getiparam('id');
$db_blocks = getdbolist('db_blocks', "coin_id=:id order by time desc limit 250", array(':id'=>$id));
$coin = getdbo('db_coins', $id);

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	headers: {
		0:{sorter:false},
		1:{sorter:false},
		2:{sorter:'metadata'},
		3:{sorter:'numeric'},
		4:{sorter:'currency'},
		5:{sorter:'text'},
		6:{sorter:'numeric'},
		7:{sorter:'numeric'},
		8:{sorter:'text'}
	},
	widgets: ['zebra','filter'],
	widgetOptions: {
		filter_columnFilters: false,
		filter_ignoreCase: true
	}
}");

echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-cubes me-2 text-primary"></i>Latest Blocks: <span class="text-primary small text-uppercase">'.($coin ? $coin->name : 'All').'</span></h5>';
echo '    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">Live Explorer</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" style="width: 40px;"></th>';
echo '            <th>Asset Name</th>';
echo '            <th>Time Ago</th>';
echo '            <th class="text-end">Height</th>';
echo '            <th class="text-end">Amount</th>';
echo '            <th class="text-center">Method</th>';
echo '            <th class="text-center">Effort</th>';
echo '            <th class="text-center">Status</th>';
echo '            <th class="text-end">Difficulty</th>';
echo '            <th class="text-end">Share Diff</th>';
echo '            <th class="text-center">Finder</th>';
echo '            <th class="text-end pe-4">Blockhash</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

foreach($db_blocks as $db_block)
{
	if(!$db_block->coin_id) continue;
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
	echo ' <small class="text-muted">('.$coin->symbol.')</small>'.$flags.'</td>';

	$d = datetoa2($db_block->time);
	echo '  <td data="'.$db_block->time.'">'.$d.' ago</td>';
	echo '  <td class="text-end fw-bold">'.$coin->createExplorerLink($db_block->height, array('height'=>$db_block->height), ['class'=>'text-decoration-none']).'</td>';
	echo '  <td class="text-end fw-bold">'.$db_block->amount.'</td>';
	
	$methodBadge = ($db_block->solo == '1') ? '<span class="badge bg-info text-uppercase" style="font-size: 0.6rem;">SOLO</span>' : '<span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.6rem;">SHARED</span>';
	echo '  <td class="text-center">'.$methodBadge.'</td>';

	echo '  <td class="text-center">'.($db_block->effort ? $db_block->effort.'%' : 'N/A').'</td>';
	
	echo '  <td class="text-center">';
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
	else if($db_block->category == 'stake') echo '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 w-100">STAKE</span>';
	else if($db_block->category == 'generated') echo '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 w-100">STAKE</span>';
	echo '  </td>';

	echo '  <td class="text-end font-monospace">'.round_difficulty($db_block->difficulty).'</td>';
	$diff_user = $db_block->difficulty_user;
	if (!$diff_user && substr($db_block->blockhash,0,4) == '0000') $diff_user = hash_to_difficulty($coin, $db_block->blockhash);
	echo '  <td class="text-end font-monospace">'.round_difficulty($diff_user).'</td>';

	$finder = '';
	if (!empty($db_block->userid)) {
		$user = getdbo('db_accounts', $db_block->userid);
		$finder = $user ? substr($user->username, 0, 7).'...' : '';
	}
	echo '  <td class="text-center small font-monospace">'.$finder.'</td>';
	
	echo '  <td class="text-end pe-4 font-monospace" style="font-size: 0.75rem;">';
	echo $coin->createExplorerLink(substr($db_block->blockhash, 0, 16).'...', array('hash'=>$db_block->blockhash), ['class'=>'text-decoration-none']);
	echo '  </td>';
	echo '</tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';

