<?php

function BackendRentingUpdate()
{
//	debuglog(__FUNCTION__);
	if(!YAAMP_RENTAL)
	{
	 	dborun("update jobs set active=false, ready=false");
		return;
	}

	dborun("update jobs set active=false where not ready");
	foreach(yaamp_get_algos() as $algo)
	{
		$rent = dboscalar("select rent from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
		$rent_mbtc = $rent * 1000;

		dborun("update jobs set active=true where ready and price>$rent_mbtc and algo=:algo", array(':algo'=>$algo));
		dborun("update jobs set active=false where active and price<$rent_mbtc and algo=:algo", array(':algo'=>$algo));
	}

	$list = getdbolist('db_jobsubmits', "status=0");
	foreach($list as $submit)
	{
		$rent = dboscalar("select rent from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$submit->algo));
		$amount = $rent * $submit->difficulty / 20116.56761169;

		$factor = yaamp_algo_mBTC_factor($submit->algo); // 1000 for sha256
		$amount /= $factor;

		$submit->amount = $amount - $amount*YAAMP_FEES_RENTING/100;
		$submit->status = 1;
		$submit->save();

		$job = getdbo('db_jobs', $submit->jobid);
		if(!$job)
		{
			$submit->delete();
			continue;
		}

		$renter = getdbo('db_renters', $job->renterid);
		if(!$renter)
		{
			$job->delete();
			$submit->delete();
			continue;
		}

		$renter->balance -= $amount;
		$renter->spent += $amount;

		if($renter->balance <= 0.00001000)
		{
			debuglog("resetting balance to 0, $renter->balance, $renter->id, $renter->address");
			$renter->balance = 0;
			dborun("update jobs set active=false, ready=false where renterid=$renter->id");
		}

		$renter->updated = time();
		$renter->save();
	}

//	debuglog(__FUNCTION__);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////

function BackendRentingPayout()
{
//	debuglog(__FUNCTION__);

	$total_cleared = 0;
	foreach(yaamp_get_algos() as $algo)
	{
		$delay = time() - 5*60;
		dborun("delete from jobsubmits where status=2 and algo=:algo and time<$delay", array(':algo'=>$algo));

		$amount = dboscalar("select sum(amount) from jobsubmits where status=1 and algo=:algo", array(':algo'=>$algo));
		if($amount < 0.00002000) continue;

		dborun("update jobsubmits set status=2 where status=1 and algo=:algo", array(':algo'=>$algo));
		$total_cleared += $amount;

		$block = new db_blocks;
		$block->coin_id = 0;
		$block->time = time();
		$block->amount = $amount;
		$block->price = 1;
		$block->algo = $algo;
		$block->category = 'generate';
		$block->save();

		$total_hash_power = dboscalar("SELECT sum(difficulty) FROM shares where valid and algo=:algo", array(':algo'=>$algo));
		if(!$total_hash_power) continue;

		$list = dbolist("SELECT userid, sum(difficulty) as total FROM shares where valid and algo=:algo GROUP BY userid", array(':algo'=>$algo));
		foreach($list as $item)
		{
			$hash_power = $item['total'];
			if(!$hash_power) continue;

			$user = getdbo('db_accounts', $item['userid']);
			if(!$user) continue;

			$earning = new db_earnings;
			$earning->userid = $user->id;
			$earning->coinid = 0;
			$earning->blockid = $block->id;
			$earning->create_time = time();
			$earning->price = 1;
			$earning->status = 2;		// cleared

			$earning->amount = $amount * $hash_power / $total_hash_power;
			if(!$user->no_fees) $earning->amount = take_yaamp_fee($earning->amount, $algo);
			if(!empty($user->donation)) {
				$earning->amount = take_yaamp_fee($earning->amount, $algo, $user->donation);
				if ($earning->amount <= 0) continue;
			}

			$earning->save();

			$refcoin = getdbo('db_coins', $user->coinid);
			$value = $earning->amount / (($refcoin && $refcoin->price2)? $refcoin->price2: 1);

			if(!empty($user->rent_address))
			{
				$rent_user = getdbosql('db_accounts', "username=:address", array(':address'=>$user->rent_address));
				if(!$rent_user)
				{
					$rent_user = new db_accounts;
					$rent_user->username = $user->rent_address;
					$rent_user->coinid = 0;
					$rent_user->balance = 0;
					$rent_user->hostaddr = $user->hostaddr;
					$rent_user->save();
				}

				$rent_user->last_earning = time();
				$rent_user->balance += $earning->amount;
				$rent_user->save();
			}
			else
			{
				$user->last_earning = time();
				$user->balance += $value;
				$user->save();
			}
		}

		$delay = time() - 5*60;
		dborun("delete from shares where algo=:algo and time<$delay", array(':algo'=>$algo));
	}

	if($total_cleared>0)
	 	debuglog("total cleared from rental $total_cleared BTC");
}

////////////////////////////////////////////////////////////////////////////////

function BackendUpdateDeposit()
{
//	debuglog(__FUNCTION__);

	$btc = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>YAAMP_RENTER_COIN));
	if(!$btc) return;

	$remote = new WalletRPC($btc);

	$info = $remote->getinfo();
	if(!$info) return;
	if(!isset($info['blocks'])) return;

	$hash = $remote->getblockhash(intval($info['blocks']));
	if(!$hash) return;

	$block = $remote->getblock($hash);
	if(!$block) return;

	if(!isset($block['time'])) return;
	if($block['time'] + 30*60 < time()) return;

	$transactions = $remote->listtransactions("*", 250, 0, true);
	if(!is_array($transactions)) return;

	$renter_unconfirmed = array();

	foreach($transactions as $tx)
	{
		if($tx['category'] != 'receive') continue;
		if(!isset($tx['address'])) continue;

		$renter = getdbosql('db_renters', "address=:address", array(':address'=>$tx['address']));
		if(!$renter) continue;

		$txid = $tx['txid'];
		$amount = (double) $tx['amount'];
		$confirms = (int) $tx['confirmations'];

		if($confirms >= 1)
		{
			$exists = getdbosql('db_rentertxs', "renterid=:renterid AND tx=:tx AND type='deposit'",
				array(':renterid'=>$renter->id, ':tx'=>$txid));

			if(!$exists)
			{
				debuglog("deposit $renter->id $renter->address, $amount");

				$rentertx = new db_rentertxs;
				$rentertx->renterid = $renter->id;
				$rentertx->time = time();
				$rentertx->amount = $amount;
				$rentertx->type = 'deposit';
				$rentertx->tx = $txid;
				$rentertx->save();

				$renter->balance += $amount;
				$renter->updated = time();
				$renter->save();
			}
		}
		else if($confirms == 0)
		{
			if(!isset($renter_unconfirmed[$renter->id])) $renter_unconfirmed[$renter->id] = 0;
			$renter_unconfirmed[$renter->id] += $amount;
		}
	}

	// Update unconfirmed balances
	$renters = getdbolist('db_renters');
	foreach($renters as $renter)
	{
		$unconfirmed = isset($renter_unconfirmed[$renter->id]) ? $renter_unconfirmed[$renter->id] : 0;
		if($renter->unconfirmed != $unconfirmed)
		{
			if($unconfirmed > 0) debuglog("unconfirmed $renter->id $renter->address, $unconfirmed");
			$renter->unconfirmed = $unconfirmed;
			$renter->updated = time();
			$renter->save();
		}
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////

	// handle generic deposit to 'bittrex' label/account if still used for some reason, but modern way is different
	// for now let's just keep the legacy logic but use listtransactions for it too if needed.
	// Actually id=7 and 'bittrex' seems very specific to a project.

	/////////////////////////////////////////////////////////////////////////////////////////////////////

	$fees = YAAMP_TXFEE_RENTING_WD; // 0.002

	$list = getdbolist('db_rentertxs', "type='withdraw' and tx='scheduled'");
	foreach($list as $tx)
	{
		$renter = getdbo('db_renters', $tx->renterid);
		if(!$renter) continue;

//		debuglog("$renter->balance < $tx->amount + $fees");
		$tx->amount = bitcoinvaluetoa(min($tx->amount, $renter->balance-$fees));
		if($tx->amount < $fees*2)
		{
			$tx->tx = 'failed';
			$tx->save();

			continue;
		}

		debuglog("withdraw send $renter->id $renter->address sendtoaddress($tx->address, $tx->amount)");
		$tx->tx = $remote->sendtoaddress($tx->address, round($tx->amount, 8));

		if(!$tx->tx)
		{
			$tx->tx = 'failed';
			$tx->save();

			continue;
		}

		$renter->balance -= $tx->amount+$fees;
		$renter->balance = max($renter->balance, 0);

		dborun("update renters set balance=$renter->balance where id=$renter->id");

		$tx->save();

		if($renter->balance <= 0.0001)
			dborun("update jobs set active=false, ready=false where id=$renter->id");
	}

}







