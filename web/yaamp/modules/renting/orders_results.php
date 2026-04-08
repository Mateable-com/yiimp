<?php

$renter = getrenterparam(''.getparam('address'));
if(!$renter) return;

echo "<div class='card shadow-sm border-0 rounded-4 overflow-hidden mb-4'>";
echo "<div class='card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center'>";
echo "<h5 class='mb-0 fw-bold text-dark'><i class='fa fa-tasks me-2 text-primary'></i>Active Rental Jobs</h5>";
echo "<div class='badge bg-light text-muted fw-normal'>mBTC/MH/day</div>";
echo "</div>";

echo "<div class='card-body p-0'>";

echo "<div class='table-responsive'><table class='table table-hover mb-0'>";
echo "<thead class='bg-light text-muted small text-uppercase'>";
echo "<tr>";
echo "<th class='ps-4'>Server</th>";
echo "<th>Algo</th>";
echo "<th class='text-end'>Max Price</th>";
echo "<th class='text-end'>Current</th>";
echo "<th class='text-end'>Max Hash</th>";
echo "<th class='text-end'>Current Hash</th>";
echo "<th class='text-end'>Diff</th>";
echo "<th class='text-center pe-4'>Status</th>";
echo "</tr>";
echo "</thead><tbody>";

$list = getdbolist('db_jobs', "renterid=$renter->id order by active desc, id desc");
foreach($list as $job)
{
	$hashrate = yaamp_job_rate($job->id);
	$hashrate_str = $hashrate? Itoa2($hashrate).'h/s': '-';

	$speed_str = $job->speed > 0 ? Itoa2($job->speed).'h/s' : 'MAX';

	$servername = substr($job->host, 0, 22);
	$price = mbitcoinvaluetoa($job->price);

	$rent = dboscalar("select rent from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$job->algo));
	$rent = mbitcoinvaluetoa($rent * 1000);

	$diff = $job->difficulty>0? round($job->difficulty, 3): '-';

    $row_class = $job->active ? 'table-success bg-opacity-10' : ($job->ready ? 'table-warning bg-opacity-10' : 'text-muted');
    $status_badge = $job->active ? '<span class="badge bg-success">ACTIVE</span>' : ($job->ready ? '<span class="badge bg-warning">READY</span>' : '<span class="badge bg-secondary">PAUSED</span>');

	echo "<tr class='$row_class' style='cursor: pointer' onclick='javascript:order_edit($job->id)'>";
	echo "<td class='ps-4 fw-bold'>$servername</td>";
	echo "<td>$job->algo</td>";
	echo "<td class='text-end'>$price</td>";
	echo "<td class='text-end text-muted small'>$rent</td>";
	echo "<td class='text-end small'>$speed_str</td>";
	echo "<td class='text-end fw-bold'>$hashrate_str</td>";
	echo "<td class='text-end small'>$diff</td>";
	echo "<td class='text-center pe-4'>$status_badge</td>";
	echo "</tr>";
}

echo "</tbody></table></div>";
echo "</div>";
echo "<div class='card-footer bg-white border-0 py-3'>";
echo "<p class='text-muted small mb-0'>";
echo "<i class='fa fa-info-circle me-1 opacity-50'></i> Click any job to edit its settings, change limits, or delete it.";
echo "</p>";
echo "</div></div>";
