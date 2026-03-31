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

$pageTitle = empty($this->pageTitle) ? YAAMP_SITE_NAME : YAAMP_SITE_NAME." - ".$this->pageTitle;

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
    echo '    <a class="navbar-brand fw-bold text-primary" href="/">' . YAAMP_SITE_NAME . '</a>';
    echo '    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">';
    echo '      <span class="navbar-toggler-icon"></span>';
    echo '    </button>';
    echo '    <div class="collapse navbar-collapse" id="navbarNav">';
    echo '      <ul class="navbar-nav me-auto mb-2 mb-lg-0">';

    $items = [
        ['url' => '/', 'name' => 'Home', 'active' => (controller()->id == 'site' && $action == 'index' && !$ad)],
        ['url' => '/site/mining', 'name' => 'Pool', 'active' => ($action == 'mining')],
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

    if (YIIMP_ADMIN_LOGIN && controller()->admin) {
        if (isAdminIP($_SERVER['REMOTE_ADDR']) === false) {
            debuglog("admin {$_SERVER['REMOTE_ADDR']}");
        }
        echo '<li class="nav-item dropdown ms-lg-3">';
        echo '  <a class="nav-link dropdown-toggle text-warning border border-warning rounded px-3" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
        echo '    <i class="fa fa-cog me-1"></i> Admin';
        echo '  </a>';
        echo '  <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="adminDropdown">';
        echo '    <li><a class="dropdown-item" href="/coin"><i class="fa fa-coins me-2"></i>Coins</a></li>';
        echo '    <li><a class="dropdown-item" href="/admin/dashboard"><i class="fa fa-tachometer-alt me-2"></i>Dashboard</a></li>';
        echo '    <li><a class="dropdown-item" href="/admin/coinwallets"><i class="fa fa-wallet me-2"></i>Wallets</a></li>';
        if (YAAMP_RENTAL) echo '    <li><a class="dropdown-item" href="/renting/admin"><i class="fa fa-tasks me-2"></i>Jobs</a></li>';
        if (YAAMP_ALLOW_EXCHANGE) echo '    <li><a class="dropdown-item" href="/trading"><i class="fa fa-exchange-alt me-2"></i>Trading</a></li>';
        if (YAAMP_USE_NICEHASH_API) echo '    <li><a class="dropdown-item" href="/nicehash"><i class="fa fa-microchip me-2 text-info"></i>Nicehash</a></li>';
        echo '    <li><hr class="dropdown-divider"></li>';
        echo '    <li><a class="dropdown-item text-danger" href="/admin/logout"><i class="fa fa-sign-out-alt me-2"></i>Logout</a></li>';
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
	echo '<footer class="footer mt-auto py-4 bg-dark text-white border-top border-secondary border-opacity-25">';
    echo '  <div class="container text-center">';
    echo '    <div class="row">';
    echo '      <div class="col-md-6 text-md-start mb-3 mb-md-0">';
    echo "        <span class='text-muted small'>&copy; $year " . YAAMP_SITE_NAME . " - </span>";
    echo '        <a href="https://github.com/Kudaraidee/yiimp" class="text-info text-decoration-none small"><i class="fab fa-github me-1"></i>Open source Project</a>';
    echo '      </div>';
    echo '      <div class="col-md-6 text-md-end">';
    echo '        <span class="text-muted small">Powered by <a href="/" class="text-primary text-decoration-none fw-bold">' . YAAMP_SITE_NAME . '</a></span>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
	echo '</footer>';
}


