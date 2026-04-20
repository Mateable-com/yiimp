<?php

$algo = user()->getState('yaamp-algo');

if ($algo == 'all')
    $list = getdbolist('db_coins', "enable order by index_avg desc");
else
    $list = getdbolist('db_coins', "enable and algo=:algo order by index_avg desc", array(':algo' => $algo));

echo '<div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-info-circle me-2 text-warning"></i>Coin Information <span class="text-warning small text-uppercase">('.$algo.')</span></h5>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" style="width: 40px;"></th>';
echo '            <th>Name</th>';
echo '            <th>Links</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

foreach($list as $coin)
{
    $id = $coin->id;
    $name = htmlspecialchars(substr($coin->name, 0, 30));

    echo '<tr>';
    echo '  <td class="ps-4"><img width="18" src="'.htmlspecialchars($coin->image).'" class="rounded-circle shadow-sm"></td>';
    echo '  <td><a href="/site/block?id='.$id.'" class="fw-bold text-decoration-none text-dark">'.$name.'</a></td>';
    echo '  <td>';

    $links = array();
    if($coin->link_bitcointalk) $links[] = '<a href="'.htmlspecialchars($coin->link_bitcointalk).'" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 me-1 mb-1"><img width="12" src="/images/bitcointalk.webp" class="me-1">Bitcointalk</a>';
    if($coin->link_site)        $links[] = '<a href="'.htmlspecialchars($coin->link_site).'" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 me-1 mb-1"><img width="12" src="/images/home.webp" class="me-1">Website</a>';
    if($coin->link_discord)     $links[] = '<a href="'.htmlspecialchars($coin->link_discord).'" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 me-1 mb-1"><img width="12" src="/images/discord.png" class="me-1">Discord</a>';
    if($coin->link_explorer)    $links[] = '<a href="'.htmlspecialchars($coin->link_explorer).'" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 me-1 mb-1"><img width="12" src="/images/blockchain.webp" class="me-1">Explorer</a>';
    if($coin->link_github)      $links[] = '<a href="'.htmlspecialchars($coin->link_github).'" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 me-1 mb-1"><img width="12" src="/images/Github.png" class="me-1">GitHub</a>';
    if($coin->link_exchange)    $links[] = '<a href="'.htmlspecialchars($coin->link_exchange).'" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 me-1 mb-1"><img width="12" src="/images/exchange.png" class="me-1">Exchange</a>';
    if($coin->link_twitter)     $links[] = '<a href="'.htmlspecialchars($coin->link_twitter).'" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-2 me-1 mb-1"><img width="12" src="/images/Twitter.png" class="me-1">Twitter</a>';
    $links[] = '<a href="/explorer/peers?id='.$id.'" class="btn btn-outline-primary btn-sm py-0 px-2 me-1 mb-1"><img width="12" src="/images/nodes16.png" class="me-1">Nodes</a>';

    echo implode('', $links);
    echo '  </td>';
    echo '</tr>';
}

echo '        </tbody>';
echo '      </table></div></div></div>';
