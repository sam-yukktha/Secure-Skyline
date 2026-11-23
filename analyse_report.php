<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';require_login();
$recordId=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$recordId||!can_access_record($recordId)){http_response_code(403);flash('error','You are not authorized to analyze that medical record.');redirect(is_role('owner')?'view_health.php':'view_response.php');}
$stmt=db()->prepare('SELECT * FROM medical_records WHERE id=?');$stmt->execute([$recordId]);$record=$stmt->fetch();
if(!$record){http_response_code(404);flash('error','Medical record not found.');redirect('view_health.php');}
$analysis=health_analysis($record);$pageTitle='Rule-Based Health Analysis';require __DIR__.'/header.php';
?>
<h1>Rule-Based Demonstration</h1><p class="lead">A transparent threshold summary for <?=e($record['patient_label'])?>. No trained machine-learning model is used.</p>
<div class="disclaimer"><strong>For educational demonstration only.</strong> This system is not intended to provide medical diagnosis or replace professional medical advice.</div>
<section class="card"><div class="table-wrap"><table><thead><tr><th>Measure</th><th>Recorded value</th><th>Demonstration interpretation</th></tr></thead><tbody><?php foreach($analysis as $item):?><tr><td><?=e($item['label'])?></td><td><?=e($item['value'])?></td><td><span class="badge badge-<?=e($item['level'])?>"><?=e($item['status'])?></span></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="card"><h2>Lifestyle flags</h2><div class="grid grid-2"><?php foreach(['chest_pain'=>'Chest pain','overweight'=>'Overweight','stress'=>'Stress','smoking'=>'Smoking','drinking'=>'Drinking','exercise'=>'Exercises regularly','hereditary'=>'Hereditary risk'] as $key=>$label):?><p class="compact"><strong><?=e($label)?>:</strong> <?=((int)$record[$key]===1)?'Yes':'No'?></p><?php endforeach;?></div></section>
<?php require __DIR__.'/footer.php';?>
