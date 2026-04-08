<?php

function WriteBoxHeader($title)
{
	echo "<div class='main-left-box'>";
	echo "<div class='main-left-title'>$title</div>";
	echo "<div class='main-left-inner'>";
}

$algo = user()->getState('yaamp-algo');

$target = yaamp_hashrate_constant($algo);
$interval = yaamp_hashrate_step();
$delay = time()-$interval;

$total_workers = getdbocount('db_workers', "algo=:algo", array(':algo'=>$algo));
$total_extranonce = getdbocount('db_workers', "algo=:algo and subscribe", array(':algo'=>$algo));
$total_hashrate = controller()->memcache->get_database_scalar("current_hashrate-$algo",
	//"SELECT SUM(difficulty) * $target / $interval / 1000 FROM shares WHERE valid AND time>$delay AND algo=:algo", array(':algo'=>$algo)
	"SELECT hashrate FROM hashrate WHERE algo=:algo ORDER BY time DESC LIMIT 1", array(':algo'=>$algo)
);
$total_invalid = !$this->admin ? 0 : controller()->memcache->get_database_scalar("current_hashrate_bad-$algo",
	//"SELECT SUM(difficulty) * $target / $interval / 1000 FROM shares WHERE NOT valid AND time>$delay AND algo=:algo", array(':algo'=>$algo)
	"SELECT hashrate_bad FROM hashrate WHERE algo=:algo ORDER BY time DESC LIMIT 1", array(':algo'=>$algo)
);

echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-microchip me-2 text-primary"></i>Miners Version: <span class="text-primary small text-uppercase">'.$algo.'</span></h5>';
echo '    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">Live Fleet</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable2">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4">Version</th>';
echo '            <th class="text-center">Count</th>';
echo '            <th class="text-center">Donators</th>';
echo '            <th class="text-center" title="Extranonce Subscribe">ES</th>';
echo '            <th class="text-center">Share %</th>';
echo '            <th class="text-end">Hashrate*</th>';
echo '            <th class="text-end">Avg. Rate</th>';
echo '            <th class="text-end pe-4 rejects" style="display:none;">Reject</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$error_tab = array(
	20=>'Invalid nonce size',
	21=>'Invalid job id',
	22=>'Duplicate share',
	23=>'Invalid time rolling',
	24=>'Invalid extranonce2 size',
	25=>'Invalid share',
	26=>'Low difficulty share',
	27=>'Invalid extranonce',
);

$total_donators = 0;

$versions = dbolist("select version, count(*) as c, sum(subscribe) as s from workers where algo=:algo group by version order by c desc", array(':algo'=>$algo));
foreach($versions as $item)
{
	$version = $item['version'];
	$count = $item['c'];
	$extranonce = $item['s'];

	$hashrate = controller()->memcache->get_database_scalar("miners-valid-$algo-v$version",
		"SELECT sum(difficulty) * $target / $interval / 1000 FROM shares WHERE valid AND time>$delay
		 AND workerid IN (SELECT id FROM workers WHERE algo=:algo and version=:version)",
		 array(':algo'=>$algo, ':version'=>$version)
	);

	if (!$hashrate && !$this->admin) continue;

	$invalid = !$total_invalid ? 0 : controller()->memcache->get_database_scalar("miners-invalid-$algo-v$version",
		"SELECT SUM(difficulty) * $target / $interval / 1000 FROM shares WHERE not valid AND time>$delay
		 AND workerid IN (SELECT id FROM workers WHERE algo=:algo AND version=:version)",
		 array(':algo'=>$algo, ':version'=>$version)
	);

	$title = '';
	foreach($error_tab as $i=>$s)
	{
		$invalid2 = !$total_invalid ? 0 : controller()->memcache->get_database_scalar("miners-invalid-$algo-v$version-err$i",
			"SELECT sum(difficulty) * $target / $interval / 1000 from shares WHERE error=$i AND time>$delay
			AND workerid in (SELECT id FROM workers WHERE algo=:algo AND version=:version)",
			array(':algo'=>$algo, ':version'=>$version)
		);

		if($invalid2) {
			$bad2 = round($invalid2*100/($hashrate+$invalid2), 2).'%';
			$title .= "$bad2 - $s\n";
		}
	}

	$donators = dboscalar(
		"SELECT COUNT(*) AS donators FROM workers W LEFT JOIN accounts A ON A.id = W.userid".
		" WHERE W.algo=:algo AND W.version=:version AND A.donation > 0",
		array(':algo'=>$algo, ':version'=>$version)
	);
	$total_donators += $donators;

	$percent = $total_hashrate && $hashrate ? round($hashrate * 100 / $total_hashrate, 2).'%': '';
	if (!$percent || $percent == '0%') $percent = '-';
	$bad = ($hashrate+$invalid)? round($invalid*100/($hashrate+$invalid), 1).'%': '';
	if (!$bad || $bad == '0%') $bad = '-';
	$avg = intval($count) ? $hashrate / intval($count) : '';
	$avg = $avg? Itoa2($avg).'H/s': '';
	$hashrate = $hashrate? Itoa2($hashrate).'H/s': '';
	$version = substr($version, 0, 30);

	echo '<tr>';
	echo '  <td class="ps-4 fw-bold">'.$version.'</td>';
	echo '  <td class="text-center">'.$count.'</td>';
	echo '  <td class="text-center">'.($donators ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">'.$donators.'</span>' : '-').'</td>';
	echo '  <td class="text-center">'.($extranonce ? '<i class="fa fa-check text-primary"></i>' : '-').'</td>';
	echo '  <td class="text-center">'.($percent != '-' ? '<div class="progress" style="height: 6px;"><div class="progress-bar bg-primary" role="progressbar" style="width: '.$percent.'"></div></div><small class="text-muted mt-1 d-block">'.$percent.'</small>' : '-').'</td>';
	echo '  <td class="text-end fw-bold">'.$hashrate.'</td>';
	echo '  <td class="text-end text-muted">'.$avg.'</td>';
	echo '  <td class="text-end pe-4 rejects text-danger fw-bold" style="display:none;" title="'.$title.'">'.$bad.'</td>';
	echo '</tr>';
}

echo '        </tbody>';

$bad = ($total_hashrate+$total_invalid) && $total_invalid ? round($total_invalid*100/($total_hashrate+$total_invalid), 1).'%': '';
$avg = intval($total_workers) ? Itoa2($total_hashrate / intval($total_workers)).'H/s' : '';
$total_hashrate_sfx = Itoa2($total_hashrate).'H/s';

echo '        <tfoot class="table-dark small text-uppercase fw-bold">';
echo '          <tr>';
echo '            <td class="ps-4">Infrastructure Totals</td>';
echo '            <td class="text-center">'.$total_workers.'</td>';
echo '            <td class="text-center">'.$total_donators.'</td>';
echo '            <td class="text-center">'.$total_extranonce.'</td>';
echo '            <td></td>';
echo '            <td class="text-end text-warning">'.$total_hashrate_sfx.'</td>';
echo '            <td class="text-end">'.$avg.'</td>';
echo '            <td class="text-end pe-4 rejects text-danger" style="display:none;">'.$bad.'</td>';
echo '          </tr>';
echo '        </tfoot>';
echo '      </table></div></div>';
echo '  <div class="card-footer bg-light py-2 small text-muted">* approximate from the last 5 minutes submitted shares</div>';
echo '</div>';

if ($this->admin) {
	// show reject column
	echo '<script type="text/javascript">jQuery(".rejects").show();</script>';
}

