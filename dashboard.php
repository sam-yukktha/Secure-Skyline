<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('owner');
$errors = [];

if (request_is_post()) {
    try {
        verify_csrf();
        $age = validated_number('age', 1, 120, $errors);
        $weight = validated_number('weight', 2, 350, $errors);
        $height = validated_number('height', 30, 250, $errors);
        $heartbeat = validated_number('heartbeat', 20, 250, $errors);
        $pulseRate = validated_number('pulse_rate', 20, 250, $errors);
        $bloodPressure = validated_number('blood_pressure', 40, 260, $errors);
        $bloodSugar = validated_number('blood_sugar', 20, 600, $errors);
        $cholesterol = validated_number('cholesterol', 50, 600, $errors);
        $gender = post_string('gender');
        $reportName = post_string('report_name');
        $keyword = post_string('report_keyword');
        if (!in_array($gender, ['Female', 'Male', 'Other'], true)) { $errors[] = 'Select a valid gender.'; }
        if ($reportName === '' || $keyword === '') { $errors[] = 'Report name and keyword are required.'; }
        if (strlen($reportName) > 150 || strlen($keyword) > 100) { $errors[] = 'Report name or keyword is too long.'; }
        $upload = validate_uploaded_report($_FILES['report_file'] ?? []);
        if ($errors) { throw new RuntimeException('Correct the highlighted medical details.'); }

        $pdo = db();
        $pdo->beginTransaction();
        $recordStmt = $pdo->prepare(
            'INSERT INTO medical_records
            (owner_id, patient_label, age, gender, weight, height, heartbeat, pulse_rate, blood_pressure, blood_sugar, cholesterol, chest_pain, overweight, stress, smoking, drinking, exercise, hereditary)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $recordStmt->execute([
            user()['id'], post_string('patient_label') ?: user()['name'], $age, $gender, $weight, $height,
            $heartbeat, $pulseRate, $bloodPressure, $bloodSugar, $cholesterol,
            isset($_POST['chest_pain']) ? 1 : 0, isset($_POST['overweight']) ? 1 : 0,
            isset($_POST['stress']) ? 1 : 0, isset($_POST['smoking']) ? 1 : 0,
            isset($_POST['drinking']) ? 1 : 0, isset($_POST['exercise']) ? 1 : 0,
            isset($_POST['hereditary']) ? 1 : 0,
        ]);
        $recordId = (int) $pdo->lastInsertId();
        $reportStmt = $pdo->prepare(
            'INSERT INTO reports (medical_record_id, report_name, keyword, original_filename, stored_filename, mime_type, size_bytes, encrypted_content)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $reportStmt->execute([
            $recordId, $reportName, $keyword, $upload['original_name'], $upload['stored_name'],
            $upload['mime_type'], $upload['size_bytes'], encrypt_value($upload['contents'], 'report:' . $recordId),
        ]);
        $pdo->commit();
        flash('success', 'Medical details saved and the report was encrypted with AES-256-GCM.');
        redirect('view_file.php');
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
        if (!$errors || $exception->getMessage() !== 'Correct the highlighted medical details.') { $errors[] = $exception->getMessage(); }
    }
}

$statsStmt = db()->prepare(
    "SELECT
       (SELECT COUNT(*) FROM reports r JOIN medical_records mr ON mr.id=r.medical_record_id WHERE mr.owner_id=:owner1) reports,
       (SELECT COUNT(*) FROM access_requests ar JOIN reports r ON r.id=ar.report_id JOIN medical_records mr ON mr.id=r.medical_record_id WHERE mr.owner_id=:owner2 AND ar.status='pending') pending,
       (SELECT COUNT(*) FROM access_requests ar JOIN reports r ON r.id=ar.report_id JOIN medical_records mr ON mr.id=r.medical_record_id WHERE mr.owner_id=:owner3 AND ar.status='approved') approved,
       (SELECT COUNT(*) FROM medical_records WHERE owner_id=:owner4) records"
);
$statsStmt->execute(['owner1'=>user()['id'],'owner2'=>user()['id'],'owner3'=>user()['id'],'owner4'=>user()['id']]);
$stats = $statsStmt->fetch();
$pageTitle = 'Data Owner Dashboard'; require __DIR__ . '/header.php';
?>
<h1>Data Owner Dashboard</h1>
<p class="lead">Add a synthetic medical record and upload its report. The file bytes are encrypted before storage; searchable metadata and numerical demo attributes remain queryable.</p>
<div class="grid stats"><div class="stat"><strong><?=e($stats['reports'])?></strong><span>Total Reports</span></div><div class="stat"><strong><?=e($stats['pending'])?></strong><span>Pending Requests</span></div><div class="stat"><strong><?=e($stats['approved'])?></strong><span>Approved Requests</span></div><div class="stat"><strong><?=e($stats['records'])?></strong><span>Medical Records</span></div></div>
<section class="card"><h2>Add Medical Details and Encrypted Report</h2>
<?php if($errors):?><div class="alert alert-error"><ul class="error-list"><?php foreach(array_unique($errors) as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="MAX_FILE_SIZE" value="5242880"><div class="form-grid">
<div class="field full"><label for="patient_label">Patient label / synthetic name</label><input id="patient_label" name="patient_label" value="<?=e(post_string('patient_label'))?>" placeholder="e.g. Demo Patient 21"></div>
<div class="field"><label for="age">Age</label><input id="age" name="age" type="number" min="1" max="120" step="1" value="<?=e(post_string('age'))?>" required></div>
<div class="field"><label for="gender">Gender</label><select id="gender" name="gender" required><option value="">Select</option><?php foreach(['Female','Male','Other'] as $option):?><option <?=post_string('gender')===$option?'selected':''?>><?=e($option)?></option><?php endforeach;?></select></div>
<div class="field"><label for="weight">Weight (kg)</label><input id="weight" name="weight" type="number" min="2" max="350" step="0.1" value="<?=e(post_string('weight'))?>" required></div>
<div class="field"><label for="height">Height (cm)</label><input id="height" name="height" type="number" min="30" max="250" step="0.1" value="<?=e(post_string('height'))?>" required></div>
<div class="field"><label for="heartbeat">Heartbeat (bpm)</label><input id="heartbeat" name="heartbeat" type="number" min="20" max="250" step="1" value="<?=e(post_string('heartbeat'))?>" required></div>
<div class="field"><label for="pulse_rate">Pulse rate (bpm)</label><input id="pulse_rate" name="pulse_rate" type="number" min="20" max="250" step="1" value="<?=e(post_string('pulse_rate'))?>" required></div>
<div class="field"><label for="blood_pressure">Blood pressure, systolic (mmHg)</label><input id="blood_pressure" name="blood_pressure" type="number" min="40" max="260" step="1" value="<?=e(post_string('blood_pressure'))?>" required></div>
<div class="field"><label for="blood_sugar">Blood sugar (mg/dL)</label><input id="blood_sugar" name="blood_sugar" type="number" min="20" max="600" step="0.1" value="<?=e(post_string('blood_sugar'))?>" required></div>
<div class="field"><label for="cholesterol">Cholesterol (mg/dL)</label><input id="cholesterol" name="cholesterol" type="number" min="50" max="600" step="0.1" value="<?=e(post_string('cholesterol'))?>" required></div>
<div class="field"><label for="report_name">Report name</label><input id="report_name" name="report_name" maxlength="150" value="<?=e(post_string('report_name'))?>" required></div>
<div class="field"><label for="report_keyword">Report keyword</label><input id="report_keyword" name="report_keyword" maxlength="100" value="<?=e(post_string('report_keyword'))?>" placeholder="e.g. cardiology-demo" required></div>
<div class="field full"><label for="report_file">Report file (PDF, TXT, PNG, JPG; maximum 5 MB)</label><input id="report_file" name="report_file" type="file" accept=".pdf,.txt,.png,.jpg,.jpeg" required></div>
<div class="field full"><label>Lifestyle and history flags</label><div class="check-row"><?php foreach(['chest_pain'=>'Chest pain','overweight'=>'Overweight','stress'=>'Stress','smoking'=>'Smoking','drinking'=>'Drinking','exercise'=>'Exercises regularly','hereditary'=>'Hereditary risk'] as $key=>$label):?><label><input type="checkbox" name="<?=e($key)?>" value="1" <?=isset($_POST[$key])?'checked':''?>> <?=e($label)?></label><?php endforeach;?></div></div>
</div><div class="actions"><button class="btn" type="submit">Save and Encrypt Report</button></div></form></section>
<?php require __DIR__.'/footer.php'; ?>
