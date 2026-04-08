<?php

$algo = user()->getState('yaamp-algo');

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$height = '240px';

echo <<<END
<div class="container-fluid py-4">

    <div id='resume_update_button' class="alert alert-warning text-center shadow-sm mb-4 fw-bold animate-pulse" style='cursor: pointer; display: none;' onclick='auto_page_resume();'>
        <i class="fa fa-play me-2"></i>Live Data Paused - Click to Resume
    </div>

    <div class="row g-4">
        <!-- Left Column: Miners Activity -->
        <div class="col-lg-7">
            <div id='miners_results' class="mb-4">
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
            </div>
        </div>

        <!-- Right Column: Pool Wide Data -->
        <div class="col-lg-5">
            <div id='pool_current_results' class="mb-4"></div>
        </div>
    </div>
</div>

<script>

function page_refresh()
{
	miners_refresh();
	pool_current_refresh();
}

function select_algo(algo)
{
	window.location.href = '/site/algo?algo='+algo+'&r=/site/miners';
}

////////////////////////////////////////////////////

function pool_current_ready(data)
{
	$('#pool_current_results').html(data);
}

function pool_current_refresh()
{
	var url = "/site/current_results";
	$.get(url, '', pool_current_ready);
}

////////////////////////////////////////////////////

function miners_ready(data)
{
	$('#miners_results').html(data);
}

function miners_refresh()
{
	var url = "/site/miners_results";
	$.get(url, '', miners_ready);
}

</script>


END;





