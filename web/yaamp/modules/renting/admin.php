<?php

$deposit = user()->getState('yaamp-deposit');

echo "<div class='row mb-4'>";
echo "<div class='col-12 d-flex justify-content-between align-items-center'>";
echo "<h3 class='fw-bold mb-0'><i class='fa fa-user-shield me-2 text-danger'></i>Rental Administration</h3>";
echo "<a href='/renting/admin' class='btn btn-outline-secondary rounded-pill fw-bold px-4 shadow-sm'><i class='fa fa-sync-alt me-1'></i>Refresh</a>";
echo "</div>";
echo "</div>";

////////////////////////////////////////////////////////////////////////////////////////////////////////

$list = getdbolist('db_rentertxs', "1 order by time desc limit 25");
if(count($list) > 0)
{
    echo "<div class='card shadow-sm border-0 rounded-4 overflow-hidden mb-5'>";
    echo "<div class='card-header bg-dark text-white py-3'><h5 class='mb-0 fw-bold'><i class='fa fa-history me-2'></i>Recent Transactions</h5></div>";
    echo "<div class='card-body p-0'><div class='table-responsive'><table class='table table-hover mb-0'>";
    echo "<thead class='bg-light small text-uppercase'><tr>";
    echo "<th class='ps-4'>ID</th><th>Address</th><th class='text-end'>Time</th><th>Type</th><th class='text-end'>Amount</th><th class='pe-4'>TxID</th>";
    echo "</tr></thead><tbody>";

    foreach($list as $tx)
    {
        $d = datetoa2($tx->time);
        $amount = bitcoinvaluetoa($tx->amount);
        $renter = getdbo('db_renters', $tx->renterid);
        if(!$renter) continue;

        echo "<tr>";
        echo "<td class='ps-4 fw-bold'>$renter->id</td>";
        echo "<td><a href='/renting?address=$renter->address' class='text-decoration-none'>$renter->address</a></td>";
        echo "<td class='text-end small'>$d ago</td>";
        echo "<td><span class='badge ".($tx->type == 'deposit' ? 'bg-success' : 'bg-warning')."'>$tx->type</span></td>";
        echo "<td class='text-end fw-bold font-monospace'>$amount</td>";

        if(strlen($tx->tx) > 32)
        {
            $tx_show = substr($tx->tx, 0, 8).'...'.substr($tx->tx, -8);
            $txurl = "https://blockchain.info/tx/$tx->tx";
            echo "<td class='pe-4'><a href='$txurl' target=_blank class='small font-monospace'>$tx_show</a></td>";
        }
        else echo "<td class='pe-4 small'>$tx->tx</td>";
        echo "</tr>";
    }
    echo "</tbody></table></div></div></div>";
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////

echo "<div class='card shadow-sm border-0 rounded-4 overflow-hidden mb-5'>";
echo "<div class='card-header bg-primary text-white py-3'><h5 class='mb-0 fw-bold'><i class='fa fa-users me-2'></i>Active Renters</h5></div>";
echo "<div class='card-body p-0'><div class='table-responsive'><table class='table table-hover mb-0'>";
echo "<thead class='bg-light small text-uppercase'><tr>";
echo "<th class='ps-4'>ID</th><th>Address / Email</th><th class='text-end'>Spent</th><th class='text-end'>Balance</th><th class='text-end'>Unconf.</th><th class='text-center pe-4'>Jobs</th>";
echo "</tr></thead><tbody>";

$list = getdbolist('db_renters', "balance>0 or spent>0 order by balance desc, spent desc");
foreach($list as $renter)
{
	$count = dboscalar("select count(*) from jobs where renterid=$renter->id");
	$active = dboscalar("select count(*) from jobs where renterid=$renter->id and active");

    $row_class = ($deposit == $renter->address) ? 'table-info' : '';

	echo "<tr class='$row_class'>";
	echo "<td class='ps-4 fw-bold'>$renter->id</td>";
	echo "<td><a href='/renting?address=$renter->address' class='fw-bold text-decoration-none'>$renter->address</a><br><small class='text-muted'>$renter->email</small></td>";
	echo "<td class='text-end small'>".bitcoinvaluetoa($renter->spent)."</td>";
	echo "<td class='text-end fw-bold text-success font-monospace'>".bitcoinvaluetoa($renter->balance)."</td>";
	echo "<td class='text-end text-warning small'>".bitcoinvaluetoa($renter->unconfirmed)."</td>";
	echo "<td class='text-center pe-4'><span class='badge bg-primary'>$active / $count</span></td>";
	echo "</tr>";
}
echo "</tbody></table></div></div></div>";

/////////////////////////////////////////////////////////////////////////////

echo "<div class='card shadow-sm border-0 rounded-4 overflow-hidden mb-5'>";
echo "<div class='card-header bg-success text-white py-3'><h5 class='mb-0 fw-bold'><i class='fa fa-microchip me-2'></i>Active Mining Jobs</h5></div>";
echo "<div class='card-body p-0'><div class='table-responsive'><table class='table table-hover mb-0'>";
echo "<thead class='bg-light small text-uppercase'><tr>";
echo "<th class='ps-4'>ID</th><th>Algo</th><th>Target Server</th><th class='text-end'>Price</th><th class='text-end'>Hashrate</th><th class='text-center pe-4'>Status</th>";
echo "</tr></thead><tbody>";

$list = getdbolist('db_jobs', "ready order by active desc, id desc");
foreach($list as $job)
{
	$hashrate = yaamp_job_rate($job->id);
	$hashrate_str = $hashrate? Itoa2($hashrate).'h/s': '-';
	$speed_str = $job->speed > 0 ? Itoa2($job->speed).'h/s' : 'MAX';

	$renter = getdbo('db_renters', $job->renterid);
	if(!$renter) continue;

    $row_class = $job->active ? 'table-success bg-opacity-10' : 'table-warning bg-opacity-10';

	echo "<tr class='$row_class'>";
	echo "<td class='ps-4 fw-bold'>$job->id <small class='text-muted'>(R#$job->renterid)</small></td>";
	echo "<td><span class='badge bg-light text-dark fw-bold border'>$job->algo</span></td>";
	echo "<td class='small'>$job->host:$job->port<br><span class='text-muted font-monospace'>$job->username</span></td>";
	echo "<td class='text-end'>".mbitcoinvaluetoa($job->price)."</td>";
	echo "<td class='text-end fw-bold'>$hashrate_str <small class='text-muted fw-normal'>/ $speed_str</small></td>";
	echo "<td class='text-center pe-4'>".($job->active ? '<span class="badge bg-success">ACTIVE</span>' : '<span class="badge bg-warning text-dark">READY</span>')."</td>";
	echo "</tr>";
}
echo "</tbody></table></div></div></div>";
