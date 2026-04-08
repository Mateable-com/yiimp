<?php

$stats = $this->memcache->memcache->getStats();
$a = controller()->memcache->memcache->get('url-map');

echo '<div class="container-fluid py-4">';

// --- Memcache Hero Header ---
echo '<div class="card shadow-sm border-0 mb-4 bg-dark text-white rounded-3">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="bg-info bg-opacity-10 p-3 rounded-circle me-4 shadow-sm">';
echo '      <i class="fa fa-memory fa-2x text-info"></i>';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h3 class="mb-0 fw-bold">Memcached Server <span class="text-info fs-5">v'.$stats['version'].'</span></h3>';
echo '      <div class="small text-muted">System Uptime: '.sectoa($stats['uptime']).'</div>';
echo '    </div>';
echo '    <div class="ms-auto">';
echo '      <a href="/admin/memcached" class="btn btn-outline-light btn-sm rounded-pill fw-bold px-3"><i class="fa fa-sync-alt me-1"></i> Refresh Stats</a>';
echo '    </div>';
echo '  </div>';
echo '</div>';

// --- Hit/Miss Gauge ---
$cmd_get = (float)$stats["cmd_get"];
$percCacheHit = $cmd_get > 0 ? round(((float)$stats["get_hits"] / $cmd_get * 100), 2) : 0;
$percCacheMiss = 100 - $percCacheHit;

echo '<div class="row g-3 mb-4">';
echo '  <div class="col-md-6">';
echo '    <div class="card shadow-sm border-0 h-100 rounded-3">';
echo '      <div class="card-body p-4">';
echo '        <div class="d-flex justify-content-between mb-2"><span class="fw-bold small text-uppercase text-muted">Cache Hit Rate</span><span class="text-success fw-bold">'.$percCacheHit.'%</span></div>';
echo '        <div class="progress" style="height: 10px;"><div class="progress-bar bg-success shadow-sm" role="progressbar" style="width: '.$percCacheHit.'%"></div></div>';
echo '        <div class="mt-3 small text-muted">Total Requests: '.number_format($stats['cmd_get']).'</div>';
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '  <div class="col-md-6">';
echo '    <div class="card shadow-sm border-0 h-100 rounded-3">';
echo '      <div class="card-body p-4">';
echo '        <div class="d-flex justify-content-between mb-2"><span class="fw-bold small text-uppercase text-muted">Memory Usage</span><span class="text-primary fw-bold">'.round($stats['bytes']/(1024*1024), 2).' MB</span></div>';
        $limit = (float)$stats["limit_maxbytes"];
        $percMem = $limit > 0 ? round(((float)$stats["bytes"] / $limit * 100), 2) : 0;
echo '        <div class="progress" style="height: 10px;"><div class="progress-bar bg-primary shadow-sm" role="progressbar" style="width: '.$percMem.'%"></div></div>';
echo '        <div class="mt-3 small text-muted">Allocated: '.round($limit/(1024*1024), 2).' MB</div>';
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

// --- Technical Details Row ---
echo '<div class="row g-4 mb-4">';
echo '  <div class="col-lg-12">';
echo '    <div class="card shadow-sm border-0 rounded-3">';
echo '      <div class="card-header bg-white py-3 border-0"><h5 class="mb-0 fw-bold small text-uppercase text-muted">System Performance Metrics</h5></div>';
echo '      <div class="card-body p-0">';
echo '        <div class="row g-0">';
$metrics = [
    ['PID', $stats['pid'], 'id-card'],
    ['Connections', $stats['curr_connections'], 'plug'],
    ['Total Items', number_format($stats['total_items']), 'database'],
    ['Evictions', $stats['evictions'], 'trash-alt'],
    ['Network Read', round($stats['bytes_read']/(1024*1024), 2).' MB', 'download'],
    ['Network Write', round($stats['bytes_written']/(1024*1024), 2).' MB', 'upload']
];
foreach($metrics as $m) {
    echo '<div class="col-6 col-md-4 col-lg-2 border-end border-bottom p-4 text-center">';
    echo '  <i class="fa fa-'.$m[2].' text-muted mb-2 opacity-50"></i>';
    echo '  <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.6rem;">'.$m[0].'</div>';
    echo '  <div class="fw-bold">'.$m[1].'</div>';
    echo '</div>';
}
echo '        </div></div></div></div></div>';

// --- URL Mapping Results ---
$res = array();
if (!empty($a)) {
	foreach($a as $url=>$n) {
		$d = $this->memcache->get("$url-time");
		$avg = $n > 0 ? $d/$n : 0;
		$res[] = array($url, $n, $d, $avg);
	}
	usort($res, function($a, $b) { return $a[2] < $b[2]; });
}

echo '<div class="card shadow-sm border-0 rounded-3">';
echo '  <div class="card-header bg-dark text-white py-3 border-0"><h5 class="mb-0 fw-bold small text-uppercase text-muted">URL Cache Performance</h5></div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem;">';
echo '          <tr><th class="ps-4">Request URL</th><th class="text-end">Call Count</th><th class="text-end">Total Time (s)</th><th class="text-end pe-4">Avg Duration (ms)</th></tr>';
echo '        </thead><tbody>';

if (!empty($res)) {
    foreach($res as $item) {
        echo '<tr>';
        echo '  <td class="ps-4 fw-bold text-primary font-monospace">'.CHtml::link($item[0], '/'.$item[0], ['class'=>'text-decoration-none']).'</td>';
        echo '  <td class="text-end">'.number_format($item[1]).'</td>';
        echo '  <td class="text-end">'.round($item[2], 3).'</td>';
        echo '  <td class="text-end pe-4 fw-bold">'.round($item[3] * 1000, 2).' ms</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="4" class="py-4 text-center text-muted">No URL mapping data available.</td></tr>';
}

echo '</tbody></table></div></div></div>';
echo '</div>'; // close container
?>