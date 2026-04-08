<?php

$mining = getdbosql('db_mining');

echo '<div class="container-fluid py-4">';

// --- Premium Hero Header ---
echo '<div class="card shadow-lg border-0 mb-5 bg-dark text-white rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">';
echo '  <div class="card-body p-5">';
echo '    <div class="row align-items-center">';
echo '      <div class="col-lg-8">';
echo '        <div class="badge bg-primary px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase small" style="letter-spacing: 2px;">Transparency & Status</div>';
echo '        <h1 class="display-5 fw-bold mb-3">Supported Digital Assets</h1>';
echo '        <p class="lead opacity-75 mb-0 pe-lg-5">A comprehensive live status of every coin currently supported by our high-performance infrastructure. Monitor network heights, difficulty, and connectivity in real-time.</p>';
echo '      </div>';
echo '      <div class="col-lg-4 text-center d-none d-lg-block">';
echo '        <i class="fa fa-coins fa-8x opacity-10 text-primary"></i>';
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '<div class="row g-4">';

$coins = getdbolist('db_coins', "enable=1 AND auto_ready=1 ORDER BY algo ASC, name ASC");

foreach($coins as $coin) {
    $algo_color = getAlgoColors($coin->algo);
    $symbol = $coin->getOfficialSymbol();
    
    // Get port from stratum
    $port_db = getdbosql('db_stratums', "algo=:algo and symbol=:symbol", [':algo' => $coin->algo, ':symbol' => $symbol]);
    $port = $port_db ? $port_db->port : 'Auto';

    echo '<div class="col-sm-6 col-xl-4">';
    echo '  <div class="card border-0 shadow-sm rounded-4 h-100 hover-up overflow-hidden">';
    
    // Colored side-bar
    echo '    <div style="height: 4px; background: '.$algo_color.'"></div>';
    
    echo '    <div class="card-body p-4">';
    echo '      <div class="d-flex align-items-center mb-4">';
    echo '        <div class="position-relative me-3">';
    echo '          <img src="'.$coin->image.'" width="48" class="shadow-sm rounded-circle">';
    echo '          <span class="position-absolute bottom-0 end-0 bg-success border border-white border-2 rounded-circle p-1" title="Online"></span>';
    echo '        </div>';
    echo '        <div>';
    echo '          <h5 class="mb-0 fw-bold">'.$coin->name.'</h5>';
    echo '          <span class="badge bg-light text-dark font-monospace small">'.$symbol.'</span>';
    echo '        </div>';
    echo '        <div class="ms-auto text-end">';
    echo '          <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Algorithm</div>';
    echo '          <span class="fw-bold" style="color: '.$algo_color.'">'.strtoupper($coin->algo).'</span>';
    echo '        </div>';
    echo '      </div>';

    echo '      <div class="row g-2 mb-4">';
    echo '        <div class="col-6">';
    echo '          <div class="bg-light p-2 rounded-3 text-center">';
    echo '            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Network Height</div>';
    echo '            <div class="fw-bold font-monospace">'.number_format($coin->block_height).'</div>';
    echo '          </div>';
    echo '        </div>';
    echo '        <div class="col-6">';
    echo '          <div class="bg-light p-2 rounded-3 text-center">';
    echo '            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Pool Port</div>';
    echo '            <div class="fw-bold font-monospace text-primary">'.$port.'</div>';
    echo '          </div>';
    echo '        </div>';
    echo '      </div>';

    echo '      <div class="d-flex gap-2">';
    echo '        <a href="/site/mining?algo='.$coin->algo.'" class="btn btn-primary flex-grow-1 rounded-pill fw-bold btn-sm shadow-sm"><i class="fa fa-tachometer-alt me-1"></i> Live Stats</a>';
    if (!empty($coin->link_explorer)) {
        echo '        <a href="'.$coin->link_explorer.'" target="_blank" class="btn btn-outline-secondary rounded-circle btn-sm shadow-sm" title="Block Explorer"><i class="fa fa-search"></i></a>';
    }
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}

echo '</div>'; // End Row

// --- Request Listing Section ---
echo '<div class="row mt-5">';
echo '  <div class="col-12">';
echo '    <div class="card border-0 shadow-sm rounded-4 bg-light p-5 text-center">';
echo '      <h3 class="fw-bold">Don\'t see your favorite coin?</h3>';
echo '      <p class="text-muted mx-auto mb-4" style="max-width: 600px;">We are constantly expanding our infrastructure. If you are a coin developer or a community member looking to have a project added, please contact our listing team.</p>';
echo '      <div><a href="mailto:'.YAAMP_ADMIN_EMAIL.'" class="btn btn-outline-dark px-5 py-2 rounded-pill fw-bold">REQUEST LISTING</a></div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '</div>'; // End Container

?>

<style>
    .hover-up { transition: all 0.3s ease; }
    .hover-up:hover { transform: translateY(-5px); shadow: 0 10px 20px rgba(0,0,0,0.1); }
</style>
