<?php

$defaultalgo = user()->getState('yaamp-algo');

echo "<div class='card shadow-sm border-0 rounded-4 overflow-hidden'>";
echo "<div class='card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center'>";
echo "<h5 class='mb-0 fw-bold text-dark'><i class='fa fa-list me-2 text-primary'></i>Algorithm Profitability</h5>";
echo "<div class='badge bg-light text-muted fw-normal'>mBTC/MH/day</div>";
echo "</div>";
echo "<div class='card-body p-0'>";

echo "<div class='table-responsive'><table class='table table-hover mb-0' id='maintable3'>";
echo "<thead class='bg-light text-muted small text-uppercase'>";
echo "<tr>";
echo "<th class='ps-4'>Algo</th>";
echo "<th class='text-end'>Active Jobs</th>";
echo "<th class='text-end'>Pool Power</th>";
echo "<th class='text-end'>Rented Power</th>";
echo "<th class='text-center'>Utilization</th>";
echo "<th class='text-end pe-4'>Current Price</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$algos = array();
foreach(yaamp_get_algos() as $algo)
{
	$algo_norm = yaamp_get_algo_norm($algo);

	$price = controller()->memcache->get_database_scalar("current_price-$algo",
			"select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));

	$norm = $price*$algo_norm;
	$norm = take_yaamp_fee($norm, $algo);

	$algos[] = array($norm, $algo);
}

function cmp($a, $b)
{
	return $a[0] < $b[0];
}

usort($algos, 'cmp');

foreach($algos as $item)
{
	$norm = $item[0];
	$algo = $item[1];

	$count1 = getdbocount('db_jobs', "algo=:algo and ready and active", array(':algo'=>$algo));
	$count2 = getdbocount('db_jobs', "algo=:algo and ready", array(':algo'=>$algo));

	$total = yaamp_pool_rate($algo);
	$hashrate = yaamp_pool_rate_rentable($algo);
	$hashrate_jobs = yaamp_rented_rate($algo);

	$hashrate = min($total, $hashrate);
	$hashrate_jobs = min($hashrate, $hashrate_jobs);

	$available = $hashrate - $hashrate_jobs;
	$percent_val = $hashrate_jobs && $hashrate ? round($hashrate_jobs/$hashrate*100, 1) : 0;
    
	$hashrate_jobs_str = $hashrate_jobs > 0 ? Itoa2($hashrate_jobs).'h/s' : '-';
	$hashrate_str = $hashrate > 0 ? Itoa2($hashrate).'h/s' : '-';

	$renting = controller()->memcache->get_database_scalar("current_renting-$algo",
		"select rent from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
	$renting = mbitcoinvaluetoa($renting * 1000);

	$active_class = ($defaultalgo == $algo) ? 'table-primary' : '';
	
	echo "<tr class='$active_class' style='cursor: pointer' onclick='javascript:select_algo(\"$algo\")'>";
	echo "<td class='ps-4 fw-bold text-dark'>$algo</td>";
	echo "<td class='text-end'><span class='badge ".($count1 ? 'bg-success' : 'bg-light text-muted')."'>$count1 / $count2</span></td>";
	echo "<td class='text-end small'>$hashrate_str</td>";
	echo "<td class='text-end small'>$hashrate_jobs_str</td>";
	echo "<td class='text-center'>";
    if ($percent_val > 0) {
        echo "<div class='progress' style='height: 6px; width: 60px; margin: 8px auto;'>";
        echo "<div class='progress-bar bg-info' role='progressbar' style='width: $percent_val%' aria-valuenow='$percent_val' aria-valuemin='0' aria-valuemax='100'></div>";
        echo "</div>";
        echo "<small class='text-muted'>$percent_val%</small>";
    } else echo "-";
    echo "</td>";
	echo "<td class='text-end pe-4 fw-bold text-primary'>$renting</td>";
	echo "</tr>";
}

echo "</tbody></table></div>";
echo "</div>";
echo "<div class='card-footer bg-white border-0 py-3'>";
echo "<p class='text-muted small mb-0'>";
echo "<i class='fa fa-info-circle me-1 opacity-50'></i> Only hashpower with <b>extranonce.subscribe</b> or <b>reconnect</b> support can be rented.<br>";
echo "<i class='fa fa-info-circle me-1 opacity-50'></i> Units are mBTC/MH/day (GH/day for sha and blake algos).";
echo "</p>";
echo "</div></div>";
