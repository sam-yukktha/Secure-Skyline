<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';require_role('user');
$available=skyline_criteria();$preferences=[];$result=null;$errors=[];
if(request_is_post()){
    try{
        verify_csrf();$selected=$_POST['criteria']??[];$directions=$_POST['direction']??[];
        if(!is_array($selected)||!is_array($directions)){throw new RuntimeException('Invalid skyline criteria.');}
        foreach($selected as $criterion){
            if(!isset($available[$criterion])){throw new RuntimeException('Unsupported skyline criterion selected.');}
            $direction=$directions[$criterion]??'min';if(!in_array($direction,['min','max'],true)){throw new RuntimeException('Invalid preference direction.');}
            $preferences[$criterion]=$direction;
        }
        if(count($preferences)<2){throw new RuntimeException('Select at least two skyline criteria.');}
        $records=db()->query('SELECT id,patient_label,age,blood_pressure,blood_sugar,cholesterol,pulse_rate,ROUND(weight/POW(height/100,2),1) bmi FROM medical_records ORDER BY id')->fetchAll();
        if(!$records){throw new RuntimeException('There are no medical records to analyze.');}
        $result=compute_skyline($records,$preferences);
    }catch(Throwable $exception){$errors[]=$exception->getMessage();}
}else{$preferences=['blood_pressure'=>'min','blood_sugar'=>'min'];}
$pageTitle='Skyline Query';require __DIR__.'/header.php';
?>
<h1>Multi-Criteria Skyline Query</h1><p class="lead">Returns every non-dominated synthetic record for the selected numerical criteria. A record dominates another when it is at least as good in every selected dimension and strictly better in at least one.</p>
<div class="alert alert-info"><strong>Accuracy note:</strong> this educational prototype does not compare AES ciphertext. Numerical demo attributes are processed by the trusted PHP application after authorization; encrypted report files remain protected and are not involved in the skyline comparison.</div>
<?php if($errors):?><div class="alert alert-error"><?=e(implode(' ',$errors))?></div><?php endif;?>
<section class="card"><h2>Choose Criteria</h2><form method="post"><?=csrf_field()?><div class="form-grid"><?php foreach($available as $key=>$label):?><div class="field"><div class="check-row"><label><input type="checkbox" name="criteria[]" value="<?=e($key)?>" <?=isset($preferences[$key])?'checked':''?>> <?=e($label)?></label><select name="direction[<?=e($key)?>]" aria-label="Preference direction for <?=e($label)?>"><option value="min" <?=($preferences[$key]??'min')==='min'?'selected':''?>>Minimize</option><option value="max" <?=($preferences[$key]??'')==='max'?'selected':''?>>Maximize</option></select></div></div><?php endforeach;?></div><div class="actions"><button class="btn" type="submit">Run Skyline Query</button></div></form></section>
<?php if($result):?><div class="grid stats"><div class="stat"><strong><?=e($result['analyzed'])?></strong><span>Records Analyzed</span></div><div class="stat"><strong><?=e($result['dominated'])?></strong><span>Dominated Records</span></div><div class="stat"><strong><?=e(count($result['skyline']))?></strong><span>Skyline Records</span></div><div class="stat"><strong><?=e(count($preferences))?></strong><span>Selected Criteria</span></div></div>
<section class="card"><h2>Selected Preferences</h2><p><?php $parts=[];foreach($preferences as $key=>$direction){$parts[]=$available[$key].' ('.($direction==='min'?'minimize':'maximize').')';}echo e(implode(' · ',$parts));?></p><div class="table-wrap"><table><thead><tr><th>ID</th><th>Patient</th><?php foreach($preferences as $key=>$direction):?><th><?=e($available[$key])?><br><span class="muted"><?=e($direction)?></span></th><?php endforeach;?></tr></thead><tbody><?php foreach($result['skyline'] as $record):?><tr><td><?=e($record['id'])?></td><td><?=e($record['patient_label'])?></td><?php foreach($preferences as $key=>$direction):?><td><?=e($record[$key])?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table></div></section><?php endif;?>
<?php require __DIR__.'/footer.php';?>
