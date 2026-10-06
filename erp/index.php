<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$counts=[];
$tables=[
 'Theatres'=>'erp_theatres','Screens'=>'erp_screens','Movies'=>'erp_movies','Shows'=>'erp_shows',
 'ERP Bookings'=>'erp_bookings','Products'=>'erp_products','Employees'=>'erp_employees','Maintenance'=>'erp_maintenance_requests'
];
foreach($tables as $label=>$table){
    try { $counts[$label]=(int)$pdo->query("SELECT COUNT(*) FROM ".$table)->fetchColumn(); }
    catch(Throwable $e){ $counts[$label]=null; }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Smart Theatre ERP</title>
<style>
body{margin:0;background:#07131d;color:#f6fbff;font-family:Arial,sans-serif}main{max-width:1200px;margin:auto;padding:32px}
h1{margin-bottom:6px}.muted{color:#a9bdca}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-top:28px}
.card{background:#10273a;border:1px solid #28465b;border-radius:14px;padding:20px}.value{font-size:30px;font-weight:700;margin-top:10px}
.section{margin-top:34px;background:#0c1e2c;border-radius:14px;padding:24px}ul{line-height:1.9}
</style></head>
<body><main>
<h1>🎬 Smart Theatre ERP</h1><p class="muted">ERP foundation connected to the existing FALCONS Theater database.</p>
<div class="grid"><?php foreach($counts as $label=>$value): ?><div class="card"><div class="muted"><?=htmlspecialchars($label)?></div><div class="value"><?=$value===null?'—':$value?></div></div><?php endforeach; ?></div>
<div class="section"><h2>ERP modules</h2><ul>
<li>Identity & Access</li><li>Theatre / Branch / Screen / Seat Management</li><li>Movies & Shows</li>
<li>Bookings, Payments, Tickets & QR</li><li>Food & Beverage POS</li><li>Inventory & Procurement</li>
<li>HR & Employees</li><li>Assets & Maintenance</li><li>Finance</li><li>Reports & Business Intelligence</li>
<li>AI Assistant & Predictions</li><li>Security & Audit</li>
</ul></div></main></body></html>
