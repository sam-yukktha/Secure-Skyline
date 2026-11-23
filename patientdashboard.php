<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';require_role('owner');
$errors=[];
if(request_is_post()){
    try{
        verify_csrf();$fields=['smoking','alcohol','exercise','fast_food','hereditary','overweight','stress','chest_pain','high_blood_sugar','high_blood_pressure'];$values=[];
        foreach($fields as $field){$value=filter_input(INPUT_POST,$field,FILTER_VALIDATE_INT);if(!in_array($value,[0,1],true)){throw new RuntimeException('Answer every health-history question.');}$values[$field]=$value;}
        $stmt=db()->prepare('INSERT INTO health_histories(owner_id,smoking,alcohol,exercise,fast_food,hereditary,overweight,stress,chest_pain,high_blood_sugar,high_blood_pressure) VALUES(?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE smoking=VALUES(smoking),alcohol=VALUES(alcohol),exercise=VALUES(exercise),fast_food=VALUES(fast_food),hereditary=VALUES(hereditary),overweight=VALUES(overweight),stress=VALUES(stress),chest_pain=VALUES(chest_pain),high_blood_sugar=VALUES(high_blood_sugar),high_blood_pressure=VALUES(high_blood_pressure),updated_at=CURRENT_TIMESTAMP');
        $stmt->execute([user()['id'],...array_values($values)]);flash('success','Health history saved.');redirect('patientdashboard.php');
    }catch(Throwable $exception){$errors[]=$exception->getMessage();}
}
$stmt=db()->prepare('SELECT * FROM health_histories WHERE owner_id=?');$stmt->execute([user()['id']]);$history=$stmt->fetch()?:[];
$questions=['smoking'=>'Do you smoke?','alcohol'=>'Do you consume alcohol?','exercise'=>'Do you exercise regularly?','fast_food'=>'Do you frequently eat fast food?','hereditary'=>'Is there hereditary cardiovascular risk?','overweight'=>'Are you currently overweight?','stress'=>'Do you frequently experience high stress?','chest_pain'=>'Do you experience chest pain?','high_blood_sugar'=>'Have you been told your blood sugar is high?','high_blood_pressure'=>'Have you been told your blood pressure is high?'];
$pageTitle='Health History';require __DIR__.'/header.php';
?>
<h1>Health History</h1><p class="lead">This restores the original questionnaire as a simple educational record. It does not calculate a medical diagnosis.</p>
<?php if($errors):?><div class="alert alert-error"><?=e(implode(' ',$errors))?></div><?php endif;?>
<section class="card"><form method="post"><?=csrf_field()?><div class="form-grid"><?php foreach($questions as $key=>$label):?><fieldset class="field"><legend><?=e($label)?></legend><div class="radio-row"><label><input type="radio" name="<?=e($key)?>" value="1" <?=isset($history[$key])&&(int)$history[$key]===1?'checked':''?> required> Yes</label><label><input type="radio" name="<?=e($key)?>" value="0" <?=isset($history[$key])&&(int)$history[$key]===0?'checked':''?> required> No</label></div></fieldset><?php endforeach;?></div><div class="actions"><button class="btn" type="submit">Save Health History</button></div></form></section>
<?php require __DIR__.'/footer.php';?>
