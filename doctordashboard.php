<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
require_role('user');
$errors=[];
if(request_is_post()){
    try{
        verify_csrf();
        $reportId=filter_input(INPUT_POST,'report_id',FILTER_VALIDATE_INT);
        if(!$reportId){throw new RuntimeException('Select a valid report.');}
        $exists=db()->prepare('SELECT id FROM reports WHERE id=?'); $exists->execute([$reportId]);
        if(!$exists->fetchColumn()){throw new RuntimeException('That report no longer exists.');}
        $check=db()->prepare('SELECT id,status FROM access_requests WHERE report_id=? AND user_id=?'); $check->execute([$reportId,user()['id']]); $request=$check->fetch();
        if($request && $request['status']!=='rejected'){throw new RuntimeException('A request for this report already exists.');}
        if($request){
            $stmt=db()->prepare("UPDATE access_requests SET status='pending',token_hash=NULL,token_secret=NULL,decided_at=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=?"); $stmt->execute([$request['id']]);
        }else{
            $stmt=db()->prepare("INSERT INTO access_requests(report_id,user_id,status) VALUES (?,?,'pending')"); $stmt->execute([$reportId,user()['id']]);
        }
        flash('success','Access request sent to the Data Owner.'); redirect('view_response.php');
    }catch(Throwable $exception){$errors[]=$exception->getMessage();}
}
$search=trim((string)($_GET['q']??''));
$params=['user_id'=>user()['id']];
$sql="SELECT r.id,r.report_name,r.keyword,r.original_filename,r.mime_type,r.created_at,mr.patient_label,o.username owner_username,ar.status request_status
      FROM reports r JOIN medical_records mr ON mr.id=r.medical_record_id JOIN data_owners o ON o.id=mr.owner_id
      LEFT JOIN access_requests ar ON ar.report_id=r.id AND ar.user_id=:user_id";
if($search!==''){
    $sql.=' WHERE r.keyword LIKE :search_keyword OR r.report_name LIKE :search_name';
    $params['search_keyword']='%'.$search.'%';
    $params['search_name']='%'.$search.'%';
}
$sql.=' ORDER BY r.created_at DESC'; $stmt=db()->prepare($sql);$stmt->execute($params);$reports=$stmt->fetchAll();
$statsStmt=db()->prepare("SELECT (SELECT COUNT(*) FROM reports) available,COUNT(*) sent,SUM(status='approved') approved,SUM(status='pending') pending FROM access_requests WHERE user_id=?");$statsStmt->execute([user()['id']]);$stats=$statsStmt->fetch();
$pageTitle='Data User Dashboard';require __DIR__.'/header.php';
?>
<h1>Data User Dashboard</h1><p class="lead">Search report metadata by keyword. Medical report contents remain encrypted until the owner approves your request and you submit the matching authorization token.</p>
<div class="grid stats"><div class="stat"><strong><?=e($stats['available']??0)?></strong><span>Available Reports</span></div><div class="stat"><strong><?=e($stats['sent']??0)?></strong><span>Requests Sent</span></div><div class="stat"><strong><?=e($stats['approved']??0)?></strong><span>Approved Requests</span></div><div class="stat"><strong><?=e($stats['pending']??0)?></strong><span>Pending Requests</span></div></div>
<?php if($errors):?><div class="alert alert-error"><ul class="error-list"><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<section class="card"><h2>Search Reports</h2><form method="get"><div class="form-grid"><div class="field full"><label for="q">Report name or keyword</label><input id="q" name="q" value="<?=e($search)?>" placeholder="Try: cardiology-demo"></div></div><div class="actions"><button class="btn" type="submit">Search</button><a class="btn btn-secondary" href="<?=e(url('doctordashboard.php'))?>">Show all</a></div></form></section>
<section class="card"><h2><?= $search!==''?'Search Results':'Available Report Metadata' ?></h2><div class="table-wrap"><table><thead><tr><th>Owner</th><th>Patient label</th><th>Report</th><th>Keyword</th><th>Type</th><th>Status / action</th></tr></thead><tbody>
<?php if(!$reports):?><tr><td colspan="6">No report metadata matched your search.</td></tr><?php endif;?>
<?php foreach($reports as $report):?><tr><td><?=e($report['owner_username'])?></td><td><?=e($report['patient_label'])?></td><td><?=e($report['report_name'])?></td><td><?=e($report['keyword'])?></td><td><?=e($report['mime_type'])?></td><td><?php if($report['request_status']):?><span class="badge badge-<?=e($report['request_status'])?>"><?=e($report['request_status'])?></span><?php if($report['request_status']==='rejected'):?><form method="post" class="actions"><?=csrf_field()?><input type="hidden" name="report_id" value="<?=e($report['id'])?>"><button class="btn btn-small" type="submit">Request again</button></form><?php endif;?><?php else:?><form method="post"><?=csrf_field()?><input type="hidden" name="report_id" value="<?=e($report['id'])?>"><button class="btn btn-small" type="submit">Request Access</button></form><?php endif;?></td></tr><?php endforeach;?>
</tbody></table></div></section>
<?php require __DIR__.'/footer.php';?>
