<?php

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-monsters me-2 text-danger"></i>Pool "Monsters" & Big Miners</h5>';
echo '    <input class="search form-control form-control-sm border-secondary bg-dark text-white" type="search" data-column="all" style="width: 200px;" placeholder="Filter results..." />';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="50">UID</th>';
echo '            <th>Coin</th>';
echo '            <th>Wallet Address</th>';
echo '            <th>Reason</th>';
echo '            <th>Last Seen</th>';
echo '            <th class="text-center">Blocks (24h)</th>';
echo '            <th class="text-end">Balance</th>';
echo '            <th class="text-end">Total Paid</th>';
echo '            <th class="text-center">Workers</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

function showUserRow($userid, $reason)
{
	$user = getdbo('db_accounts', $userid);
	if(!$user) return;

	$balance = bitcoinvaluetoa($user->balance);
    $paid_raw = dboscalar("select sum(amount) from payouts where account_id=$user->id");
	$paid = bitcoinvaluetoa($paid_raw);

	$t = time()-24*60*60;
	$miner_count = getdbocount('db_workers', "userid=$user->id");
	$block_count = getdbocount('db_blocks', "userid=$user->id and time>$t");
	$coin = getdbo('db_coins', $user->coinid);

    $reason_badge = 'bg-secondary';
    if ($reason == 'miners') $reason_badge = 'bg-primary';
    if ($reason == 'shares') $reason_badge = 'bg-info text-dark';
    if ($reason == 'locked') $reason_badge = 'bg-danger';
    if ($reason == 'blocks') $reason_badge = 'bg-success';

	echo '<tr>';
	echo '<td class="ps-4 text-muted small">'.$user->id.'</td>';
	echo '<td>';
    if ($coin) {
        echo '<img src="'.$coin->image.'" width="18" class="me-1 rounded-circle shadow-sm">';
        echo '<b>'.CHtml::link($coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b>';
    } else echo '-';
    echo '</td>';

	echo '<td>';
    echo '  <div class="fw-bold font-monospace" style="font-size: 0.8rem;">'.CHtml::link(substr($user->username,0,20).'...', '/?address='.urlencode($user->username), ['class'=>'text-primary text-decoration-none', 'target'=>'_blank']).'</div>';
    echo '</td>';

    echo '<td><span class="badge '.$reason_badge.' text-uppercase" style="font-size: 0.6rem;">'.$reason.'</span></td>';
	echo '<td class="text-muted small">'.datetoa2($user->last_earning).' ago</td>';
	echo '<td class="text-center fw-bold">'.($block_count?:'-').'</td>';
	echo '<td class="text-end text-success fw-bold">'.$balance.'</td>';
	echo '<td class="text-end fw-bold">'.$paid.'</td>';
	echo '<td class="text-center"><span class="badge bg-secondary rounded-pill">'.$miner_count.'</span></td>';

	echo '<td class="text-end pe-4">';
    if($user->is_locked)
		echo '<a href="/admin/unblockuser?wallet='.urlencode($user->username).'" class="btn btn-xs btn-outline-warning py-0 px-2 fw-bold"><i class="fa fa-unlock me-1"></i>UNLOCK</a>';
	else
		echo '<a href="/admin/blockuser?wallet='.urlencode($user->username).'" class="btn btn-xs btn-outline-dark py-0 px-2 fw-bold"><i class="fa fa-lock me-1"></i>BLOCK</a>';
	echo '</td>';
	echo '</tr>';
}

$t = time()-24*60*60;

// High hashrate / ghost workers
$list = dbolist("select userid from shares where pid is null or pid not in (select pid from stratums) group by userid");
foreach($list as $item) showUserRow($item['userid'], 'ghosts');

// Whales with balances but no recent blocks
$list = dbolist("select id from accounts where balance>0.001 and id not in (select distinct userid from blocks where userid is not null and time>$t)");
foreach($list as $item) showUserRow($item['id'], 'idle_balance');

// Top worker counts
$monsters = dbolist("SELECT COUNT(*) AS total, userid FROM workers GROUP BY userid ORDER BY total DESC LIMIT 5");
foreach($monsters as $item) showUserRow($item['userid'], 'miners');

// Block finders
$monsters = dbolist("SELECT COUNT(*) AS total, userid FROM blocks WHERE time>$t GROUP BY userid ORDER BY total DESC LIMIT 5");
foreach($monsters as $item) {
    if ($item['userid']) showUserRow($item['userid'], 'blocks');
}

// Manually locked
$list = getdbolist('db_accounts', "is_locked");
foreach($list as $user) showUserRow($user->id, 'locked');

echo "        </tbody>";
echo "      </table></div></div></div>";
?>