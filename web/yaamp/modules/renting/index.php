<?php

$algo = user()->getState('yaamp-algo');

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.cursor.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$this->widget('UniForm');

$balance = bitcoinvaluetoa($renter->balance);
$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>YAAMP_RENTER_COIN));
$coin_symbol = $coin ? $coin->symbol : 'BTC';

?>

<div class="row mb-4 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-dark text-white overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1 fw-bold text-warning"><i class="fa fa-server me-2"></i>Hashpower Rental Control</h3>
                    <p class="text-white-50 mb-0">Manage your active mining jobs and redirect pool power to your targets.</p>
                </div>
                <div class="text-end">
                    <div class="small text-white-50 text-uppercase fw-bold mb-1">Your Balance</div>
                    <div class="h3 mb-0 fw-bold text-success font-monospace"><?= $balance ?> <small class="fs-6 text-white-50"><?= $coin_symbol ?></small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div id='balance_results' class="mb-4"></div>
        <div id='orders_results' class="mb-4"></div>
    </div>
    <div class="col-lg-5">
        <div id='pool_current_results' class="mb-4">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading network status...</p>
            </div>
        </div>
        <div id='all_orders_results' class="mb-4"></div>
        
        <div class="card shadow-sm border-0 rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-light border-0 py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-chart-line me-2 text-primary"></i>Price History (<?= $algo ?>)</h5>
            </div>
            <div class="card-body p-3">
                <div id='graph_results_price' style='height: 240px;'></div>
            </div>
        </div>
    </div>
</div>

<script>

function page_refresh()
{
	balance_refresh();
	orders_refresh();
	all_orders_refresh();
	pool_current_refresh();
	main_refresh_price();
}

function select_algo(algo)
{
	window.location.href = '/site/algo?algo='+algo;
}

function pool_current_ready(data) { $('#pool_current_results').html(data); }
function pool_current_refresh() { $.get("/renting/status_results", '', pool_current_ready); }

function balance_ready(data) { $('#balance_results').html(data); }
function balance_refresh() { $.get("/renting/balance_results?address=<?= $renter->address ?>", '', balance_ready); }

function orders_ready(data) { $('#orders_results').html(data); }
function orders_refresh() { $.get("/renting/orders_results?address=<?= $renter->address ?>", '', orders_ready); }

function all_orders_ready(data) { $('#all_orders_results').html(data); }
function all_orders_refresh() { $.get("/renting/all_orders_results?address=<?= $renter->address ?>", '', all_orders_ready); }

function main_refresh_price() { $.get("/renting/graph_price_results", '', graph_init_price); }

function graph_init_price(data)
{
	$('#graph_results_price').empty();
	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_price', t,
	{
		title: '',
		axes: {
			xaxis: {
				tickInterval: 7200,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '%#Hh'}
			},
			yaxis: {
				min: 0,
				tickOptions: {formatString: '%#.3f'}
			}
		},
		seriesDefaults: { markerOptions: { style: 'none' }, shadow: false, color: '#3b82f6' },
		grid: { borderWidth: 0, shadow: false, background: 'transparent' },
	});
}

function order_edit(jobid)
{
	$('#order-edit-dialog').load('/renting/orderdialog?address=<?= $renter->address ?>&id='+jobid).dialog(
	{
		title: 'Edit Job',
		autoOpen: true,
		modal: true,
		width: 500,
		buttons:
		{
			"Submit": function() { $('#order-edit-form').submit(); },
			"Cancel": function() { $(this).dialog('close'); },
			"Delete": function()
			{
				if(confirm("Are you sure you want to delete this job?")) 
                    window.location.href = '/renting/orderdelete?id='+jobid;
			},
		}
	});
}

function order_new()
{
	$('#order-edit-dialog').load('/renting/orderdialog?address=<?= $renter->address ?>').dialog(
	{
		title: 'New Job',
		autoOpen: true,
		modal: true,
		width: 500,
		buttons: { "Submit": function() { $('#order-edit-form').submit(); } }
	});
}

function reset_spent()
{
	if(confirm("Are you sure you want to reset the spent counter?"))
	    window.location.href = '/renting/resetspent?address=<?= $renter->address ?>';
}

function show_job_graph(jobid)
{
	if($('#graph_placeholder_job-'+jobid).is(':visible'))
	{
		$('#graph_toggle_job-'+jobid).attr('src', '/images/plus2-78.png');
		$('#graph_placeholder_job-'+jobid).hide();
	}
	else
	{
		$('#graph_toggle_job-'+jobid).attr('src', '/images/minus2-78.png');
		$('#graph_placeholder_job-'+jobid).show();

		$.get("/renting/graph_job_results?jobid="+jobid, '', function (data)
		{
			$('#graph_results_job-'+jobid).empty();
			var t = $.parseJSON(data);
			var plot1 = $.jqplot('graph_results_job-'+jobid, t,
			{
				title: '',
				axes: {
					xaxis: { tickInterval: 7200, renderer: $.jqplot.DateAxisRenderer, tickOptions: {formatString: '%#Hh'} },
					yaxis: { min: 0, tickOptions: {formatString: '%#.3f'} }
				},
				seriesDefaults: { markerOptions: { style: 'none' }, shadow: false, color: '#10b981' },
				grid: { borderWidth: 0, shadow: false, background: 'transparent' },
			});
		});
	}
}

function main_renter_tx() {
	window.open("/renting/tx?address=<?= $renter->address ?>", "yaamp_tx",
		"width=800,height=600,location=no,menubar=no,resizable=yes,status=yes,toolbar=no");
}

function yaamp_withdraw() {
	$('#yaamp-withdraw').dialog({ title: 'Withdraw Funds', autoOpen: true, modal: true, width: 450 });
}

</script>

<div id="order-edit-dialog" style='display: none; overflow: hidden;'></div>

<div id="yaamp-withdraw" class="p-3" style='display: none; overflow: hidden;'>
    <form action='/renting/withdraw' method='post'><?php echo csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label fw-bold small text-uppercase">Amount (BTC)</label>
            <input type="text" name="withdraw_amount" class="form-control" value='<?= $balance ?>'>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold small text-uppercase">Withdraw Address</label>
            <input type="text" name="withdraw_address" class="form-control" placeholder="Bitcoin Address">
        </div>
        <div class="alert alert-info small py-2">
            <i class="fa fa-info-circle me-1"></i> Withdrawal fee: 0.0001 BTC
        </div>
        <div class="text-end">
            <input type="submit" value="Withdraw" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm">
        </div>
    </form>
</div>
