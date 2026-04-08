<?php

if (!$coin) $this->goback();
$this->pageTitle = 'Triggers - '.$coin->symbol;

$remote = new WalletRPC($coin);
$info = $remote->getinfo();

echo '<div class="container-fluid py-4">';

// --- Trigger Hero Header ---
echo '<div class="card shadow-sm border-0 mb-4 bg-dark text-white rounded-3">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-4 shadow-sm">';
echo '      <i class="fa fa-bolt fa-2x text-warning"></i>';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h3 class="mb-0 fw-bold">'.$coin->name.' <span class="text-primary fs-5">Automation Triggers</span></h3>';
echo '      <div class="small text-muted">Set automated rules for balance alerts, RPC commands, and system notifications.</div>';
echo '    </div>';
echo '    <div class="ms-auto">';
echo '      <a href="/admin/coin?id='.$coin->id.'" class="btn btn-outline-light btn-sm rounded-pill fw-bold px-3"><i class="fa fa-arrow-left me-1"></i> Back</a>';
echo '    </div>';
echo '  </div>';
echo '</div>';

if (!$info) {
	echo '<div class="alert alert-danger shadow-sm border-0"><i class="fa fa-exclamation-triangle me-2"></i><b>Wallet Error:</b> '.$remote->error.'</div>';
    echo '</div>'; return;
}

// --- Active Rules Card ---
echo '<div class="card shadow-sm border-0 rounded-3 mb-4">';
echo '  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">';
echo '    <h5 class="mb-0 fw-bold small text-uppercase text-muted"><i class="fa fa-list-check me-2"></i>Active Monitoring Rules</h5>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4">Type</th>';
echo '            <th>Condition</th>';
echo '            <th class="text-center">Value</th>';
echo '            <th width="40%">Description / Command</th>';
echo '            <th class="text-center">Status</th>';
echo '            <th>Last Checked</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$notifications = getdbolist('db_notifications', "idcoin={$coin->id}");
if(empty($notifications)) {
    echo '<tr><td colspan="7" class="py-5 text-center text-muted">No automation rules configured for this coin.</td></tr>';
} else {
    foreach($notifications as $rule)
    {
        $is_triggered = ($rule->lasttriggered && $rule->lasttriggered == $rule->lastchecked);
        $type_badge = ($rule->notifytype == 'email') ? 'bg-primary' : (($rule->notifytype == 'rpc') ? 'bg-info text-dark' : 'bg-secondary');

        echo '<tr>';
        echo '<td class="ps-4"><span class="badge '.$type_badge.' text-uppercase">'.$rule->notifytype.'</span></td>';
        echo '<td class="fw-bold text-dark">'.$rule->conditiontype.'</td>';
        echo '<td class="text-center fw-bold">'.$rule->conditionvalue.'</td>';
        echo '<td>';
        echo '  <div class="fw-bold">'.$rule->description.'</div>';
        if($rule->notifycmd) echo '  <div class="font-monospace text-muted" style="font-size: 0.7rem;">'.$rule->notifycmd.'</div>';
        echo '</td>';
        
        echo '<td class="text-center">';
        if($is_triggered) echo '<span class="badge bg-danger pulse-danger">TRIGGERED</span>';
        else echo '<span class="badge bg-light text-muted">Idle</span>';
        echo '</td>';

        echo '<td class="text-muted small">'.datetoa2($rule->lastchecked).' ago</td>';

        echo '<td class="text-end pe-4"><div class="btn-group">';
        if($is_triggered) echo '<a href="/admin/cointriggerReset?id='.$rule->id.'" class="btn btn-xs btn-success py-0 px-2 fw-bold" title="Reset Trigger">RESET</a>';
        
        if ($rule->enabled)
            echo '<a href="/admin/cointriggerEnable?id='.$rule->id.'&en=0" class="btn btn-xs btn-outline-warning py-0 px-2" title="Disable"><i class="fa fa-pause"></i></a>';
        else
            echo '<a href="/admin/cointriggerEnable?id='.$rule->id.'&en=1" class="btn btn-xs btn-outline-success py-0 px-2" title="Enable"><i class="fa fa-play"></i></a>';
        
        echo '<a href="/admin/cointriggerDel?id='.$rule->id.'" class="btn btn-xs btn-outline-danger py-0 px-2" onclick="return confirm(\'Delete rule?\')"><i class="fa fa-trash"></i></a>';
        echo '</div></td></tr>';
    }
}
echo '        </tbody>';
echo '      </table></div></div></div>';

// --- Add Rule & Help Grid ---
echo '<div class="row">';
echo '  <div class="col-lg-7">';
echo '    <div class="card shadow-sm border-0 rounded-3 mb-4">';
echo '      <div class="card-header bg-primary text-white py-3 border-0"><h5 class="mb-0 fw-bold small text-uppercase">Add Automation Rule</h5></div>';
echo '      <div class="card-body p-4">';
echo '        <form action="/admin/cointriggerAdd?id={$coin->id}" method="post" class="row g-3">';
echo '          <input type="hidden" name="idcoin" value="'.$coin->id.'">';
echo '          <div class="col-md-4"><label class="form-label fw-bold small text-muted">Notify Type</label><select name="notifytype" class="form-select border-2"><option value="email">Email Notification</option><option value="rpc">RPC Command</option><option value="system">System Shell</option></select></div>';
echo '          <div class="col-md-5"><label class="form-label fw-bold small text-muted">Condition Field</label><input type="text" name="conditiontype" class="form-control border-2" placeholder="e.g. balance >"></div>';
echo '          <div class="col-md-3"><label class="form-label fw-bold small text-muted">Value</label><input type="text" name="conditionvalue" class="form-control border-2" placeholder="0.01"></div>';
echo '          <div class="col-12"><label class="form-label fw-bold small text-muted">Command / Email Address</label><input type="text" name="notifycmd" class="form-control border-2 font-monospace" placeholder="email@example.com or wallet-command"></div>';
echo '          <div class="col-12"><label class="form-label fw-bold small text-muted">Rule Description</label><input type="text" name="description" class="form-control border-2" placeholder="Friendly name for this trigger"></div>';
echo '          <div class="col-12 text-end mt-4"><button type="submit" class="btn btn-primary fw-bold px-5 rounded-pill shadow-sm">CREATE TRIGGER</button></div>';
echo '        </form>';
echo '      </div>';
echo '    </div>';
echo '  </div>';

echo '  <div class="col-lg-5">';
echo '    <div class="card shadow-sm border-0 rounded-3 bg-light">';
echo '      <div class="card-header py-3 bg-dark text-white border-0"><h5 class="mb-0 fw-bold small text-uppercase">Command Variables</h5></div>';
echo '      <div class="card-body p-0">';
echo '        <div class="list-group list-group-flush">';
$vars = ['$X'=>'Current Value', '$F'=>'Condition Field', '$T'=>'Condition Type', '$V'=>'Ref Value', '$SYM'=>'Coin Symbol', '$N'=>'Coin Name', '$A'=>'Wallet Address'];
foreach($vars as $k=>$v) {
    echo '<div class="list-group-item d-flex justify-content-between bg-transparent py-2 px-4 border-bottom border-secondary border-opacity-10"><b class="text-primary font-monospace">'.$k.'</b><span class="text-muted small">'.$v.'</span></div>';
}
echo '        </div></div></div></div></div>';

echo '</div>'; // close container
?>