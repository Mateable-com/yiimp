<?php

require('misc.php');
echo <<<END

<!doctype html>
<!--[if IE 7 ]>		 <html class="no-js ie ie7 lte7 lte8 lte9" lang="en-US"> <![endif]-->
<!--[if IE 8 ]>		 <html class="no-js ie ie8 lte8 lte9" lang="en-US"> <![endif]-->
<!--[if IE 9 ]>		 <html class="no-js ie ie9 lte9>" lang="en-US"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html class="no-js" lang="en-US"> <!--<![endif]-->

<head>

<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="description" content="Yii mining pools for alternative crypto currencies">
<meta name="keywords" content="anonymous,mining,pool,maxcoin,bitcoin,altcoin,auto,switch,exchange,profit,decred,scrypt,x11,x13,x14,x15,lbry,lyra2re,neoscrypt,sha256,quark,skein2">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

END;

$pageTitle = empty($this->pageTitle) ? settings_get('site_name', YAAMP_SITE_NAME) : settings_get('site_name', YAAMP_SITE_NAME)." - ".$this->pageTitle;

echo '<title>'.$pageTitle.'</title>';

echo CHtml::cssFile("/extensions/jquery/themes/ui-lightness/jquery-ui.css");
echo CHtml::cssFile('/yaamp/ui/css/main.css');
echo CHtml::cssFile('/yaamp/ui/css/table.css');

//echo CHtml::scriptFile('/extensions/jquery/js/jquery-1.8.3-dev.js');
//echo CHtml::scriptFile('/extensions/jquery/js/jquery-ui-1.9.1.custom.min.js');

$cs = app()->getClientScript();
$cs->registerCoreScript('jquery.ui');
//$cs->registerScriptFile('/yaamp/ui/js/jquery.tablesorter.js', CClientScript::POS_END);

echo CHtml::scriptFile('/yaamp/ui/js/jquery.tablesorter.js');

// if(!controller()->admin)
// echo <<<end
// <script>
// (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
// (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
// m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
// })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

// ga('create', 'UA-58136019-1', 'auto');
// ga('send', 'pageview');

// $(document).ajaxSuccess(function(){ga('send', 'pageview');});

// </script>
// end;

echo "</head>";

///////////////////////////////////////////////////////////////

echo '<body class="page bg-light">';
echo '<a href="/site/mainbtc" style="display: none;">main</a>';

showPageHeader();
showPageContent($content);
showPageFooter();

echo "</body></html>";
return;

/////////////////////////////////////////////////////////////////////

function showItemHeader($selected, $url, $name)
{
	if($selected) $selected_text = "class='selected'";
	else $selected_text = '';

	echo "<span><a $selected_text href='$url'>$name</a></span>";
	echo "&nbsp;";
}

function showPageHeader()
{
    $action = controller()->action->id;
    $wallet = user()->getState('yaamp-wallet');
    $ad = isset($_GET['address']);

    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">';
    echo '  <div class="container-fluid">';
    echo '    <a class="navbar-brand fw-bold text-primary" href="/">' . settings_get('site_name', YAAMP_SITE_NAME) . '</a>';
    echo '    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">';
    echo '      <span class="navbar-toggler-icon"></span>';
    echo '    </button>';
    echo '    <div class="collapse navbar-collapse" id="navbarNav">';
    echo '      <ul class="navbar-nav me-auto mb-2 mb-lg-0">';

    $items = [
        ['url' => '/', 'name' => 'Home', 'active' => (controller()->id == 'site' && $action == 'index' && !$ad)],
        ['url' => '/site/mining', 'name' => 'Pool', 'active' => ($action == 'mining')],
        ['url' => '/site/coins', 'name' => 'Coins', 'active' => ($action == 'coins')],
        ['url' => "/?address=$wallet", 'name' => 'Wallet', 'active' => (controller()->id == 'site' && ($action == 'index' || $action == 'wallet') && $ad)],
        ['url' => '/stats', 'name' => 'Graphs', 'active' => (controller()->id == 'stats')],
        ['url' => '/site/miners', 'name' => 'Miners', 'active' => ($action == 'miners')],
        ['url' => '/site/api', 'name' => 'API', 'active' => (controller()->id == 'api')],
    ];

    if (YIIMP_PUBLIC_EXPLORER) $items[] = ['url' => '/explorer', 'name' => 'Explorers', 'active' => (controller()->id == 'explorer')];
    if (YIIMP_PUBLIC_BENCHMARK) $items[] = ['url' => '/bench', 'name' => 'Benchs', 'active' => (controller()->id == 'bench')];
    if (YAAMP_RENTAL) $items[] = ['url' => '/renting', 'name' => 'Rental', 'active' => (controller()->id == 'renting')];

    foreach ($items as $item) {
        $activeClass = $item['active'] ? 'active bg-primary' : '';
        echo '<li class="nav-item">';
        echo '  <a class="nav-link rounded px-3 ' . $activeClass . '" href="' . $item['url'] . '">' . $item['name'] . '</a>';
        echo '</li>';
    }

    if (controller()->admin && user()->getState('yaamp_admin')) {
        echo '<li class="nav-item dropdown ms-lg-3">';
        echo '  <a class="nav-link dropdown-toggle text-primary fw-bold border border-primary rounded px-3" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        echo '    <i class="fa fa-user-shield me-1"></i> Admin Panel';
        echo '  </a>';
        echo '  <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2" aria-labelledby="adminDropdown" style="min-width: 220px; border-radius: 12px;">';
        echo '    <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted">Management</h6></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin"><i class="fa fa-tachometer-alt me-2 text-primary opacity-75" style="width:20px"></i>Dashboard</a></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/coinWallets"><i class="fa fa-coins me-2 text-success opacity-75" style="width:20px"></i>Manage Coins</a></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/user"><i class="fa fa-users me-2 text-info opacity-75" style="width:20px"></i>User Manager</a></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/payments"><i class="fa fa-money-bill-wave me-2 text-success opacity-75" style="width:20px"></i>Payments</a></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/earning"><i class="fa fa-hand-holding-usd me-2 text-primary opacity-75" style="width:20px"></i>Earnings</a></li>';
        echo '    <li><hr class="dropdown-divider mx-2"></li>';
        echo '    <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted">Infrastructure</h6></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/exchange"><i class="fa fa-exchange-alt me-2 text-warning opacity-75" style="width:20px"></i>Exchanges</a></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/monsters"><i class="fa fa-ghost me-2 text-danger opacity-75" style="width:20px"></i>Big Miners</a></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/botnets"><i class="fa fa-spider me-2 text-dark opacity-75" style="width:20px"></i>Botnets</a></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/connections"><i class="fa fa-plug me-2 text-info opacity-75" style="width:20px"></i>Connections</a></li>';
        echo '    <li><a class="dropdown-item py-2" href="/admin/memcached"><i class="fa fa-memory me-2 text-secondary opacity-75" style="width:20px"></i>Memcached</a></li>';
        echo '    <li><hr class="dropdown-divider mx-2"></li>';
        echo '    <li><a class="dropdown-item text-danger fw-bold py-2" href="/admin/logout"><i class="fa fa-sign-out-alt me-2"></i>Logout</a></li>';
        echo '  </ul>';
        echo '</li>';
    }

    echo '      </ul>';

    $mining = getdbosql('db_mining');
    $nextpayment = date('H:i T', $mining->last_payout + YAAMP_PAYMENTS_FREQ);
    $eta = ($mining->last_payout + YAAMP_PAYMENTS_FREQ) - time();
    $eta_mn = round($eta / 60);

    echo '      <div class="navbar-text ms-auto text-light small d-none d-lg-block bg-secondary bg-opacity-25 rounded px-2 py-1">';
    echo '        <i class="fa fa-clock me-1 text-info"></i> Next Payout: <span class="fw-bold">' . $nextpayment . '</span> (' . $eta_mn . ' min)';
    echo '      </div>';

    echo '    </div>';
    echo '  </div>';
    echo '</nav>';
}

function showPageFooter()
{
	$year = date("Y", time());
	echo '<footer class="footer mt-auto py-5 bg-dark text-white border-top border-secondary border-opacity-25">';
    echo '  <div class="container">';
    echo '    <div class="row g-4">';
    
    // Column 1: Brand & About
    echo '      <div class="col-lg-4">';
    echo '        <h5 class="fw-bold mb-3 text-primary"><i class="fa fa-microchip me-2"></i>' . settings_get('site_name', YAAMP_SITE_NAME) . '</h5>';
    echo '        <p class="text-muted small pe-lg-5">A high-performance mining pool infrastructure designed for stability, security, and maximum profitability. Built for the future of crypto.</p>';
    echo '        <div class="d-flex gap-3 mt-4">';
    $tw = settings_get('link_twitter');
    if($tw) echo '          <a href="'.$tw.'" class="text-muted" target="_blank"><i class="fab fa-twitter fs-5"></i></a>';
    $ds = settings_get('link_discord');
    if($ds) echo '          <a href="'.$ds.'" class="text-muted" target="_blank"><i class="fab fa-discord fs-5"></i></a>';
    $gh = settings_get('link_github', 'https://github.com/Kudaraidee/yiimp');
    if($gh) echo '          <a href="'.$gh.'" class="text-muted" target="_blank"><i class="fab fa-github fs-5"></i></a>';
    echo '        </div>';
    echo '      </div>';

    // Column 2: Quick Links
    echo '      <div class="col-6 col-lg-2">';
    echo '        <h6 class="text-uppercase fw-bold mb-3 small" style="letter-spacing: 1px;">Mining</h6>';
    echo '        <ul class="list-unstyled small">';
    echo '          <li class="mb-2"><a href="/site/mining" class="text-muted text-decoration-none hover-white">Active Pools</a></li>';
    echo '          <li class="mb-2"><a href="/site/coins" class="text-muted text-decoration-none hover-white">Supported Coins</a></li>';
    echo '          <li class="mb-2"><a href="/stats" class="text-muted text-decoration-none hover-white">Network Stats</a></li>';
    echo '          <li class="mb-2"><a href="/bench" class="text-muted text-decoration-none hover-white">Benchmarks</a></li>';
    echo '          <li class="mb-2"><a href="/explorer" class="text-muted text-decoration-none hover-white">Explorers</a></li>';
    echo '        </ul>';
    echo '      </div>';

    // Column 3: Support
    echo '      <div class="col-6 col-lg-2">';
    echo '        <h6 class="text-uppercase fw-bold mb-3 small" style="letter-spacing: 1px;">Support</h6>';
    echo '        <ul class="list-unstyled small">';
    echo '          <li class="mb-2"><a href="/site/about" class="text-muted text-decoration-none hover-white">About Us</a></li>';
    echo '          <li class="mb-2"><a href="/site/terms" class="text-muted text-decoration-none hover-white">Terms of Service</a></li>';
    echo '          <li class="mb-2"><a href="https://bitcointalk.org" target="_blank" class="text-muted text-decoration-none hover-white">BitcoinTalk</a></li>';
    echo '          <li class="mb-2"><a href="mailto:' . YAAMP_ADMIN_EMAIL . '" class="text-muted text-decoration-none hover-white">Contact Admin</a></li>';
    echo '        </ul>';
    echo '      </div>';

    // Column 4: Next Payout (Mobile Friendly)
    $mining = getdbosql('db_mining');
    $nextpayment = date('H:i T', $mining->last_payout + YAAMP_PAYMENTS_FREQ);
    echo '      <div class="col-lg-4 text-lg-end">';
    echo '        <div class="bg-secondary bg-opacity-10 p-4 rounded-3 d-inline-block text-start w-100">';
    echo '          <h6 class="text-uppercase fw-bold mb-2 small text-info"><i class="fa fa-clock me-2"></i>Automated Payouts</h6>';
    echo '          <p class="mb-0 small text-muted">The next payment cycle is scheduled for <span class="text-white fw-bold">'.$nextpayment.'</span>. Ensure your balance meets the minimum threshold.</p>';
    echo '        </div>';
    echo '      </div>';

    echo '    </div>'; // close row

    echo '    <hr class="my-5 border-secondary border-opacity-25">';
    
    echo '    <div class="row align-items-center">';
    echo '      <div class="col-md-6 text-center text-md-start">';
    echo '        <span class="text-muted small">&copy; ' . $year . ' <b class="text-white">' . settings_get('site_name', YAAMP_SITE_NAME) . '</b>. All rights reserved.</span>';
    echo '      </div>';
    echo '      <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">';
    echo '        <span class="text-muted small">Modernized by <a href="/" class="text-primary text-decoration-none fw-bold">YiiMP 2026 Engine</a></span>';
    echo '      </div>';
    echo '    </div>';

    echo '  </div>';
	echo '</footer>';
    
    echo '<style>
        .hover-white:hover { color: #fff !important; }
        footer a { transition: color 0.2s ease; }
        footer .text-muted { color: #adb5bd !important; }
        footer h5, footer h6 { color: #ffffff !important; }
    </style>';
}


