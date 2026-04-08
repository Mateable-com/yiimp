<?php

$last = dboscalar("select max(last) from connections");
$list = getdbolist('db_connections', "1 order by id desc");

echo '<div class="card shadow-sm border-0 mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-plug me-2 text-primary"></i>Active Database Connections</h5>';
echo '    <span class="badge bg-primary px-3 fw-bold">Total: '.count($list).'</span>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-4" width="50">ID</th>';
echo '            <th>DB User</th>';
echo '            <th>Client Host</th>';
echo '            <th>Database</th>';
echo '            <th class="text-center">Idle Time</th>';
echo '            <th>Created At</th>';
echo '            <th>Last Activity</th>';
echo '            <th class="text-end pe-4">Current</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

foreach($list as $conn)
{
    $is_latest = ($conn->last == $last);
	$row_class = $is_latest ? 'table-success bg-opacity-10' : '';

	echo '<tr class="'.$row_class.'">';
	echo '<td class="ps-4 text-muted small">'.$conn->id.'</td>';
	echo '<td class="fw-bold">'.$conn->user.'</td>';
	echo '<td class="font-monospace text-muted" style="font-size: 0.75rem;">'.$conn->host.'</td>';
	echo '<td><span class="badge bg-light text-dark border">'.$conn->db.'</span></td>';
	echo '<td class="text-center"><span class="badge '.($conn->idle > 300 ? 'bg-warning text-dark' : 'bg-light text-muted').'">'.sectoa($conn->idle).'</span></td>';
	echo '<td class="text-muted small">'.datetoa2($conn->created).' ago</td>';
	echo '<td class="small">'.datetoa2($conn->last).' ago</td>';
    
    echo '<td class="text-end pe-4">';
    if($is_latest) echo '<i class="fa fa-circle text-success" style="font-size: 0.6rem;" title="Latest Activity"></i>';
    else echo '<i class="fa fa-circle text-muted opacity-25" style="font-size: 0.6rem;"></i>';
    echo '</td>';

	echo "</tr>";
}

echo "        </tbody>";
echo "      </table></div></div></div>";
?>