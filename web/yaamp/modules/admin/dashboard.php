<?php

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-dark text-white">
                <div class="card-body py-3 d-flex flex-wrap align-items-center">
                    <h4 class="mb-0 me-auto"><i class="fa fa-tachometer-alt me-2 text-primary"></i>Admin Dashboard</h4>
                    <div class="btn-group shadow-sm">
                        <a href='/admin/coincreate' class="btn btn-sm btn-success"><i class="fa fa-plus-circle me-1"></i>Create Coin</a>
                        <a href='/admin/updateprice' class="btn btn-sm btn-info text-white"><i class="fa fa-sync-alt me-1"></i>Update Prices</a>
                        <a href='/admin/memcached' class="btn btn-sm btn-secondary"><i class="fa fa-memory me-1"></i>Memcache</a>
                        <a href='/admin/connections' class="btn btn-sm btn-secondary"><i class="fa fa-network-wired me-1"></i>Connections</a>
                        <?php if (YAAMP_RENTAL) : ?>
                        <a href='/renting/admin' class="btn btn-sm btn-warning"><i class="fa fa-server me-1"></i>Rental</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id='main_results'>
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading dashboard...</span>
            </div>
            <p class="mt-2 text-muted">Loading live pool statistics...</p>
        </div>
    </div>
</div>

<script type="text/javascript">

$(function()
{
	main_refresh();
});

var main_delay=30000;
var main_timeout;

function main_ready(data)
{
	$('#main_results').html(data);
	main_timeout = setTimeout(main_refresh, main_delay);

	main_refresh_assets();
	main_refresh_negative();
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var url = "/admin/common_results";

	clearTimeout(main_timeout);
	$.get(url, '', main_ready).fail(main_error);
}

///////////////////////////////////////////////////////////////////////

function main_ready_assets(data)
{
	graph_init_assets(data);
}

function main_refresh_assets()
{
	var url = "/admin/graph_assets_results";
	$.get(url, '', main_ready_assets);
}

function graph_init_assets(data)
{
	var el = $('#graph_results_assets');
    if (!el.length) return;
    el.empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_assets', t,
	{
		stackSeries: true,
		seriesDefaults:
		{
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},
		axes: {
			xaxis: {
				tickInterval: 7200,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				min: 0,
				tickOptions: {formatString: '<font size=1>%#.3f &nbsp;</font>'}
			}
		},
		grid:
		{
			borderWidth: 1,
			shadowWidth: 0,
			shadowDepth: 0,
			background: '#ffffff'
		},
	});
}

///////////////////////////////////////////////////////////////////////

function main_ready_negative(data)
{
	graph_init_negative(data);
}

function main_refresh_negative()
{
	var url = "/admin/graph_negative_results";
	$.get(url, '', main_ready_negative);
}

function graph_init_negative(data)
{
	var el = $('#graph_results_negative');
    if (!el.length) return;
    el.empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_negative', t,
	{
		stackSeries: true,
		seriesDefaults:
		{
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},
		axes: {
			xaxis: {
				tickInterval: 7200,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				min: 0,
				tickOptions: {formatString: '<font size=1>%#.3f &nbsp;</font>'}
			}
		},
		grid:
		{
			borderWidth: 1,
			shadowWidth: 0,
			shadowDepth: 0,
			background: '#ffffff'
		},
	});
}
</script>
