<?php

function BackendWorkerOfflineCheck()
{
	$mc = controller()->memcache->memcache;
	$grace = 15 * 60;

	$rows = dbolist("SELECT DISTINCT userid FROM workers WHERE userid > 0");
	$active_now = array();
	foreach ($rows as $r) $active_now[(int)$r['userid']] = 1;

	$prev_active = memcache_get($mc, 'worker_active_users');
	if ($prev_active === false || !is_array($prev_active)) {
		memcache_set($mc, 'worker_active_users', $active_now, 0, 3600);
		return;
	}

	foreach ($prev_active as $userid => $dummy) {
		if (isset($active_now[$userid])) {
			memcache_set($mc, "worker_offline_since_$userid", -1, 0, 3600);
			continue;
		}

		$since = memcache_get($mc, "worker_offline_since_$userid");
		if ($since === false || $since === -1) {
			memcache_set($mc, "worker_offline_since_$userid", time(), 0, 3600);
		} elseif ($since > 0 && time() - $since > $grace) {
			$user = getdbo('db_accounts', intval($userid));
			if ($user) {
				$addr = substr($user->username, 0, 20) . '...';
				send_email_alert(
					"worker_offline_$userid",
					"[Pool Alert] Miner offline: $addr",
					"All workers for miner {$user->username} have been offline for more than " . round($grace/60) . " minutes.\n\nUser ID: {$user->id}\nWallet: {$user->username}",
					60
				);
			}
			memcache_set($mc, "worker_offline_since_$userid", 0, 0, 3600);
		}
	}

	foreach ($active_now as $userid => $dummy) {
		if (!isset($prev_active[$userid])) {
			$since = memcache_get($mc, "worker_offline_since_$userid");
			if ($since !== false && $since === 0) {
				$user = getdbo('db_accounts', intval($userid));
				if ($user) {
					$addr = substr($user->username, 0, 20) . '...';
					send_email_alert(
						"worker_recovery_$userid",
						"[Pool Alert] Miner reconnected: $addr",
						"Workers for miner {$user->username} are back online.\n\nUser ID: {$user->id}\nWallet: {$user->username}",
						60
					);
				}
				memcache_set($mc, "worker_offline_since_$userid", -1, 0, 3600);
			}
		}
	}

	memcache_set($mc, 'worker_active_users', $active_now, 0, 3600);
}

function BackendDuplicateAddressCheck()
{
	$dupes = dbolist(
		"SELECT username, COUNT(*) AS cnt, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') AS ids
		 FROM accounts
		 WHERE coinid IS NOT NULL
		 GROUP BY username
		 HAVING cnt > 1
		 LIMIT 50"
	);

	if (empty($dupes)) return;

	$msg = '';
	foreach ($dupes as $row) {
		$msg .= "Address: {$row['username']}  — account IDs: {$row['ids']}\n";
	}

	send_email_alert('duplicate_addresses',
		'[Pool Alert] Duplicate payout addresses detected',
		"The following wallet addresses appear under multiple account IDs. This may indicate duplicate registrations or address reuse:\n\n$msg\nCheck /admin/user for details.",
		360
	);
}

function BackendUsersUpdate()
{
	$t1 = microtime(true);

	$list = getdbolist('db_accounts', "coinid IS NULL OR IFNULL(coinsymbol,'') != ''");
	foreach($list as $user)
	{
		$old_usercoinid = $user->coinid;
	//	debuglog("testing user $user->username, $user->coinsymbol");
		if(!empty($user->coinsymbol))
		{
			$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$user->coinsymbol));
			$user->coinsymbol = '';

			if($coin)
			{
				if($user->coinid == $coin->id)
				{
					$user->save();
					continue;
				}

				$remote = new WalletRPC($coin);

				$b = $remote->validateaddress($user->username);
				if(arraySafeVal($b,'isvalid'))
				{
					$old_balance = $user->balance;
					if($user->balance > 0)
					{
						$coinref = getdbo('db_coins', $user->coinid);
						if(!$coinref) {
							if (YAAMP_ALLOW_EXCHANGE)
								$coinref = getdbosql('db_coins', "symbol='BTC'");
							else
								continue;
						}

						$user->balance = $user->balance * $coinref->price / $coin->price;
					}

					$user->coinid = $coin->id;
					$user->save();

					debuglog("{$user->username} converted to {$user->balance} {$coin->symbol} (old: $old_balance)");
					continue;
				}
			}
		}

		$user->coinid = 0;

		$order = YAAMP_ALLOW_EXCHANGE ? "difficulty" : "id";
		$coins = getdbolist('db_coins', "enable ORDER BY $order DESC");
		foreach($coins as $coin)
		{
			$remote = new WalletRPC($coin);

			$b = $remote->validateaddress($user->username);
			if(!arraySafeVal($b,'isvalid')) continue;

			if ($old_usercoinid && $old_usercoinid != $coin->id) {
				debuglog("{$user->username} set to {$coin->symbol}, balance {$user->balance} reset to 0");
				$user->balance = 0;
			}
			$user->coinid = $coin->id;
			break;
		}

		if (empty($user->coinid)) {
			debuglog("{$user->hostaddr} - {$user->username} is an unknown address!");
		}

		$user->save();
	}

//	$delay=time()-60*60;
//	$list = dborun("UPDATE coins SET dontsell=1 WHERE id in (SELECT coinid FROM accounts WHERE balance>0 OR last_earning>$delay GROUP BY coinid)");
//	$list = dborun("UPDATE coins SET dontsell=0 WHERE id not in (SELECT coinid FROM accounts WHERE balance>0 OR last_earning>$delay GROUP BY coinid)");


//	$list = getdbolist('db_workers', "dns is null");
//	foreach($list as $worker)
//	{
//		$worker->dns = $worker->ip;
//		$res = system("resolveip $worker->ip");

//		if($res)
//		{
//			$a = explode(' ', $res);
//			if($a && isset($a[5]))
//				$worker->dns = $a[5];
//		}

//		$worker->save();
//	}

	$d1 = microtime(true) - $t1;
	controller()->memcache->add_monitoring_function(__METHOD__, $d1);
}


