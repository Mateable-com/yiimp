<?php

$algo = user()->getState('yaamp-algo');

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.cursor.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$this->widget('UniForm');

$address = getparam('address');
if($address == 0) $address = '';
if (!empty($address) && preg_match('/[^A-Za-z0-9]/', $address)) {
	die;
}

?>

<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden h-100">
            <div class="card-header bg-dark text-white p-4 border-0">
                <h4 class="mb-0 fw-bold"><i class="fa fa-key me-2 text-warning"></i>Renter Login</h4>
                <p class="text-white-50 small mb-0 mt-1">Access your hashpower rental dashboard.</p>
            </div>
            <div class="card-body p-4">
                <?php if(!YAAMP_RENTAL): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        <div>Renting is temporarily disabled.</div>
                    </div>
                <?php endif; ?>

                <form action='/renting/login' method='post'>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Deposit Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-2 border-end-0"><i class="fa fa-wallet text-muted"></i></span>
                            <input type="text" value='<?= $address ?>' name="deposit_address" class="form-control border-2 border-start-0 bg-light px-3 py-2" placeholder="Paste your deposit address" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-2 border-end-0"><i class="fa fa-lock text-muted"></i></span>
                            <input type="password" name="deposit_password" class="form-control border-2 border-start-0 bg-light px-3 py-2" placeholder="Your account password">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill shadow-sm">LOGIN TO DASHBOARD</button>
                        <?php
                        $recents = isset($_COOKIE['deposits'])? explode("|", $_COOKIE['deposits']): array();
                        if(controller()->admin || sizeof($recents) < 10): ?>
                            <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill" onclick='javascript:deposit_create()'>REGISTER NEW ACCOUNT</button>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if(!empty($recents)): ?>
                <div class="mt-5">
                    <h6 class="text-muted fw-bold text-uppercase small mb-3">Recently Used Addresses</h6>
                    <div class="list-group list-group-flush rounded-3 border">
                        <?php foreach($recents as $addr): 
                            if(empty($addr)) continue;
                            $renter = getdbosql('db_renters', "address=:address", array(':address'=>$addr));
                            if(!$renter) continue;
                        ?>
                            <a href='/renting/login?address=<?= $addr ?>' class="list-group-item list-group-item-action font-monospace py-3">
                                <img width="16" src="/images/btc.png" class="me-2 rounded-circle shadow-sm"> <?= $addr ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div id='pool_current_results' class="mb-4"></div>
        <div id='all_orders_results' class="mb-4"></div>
        
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold"><i class="fa fa-chart-line me-2 text-primary"></i>Market Price (<?= $algo ?>)</h5>
            </div>
            <div class="card-body p-3">
                <div id='graph_results_price' style='height: 240px;'></div>
            </div>
        </div>
    </div>
</div>

<div id="deposit-create-dialog" style='display: none; overflow: hidden;'>
    <div class="p-3">
        <form action='/renting/create' method='post'>
            <p class="text-muted small">You are about to create a new Bitcoin deposit address. Fund this address to start renting hashpower.</p>
            <div class="alert alert-warning py-2 small">
                <i class="fa fa-info-circle me-1"></i> Minimum deposit: 0.001 BTC
            </div>
            <div class="mb-3 text-center">
                <?php $this->widget('CCaptcha'); ?>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-uppercase">Enter Verification Code</label>
                <input type="text" name="create_code" class="form-control form-control-lg text-center font-monospace" placeholder="CODE" required autofocus>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-success fw-bold rounded-pill">CREATE MY ACCOUNT</button>
            </div>
        </form>
    </div>
</div>

<script>

function page_refresh()
{
	pool_current_refresh();
	main_refresh_price();
	all_orders_refresh();
}

function select_algo(algo) { window.location.href = '/site/algo?algo='+algo; }
function pool_current_ready(data) { $('#pool_current_results').html(data); }
function pool_current_refresh() { $.get("/renting/status_results", '', pool_current_ready); }
function all_orders_ready(data) { $('#all_orders_results').html(data); }
function all_orders_refresh() { $.get("/renting/all_orders_results", '', all_orders_ready); }
function main_refresh_price() { $.get("/renting/graph_price_results", '', graph_init_price); }

function graph_init_price(data)
{
	$('#graph_results_price').empty();
	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_price', t,
	{
		axes: {
			xaxis: { tickInterval: 7200, renderer: $.jqplot.DateAxisRenderer, tickOptions: {formatString: '%#Hh'} },
			yaxis: { min: 0, tickOptions: {formatString: '%#.3f'} }
		},
		seriesDefaults: { markerOptions: { style: 'none' }, shadow: false, color: '#3b82f6' },
		grid: { borderWidth: 0, shadow: false, background: 'transparent' },
	});
}

function deposit_create() {
	$('#deposit-create-dialog').dialog({ title: 'Create Deposit Address', autoOpen: true, modal: true, width: 450 });
}

</script>
