<?php

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-broom me-2 text-warning"></i>Empty Markets & Connection Issues</h5>';
echo '    <input class="search form-control form-control-sm border-secondary bg-dark text-white" type="search" data-column="all" style="width: 200px;" placeholder="Filter markets..." />';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="20"></th>';
echo '            <th>Coin</th>';
echo '            <th>Exchange Market</th>';
echo '            <th class="text-end">Price (BTC)</th>';
echo '            <th>Error / Message</th>';
echo '            <th class="pe-4">Deposit Address</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$list = dbolist("SELECT coins.id as coinid, markets.id as marketid FROM markets 
LEFT JOIN coins ON coins.id=markets.coinid 
WHERE coins.installed AND coins.enable AND 
	 (markets.deposit_address IS NULL OR (markets.message is not null and markets.message!='')) 
ORDER BY coins.id DESC, markets.id DESC");

if (!empty($list)) {
    foreach($list as $item)
    {
        $coin = getdbo('db_coins', $item['coinid']);
        $market = getdbo('db_markets', $item['marketid']);
        if(!$coin || !$market) continue;

        echo '<tr>';
        echo '<td class="ps-4 text-center"><img src="'.$coin->image.'" width="18" class="rounded-circle shadow-sm"></td>';
        echo '<td><b>'.CHtml::link($coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</b></td>';
        echo '<td><span class="badge bg-secondary opacity-75">'.$market->name.'</span></td>';
        echo '<td class="text-end fw-bold text-success">'.bitcoinvaluetoa($market->price).'</td>';
        
        echo '<td>';
        if(!empty($market->message)) echo '<span class="text-danger small fw-bold"><i class="fa fa-exclamation-circle me-1"></i>'.$market->message.'</span>';
        else echo '<span class="text-muted small"><i>No address set</i></span>';
        echo '</td>';

        echo '<td class="pe-4 font-monospace small">'.($market->deposit_address ? : '-').'</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="py-5 text-center text-muted">All active markets have deposit addresses configured.</td></tr>';
}

echo "        </tbody>";
echo "      </table></div></div></div>";

echo '<div class="alert alert-info border-0 shadow-sm small py-2"><i class="fa fa-info-circle me-2"></i>This table displays enabled coins where the exchange market is missing a deposit address or has reported an error message.</div>';
?>