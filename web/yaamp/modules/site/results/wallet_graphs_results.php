<?php

$user = getuserparam(getparam('address'));
if(!$user) return;

$hasAlgos = false;
foreach(yaamp_get_algos() as $algo)
{
    $delay = time()-24*60*60;
    $user_shares = controller()->memcache->get_database_scalar("wallet_hashuser-$user->id-$algo",
        "select count(*) from hashuser where userid=$user->id and time>$delay and algo=:algo limit 1", array(':algo'=>$algo));

    $minercount = getdbocount('db_workers', "userid=$user->id and algo=:algo limit 1", array(':algo'=>$algo));
    if(!$user_shares && !$minercount) continue;

    $hasAlgos = true;
    echo '<input type="hidden" id="'.htmlspecialchars($algo).'" class="graph_algo">';
    echo '<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-3">';
    echo '  <div class="card-header bg-white py-2 px-4 border-0">';
    echo '    <h6 class="mb-0 fw-bold text-muted text-uppercase small"><i class="fa fa-chart-area me-2 text-primary"></i>'.htmlspecialchars(strtoupper($algo)).' Hashrate (24h)</h6>';
    echo '  </div>';
    echo '  <div class="card-body p-3">';
    echo '    <div id="graph_results_'.htmlspecialchars($algo).'" style="height: 200px;"></div>';
    echo '  </div>';
    echo '</div>';
}

if (!$hasAlgos) {
    echo '<div class="text-center py-4 text-muted"><i class="fa fa-chart-bar fa-2x mb-2 opacity-50"></i><p class="mb-0">No hashrate data in the last 24 hours.</p></div>';
}
