<?php

if (!$coin) $this->goback();

require dirname(__FILE__).'/../../ui/lib/pageheader.php';

$this->pageTitle = 'Peers - '.$coin->name;

$remote = new WalletRPC($coin);
$info = $remote->getinfo();

$addnode = array();
$localheight = arraySafeVal($info, 'blocks');

$list = $remote->getpeerinfo();

if(!empty($list))
foreach($list as $peer)
{
    $node = arraySafeVal($peer,'addr');
    if (strstr($node,'127.0.0.1')) continue;
    if (strstr($node,'192.168.')) continue;
    if (strstr($node,'yiimp')) continue;

    $addnode[] = ($coin->rpcencoding=='DCR' ? 'addpeer=' : 'addnode=') . $node;
}

asort($addnode);

?>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-dark text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">
            <img width="20" src="<?= htmlspecialchars($coin->image) ?>" class="rounded-circle me-2 shadow-sm">
            <?= htmlspecialchars($coin->name) ?> <span class="text-muted small fw-normal">Peer Nodes</span>
        </h5>
        <?php if ($localheight): ?>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">
            Height: <?= number_format($localheight) ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="card-body p-4">
        <?php if (empty($addnode)): ?>
        <div class="text-center py-4 text-muted">
            <i class="fa fa-network-wired fa-2x mb-2 opacity-50"></i>
            <p class="mb-0">No peer nodes found.</p>
        </div>
        <?php else: ?>
        <p class="small text-muted mb-3">Add these lines to your <code><?= $coin->rpcencoding=='DCR' ? 'dcrctl.conf' : 'coin.conf' ?></code> to connect to known peers:</p>
        <div class="bg-dark text-light rounded-3 p-4 font-monospace" style="font-size: 0.8rem; white-space: pre-wrap; word-break: break-all;"><?= htmlspecialchars(implode("\n", $addnode)) ?></div>
        <div class="mt-3 d-flex gap-2">
            <span class="badge bg-secondary"><?= count($addnode) ?> peers</span>
        </div>
        <?php endif; ?>
    </div>
</div>
