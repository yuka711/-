<?php
header("Content-Type: text/html; charset=UTF-8");

//データベース接続
$dsn = 'データベース名';
$user = 'ユーザー名';
$password = 'パスワード';
$pdo = new PDO($dsn,$user,$password);

//テーブル作成
$sql = "CREATE TABLE mission6_keijiban_2"
."("
."id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,"
."name char(32),"
."comment TEXT,"
."userid TEXT,"
."pass TEXT,"
."syozoku TEXT,"
."mail TEXT,"
."day TEXT"
.");";
$stmt = $pdo->query($sql);

session_start();

//セッション情報が空（ログイン画面を通さずにアクセス）の時、ログアウト画面へ遷移
if(!isset($_SESSION['loginid']) || !isset($_SESSION['loginpass'])){
	header('Location: mission6_logout.php');
	exit();
}

//ログイン時に入力したID,パスワードを会員情報から探し、情報を変数化
$sql = 'SELECT * FROM mission6_kaiin_1';
$results = $pdo -> query($sql);
foreach($results as $row){
	if($row['userid'] == $_SESSION['loginid'] && $row['userpass'] == $_SESSION['loginpass']){
		$username = $row['username'];
		$userpass = $row['userpass'];
		$userid = $row['userid'];
		$syozoku = $row['syozoku'];
		$mail = $row['mail'];
	}
}
?>

<title>掲示板</title>
<font size = "5" color = "blue">
<?php echo 'ようこそ'.' '.$username.'さん！'; ?><br>
</font>
<font color = "blue">
<?php echo 'ご自分の投稿は名前を青で表示しております。'; ?>
</font>

<?php
//編集モード
if(!empty($_POST['hensyu'])){
	$sql = 'SELECT * FROM mission6_keijiban_2';
	$results = $pdo -> query($sql);
	foreach($results as $row){
		//投稿番号=編集番号の時、本人なら変数取得
		if($row['id'] == $_POST['hensyu'] && $row['userid'] == $userid){
			$aftercomment = $row['comment'];
			$afterhide = $row['id'];
			echo '<font color = "red">投稿内容を書き直してください</font>';
		}
	}
}
?>

<form action = 'mission6_keijiban.php' method = 'post' accept-charset='UTF-8'>
　投稿　：<input type = 'text' name = 'comment' value = "<?=$aftercomment?>" placeholder = 'こんにちは！'>
<input type = 'hidden' name = 'hide' value = "<?=$afterhide?>">
<input type = 'submit' value = '送信'><br><br>
削除番号：<input type = 'text' name = 'sakuzyo' placeholder = '半角数字'>
<input type = 'submit' value = '削除'><font color = "gray">　ご自分の投稿を消せます</font><br><br>
編集番号：<input type = 'text' name = 'hensyu' placeholder = '半角数字'>
<input type = 'submit' value = '編集'><font color = "gray">　ご自分の投稿を書き換えられます</font><br><br>
　検索　：<input type="text" name="word" placeholder = '説明会'><font color = "gray">　キーワード・所属で検索ができます。</font><br>
<input type = 'checkbox' name = 'syozoku[]' value = '学生'>学生
<input type = 'checkbox' name = 'syozoku[]' value = '転職者'>転職者
<input type = 'checkbox' name = 'syozoku[]' value = '人事担当者'>人事担当者<br>
<input type="reset" value="リセットボタン">
<input type="submit" value="検索"><br><br>
<input type = 'submit' name = 'maypage' value = 'マイページ'>
<input type = 'submit' name = 'logout' value = 'ログアウト'><br>
</form>

<?php
//マイページ
if(!empty($_POST['maypage'])){
	header('Location: mission6_mypage.php');
	exit();
//ログアウト
}else if(!empty($_POST['logout'])){
	header('Location: mission6_logout.php');
	exit();
//編集機能
}else if(!empty($_POST['comment']) && !empty($_POST['hide'])){
	$id = $_POST['hide'];
	$kome = $_POST['comment'];
	$day = date( "Y年m月d日 h:i:s" );
	$sql = "update mission6_keijiban_2 set comment = '$kome', day = '$day' where id = '$id'";
	$result = $pdo -> query($sql);
	//メッセージ
	echo '<font color = "red">投稿内容を編集しました</font>'.'<br>';

//削除機能
}else if(!empty($_POST['sakuzyo'])){
	//メッセージ
	$sql = 'SELECT * FROM mission6_keijiban_2';
	$results = $pdo -> query($sql);
	foreach($results as $row){
		//投稿番号=削除番号の時、本人ならメッセージ
		if($row['id'] == $_POST['sakuzyo'] && $row['userid'] == $userid){
			echo '<font color = "red">投稿内容を削除しました</font>'.'<br>';
		}
	}
	//削除
	$id = $_POST['sakuzyo'];
	$sql = "delete from mission6_keijiban_2 where id = '$id' and userid = '$userid'";
	$result = $pdo -> query($sql);

//投稿機能
}else if(!empty($_POST['comment'])){
	//データ入力
	$sql = $pdo -> prepare("INSERT INTO mission6_keijiban_2(name,comment,userid,pass,syozoku,mail,day)VALUES(:name,:comment,:userid,:pass,:syozoku,:mail,:day)");
	$sql -> bindParam(':name', $name, PDO::PARAM_STR);
	$sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
	$sql -> bindParam(':userid', $userid, PDO::PARAM_STR);
	$sql -> bindParam(':pass', $pass, PDO::PARAM_STR);
	$sql -> bindParam(':syozoku', $syozoku, PDO::PARAM_STR);
	$sql -> bindParam(':mail', $mail, PDO::PARAM_STR);
	$sql -> bindParam(':day', $day, PDO::PARAM_STR);

	$name = $username;
	$comment = $_POST['comment'];
	$pass = $userpass;
	$day = date( "Y年m月d日 h:i:s" );
	$sql -> execute();
//検索機能
}else{
	//検索フォームの全角スペースなどを半角スペース化
	$not_word = array(",", "、", "　", "。", ".");
	$new_word = str_replace($not_word, ' ', $_POST['word']);

	//1語ずつに分割
	$words = explode(' ', $new_word);

	//空の時と半角スペース連続を除去
	if(count($words) > 0){
		foreach ($words as $word) {
			if (!empty($word)) {
				$final_words[] = $word;
			}
		}
	}
	//whereの中の句を作る
	if(count($final_words) > 0){
		foreach ($final_words as $word) {
			$parts[] = "name LIKE "."'"."%".$word."%"."'"." OR comment LIKE "."'"."%".$word."%"."'";
		}
		//orで繋げる
		$connect = implode(' OR ',$parts);
	}
	//チェックボックスの句を作る
	if(!empty($_POST['syozoku'])){
		foreach($_POST['syozoku'] as $value){
			$check[] = "syozoku LIKE '%".$value."%'";
		}
		//orで繋げる
		$checkset = implode(' OR ',$check);
	}
}
//入力したデータを表示
//検索フォームとチェックボックスで検索の時
if(!empty($connect) && !empty($checkset)){
	$sql = "SELECT * FROM mission6_keijiban_2 WHERE (".$connect.") AND (".$checkset.")";
	$results = $pdo -> query($sql);
	$data = $results -> fetchAll();
	foreach($data as $row){
		if($userid == $row['userid']){
			echo $row['id'].':';
			echo '<font color = "blue">'.$row['name'].'</font>'.':';
			echo $row['syozoku'].':';
			echo $row['day'].'<br>';
			echo $row['comment'].'<br>'.'<br>';
		}else{
			echo $row['id'].':';
			echo $row['name'].':';
			echo $row['syozoku'].':';
			echo $row['day'].'<br>';
			echo $row['comment'].'<br>'.'<br>';
		}
	}
//検索フォームでのみ検索の時
}else if(!empty($connect)){
	$sql = "SELECT * FROM mission6_keijiban_2 WHERE ".$connect;
	$results = $pdo -> query($sql);
	foreach($results as $row){
		if($userid == $row['userid']){
			echo $row['id'].':';
			echo '<font color = "blue">'.$row['name'].'</font>'.':';
			echo $row['syozoku'].':';
			echo $row['day'].'<br>';
			echo $row['comment'].'<br>'.'<br>';
		}else{
			echo $row['id'].':';
			echo $row['name'].':';
			echo $row['syozoku'].':';
			echo $row['day'].'<br>';
			echo $row['comment'].'<br>'.'<br>';
		}
	}
//チェックボックスでのみ検索の時
}else if(!empty($checkset)){
	$sql = "SELECT * FROM mission6_keijiban_2 WHERE ".$checkset;
	$results = $pdo -> query($sql);
	foreach($results as $row){
		if($userid == $row['userid']){
			echo $row['id'].':';
			echo '<font color = "blue">'.$row['name'].'</font>'.':';
			echo $row['syozoku'].':';
			echo $row['day'].'<br>';
			echo $row['comment'].'<br>'.'<br>';
		}else{
			echo $row['id'].':';
			echo $row['name'].':';
			echo $row['syozoku'].':';
			echo $row['day'].'<br>';
			echo $row['comment'].'<br>'.'<br>';
		}
	}
//検索していないとき
}else{
	$sql = "SELECT * FROM mission6_keijiban_2";
	$results = $pdo -> query($sql);
	foreach($results as $row){
		if($userid == $row['userid']){
			echo $row['id'].':';
			echo '<font color = "blue">'.$row['name'].'</font>'.':';
			echo $row['syozoku'].':';
			echo $row['day'].'<br>';
			echo $row['comment'].'<br>'.'<br>';
		}else{
			echo $row['id'].':';
			echo $row['name'].':';
			echo $row['syozoku'].':';
			echo $row['day'].'<br>';
			echo $row['comment'].'<br>'.'<br>';
		}
	}
}
?>
