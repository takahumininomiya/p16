<?php
// データベースの接続情報
define( 'DB_HOST', 'localhost');
define( 'DB_USER', 'root');
define( 'DB_PASS', 'root');
define( 'DB_NAME', 'board');

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

session_start();

// データベースに接続
try {
	$option = array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
	);
	$pdo = new PDO('mysql:charset=UTF8;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);
} catch(PDOException $e) {

	// 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}
	// 空白除去
	$view_name = preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_name']);
	$message = preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['message']);
	// 表示名の入力チェック
	if( empty($view_name) ) {
		$error_message[] = '表示名を入力してください。';
	} else {
		
		// セッションに表示名を保存
		$_SESSION['view_name'] = $view_name;
	}
	
	// メッセージの入力チェック
	if( empty($message) ) {
		$error_message[] = 'ひと言メッセージを入力してください。';
	}
	

	if( empty($error_message) ) {

			// 書き込み日時を取得
		$current_date = date("Y-m-d H:i:s");
// トランザクション開始
$pdo->beginTransaction();

try {
		// SQL作成
		$stmt = $pdo->prepare("INSERT INTO message (view_name, message, post_date) VALUES ( :view_name, :message, :current_date)");

		// 値をセット
		$stmt->bindParam( ':view_name', $view_name, PDO::PARAM_STR);
		$stmt->bindParam( ':message', $message, PDO::PARAM_STR);
		$stmt->bindParam( ':current_date', $current_date, PDO::PARAM_STR);

		// SQLクエリの実行
		$res = $stmt->execute();
		// コミット
		$res = $pdo->commit();

	} catch(Exception $e) {

		// エラーが発生した時はロールバック
		$pdo->rollBack();
	}
		if( $res ) {
			$_SESSION['success_message'] = 'メッセージを書き込みました。';
		} else {
			$error_message[] = '書き込みに失敗しました。';
		}
		
		// プリペアドステートメントを削除
		$stmt = null;
		header('Location: ./');
		exit;
	}

	if( empty($error_message) ) {

		// メッセージのデータを取得する
		$sql = "SELECT view_name,message,post_date FROM message ORDER BY post_date DESC";
		$message_array = $pdo->query($sql);
	}
	// データベースの接続を閉じる
	$pdo = null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<link rel="stylesheet" href="index.css">
<meta charset="utf-8">
<title>ひと言掲示板</title>
</head>
<body>
<h1>ひと言掲示板</h1>
<?php if( empty($_POST['btn_submit']) && !empty($_SESSION['success_message']) ): ?>
    <p class="success_message"><?php echo htmlspecialchars( $_SESSION['success_message'], ENT_QUOTES,
	 'UTF-8'); ?></p> 
	<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
<?php if( !empty($error_message) ): ?>
    <ul class="error_message">
		<?php foreach( $error_message as $value ): ?>
            <li>・<?php echo $value; ?></li>
		<?php endforeach; ?>
    </ul>
<?php endif; ?>
<form method="post">
	<div>
		<label for="view_name">表示名</label>
		<input id="view_name" type="text" name="view_name" value="<?php if( !empty($_SESSION['view_name']) )
		{ echo htmlspecialchars( $_SESSION['view_name'], ENT_QUOTES, 'UTF-8'); } ?>">
	</div>
	<div>
		<label for="message">ひと言メッセージ</label>
		<textarea id="message" name="message"></textarea>
	</div>
	<input type="submit" name="btn_submit" value="書き込む">
</form>
<hr>
<section>
<?php if( !empty($message_array) ){ ?>
<?php foreach( $message_array as $value ){ ?>
<article>
    <div class="info">
	<h2><?php echo htmlspecialchars( $value['view_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <time><?php echo date('Y年m月d日 H:i', strtotime($value['post_date'])); ?></time>
    </div>
	<p><?php echo nl2br( htmlspecialchars( $value['message'], ENT_QUOTES, 'UTF-8') ); ?></p>
</article>
<?php } ?>
<?php } ?>
</section>
</body>
</html>