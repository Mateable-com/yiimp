<?php

$algo = user()->getState('yaamp-algo');

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.cursor.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$this->widget('UniForm');

$renter = getrenterparam(user()->getState('yaamp-deposit'));
if(!$renter) return;

$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>YAAMP_RENTER_COIN));
$coin_name = $coin ? $coin->name : 'Bitcoin';
$coin_symbol = $coin ? $coin->symbol : 'BTC';
$coin_scheme = $coin ? strtolower($coin->name) : 'bitcoin';

?>

<div class="row g-4 mt-2">
    <div class="col-lg-7">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-dark text-white p-4 border-0">
                <h4 class="mb-0 fw-bold"><i class="fa fa-cog me-2 text-warning"></i>Account Settings</h4>
                <p class="text-white-50 small mb-0 mt-1">Configure your renter profile and security.</p>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
                    <h6 class="fw-bold mb-2"><i class="fa fa-exclamation-circle me-1"></i> Important Information</h6>
                    <p class="small mb-2">This is your unique <?= $coin_name ?> deposit address. Fund it to start renting hashpower.</p>
                    <div class="bg-white p-3 rounded border text-center">
                        <span class="font-monospace fs-5 fw-bold text-dark d-block mb-2"><?= $renter->address ?></span>
                        <img class="img-fluid rounded shadow-sm" src="https://chart.googleapis.com/chart?cht=qr&amp;chl=<?= $coin_scheme ?>%3A<?= $renter->address ?>&amp;choe=UTF-8&amp;chs=200x200">
                    </div>
                    <p class="small mt-2 mb-0">Minimum deposit: 0.001 <?= $coin_symbol ?>. Save this address to login next time.</p>
                </div>

                <form action='/renting?address=<?= $renter->address ?>' method='post'>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                            <input value='<?= $renter->email ?>' type="email" name="deposit_email" placeholder="Optional - for recovery" class="form-control border-2 bg-light">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">API Key</label>
                            <div class="input-group">
                                <input readonly value='<?= $renter->apikey ?>' type="text" id="api_key" class="form-control border-2 bg-light font-monospace">
                                <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()"><i class="fa fa-copy"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">New Password</label>
                            <input type="password" name="deposit_password" placeholder='Leave empty for no change' class="form-control border-2 bg-light">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Confirm Password</label>
                            <input type="password" name="deposit_confirm" class="form-control border-2 bg-light">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-5 fw-bold rounded-pill shadow-sm">SAVE SETTINGS</button>
                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold rounded-pill" onclick='javascript:window.history.back()'>CANCEL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div id='pool_current_results' class="mb-4"></div>
        
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="mb-0 fw-bold"><i class="fa fa-chart-line me-2 text-primary"></i>Renting Price (<?= $algo ?>)</h5>
            </div>
            <div class="card-body p-3">
                <div id='graph_results_price' style='height: 240px;'></div>
            </div>
        </div>
    </div>
</div>

<script>

function copyApiKey() {
    var copyText = document.getElementById("api_key");
    copyText.select();
    document.execCommand("copy");
    alert("API Key copied to clipboard");
}

function page_refresh() { pool_current_refresh(); main_refresh_price(); }
function select_algo(algo) { window.location.href = '/site/algo?algo='+algo; }
function pool_current_ready(data) { $('#pool_current_results').html(data); }
function pool_current_refresh() { $.get("/renting/status_results", '', pool_current_ready); }
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

</script>
