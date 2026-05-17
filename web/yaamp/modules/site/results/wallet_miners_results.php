<?php

$user = getuserparam(getparam('address'));
if(!$user) return;

$userid = intval($user->id);

echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-users me-2 text-primary"></i>Miner Strategy Overview</h5>';
echo '    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">Miner: <small class="font-monospace">'.htmlspecialchars($user->username).'</small></span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="wallet-strategy-table">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" style="width: 40px;"></th>';
echo '            <th>Asset / Algo</th>';
echo '            <th class="text-center">Workers (Shared/Solo)</th>';
echo '            <th class="text-center">Shares %</th>';
echo '            <th class="text-end">Hashrate (Shared)</th>';
echo '            <th class="text-end">TTF (Shared)</th>';
echo '            <th class="text-end">Hashrate (Solo)</th>';
echo '            <th class="text-end pe-4">TTF (Solo)</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

foreach(yaamp_get_algos() as $algo)
{
	$recent = time() - yaamp_hashrate_step();
	$list = getdbolist('db_coins', "id IN (SELECT DISTINCT coinid FROM shares WHERE userid=$userid AND algo=:algo AND time>$recent)", array(':algo' => $algo));
	foreach ($list as $coin)
	{
		if (!YAAMP_ALLOW_EXCHANGE && isset($coin) && $coin->algo != $algo) continue;
		$coinid = $coin->id;
		$name = substr($coin->name, 0, 20);

		$user_shared_rate = yaamp_user_coin_shared_rate($userid, $coinid);
		$user_solo_rate = yaamp_user_coin_solo_rate($userid, $coinid);

		$blocktime = $coin->block_time ? $coin->block_time : max(min($coin->actual_ttf, 60), 30);
		$network_hash = yaamp_coin_nethash($coin);

		$pool_shared_hash = yaamp_coin_shared_rate($coinid);
		$user_shared_ttf  = ($user_shared_rate && $pool_shared_hash) ? $network_hash / $pool_shared_hash * $blocktime * ($pool_shared_hash / $user_shared_rate) : 0;
		$user_shared_ttf  = $user_shared_ttf ? sectoa2($user_shared_ttf) : '-';

		$pool_solo_hash = yaamp_coin_solo_rate($coinid);
		$user_solo_ttf  = ($user_solo_rate && $network_hash) ? $network_hash / $user_solo_rate * $blocktime : 0;
		$user_solo_ttf  = $user_solo_ttf ? sectoa2($user_solo_ttf) : '-';

		$user_shared_rate_sfx = $user_shared_rate? Itoa2($user_shared_rate).'h/s': '-';
		$user_solo_rate_sfx = $user_solo_rate? Itoa2($user_solo_rate).'h/s': '-';

		$shared_minercount = getdbocount('db_workers', "userid=$userid AND algo=:algo and password not like '%m=solo%'", array(':algo'=>$algo));
		$solo_minercount = getdbocount('db_workers',"algo=:algo and userid=$userid and password like '%m=solo%'",array(':algo'=>$algo));

		$user_shared_shares = controller()->memcache->get_database_scalar("wallet_user_shared_shares-$algo-$coinid-$userid",
			"SELECT SUM(difficulty) FROM shares WHERE valid AND userid=$userid AND coinid=$coinid AND algo=:algo AND solo=0", array(':algo'=>$algo));
		if(!$user_shared_shares) continue;

		$total_shared_shares = controller()->memcache->get_database_scalar("wallet_coin_shared_shares-$coinid",
			"SELECT SUM(difficulty) FROM shares WHERE valid AND coinid=$coinid AND algo=:algo AND solo=0", array(':algo'=>$algo));

		if(!$total_shared_shares) continue;
		$percent_shared_shares = round($user_shared_shares * 100 / $total_shared_shares, 2);
		$percent_shared_shares = $percent_shared_shares? Itoa2($percent_shared_shares).'%': '-';

		echo '<tr>';
		echo '  <td class="ps-4 text-center"><img width="18" src="'.$coin->image.'" class="rounded-circle shadow-sm"></td>';
		echo '  <td><span class="fw-bold">'.$name.'</span> <small class="text-muted text-uppercase">('.$algo.')</small></td>';
		echo '  <td class="text-center fw-bold">'.$shared_minercount.' <span class="text-muted mx-1">/</span> '.$solo_minercount.'</td>';
		echo '  <td class="text-center small">'.$percent_shared_shares.'</td>';
		echo '  <td class="text-end fw-bold text-primary">'.$user_shared_rate_sfx.'</td>';
		echo '  <td class="text-end small">'.$user_shared_ttf.'</td>';
		echo '  <td class="text-end fw-bold text-info">'.$user_solo_rate_sfx.'</td>';
		echo '  <td class="text-end pe-4 small">'.$user_solo_ttf.'</td>';
		echo '</tr>';
	}
}

echo '        </tbody>';
echo '      </table></div></div></div>';

$workers = getdbolist('db_workers', "userid=$user->id order by password");
if(count($workers))
{
	echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
	echo '  <div class="card-header bg-dark text-white py-3 border-0">';
	echo '    <h5 class="mb-0 fw-bold small text-uppercase"><i class="fa fa-list me-2 text-info"></i>Active Worker Details</h5>';
	echo '  </div>';
	echo '  <div class="card-body p-0">';
	echo '    <div class="table-responsive">';
	echo '      <table class="table table-hover align-middle mb-0 small" id="wallet-workers-table">';
	echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
	echo '          <tr>';
	echo '            <th class="ps-4">Worker Identity</th>';
	if ($this->admin) echo '<th>IP Address</th>';
	echo '            <th>Password / Extra</th>';
	echo '            <th class="text-center">Algo</th>';
	echo '            <th class="text-end">Diff</th>';
	echo '            <th class="text-center" title="extranonce.subscribe">ES</th>';
	echo '            <th class="text-end">Hashrate*</th>';
	echo '            <th class="text-end pe-4">Shares/Min*</th>';
	echo '          </tr>';
	echo '        </thead>';
	echo '        <tbody>';

	foreach($workers as $worker)
	{
		$user_rate1 = yaamp_worker_rate($worker->id, $worker->algo);
		$user_rate1_sfx = $user_rate1? Itoa2($user_rate1).'h/s': '-';

		$version = substr($worker->version, 0, 25);
		$password = $worker->password;
		$name = $worker->worker;
		$subscribe = $worker->subscribe ? '<i class="fa fa-check text-success"></i>' : '-';

		$t = time() - 60;
		$shares_per_minute = getdbocount('db_shares',"algo=:algo and userid=$user->id and workerid=$worker->id and time>=$t",array(':algo'=>$worker->algo));

		echo '<tr>';
		echo '  <td class="ps-4">';
		echo '    <div class="fw-bold">'.($name ? $name : 'Unnamed').'</div>';
		echo '    <div class="text-muted" style="font-size: 0.7rem;">'.$version.'</div>';
		echo '  </td>';
		if ($this->admin) echo '<td><span class="badge bg-light text-dark font-monospace">'.htmlspecialchars($worker->ip).'</span></td>';
		echo '  <td class="font-monospace small" style="max-width:220px;word-break:break-all;white-space:normal;">'.$password.'</td>';
		echo '  <td class="text-center small text-uppercase fw-bold">'.$worker->algo.'</td>';
		echo '  <td class="text-end small">'.round($worker->difficulty, 3).'</td>';
		echo '  <td class="text-center">'.$subscribe.'</td>';
		echo '  <td class="text-end fw-bold text-primary">'.$user_rate1_sfx.'</td>';
		echo '  <td class="text-end pe-4">'.$shares_per_minute.'</td>';
		echo '</tr>';
	}

	echo '        </tbody>';
	echo '      </table></div></div>';
	echo '  <div class="card-footer bg-light py-2 small text-muted">';
	echo '    * approximate from the last 5 minutes submitted shares | ES: extranonce.subscribe';
	echo '  </div>';
	echo '</div>';
}
