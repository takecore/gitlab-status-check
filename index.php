<?php

try
{
	$data = json_decode(file_get_contents('status.json'), true);
}
catch (Exception $e)
{
	echo 'json の読み込みに失敗しました。。。';
	exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>test</title>
	<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
	<h1>Gitlab Status Check</h1>

	<?php if ($data['on_emergency']) : ?>
	<h3 class="bg-danger"><span class="glyphicon glyphicon-alert" aria-hidden="true"></span>&nbsp;Gitlabに問題が発生しているようです。以下のURLから確認してください。</h3>
	<a href="https://status.gitlab.com/"><img src="images/Olaf_0.jpg" alt="" width="500"></a>
	<?php else : ?>
	<h3 class="bg-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>&nbsp;Gitlabは正常に動いてます。進捗どうですか。</h3>
	<a href="https://gitlab.com/"><img src="images/Teemo_0.jpg" alt="" width="500"></a>
	<?php endif ?>
	<br>
	<a href="https://status.gitlab.com/">https://status.gitlab.com/</a>
</div>
</body>
</html>