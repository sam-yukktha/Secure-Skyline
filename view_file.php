<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
require_role('owner');
$stmt=db()->prepare('SELECT r.*,mr.patient_label,mr.created_at record_created FROM reports r JOIN medical_records mr ON mr.id=r.medical_record_id WHERE mr.owner_id=? ORDER BY r.created_at DESC');
$stmt->execute([user()['id']]); $reports=$stmt->fetchAll();
$pageTitle='My Encrypted Reports'; require __DIR__.'/header.php';
?>
<h1>My Encrypted Reports</h1><p class="lead">Files are stored as authenticated ciphertext in MySQL. The application decrypts them only after ownership or approved-token authorization is verified.</p>
<section class="card"><div class="table-wrap"><table><thead><tr><th>Patient</th><th>Report</th><th>Keyword</th><th>Original file</th><th>Size</th><th>Added</th><th>Action</th></tr></thead><tbody>
<?php if(!$reports):?><tr><td colspan="7">No reports uploaded yet.</td></tr><?php endif;?>
<?php foreach($reports as $report):?><tr><td><?=e($report['patient_label'])?></td><td><?=e($report['report_name'])?></td><td><?=e($report['keyword'])?></td><td><?=e($report['original_filename'])?></td><td><?=e(number_format((int)$report['size_bytes']/1024,1))?> KB</td><td><?=e(format_datetime($report['created_at']))?></td><td><a class="btn btn-small" href="<?=e(url('authorized_report.php?report_id='.(int)$report['id']))?>">View / Download</a></td></tr><?php endforeach;?>
</tbody></table></div></section>
<?php require __DIR__.'/footer.php'; ?>
