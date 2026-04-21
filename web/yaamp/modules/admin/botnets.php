<?php

$this->pageTitle = 'Botnets - Admin';

echo '<div class="container-fluid py-4">';

// --- Header Card ---
echo '<div class="card shadow-sm border-0 mb-4 bg-dark text-white rounded-3">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="bg-danger bg-opacity-10 p-3 rounded-circle me-4 shadow-sm">';
echo '      <i class="fa fa-spider fa-2x text-danger"></i>';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h3 class="mb-0 fw-bold">Botnet & Large Farm Detection</h3>';
echo '      <div class="small text-muted">Groups users by PID and Algo where unique IPs > 10.</div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold small text-uppercase text-muted"><i class="fa fa-list-ul me-2"></i>Detected Large Connections</h5>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="20"></th>';
echo '            <th>Coin</th>';
echo '            <th>Algo</th>';
echo '            <th>Wallet Address</th>';
echo '            <th class="text-center">Unique IPs</th>';
echo '            <th class="text-center">Total Workers</th>';
echo '            <th>Last Seen</th>';
echo '            <th>PID</th>';
echo '            <th>Version</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$botnets = dbolist("SELECT userid, algo, pid, max(time) AS time, count(userid) AS workers, count(DISTINCT ip) AS ips, max(version) AS version ".
	" FROM workers GROUP BY userid, algo, pid HAVING ips > 10 ORDER BY ips DESC"
);

if(!empty($botnets)) {
    foreach($botnets as $botnet)
    {
        if (!$botnet['userid']) continue;
        $user = getdbo('db_accounts', $botnet['userid']);
        if (!$user) continue;
        $coin = getdbo('db_coins', $user->coinid);
        if (!$coin) continue;

        echo '<tr>';
        echo '<td class="ps-4 text-center"><img src="'.$coin->image.'" width="18" class="rounded-circle shadow-sm"></td>';
        echo '<td><b>'.CHtml::link($coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b></td>';
        echo '<td><span class="badge bg-secondary opacity-75">'.$botnet['algo'].'</span></td>';
        echo '<td>'.CHtml::link(substr($user->username,0,20).'...', '/?address='.urlencode($user->username), ['class'=>'text-primary text-decoration-none fw-bold', 'target'=>'_blank']).'</td>';
        echo '<td class="text-center"><span class="badge bg-danger px-3">'.$botnet['ips'].'</span></td>';
        echo '<td class="text-center fw-bold">'.$botnet['workers'].'</td>';
        echo '<td class="text-muted small">'.datetoa2($botnet['time']).' ago</td>';
        echo '<td><span class="badge bg-light text-dark border font-monospace">'.$botnet['pid'].'</span></td>';
        echo '<td class="small text-muted">'.$botnet['version'].'</td>';

        echo '<td class="text-end pe-4">';
        echo '  <div class="btn-group">';
        if ($user->logtraffic)
            echo '    <a href="/admin/loguser?id='.$user->id.'&en=0" class="btn btn-xs btn-outline-info py-0 px-2" title="Unwatch"><i class="fa fa-eye-slash"></i></a>';
        else
            echo '    <a href="/admin/loguser?id='.$user->id.'&en=1" class="btn btn-xs btn-outline-secondary py-0 px-2" title="Watch"><i class="fa fa-eye"></i></a>';

        if ($user->is_locked)
            echo '    <a href="/admin/unblockuser?wallet='.urlencode($user->username).'" class="btn btn-xs btn-outline-warning py-0 px-2" title="Unblock"><i class="fa fa-unlock"></i></a>';
        else
            echo '    <a href="/admin/blockuser?wallet='.urlencode($user->username).'" class="btn btn-xs btn-outline-dark py-0 px-2" title="Block"><i class="fa fa-lock"></i></a>';

        echo '    <a href="/admin/banuser?id='.$user->id.'" class="btn btn-xs btn-outline-danger py-0 px-2 fw-bold" onclick="return confirm(\'BAN user?\')"><i class="fa fa-ban"></i></a>';
        echo '  </div>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="10" class="py-5 text-center text-muted">No botnets or large farms detected at this time.</td></tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';
echo '</div>'; // close container
?>