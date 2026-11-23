<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';require_role('user');
$attributes=['age'=>'Age','blood_pressure'=>'Blood Pressure','blood_sugar'=>'Blood Sugar','cholesterol'=>'Cholesterol','pulse_rate'=>'Pulse Rate','weight'=>'Weight','height'=>'Height'];
$comparisons=['gt'=>['>','Greater Than'],'eq'=>['=','Equal To'],'lt'=>['<','Less Than']];
$matches=[];$total=(int)db()->query('SELECT COUNT(*) FROM medical_records')->fetchColumn();$errors=[];$submitted=false;
if(request_is_post()){
    $submitted=true;
    try{
        verify_csrf();$attribute=post_string('attribute');$comparison=post_string('comparison');$raw=post_string('value');
        if(!isset($attributes[$attribute])||!isset($comparisons[$comparison])){throw new RuntimeException('Choose a supported attribute and comparison.');}
        if($raw===''||!is_numeric($raw)){throw new RuntimeException('Enter a valid numeric comparison value.');}$value=(float)$raw;$operator=$comparisons[$comparison][0];
        $sql="SELECT id,patient_label,age,weight,height,pulse_rate,blood_pressure,blood_sugar,cholesterol FROM medical_records WHERE `$attribute` $operator ? ORDER BY `$attribute`";
        $stmt=db()->prepare($sql);$stmt->execute([$value]);$matches=$stmt->fetchAll();
    }catch(Throwable $exception){$errors[]=$exception->getMessage();}
}
$pageTitle='Performance Report';require __DIR__.'/header.php';
?>
<h1>Performance Report</h1><p class="lead">Filter numerical attributes with validated greater-than, equal-to, or less-than comparisons. Counts and the chart come from the live database.</p>
<?php if($errors):?><div class="alert alert-error"><?=e(implode(' ',$errors))?></div><?php endif;?>
<section class="card"><form method="post"><?=csrf_field()?><div class="form-grid"><div class="field"><label for="attribute">Attribute</label><select id="attribute" name="attribute"><?php foreach($attributes as $key=>$label):?><option value="<?=e($key)?>" <?=post_string('attribute')===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></div><div class="field"><label for="comparison">Condition</label><select id="comparison" name="comparison"><?php foreach($comparisons as $key=>$item):?><option value="<?=e($key)?>" <?=post_string('comparison')===$key?'selected':''?>><?=e($item[1])?></option><?php endforeach;?></select></div><div class="field"><label for="value">Value</label><input id="value" name="value" type="number" step="0.1" value="<?=e(post_string('value'))?>" required></div></div><div class="actions"><button class="btn" type="submit">Generate Report</button></div></form></section>
<?php if($submitted&&!$errors):?><?php $count=count($matches);$other=max(0,$total-$count);$max=max(1,$total);?><div class="grid stats"><div class="stat"><strong><?=e($total)?></strong><span>Total Records</span></div><div class="stat"><strong><?=e($count)?></strong><span>Matching Records</span></div><div class="stat"><strong><?=e($other)?></strong><span>Non-matching Records</span></div><div class="stat"><strong><?=e($total?round($count/$total*100,1):0)?>%</strong><span>Match Rate</span></div></div>
<section class="card"><h2>Result Count Chart</h2><div class="metric-chart" role="img" aria-label="Bar chart showing <?=e($count)?> matching and <?=e($other)?> non-matching records"><div class="bar" style="height:<?=e(max(3,round($count/$max*100)))?>%"><strong><?=e($count)?></strong><span>Matching</span></div><div class="bar" style="height:<?=e(max(3,round($other/$max*100)))?>%"><strong><?=e($other)?></strong><span>Other</span></div></div></section>
<section class="card"><h2>Matching Synthetic Records</h2><div class="table-wrap"><table><thead><tr><th>Patient</th><th>Age</th><th>Pulse</th><th>Blood Pressure</th><th>Blood Sugar</th><th>Cholesterol</th><th>BMI</th></tr></thead><tbody><?php if(!$matches):?><tr><td colspan="7">No records matched this condition.</td></tr><?php endif;?><?php foreach($matches as $record):?><tr><td><?=e($record['patient_label'])?></td><td><?=e($record['age'])?></td><td><?=e($record['pulse_rate'])?></td><td><?=e($record['blood_pressure'])?></td><td><?=e($record['blood_sugar'])?></td><td><?=e($record['cholesterol'])?></td><td><?=e(bmi((float)$record['weight'],(float)$record['height']))?></td></tr><?php endforeach;?></tbody></table></div></section><?php endif;?>
<?php require __DIR__.'/footer.php';?>
