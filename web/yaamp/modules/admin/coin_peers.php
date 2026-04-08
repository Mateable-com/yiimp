<?php

if (!$coin) $this->goback();
$this->pageTitle = 'Peers - '.$coin->symbol;

$remote = new WalletRPC($coin);
$info = $remote->getinfo();

echo '<div class="container-fluid py-4">';

// --- Peer Hero Header ---
echo '<div class="card shadow-sm border-0 mb-4 bg-dark text-white rounded-3">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="me-4 shadow-sm bg-white rounded-circle p-2" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">';
echo '      <img src="'.$coin->image.'" style="max-width: 48px; max-height: 48px;">';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h3 class="mb-0 fw-bold">'.$coin->name.' <span class="text-primary fs-5">Network Peers</span></h3>';
echo '      <div class="small text-muted"><i class="fa fa-network-wired me-1"></i> Local Height: '.number_format(arraySafeVal($info,'blocks',0)).'</div>';
echo '    </div>';
echo '    <div class="ms-auto d-flex gap-3">';
echo '      <div class="text-end border-end pe-3 border-secondary border-opacity-25 d-none d-md-block">';
echo '        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Local Version</div>';
echo '        <div class="fw-bold small">'.formatWalletVersion($coin).'</div>';
echo '      </div>';
echo '      <a href="/admin/coin?id='.$coin->id.'" class="btn btn-outline-light btn-sm rounded-pill fw-bold px-3 d-flex align-items-center"><i class="fa fa-arrow-left me-1"></i> Back</a>';
echo '    </div>';
echo '  </div>';
echo '</div>';

// --- Add Node Form ---
echo '<div class="card shadow-sm border-0 mb-4 bg-light rounded-3">';
echo '  <div class="card-body py-3">';
echo '    <form action="/admin/coinpeerAdd?id='.$coin->id.'" method="post" class="row g-2 align-items-center">';
echo '      <div class="col-auto text-muted small fw-bold text-uppercase me-2"><i class="fa fa-plus-circle me-1"></i> Add Peer</div>';
echo '      <div class="col-md-4"><input type="text" name="node" class="form-control form-control-sm border-2" placeholder="IP Address [:port]" autocomplete="off"></div>';
echo '      <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm fw-bold px-4 rounded-pill shadow-sm">CONNECT NODE</button></div>';
echo '    </form>';
echo '  </div>';
echo '</div>';

// --- Peer List Card ---
echo '<div class="card shadow-sm border-0 rounded-3 mb-4">';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4">Node Address</th>';
echo '            <th>Version</th>';
echo '            <th class="text-center">Height</th>';
echo '            <th class="text-center">Ping</th>';
echo '            <th>Connected</th>';
echo '            <th class="text-center">Rx / Tx (KB)</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$addnode = array();
$version = '';
$localheight = (int)arraySafeVal($info, 'blocks');
$list = $remote->getpeerinfo();

if(!empty($list)) {
    foreach($list as $peer) {
        $node = arraySafeVal($peer,'addr');
        $peerver = trim(arraySafeVal($peer,'subver'),'/');
        $version = max($version, $peerver);
        $height = (int)arraySafeVal($peer,'currentheight', arraySafeVal($peer,'synced_blocks', 0));
        $conntime = (int)arraySafeVal($peer,'conntime',time());
        $addnode[] = ($coin->rpcencoding=='DCR' ? 'addpeer=' : 'addnode=') . $node;

        $height_class = abs($height - $localheight) > 5 ? 'text-danger fw-bold' : 'text-success fw-bold';

        echo '<tr>';
        echo '  <td class="ps-4 font-monospace">'.$node.'</td>';
        echo '  <td class="small text-muted">'.$peerver.'</td>';
        echo '  <td class="text-center '.$height_class.'">'.number_format($height).'</td>';
        echo '  <td class="text-center small">'.arraySafeVal($peer,'pingtime','').'</td>';
        echo '  <td class="small">'.datetoa2($conntime).' ago</td>';
        
        $bytesrecv = round(arraySafeVal($peer,'bytesrecv')/1024.,1);
        $bytessent = round(arraySafeVal($peer,'bytessent')/1024.,1);
        echo '  <td class="text-center small text-muted">'.($bytesrecv+$bytessent ? "{$bytesrecv} / {$bytessent}" : "-").'</td>';

        echo '  <td class="text-end pe-4">';
        echo '    <a href="/admin/coinpeerRemove?id='.$coin->id.'&node='.$node.'" class="btn btn-xs btn-outline-danger py-0 px-2 fw-bold" onclick="return confirm(\'Disconnect from node?\')" title="Disconnect"><i class="fa fa-unlink"></i></a>';
        echo '  </td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="7" class="py-5 text-center text-muted">No peers currently connected to the wallet.</td></tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';

// --- Peer Info Grid ---
if(!empty($addnode)) {
    echo '<div class="row">';
    echo '  <div class="col-md-6">';
    echo '    <div class="card shadow-sm border-0 bg-dark text-white rounded-3 h-100">';
    echo '      <div class="card-header border-bottom border-secondary border-opacity-25 py-3 d-flex justify-content-between align-items-center">';
    echo '        <h6 class="mb-0 fw-bold small text-uppercase">Raw AddNode List</h6>';
    echo '        <span class="badge bg-secondary">'.$coin->symbol.'</span>';
    echo '      </div>';
    echo '      <div class="card-body p-0">';
    echo '        <pre class="bg-black text-success p-4 mb-0 font-monospace small" style="min-height: 150px; opacity: 0.8;">'.implode("\n",$addnode).'</pre>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '  <div class="col-md-6">';
    echo '    <div class="card shadow-sm border-0 rounded-3 h-100 bg-white p-4 d-flex flex-column justify-content-center">';
    echo '      <div class="text-center">';
    echo '        <div class="text-muted small fw-bold text-uppercase mb-2">Network Health</div>';
    echo '        <div class="display-6 fw-bold text-primary mb-1">'.count($list).'</div>';
    echo '        <div class="small text-muted">Connected Nodes</div>';
    echo '        <hr class="w-25 mx-auto opacity-10 my-4">';
    echo '        <div class="small fw-bold">Latest Network SubVer: <span class="text-success">'.$version.'</span></div>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}

echo '</div>'; // close container
?>