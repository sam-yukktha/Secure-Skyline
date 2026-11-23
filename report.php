<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';require_role('user');require_once __DIR__.'/KMeans.php';
$attributes=['blood_pressure'=>'Blood Pressure','blood_sugar'=>'Blood Sugar','cholesterol'=>'Cholesterol','pulse_rate'=>'Pulse Rate','age'=>'Age','bmi'=>'BMI'];$clusters=[];$errors=[];$x='blood_pressure';$y='blood_sugar';$k=3;
if(request_is_post()){
    try{
        verify_csrf();$x=post_string('x_attribute');$y=post_string('y_attribute');$k=(int)post_string('cluster_count');
        if(!isset($attributes[$x])||!isset($attributes[$y])||$x===$y){throw new RuntimeException('Choose two different supported attributes.');}
        if($k<2||$k>5){throw new RuntimeException('Cluster count must be between 2 and 5.');}
        $rows=db()->query('SELECT id,patient_label,age,blood_pressure,blood_sugar,cholesterol,pulse_rate,ROUND(weight/POW(height/100,2),1) bmi FROM medical_records ORDER BY id')->fetchAll();
        if(count($rows)<$k){throw new RuntimeException('Not enough records for the requested number of clusters.');}
        $clusters=(new KMeans())->setData($rows)->setXKey($x)->setYKey($y)->setClusterCount($k)->solve()->getClusters();
    }catch(Throwable $exception){$errors[]=$exception->getMessage();}
}
$pageTitle='K-Means Clustering Demo';require __DIR__.'/header.php';
?>
<h1>K-Means Clustering Demonstration</h1><p class="lead">The original two-dimensional K-Means feature has been retained and made deterministic. It groups synthetic records by two selected numerical attributes; it is an educational algorithm, not a trained or validated medical model.</p>
<?php if($errors):?><div class="alert alert-error"><?=e(implode(' ',$errors))?></div><?php endif;?>
<section class="card"><form method="post"><?=csrf_field()?><div class="form-grid"><div class="field"><label for="x_attribute">X attribute</label><select id="x_attribute" name="x_attribute"><?php foreach($attributes as $key=>$label):?><option value="<?=e($key)?>" <?=$x===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></div><div class="field"><label for="y_attribute">Y attribute</label><select id="y_attribute" name="y_attribute"><?php foreach($attributes as $key=>$label):?><option value="<?=e($key)?>" <?=$y===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></div><div class="field"><label for="cluster_count">Clusters</label><input id="cluster_count" name="cluster_count" type="number" min="2" max="5" value="<?=e($k)?>"></div></div><div class="actions"><button class="btn" type="submit">Run Clustering</button></div></form></section>
<?php if($clusters):?><div class="grid grid-2"><?php foreach($clusters as $index=>$cluster):?><section class="card"><h2>Cluster <?=e($index+1)?></h2><p><strong>Centroid:</strong> <?=e($attributes[$x])?> <?=e(round($cluster->getX(),2))?>, <?=e($attributes[$y])?> <?=e(round($cluster->getY(),2))?></p><p><strong>Records:</strong> <?=e(count($cluster->getData()))?></p><div class="table-wrap"><table><thead><tr><th>Patient</th><th><?=e($attributes[$x])?></th><th><?=e($attributes[$y])?></th></tr></thead><tbody><?php if(!$cluster->getData()):?><tr><td colspan="3">Empty cluster</td></tr><?php endif;?><?php foreach($cluster->getData() as $point):?><tr><td><?=e($point['patient_label'])?></td><td><?=e($point[$x])?></td><td><?=e($point[$y])?></td></tr><?php endforeach;?></tbody></table></div></section><?php endforeach;?></div><?php endif;?>
<?php require __DIR__.'/footer.php';?>
