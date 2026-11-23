<?php
declare(strict_types=1);

putenv('SKYLINE_DB_HOST=127.0.0.1');
putenv('SKYLINE_DB_PORT=' . (getenv('SKYLINE_TEST_DB_PORT') ?: '3306'));
putenv('SKYLINE_DB_NAME=skyline');
putenv('SKYLINE_DB_USER=' . (getenv('SKYLINE_TEST_DB_USER') ?: 'root'));
putenv('SKYLINE_DB_PASS=' . (getenv('SKYLINE_TEST_DB_PASS') ?: ''));
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$passed = 0; $failed = 0;
function check(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) { $passed++; echo "PASS: {$name}\n"; }
    else { $failed++; echo "FAIL: {$name}\n"; }
}

try {
    $pdo = db();
    check('01 Database connection', $pdo->query('SELECT 1')->fetchColumn() === 1);
    $pdo->beginTransaction();

    $suffix = bin2hex(random_bytes(4));
    $ownerPassword = 'TestOwner123!';
    $stmt = $pdo->prepare('INSERT INTO data_owners(name,email,username,password_hash) VALUES(?,?,?,?)');
    $stmt->execute(['Automated Owner', "owner-{$suffix}@example.test", "owner_{$suffix}", password_hash($ownerPassword, PASSWORD_DEFAULT)]);
    $ownerId = (int)$pdo->lastInsertId();
    check('02 Owner registration', $ownerId > 0);
    $stmt = $pdo->prepare('SELECT * FROM data_owners WHERE username=?'); $stmt->execute(["owner_{$suffix}"]); $owner = $stmt->fetch();
    check('03 Owner login verification', $owner && password_verify($ownerPassword, $owner['password_hash']));

    $userPassword = 'TestDoctor123!';
    $stmt = $pdo->prepare('INSERT INTO data_users(name,email,username,password_hash) VALUES(?,?,?,?)');
    $stmt->execute(['Automated User', "doctor-{$suffix}@example.test", "doctor_{$suffix}", password_hash($userPassword, PASSWORD_DEFAULT)]);
    $userId = (int)$pdo->lastInsertId();
    check('04 User registration', $userId > 0);
    $stmt = $pdo->prepare('SELECT * FROM data_users WHERE username=?'); $stmt->execute(["doctor_{$suffix}"]); $dataUser = $stmt->fetch();
    check('05 User login verification', $dataUser && password_verify($userPassword, $dataUser['password_hash']));

    $stmt = $pdo->prepare('INSERT INTO medical_records(owner_id,patient_label,age,gender,weight,height,heartbeat,pulse_rate,blood_pressure,blood_sugar,cholesterol) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$ownerId,'Automated Synthetic Patient',30,'Other',70,170,70,70,100,100,180]);
    $recordId = (int)$pdo->lastInsertId();
    check('06 Medical record creation', $recordId > 0);

    $plaintext = "Automated encrypted report {$suffix}";
    $ciphertext = encrypt_value($plaintext, 'report:' . $recordId);
    $stmt = $pdo->prepare('INSERT INTO reports(medical_record_id,report_name,keyword,original_filename,stored_filename,mime_type,size_bytes,encrypted_content) VALUES(?,?,?,?,?,?,?,?)');
    $stmt->execute([$recordId,'Automated Test Report','automation-keyword','test.txt',"{$suffix}.txt.enc",'text/plain',strlen($plaintext),$ciphertext]);
    $reportId = (int)$pdo->lastInsertId();
    check('07 Report upload persistence', $reportId > 0);
    check('08 Ciphertext differs from plaintext', $ciphertext !== $plaintext && !str_contains($ciphertext, $plaintext));
    $stmt = $pdo->prepare('SELECT id FROM reports WHERE keyword LIKE ?'); $stmt->execute(['%automation-keyword%']);
    check('09 Report search', (int)$stmt->fetchColumn() === $reportId);

    $stmt = $pdo->prepare("INSERT INTO access_requests(report_id,user_id,status) VALUES(?,?,'pending')"); $stmt->execute([$reportId,$userId]);
    $requestId = (int)$pdo->lastInsertId();
    check('10 Access request creation', $requestId > 0);
    $token = generate_access_token();
    $stmt = $pdo->prepare("UPDATE access_requests SET status='approved',token_hash=?,token_secret=? WHERE id=?");
    $stmt->execute([password_hash($token,PASSWORD_DEFAULT),encrypt_value($token,'access-token:'.$requestId),$requestId]);
    check('11 Owner approval', $stmt->rowCount() === 1);
    $stmt = $pdo->prepare('SELECT * FROM access_requests WHERE id=?'); $stmt->execute([$requestId]); $request = $stmt->fetch();
    check('12 Authorization token verification', password_verify($token,$request['token_hash']));
    check('13 Report decryption', decrypt_value($ciphertext,'report:'.$recordId) === $plaintext);
    check('14 Wrong authorization denied', !password_verify('SKY-WRONG-TOKEN',$request['token_hash']));

    $dominanceRows = [
        ['id'=>1,'blood_pressure'=>120,'blood_sugar'=>90],
        ['id'=>2,'blood_pressure'=>140,'blood_sugar'=>120],
        ['id'=>3,'blood_pressure'=>115,'blood_sugar'=>105],
    ];
    check('15 Skyline dominance function', record_dominates($dominanceRows[0],$dominanceRows[1],['blood_pressure'=>'min','blood_sugar'=>'min']));
    $smallSkyline = compute_skyline($dominanceRows,['blood_pressure'=>'min','blood_sugar'=>'min']);
    check('16 Skyline result generation', count($smallSkyline['skyline']) === 2 && $smallSkyline['dominated'] === 1);

    $performance = $pdo->prepare('SELECT COUNT(*) FROM medical_records WHERE blood_pressure > ?'); $performance->execute([140]);
    check('17 Performance report query', (int)$performance->fetchColumn() === 7);

    $_SESSION['auth']=['id'=>$userId,'username'=>"doctor_{$suffix}",'name'=>'Automated User','role'=>'user'];
    check('18 Approved route authorization', can_access_record($recordId));
    $_SESSION['auth']=['id'=>999999,'username'=>'intruder','name'=>'Intruder','role'=>'user'];
    check('19 Unauthorized route access denied', !can_access_record($recordId));

    $seedRows = $pdo->query('SELECT id,patient_label,blood_pressure,blood_sugar FROM medical_records WHERE id<=20 ORDER BY id')->fetchAll();
    $seedSkyline = compute_skyline($seedRows,['blood_pressure'=>'min','blood_sugar'=>'min']);
    $seedIds = array_map('intval',array_column($seedSkyline['skyline'],'id'));
    $expectedSeedIds = [1,2,3,9,10,16,20];
    check('20 Seeded skyline exact result', $seedIds === $expectedSeedIds);
    if ($seedIds !== $expectedSeedIds) { echo '      Actual seeded skyline IDs: ' . implode(', ', $seedIds) . "\n"; }
    $pdo->rollBack();
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    $failed++;
    echo 'FATAL: ' . $exception->getMessage() . "\n";
}

echo "\nRESULT: {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
