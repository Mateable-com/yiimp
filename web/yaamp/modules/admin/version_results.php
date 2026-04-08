<?php

if (isset($_GET['algo']))
	user()->setState('yaamp-algo', $_GET['algo']);

$algo = user()->getState('yaamp-algo');
$target = yaamp_hashrate_constant($algo);
$interval = yaamp_hashrate_step();
$delay = time()-$interval;

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-code-branch me-2 text-info"></i>Miner Client Versions: '.strtoupper($algo).'</h5>';
echo '    <input class="search form-control form-control-sm border-secondary bg-dark text-white" type="search" data-column="all" style="width: 200px;" placeholder="Filter versions..." />';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="versionstable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4">Client / Version String</th>';
echo '            <th class="text-center">Workers</th>';
echo '            <th class="text-end">Hashrate</th>';
echo '            <th class="text-end">Invalid</th>';
echo '            <th class="text-end pe-4">Error Rate %</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$versions = dbolist("select version, count(*) as c from workers where algo=:algo group by version order by c desc", array(':algo'=>$algo));
foreach($versions as $item)
{
	$version = !empty($item['version']) ? $item['version'] : 'Unknown';
	$count = $item['c'];

	$hashrate = (double)dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and 
		workerid in (select id from workers where algo=:algo and version=:version)", array(':algo'=>$algo, ':version'=>$item['version']));

	$invalid = (double)dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and 
		workerid in (select id from workers where algo=:algo and version=:version)", array(':algo'=>$algo, ':version'=>$item['version']));

	$percent = $hashrate? round($invalid*100/$hashrate, 2): 0;
	
	echo '<tr>';
	echo '<td class="ps-4 fw-bold font-monospace">'.CHtml::encode($version).'</td>';
	echo '<td class="text-center"><span class="badge bg-secondary rounded-pill px-3">'.$count.'</span></td>';
	echo '<td class="text-end fw-bold text-dark">'.($hashrate ? Itoa2($hashrate).'h/s' : '-').'</td>';
	echo '<td class="text-end text-muted small">'.($invalid ? Itoa2($invalid).'h/s' : '-').'</td>';
    
    $err_class = $percent > 5 ? 'text-danger' : ($percent > 2 ? 'text-warning' : 'text-success');
	echo '<td class="text-end pe-4 fw-bold '.$err_class.'">'.$percent.'%</td>';
	echo '</tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';
?>