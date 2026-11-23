<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';require_role('owner');
$errors=[];
if(request_is_post()){
    try{
        verify_csrf();$requestId=filter_input(INPUT_POST,'request_id',FILTER_VALIDATE_INT);$decision=post_string('decision');
        if(!$requestId||!in_array($decision,['approve','reject'],true)){throw new RuntimeException('Invalid request decision.');}
        $scope=db()->prepare('SELECT ar.id FROM access_requests ar JOIN reports r ON r.id=ar.report_id JOIN medical_records mr ON mr.id=r.medical_record_id WHERE ar.id=? AND mr.owner_id=?');$scope->execute([$requestId,user()['id']]);
        if(!$scope->fetchColumn()){throw new RuntimeException('Request not found or not owned by you.');}
        if($decision==='approve'){
            $token=generate_access_token();$stmt=db()->prepare("UPDATE access_requests SET status='approved',token_hash=?,token_secret=?,decided_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([password_hash($token,PASSWORD_DEFAULT),encrypt_value($token,'access-token:'.$requestId),$requestId]);
            flash('success','Request approved. The Data User can now see and use the generated authorization token.');
        }else{
            $stmt=db()->prepare("UPDATE access_requests SET status='rejected',token_hash=NULL,token_secret=NULL,decided_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?");$stmt->execute([$requestId]);
            flash('success','Request rejected. Report access remains denied.');
        }
        redirect('view_request.php');
    }catch(Throwable $exception){$errors[]=$exception->getMessage();}
}
$stmt=db()->prepare('SELECT ar.*,u.name user_name,u.username,r.report_name,r.original_filename,mr.patient_label FROM access_requests ar JOIN data_users u ON u.id=ar.user_id JOIN reports r ON r.id=ar.report_id JOIN medical_records mr ON mr.id=r.medical_record_id WHERE mr.owner_id=? ORDER BY ar.created_at DESC');$stmt->execute([user()['id']]);$requests=$stmt->fetchAll();
$pageTitle='Access Requests';require __DIR__.'/header.php';
?>
<h1>Report Access Requests</h1><p class="lead">Approve only users who should receive controlled report access. Approval creates a separate authorization token; it never reveals the server encryption key.</p>
<?php if($errors):?><div class="alert alert-error"><ul class="error-list"><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<section class="card"><div class="table-wrap"><table><thead><tr><th>Data User</th><th>Patient</th><th>Report</th><th>Requested</th><th>Status</th><th>Decision</th></tr></thead><tbody><?php if(!$requests):?><tr><td colspan="6">No access requests yet.</td></tr><?php endif;?><?php foreach($requests as $request):?><tr><td><?=e($request['user_name'])?><br><span class="muted">@<?=e($request['username'])?></span></td><td><?=e($request['patient_label'])?></td><td><?=e($request['report_name'])?><br><span class="muted"><?=e($request['original_filename'])?></span></td><td><?=e(format_datetime($request['created_at']))?></td><td><span class="badge badge-<?=e($request['status'])?>"><?=e($request['status'])?></span></td><td><form method="post" class="actions"><?=csrf_field()?><input type="hidden" name="request_id" value="<?=e($request['id'])?>"><button class="btn btn-small btn-success" name="decision" value="approve" type="submit">Approve</button><button class="btn btn-small btn-danger" name="decision" value="reject" type="submit">Reject</button></form></td></tr><?php endforeach;?></tbody></table></div></section>
<?php require __DIR__.'/footer.php';?>
