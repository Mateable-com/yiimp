<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

?>
<script>
function wallet_peers(id) {
    window.open("/explorer/peers?id=" + id, "peers", "width=400,height=600,location=no,menubar=no,resizable=yes,status=no,toolbar=no");
}
</script>

<div class="container-fluid py-4">

<div class="card shadow-sm border-0">
  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold"><i class="fa fa-cube me-2 text-primary"></i>Block Explorer</h5>
    <div class="input-group shadow-sm border-0 rounded-pill overflow-hidden" style="max-width:250px;">
      <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-search text-muted small"></i></span>
      <input class="form-control border-0 bg-white small search" type="search" data-column="all" placeholder="Filter coins..." />
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small" id="maintable">
        <thead class="table-light text-muted text-uppercase" style="font-size:0.7rem;">
          <tr>
            <th class="ps-4" style="width:40px;"></th>
            <th>Name</th>
            <th>Symbol</th>
            <th>Algo</th>
            <th>Version</th>
            <th class="text-end">Height</th>
            <th class="text-end">Difficulty</th>
            <th class="text-end">Connections</th>
            <th class="text-end">Network Hash</th>
            <th class="text-end pe-4">Links</th>
          </tr>
        </thead>
        <tbody>
<?php

$list = getdbolist('db_coins', "enable and visible order by name");
foreach($list as $coin)
{
    if($coin->symbol == 'BTC') continue;
    if(!empty($coin->symbol2)) continue;

    $coin->version = formatWalletVersion($coin);

    $coin->network_hash = controller()->memcache->get("yiimp-nethashrate-{$coin->symbol}");
    if (!$coin->network_hash) {
        $remote = new WalletRPC($coin);
        if ($remote)
            $info = $remote->getmininginfo();
        if (isset($info['networkhashps'])) {
            if (is_array($info['networkhashps'])) {
                if (isset($info['networkhashps'][$coin->algo]))
                    $coin->network_hash = $info['networkhashps'][$coin->algo];
            } else {
                $coin->network_hash = $info['networkhashps'];
            }
            controller()->memcache->set("yiimp-nethashrate-{$coin->symbol}", $coin->network_hash, 60);
        } else if (isset($info['netmhashps'])) {
            $coin->network_hash = floatval($info['netmhashps']) * 1e6;
            controller()->memcache->set("yiimp-nethashrate-{$coin->symbol}", $coin->network_hash, 60);
        }
    }

    $difficulty  = Itoa2($coin->difficulty, 3);
    $diffnote    = ($coin->algo == 'equihash' || $coin->algo == 'quark') ? '*' : '';
    $nethash_sfx = $coin->network_hash ? strtoupper(Itoa2($coin->network_hash)).'H/s' : '-';
    $cnx_class   = (intval($coin->connections) > 3) ? 'text-success' : 'text-danger fw-bold';
    $algo_color  = getAlgoColors($coin->algo);

    echo '<tr>';
    echo '<td class="ps-4"><img src="'.CHtml::encode($coin->image).'" width="24" class="rounded-circle shadow-sm"></td>';
    echo '<td class="fw-bold">'.$coin->createExplorerLink(CHtml::encode($coin->name)).'</td>';
    echo '<td><span class="badge bg-secondary">'.CHtml::encode($coin->symbol).'</span></td>';
    echo '<td><span class="badge" style="background-color:'.$algo_color.';color:#000;">'.CHtml::encode($coin->algo).'</span></td>';
    echo '<td class="text-muted small">'.CHtml::encode($coin->version).'</td>';
    echo '<td class="text-end">'.number_format($coin->block_height).'</td>';
    echo '<td class="text-end">'.CHtml::encode($difficulty.$diffnote).'</td>';
    echo '<td class="text-end '.$cnx_class.'">'.CHtml::link(CHtml::encode($coin->connections), "javascript:wallet_peers({$coin->id});").'</td>';
    echo '<td class="text-end small">'.CHtml::encode($nethash_sfx).'</td>';
    echo '<td class="text-end pe-4">';
    if (!empty($coin->link_bitcointalk))
        echo CHtml::link('<i class="fab fa-btc"></i>', $coin->link_bitcointalk, ['target'=>'_blank', 'class'=>'btn btn-sm btn-outline-warning py-0 px-2 me-1', 'title'=>'Forum']);
    if (!empty($coin->link_site))
        echo CHtml::link('<i class="fa fa-globe"></i>', $coin->link_site, ['target'=>'_blank', 'class'=>'btn btn-sm btn-outline-info py-0 px-2', 'title'=>'Website']);
    echo '</td>';
    echo '</tr>';
}
?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer text-muted small bg-light">
    * Unified difficulty based on the hash target (may differ from wallet value)
  </div>
</div>

</div>
