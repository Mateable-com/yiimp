<?php

// Functions commonly used in admin pages

function getAdminSideBarLinks()
{
    $links = '<div class="btn-group btn-group-sm mb-4 shadow-sm rounded-pill overflow-hidden border">';
    $items = [
        ['url' => '/admin', 'name' => 'Dashboard', 'icon' => 'tachometer-alt'],
        ['url' => '/admin/coinwallets', 'name' => 'Wallets', 'icon' => 'wallet'],
        ['url' => '/admin/user', 'name' => 'Users', 'icon' => 'users'],
        ['url' => '/admin/worker', 'name' => 'Workers', 'icon' => 'microchip'],
        ['url' => '/admin/earning', 'name' => 'Earnings', 'icon' => 'hand-holding-usd'],
        ['url' => '/admin/payments', 'name' => 'Payments', 'icon' => 'money-bill-wave'],
        ['url' => '/admin/exchange', 'name' => 'Exchanges', 'icon' => 'exchange-alt'],
        ['url' => '/admin/monsters', 'name' => 'Monsters', 'icon' => 'ghost'],
    ];

    foreach ($items as $item) {
        $active = (strpos($_SERVER['REQUEST_URI'], $item['url']) !== false) ? 'active' : 'btn-light';
        $links .= '<a href="' . $item['url'] . '" class="btn ' . $active . ' px-3 border-0"><i class="fa fa-' . $item['icon'] . ' me-1 opacity-75"></i>' . $item['name'] . '</a>';
    }
    $links .= '</div>';
    return $links;
}

// shared by wallet "tabs", to move in another php file...
function getAdminWalletLinks($coin, $info=NULL, $src='wallet')
{
    $html = '<div class="d-flex flex-wrap gap-2 mb-4">';
    
    // Core Actions
    $html .= CHtml::link('<i class="fa fa-edit me-1"></i>Properties', '/admin/coinupdate?id='.$coin->id, ['class'=>'btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm']);
    
	if($info) {
		$html .= $coin->createExplorerLink('<i class="fa fa-search me-1"></i>Explorer', [], ['class'=>'btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold']);
		$html .= CHtml::link('<i class="fa fa-network-wired me-1"></i>Peers', '/admin/coinpeers?id='.$coin->id, ['class'=>'btn btn-sm btn-outline-dark rounded-pill px-3 fw-bold']);
		if (YAAMP_ADMIN_WEBCONSOLE)
			$html .= CHtml::link('<i class="fa fa-terminal me-1"></i>Console', '/admin/coinconsole?id='.$coin->id, ['class'=>'btn btn-sm btn-dark rounded-pill px-3 fw-bold']);
		$html .= CHtml::link('<i class="fa fa-bolt me-1"></i>Triggers', '/admin/cointriggers?id='.$coin->id, ['class'=>'btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold']);
		if ($src != 'wallet')
			$html .= CHtml::link('<i class="fa fa-coins me-1"></i>'.$coin->symbol, '/admin/coin?id='.$coin->id, ['class'=>'btn btn-sm btn-info rounded-pill px-3 fw-bold']);
	}

    // Status Toggles
	if(!$info && $coin->enable)
		$html .= CHtml::link('<i class="fa fa-stop-circle me-1 text-danger"></i>Stop', '/admin/stopcoin?id='.$coin->id, ['class'=>'btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold']);

	if($coin->auto_ready)
		$html .= CHtml::link('<i class="fa fa-robot me-1"></i>Unset Auto', '/admin/coinunsetauto?id='.$coin->id, ['class'=>'btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold']);
	else
		$html .= CHtml::link('<i class="fa fa-robot me-1"></i>Set Auto', '/admin/coinsetauto?id='.$coin->id, ['class'=>'btn btn-sm btn-outline-success rounded-pill px-3 fw-bold']);

    // Social/External Links
    $html .= '<div class="ms-auto d-flex gap-2 align-items-center">';
	if(!empty($coin->link_bitcointalk))
		$html .= CHtml::link('<i class="fab fa-bitcoin text-warning fs-5"></i>', $coin->link_bitcointalk, array('target'=>'_blank', 'title'=>'Bitcointalk Forum'));
	if(!empty($coin->link_github))
		$html .= CHtml::link('<i class="fab fa-github text-dark fs-5"></i>', $coin->link_github, array('target'=>'_blank', 'title'=>'GitHub Source'));
	if(!empty($coin->link_site))
		$html .= CHtml::link('<i class="fa fa-globe text-primary fs-5"></i>', $coin->link_site, array('target'=>'_blank', 'title'=>'Official Website'));
    $html .= '</div>';

	$html .= '</div>';
	return $html;
}

/////////////////////////////////////////////////////////////////////////////////////////////

// Check if $IP is in $CIDR range
function ipCIDRCheck($IP, $CIDR)
{
	list($net, $mask) = explode('/', $CIDR);

	$ip_net = ip2long($net);
	$ip_mask = ~((1 << (32 - $mask)) - 1);

	$ip_ip = ip2long($IP);
	$ip_ip_net = $ip_ip & $ip_mask;

	return ($ip_ip_net === $ip_net);
}

function isAdminIP($ip)
{
	foreach(explode(',', YAAMP_ADMIN_IP) as $range)
	{
		if (strpos($range, '/')) {
			if(ipCIDRCheck($ip, $range) === true) return true;
		} else if ($range === $ip) {
			return true;
		}
	}
	return false;
}

/////////////////////////////////////////////////////////////////////////////////////////////
