<?php

$defaultalgo = user()->getState('yaamp-algo');

$rent = dboscalar("select rent from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$defaultalgo));
$rent = mbitcoinvaluetoa($rent * 1000);

echo "<div class='card shadow-sm border-0 rounded-4 overflow-hidden mb-4'>";
echo "<div class='card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center'>";
echo "<h5 class='mb-0 fw-bold text-dark'><i class='fa fa-globe me-2 text-info'></i>Global Market ($defaultalgo)</h5>";
echo "<div class='badge bg-light text-muted fw-normal'>Current Price $rent</div>";
echo "</div>";

echo "<div class='card-body p-0'>";

echo "<div class='table-responsive'><table class='table table-hover mb-0'>";
echo "<thead class='bg-light text-muted small text-uppercase'>";
echo "<tr>";
echo "<th class='ps-4'>Server</th>";
echo "<th>Price</th>";
echo "<th class='text-end'>Max Hash</th>";
echo "<th class='text-end pe-4'>Current Hash</th>";
echo "</tr>";
echo "</thead><tbody>";

$list = getdbolist('db_jobs', "algo=:algo and active order by price desc", array(':algo'=>$defaultalgo));
foreach($list as $job)
{
	$hashrate = yaamp_job_rate($job->id);
	$hashrate = $hashrate? Itoa2($hashrate).'h/s': '-';

	$speed = $job->speed > 0 ? Itoa2($job->speed).'h/s' : 'MAX';

	$servername = substr($job->host, 0, 22);
	$price = mbitcoinvaluetoa($job->price);

	echo "<tr class='ssrow'>";
	echo "<td class='ps-4 fw-bold small'>$servername</td>";
	echo "<td class='small'>$price</td>";
	echo "<td class='text-end small'>$speed</td>";
	echo "<td class='text-end small fw-bold pe-4'>$hashrate</td>";
	echo "</tr>";
}

echo "</tbody></table></div>";
echo "</div>";
echo "<div class='card-footer bg-white border-0 py-3'>";
echo "<p class='text-muted small mb-0'>";
echo "<i class='fa fa-info-circle me-1 opacity-50'></i> All active jobs on the pool for the selected algorithm.";
echo "</p>";
echo "</div></div>";
