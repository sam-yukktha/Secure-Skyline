<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';require_login();
if(is_role('owner')){
    $stmt=db()->prepare('SELECT mr.* FROM medical_records mr WHERE mr.owner_id=? ORDER BY mr.created_at DESC');$stmt->execute([user()['id']]);
}else{
    $stmt=db()->prepare("SELECT DISTINCT mr.* FROM medical_records mr JOIN reports r ON r.medical_record_id=mr.id JOIN access_requests ar ON ar.report_id=r.id WHERE ar.user_id=? AND ar.status='approved' ORDER BY mr.created_at DESC");$stmt->execute([user()['id']]);
}
$records=$stmt->fetchAll();$pageTitle='Medical Records';require __DIR__.'/header.php';
?>
<h1><?=is_role('owner')?'My Medical Records':'Authorized Medical Records'?></h1><p class="lead"><?=is_role('owner')?'Records associated with your uploaded reports.':'Only records whose report access request has been approved are shown.'?></p>
<section class="card"><div class="table-wrap"><table><thead><tr><th>Patient</th><th>Age</th><th>Gender</th><th>BMI</th><th>Pulse</th><th>Blood pressure</th><th>Blood sugar</th><th>Cholesterol</th><th>Action</th></tr></thead><tbody><?php if(!$records):?><tr><td colspan="9">No accessible medical records.</td></tr><?php endif;?><?php foreach($records as $record):?><tr><td><?=e($record['patient_label'])?></td><td><?=e($record['age'])?></td><td><?=e($record['gender'])?></td><td><?=e(bmi((float)$record['weight'],(float)$record['height']))?></td><td><?=e($record['pulse_rate'])?></td><td><?=e($record['blood_pressure'])?></td><td><?=e($record['blood_sugar'])?></td><td><?=e($record['cholesterol'])?></td><td><a class="btn btn-small" href="<?=e(url('analyse_report.php?id='.(int)$record['id']))?>">Analyze</a></td></tr><?php endforeach;?></tbody></table></div></section>
<?php require __DIR__.'/footer.php';?>
