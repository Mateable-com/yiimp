<?php

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$recents = array();
$raw_recents = isset($_COOKIE['wallets'])? explode("|", $_COOKIE['wallets']): array();
// make it unique
foreach($raw_recents as $addr) {
	$recents[$addr] = $addr;
}

$address = getparam('address');
if (!empty($address) && preg_match('/[^A-Za-z0-9]/', $address)) {
	// Just to make happy XSS seekers who can hack their own browser html...
	die;
}

$drop_address = getparam('drop');
if (!empty($drop_address)) {
	// to clean cookies
	foreach($recents as $k=>$addr) {
		if ($addr == $drop_address) {
			unset($recents[$k]);
			if (controller()->admin)
				setcookie('wallets', implode("|", $recents), time()+60*60*24*30, '/');
			break;
		}
	}
}

$user = getuserparam($address);
if($user)
{
	user()->setState('yaamp-wallet', $user->username);
	$recents[$user->username] = $user->username;

	$coin = getdbo('db_coins', $user->coinid);
	if($coin) echo <<<END
	<script type="text/javascript">
	$(function() {
		$('#favicon').remove();
		$('head').append('<link href="{$coin->image}" id="favicon" rel="shortcut icon">');
	});
	</script>
END;

	if(empty($user->hostaddr) && !$this->admin) {
		$user->hostaddr = $_SERVER['REMOTE_ADDR'];
		$user->save();
	}
}

$username = $user? $user->username: '';

if(!controller()->admin)
	setcookie('wallets', implode("|", $recents), time()+60*60*24*30, '/');

echo <<<END
<div class="container-fluid py-4">

    <div id='resume_update_button' class="alert alert-warning text-center shadow-sm mb-4 fw-bold animate-pulse" style='cursor: pointer; display: none;' onclick='auto_page_resume();'>
        <i class="fa fa-play me-2"></i>Live Data Paused - Click to Resume
    </div>

    <div class="row g-4">
        <!-- Left Column: User Specific Data -->
        <div class="col-lg-7">
END;

if($user)
{
    echo <<<END
            <div id='main_wallet_results' class="mb-4">
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
            </div>
END;

    if (YAAMP_RENTAL && !empty($user->rent_address)) {
        $rent_user = getdbosql('db_accounts', "username=:address", array(':address'=>$user->rent_address));
        $rent_balance = $rent_user ? bitcoinvaluetoa($rent_user->balance) : '0.00000000';
        echo <<<END
            <div class="card shadow-sm border-0 mb-4 rounded-4 bg-primary bg-opacity-10 border-primary border-opacity-25">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-primary fw-bold mb-1 text-uppercase small" style="letter-spacing: 1px;"><i class="fa fa-bitcoin me-2"></i>Renter Bonus BTC</h6>
                        <div class="font-monospace fw-bold text-dark mb-0">{$user->rent_address}</div>
                        <p class="text-muted small mb-0 mt-1">Renter bonuses are paid directly to this Bitcoin address.</p>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Current Balance</div>
                        <div class="h4 mb-0 fw-bold text-primary font-monospace">{$rent_balance} <small class="text-muted fs-6">BTC</small></div>
                    </div>
                </div>
            </div>
END;
    }

    echo <<<END
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-chart-area me-2 text-info"></i>Last 24 Hours Balance: <small class="text-muted font-monospace">{$user->username}</small></h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div id='graph_earnings_results' style='height: 280px;'></div>
                    <div class="d-flex justify-content-end gap-3 mt-2">
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3">Balance</span>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3">Pending</span>
                    </div>
                </div>
            </div>

            <div id='main_workers_results' class="mb-4"></div>
            <div id='main_graphs_results' class="mb-4"></div>
            <div id='main_miners_results' class="mb-4"></div>
            <div id='main_found_results' class="mb-4"></div>
END;
}

echo <<<END
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-dark text-white py-3 border-0">
                    <h5 class="mb-0 fw-bold small text-uppercase"><i class="fa fa-search me-2 text-primary"></i>Wallet Search & History</h5>
                </div>
                <div class="card-body p-4">
                    <form action="/" method="get" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="address" class="form-control border-2 bg-light px-3" placeholder="Enter Wallet Address...">
                            <button class="btn btn-primary px-4 fw-bold" type="submit">SEARCH</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem;">
                                <tr>
                                    <th class="ps-3" style="width: 40px;"></th>
                                    <th>Address</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
END;

foreach($recents as $addr)
{
    if(empty($addr)) continue;

    $u = getuserparam($addr);
    if(!$u) continue;

    $c = getdbo('db_coins', $u->coinid);
    $activeClass = ($u->username == $username) ? 'table-primary bg-opacity-10' : '';

    echo '<tr class="'.$activeClass.'">';
    echo '  <td class="ps-3">'.($c ? '<img width="20" src="'.$c->image.'" class="rounded-circle shadow-sm">' : '').'</td>';
    echo '  <td><a class="address text-decoration-none font-monospace fw-bold" href="/?address='.$addr.'">'.$addr.'</a></td>';

    $bal = bitcoinvaluetoa($u->balance); 
    $balText = ($bal > 0) ? $bal.' '.($c ? $c->symbol : 'BTC') : '<span class="text-muted">0.0000</span>';

    echo '  <td class="text-end fw-bold">'.$balText.'</td>';
    
    $delBtn = ($address == $addr) ? '' : '<button class="btn btn-link btn-sm text-danger p-0" onclick="javascript:drop_cookie(this);"><i class="fa fa-times-circle"></i></button>';
    echo '  <td class="text-center">'.$delBtn.'</td>';
    echo '</tr>';
}

echo <<<END
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Pool Wide Data -->
        <div class="col-lg-5">
            <div id='pool_current_results' class="mb-4"></div>
END;

if($user) echo <<<END
            <div id='found_results' class="mb-4"></div>
END;

echo <<<END
        </div>
    </div>
</div>

<script>

function page_refresh()
{
	pool_current_refresh();
	found_refresh();

	if('$username' != '')
	{
		main_wallet_refresh();
		main_workers_refresh();
		main_miners_refresh();

		main_graphs_refresh();
		main_title_refresh();

		main_found_refresh();
	}
}

function select_algo(algo)
{
	window.location.href = '/site/algo?algo='+algo+'&r=/site/mining';
}

////////////////////////////////////////////////////

function main_wallet_ready(data)
{
	$('#main_wallet_results').html(data);
}

function main_wallet_refresh()
{
	var url = "/site/wallet_results?address=$username";
	$.get(url, '', main_wallet_ready);
}

function main_found_ready(data)
{
	$('#main_found_results').html(data);
}

function main_found_refresh()
{
	var url = "/site/wallet_found_results?address=$username";
	$.get(url, '', main_found_ready);
}

function main_wallet_refresh_details()
{
	var url = "/site/wallet_results?address=$username&showdetails=1";
	$.get(url, '', main_wallet_ready);
}

////////////////////////////////////////////////////

function main_workers_ready(data)
{
	$('#main_workers_results').html(data);
}

function main_workers_refresh()
{
	var url = "/site/wallet_workers_results?address=$username";
	$.get(url, '', main_workers_ready);
}

////////////////////////////////////////////////////

function main_miners_ready(data)
{
	$('#main_miners_results').html(data);
}

function main_miners_refresh()
{
	var url = "/site/wallet_miners_results?address=$username";
	$.get(url, '', main_miners_ready);
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

function main_title_ready(data)
{
	document.title = data;
}

function main_title_refresh()
{
	var url = "/site/title_results?address=$username";
	$.get(url, '', main_title_ready);
}

////////////////////////////////////////////////////

function found_ready(data)
{
	$('#found_results').html(data);
}

function found_refresh()
{
	var url = "/site/user_earning_results?address=$username";
	$.get(url, '', found_ready);
}

////////////////////////////////////////////////////

var last_graph_update = 0;

function main_graphs_ready(data)
{
	$('#main_graphs_results').html(data);
	$('.graph_algo').each(function()
	{
		var algo = $(this).attr('id');
		main_refresh_hashrate(algo);
	});
}

function main_graphs_refresh()
{
	var now = Date.now()/1000;

	if(now < last_graph_update + 900) return;
	last_graph_update = now;

	var url = "/site/wallet_graphs_results?address=$username";
	$.get(url, '', main_graphs_ready);

	graph_earnings_refresh();
}

///////////////////////////////////////////////////////////////////////

function main_refresh_hashrate(algo)
{
	var url = "/site/graph_user_results?address=$username&algo="+algo;
	$.get(url, '', function(data)
	{
		graph_init_hashrate(data, algo);
	});
}

///////////////////////////////////////////////////////////////////////

function graph_init_hashrate(data, algo)
{
	$('#graph_results_'+algo).empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_'+algo, t[0],
	{
		title: '<b>'+t[1]+'</b>',
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

		seriesDefaults:
		{
			markerOptions: { style: 'none' }
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 0,
			shadowDepth: 0,
			background: '#ffffff'
		},

		highlighter:
		{
			show: true
		},

	});
}

///////////////////////////////////////////////////////////////////////

function graph_earnings_ready(data)
{
	graph_earnings_init(data);
}

function graph_earnings_refresh()
{
	var url = "/site/graph_earnings_results?address=$username";
	$.get(url, '', graph_earnings_ready);
}

function graph_earnings_init(data)
{
	$('#graph_earnings_results').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_earnings_results', t,
	{
	//	title: '<b></b>',
		stackSeries: true,
		axes: {
			xaxis: {
				tickInterval: 7200,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				min: 0,
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults:
		{
			markerOptions: { style: 'none' }
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

///////////////////////////////////////////////////////////////////////////////////////////////////////////

function main_wallet_tx()
{
	var w = window.open("/site/tx?address=$username", "yaamp_tx",
		"width=800,height=600,location=no,menubar=no,resizable=yes,status=yes,toolbar=no");
}

function drop_cookie(el)
{
	var addr = $(el).closest('tr').find('td a.address').text();
	window.location.href = '?address={$address}&drop=' + addr;
}

</script>


END;

