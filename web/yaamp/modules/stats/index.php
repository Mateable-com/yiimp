<?php

$algo = user()->getState('yaamp-algo');
$algo_unit = 'Mh';
$algo_factor = yaamp_algo_mBTC_factor($algo);
if ($algo_factor == 0.001) $algo_unit = 'Kh';
if ($algo_factor == 1000) $algo_unit = 'Gh';
if ($algo_factor == 1000000) $algo_unit = 'Th';
if ($algo_factor == 1000000000) $algo_unit = 'Ph';

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$hour = 60 * 60;
$days = 24 * $hour;

$dbMax = (int) controller()->memcache->get_database_scalar("stats_maxt-$algo",
	"SELECT (MAX(time)-30*60) FROM hashstats WHERE time>:t AND algo=:algo", array(':t'=>time()-2*$hour,':algo'=>$algo));
$dtMax = max(time()-$hour, $dbMax);

$t1 = $dtMax - 2*$days;
$t2 = $dtMax - 7*$days;
$t3 = $dtMax - 30*$days;

$row1 = controller()->memcache->get_database_row("stats_col1-$algo",
	"SELECT AVG(hashrate) as a, SUM(earnings) as b FROM hashstats WHERE time>$t1 AND algo=:algo", array(':algo'=>$algo));
$row2 = controller()->memcache->get_database_row("stats_col2-$algo",
	"SELECT AVG(hashrate) as a, SUM(earnings) as b FROM hashstats WHERE time>$t2 AND algo=:algo", array(':algo'=>$algo));
$row3 = controller()->memcache->get_database_row("stats_col3-$algo",
	"SELECT AVG(hashrate) as a, SUM(earnings) as b FROM hashstats WHERE time>$t3 AND algo=:algo", array(':algo'=>$algo));

if($row1['a']>0 && $row2['a']>0 && $row3['a']>0)
{
	$a1 = max(1., (double) $row1['a']);
	$a2 = max(1., (double) $row2['a']);
	$a3 = max(1., (double) $row3['a']);

	$btcmhday1 = bitcoinvaluetoa(($row1['b'] / 2)  * $algo_factor * (1000000 / $a1));
	$btcmhday2 = bitcoinvaluetoa(($row2['b'] / 7)  * $algo_factor * (1000000 / $a2));
	$btcmhday3 = bitcoinvaluetoa(($row3['b'] / 30) * $algo_factor * (1000000 / $a3));
}
else
{
	$btcmhday1 = 0;
	$btcmhday2 = 0;
	$btcmhday3 = 0;
}

$hashrate1 = Itoa2($row1['a']);
$hashrate2 = Itoa2($row2['a']);
$hashrate3 = Itoa2($row3['a']);

$total1 = bitcoinvaluetoa($row1['b']);
$total2 = bitcoinvaluetoa($row2['b']);
$total3 = bitcoinvaluetoa($row3['b']);

$height = '240px';

//$algos = yaamp_get_algos();
$algos = array();
$enabled = dbolist("SELECT algo, count(id) as count FROM coins WHERE enable AND visible GROUP BY algo ORDER BY algo");
foreach ($enabled as $row) {
	$algos[$row['algo']] = $row['count'];
}

$string = '';
foreach($algos as $a => $count)
{
	if($a == $algo)
		$string .= "<option value='$a' selected>$a</option>";
	else
		$string .= "<option value='$a'>$a</option>";
}

// to fill the graphs on right edges (big tick interval of 4 days)
$dtMin1 = $t1 + $hour;
$dtMax1 = $dtMax;

$dtMin2 = $t2 - 2*$hour;
$dtMax2 = $dtMin2 + 7 * $days;

$dtMin3 = $dtMax1 - (8*4+1)*$days;
$dtMax3 = $dtMin3 + (8*4) * $days;

echo <<<end

<div class="container-fluid py-4">

<div id='resume_update_button' class="alert alert-warning text-center fw-bold d-none" role="alert" onclick='auto_page_resume();' style="cursor:pointer;">
  <i class="fa fa-pause-circle me-2"></i>Auto refresh is paused — Click to resume
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="fa fa-chart-line me-2 text-primary"></i>Pool Statistics</h4>
  <div class="d-flex align-items-center gap-2">
    <label class="fw-bold small text-muted mb-0">Algo:</label>
    <select id='algo_select' class="form-select form-select-sm" style="width:auto;">$string</select>
  </div>
</div>

<script>
$('#algo_select').change(function(event) {
  var algo = $('#algo_select').val();
  window.location.href = '/site/algo?algo='+algo+'&r=/stats';
});
</script>

<div class="row g-4">

<div class="col-lg-4">
  <div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-dark text-white py-3">
      <h6 class="mb-0 fw-bold"><i class="fa fa-clock me-2 text-info"></i>Last 48 Hours</h6>
    </div>
    <div class="card-body">
      <ul class="list-unstyled mb-3 small">
        <li class="mb-1"><span class="text-muted">Avg Hashrate:</span> <b>{$hashrate1}h/s</b></li>
        <li class="mb-1"><span class="text-muted">BTC Value:</span> <b>$total1</b></li>
        <li><span class="text-muted">BTC/{$algo_unit}/d:</span> <b>$btcmhday1</b></li>
      </ul>
      <div id='graph_results_1' style='height:$height;'></div>
      <hr class="my-3">
      <div id='graph_results_2' style='height:$height;'></div>
      <hr class="my-3">
      <div id='graph_results_3' style='height:$height;'></div>
    </div>
  </div>
</div>

<div class="col-lg-4">
  <div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-dark text-white py-3">
      <h6 class="mb-0 fw-bold"><i class="fa fa-calendar-week me-2 text-warning"></i>Last 7 Days</h6>
    </div>
    <div class="card-body">
      <ul class="list-unstyled mb-3 small">
        <li class="mb-1"><span class="text-muted">Avg Hashrate:</span> <b>{$hashrate2}h/s</b></li>
        <li class="mb-1"><span class="text-muted">BTC Value:</span> <b>$total2</b></li>
        <li><span class="text-muted">BTC/{$algo_unit}/d:</span> <b>$btcmhday2</b></li>
      </ul>
      <div id='graph_results_4' style='height:$height;'></div>
      <hr class="my-3">
      <div id='graph_results_5' style='height:$height;'></div>
      <hr class="my-3">
      <div id='graph_results_6' style='height:$height;'></div>
    </div>
  </div>
</div>

<div class="col-lg-4">
  <div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-dark text-white py-3">
      <h6 class="mb-0 fw-bold"><i class="fa fa-calendar-alt me-2 text-success"></i>Last 30 Days</h6>
    </div>
    <div class="card-body">
      <ul class="list-unstyled mb-3 small">
        <li class="mb-1"><span class="text-muted">Avg Hashrate:</span> <b>{$hashrate3}h/s</b></li>
        <li class="mb-1"><span class="text-muted">BTC Value:</span> <b>$total3</b></li>
        <li><span class="text-muted">BTC/{$algo_unit}/d:</span> <b>$btcmhday3</b></li>
      </ul>
      <div id='graph_results_7' style='height:$height;'></div>
      <hr class="my-3">
      <div id='graph_results_8' style='height:$height;'></div>
      <hr class="my-3">
      <div id='graph_results_9' style='height:$height;'></div>
    </div>
  </div>
</div>

</div><!-- end row -->
</div><!-- end container -->

<script type="text/javascript">

var dtMin1 = new Date(1000*{$dtMin1});
var dtMax1 = new Date(1000*{$dtMax1});

var dtMin2 = new Date(1000*{$dtMin2});
var dtMax2 = new Date(1000*{$dtMax2});

var dtMin3 = new Date(1000*{$dtMin3});
var dtMax3 = new Date(1000*{$dtMax3});

function page_refresh()
{
	main_refresh_1();
	main_refresh_2();
	main_refresh_3();
	main_refresh_4();
	main_refresh_5();
	main_refresh_6();
	main_refresh_7();
	main_refresh_8();
	main_refresh_9();
}

end;

for($i = 1; $i < 10; $i++)
{
	echo <<<end
	///////////////////////////////////////////////////////////////////////

	function main_ready_$i(data)
	{
		graph_init_$i(data);
	}

	function main_refresh_$i()
	{
		var url = "/stats/graph_results_$i";
		$.get(url, '', main_ready_$i);
	}
end;
}

echo <<<end

function graph_init_1(data)
{
	$('#graph_results_1').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_1', [t],
	{
		title: '<b>Hashrate ({$algo_unit}/s)</b>',
		axes: {
			xaxis: {
				min: dtMin1,
				max: dtMax1,
				tickInterval: 14400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.3f</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}
			}
		},

		seriesDefaults: {
			markerOptions: { style: 'none' },
			rendererOptions: { smooth: true }
		},

		seriesColors: [ "rgba(78, 180, 180, 0.8)" ],
		series: [ { fill: true } ],

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_2(data)
{
	$('#graph_results_2').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_2', [t],
	{
		title: '<b>BTC/Day</b>',
		axes: {
			xaxis: {
				min: dtMin1,
				max: dtMax1,
				tickInterval: 14400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults: {
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_3(data)
{
	$('#graph_results_3').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_3', [t],
	{
		title: '<b>BTC/{$algo_unit}/d</b>',
		axes: {
			xaxis: {
				min: dtMin1,
				max: dtMax1,
				tickInterval: 14400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults: {
			renderer: $.jqplot.BarRenderer,
			rendererOptions: { barWidth: 3 }
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

//////////////////////////////////////////////////////////////////////////////////////////////

function graph_init_4(data)
{
	$('#graph_results_4').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_4', [t],
	{
		title: '<b>Hashrate ({$algo_unit}/s)</b>',
		axes: {
			xaxis: {
				min: dtMin2,
				max: dtMax2,
				tickInterval: 86400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%d</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.3f</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}
			}
		},

		seriesDefaults: {
			markerOptions: { style: 'none' },
			rendererOptions: { smooth: true }
		},

		seriesColors: [ "rgba(78, 180, 180, 0.8)" ],
		series: [ { fill: true } ],

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_5(data)
{
	$('#graph_results_5').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_5', [t],
	{
		title: '<b>BTC/Day</b>',
		axes: {
			xaxis: {
				min: dtMin2,
				max: dtMax2,
				tickInterval: 86400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%d</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults: {
			renderer: $.jqplot.BarRenderer,
			rendererOptions: { barWidth: 3 }
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_6(data)
{
	$('#graph_results_6').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_6', [t],
	{
		title: '<b>BTC/{$algo_unit}/d</b>',
		axes: {
			xaxis: {
				min: dtMin2,
				max: dtMax2,
				tickInterval: 86400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%d</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults: {
			renderer: $.jqplot.BarRenderer,
			rendererOptions: { barWidth: 3 }
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

//////////////////////////////////////////////////////////////////////////////////////////////

function graph_init_7(data)
{
	$('#graph_results_7').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_7', [t],
	{
		title: '<b>Hashrate ({$algo_unit}/s)</b>',
		axes: {
			xaxis: {
				min: dtMin3,
				max: dtMax3,
				tickInterval: 4 * 24*60*60,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%m/%d</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.3f</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}
			}
		},

		seriesDefaults: {
			markerOptions: { style: 'none' },
			rendererOptions: { smooth: true }
		},

		seriesColors: [ "rgba(78, 180, 180, 0.8)" ],
		series: [ { fill: true } ],

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_8(data)
{
	$('#graph_results_8').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_8', [t],
	{
		title: '<b>BTC/Day</b>',
		axes: {
			xaxis: {
				min: dtMin3,
				max: dtMax3,
				tickInterval: 4 * 24*60*60,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%m/%d</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults: {
			markerOptions: { style: 'none' },
			rendererOptions: { smooth: true }
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_9(data)
{
	$('#graph_results_9').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_9', [t],
	{
		title: '<b>BTC/{$algo_unit}/d</b>',
		axes: {
			xaxis: {
				min: dtMin3,
				max: dtMax3,
				tickInterval: 4 * 24*60*60,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%m/%d</font>'}
			},
			yaxis: {
				min: 0.0,
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults: {
			markerOptions: { style: 'none' },
			rendererOptions: { smooth: true }
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}


</script>
end;


