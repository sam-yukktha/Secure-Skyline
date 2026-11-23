<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';require_login();
try{
    if(is_role('owner')){
        $reportId=filter_input(INPUT_GET,'report_id',FILTER_VALIDATE_INT);if(!$reportId){throw new RuntimeException('Invalid report.');}
        $stmt=db()->prepare('SELECT r.* FROM reports r JOIN medical_records mr ON mr.id=r.medical_record_id WHERE r.id=? AND mr.owner_id=?');$stmt->execute([$reportId,user()['id']]);$report=$stmt->fetch();
        if(!$report){throw new RuntimeException('Report not found or access denied.');}$mode=(string)($_GET['mode']??'view');
    }else{
        if(!request_is_post()){throw new RuntimeException('Submit the authorization form to access this report.');}
        verify_csrf();$requestId=filter_input(INPUT_POST,'request_id',FILTER_VALIDATE_INT);$token=(string)($_POST['access_token']??'');$mode=(string)($_POST['mode']??'view');
        if(!$requestId||$token===''){throw new RuntimeException('Request and authorization token are required.');}
        $stmt=db()->prepare("SELECT r.*,ar.token_hash FROM access_requests ar JOIN reports r ON r.id=ar.report_id WHERE ar.id=? AND ar.user_id=? AND ar.status='approved'");$stmt->execute([$requestId,user()['id']]);$report=$stmt->fetch();
        if(!$report||!$report['token_hash']||!password_verify($token,$report['token_hash'])){throw new RuntimeException('Authorization failed. The token is incorrect or access is not approved.');}
    }
    $contents=decrypt_value($report['encrypted_content'],'report:'.$report['medical_record_id']);
    $inline=$mode!=='download' && in_array($report['mime_type'],['text/plain','application/pdf','image/png','image/jpeg'],true);
    header('Content-Type: '.$report['mime_type']);header('Content-Length: '.strlen($contents));header('Cache-Control: no-store, private');
    header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.str_replace(['"',"\r","\n"],'_',basename($report['original_filename'])).'"');echo $contents;exit;
}catch(Throwable $exception){
    error_log('Authorized report access failed: '.$exception->getMessage());flash('error',$exception->getMessage());redirect(is_role('owner')?'view_file.php':'view_response.php');
}
