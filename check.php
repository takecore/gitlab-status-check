<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

define('CHATWORK_API_TOKEN', getenv('CHATWORK_API_TOKEN'));

$filename = 'status.json';
$gitlab_status_url = 'https://status.gitlab.com/';
try
{
	// {
	//     "on_emergency": false, // 無事はfalse: status 全てtrue、有事はtrue: status falseが一つでもある状態
	//     "notificated_at": "2015-10-28 01:30:00",
	//     "status": {
	//         "latency": "OK",
	//         "ssh response time": "OK",
	//         "http project response time": "OK",
	//         "http response time": "OK"
	//     }
	// }
	$data = json_decode(file_get_contents($filename), true);
}
catch (Exception $e)
{
	echo 'json の読み込みに失敗しました。。。';
	exit;
}

try
{
	$cli = new Goutte\Client();
	$crawler = $cli->request('GET', $gitlab_status_url);
}
catch (Exception $e)
{
	// status.gitlab.com が落ちてる。。。
	exit;
}

$crawler->filter('h5')->each(function($node) use (&$data) {
	// $matches 1 'OK', 2 '', 3 'ssh response time'
	preg_match('/(OK|(.+?)) (.+)/', $node->text(), $matches);
	foreach ($data['status'] as $part => $status)
	{
		if ($part === $matches[3])
		{
			$data['status'][$part] = $matches[1];
		}
	}
});

/**
 * tkg に連絡します
 * @params body: string
 * @return Symfony\Component\DomCrawler\Crawler
 */
$nortificate_to_tkg = function($body) {
	$cli = new Goutte\Client();
	$cli->setHeader('X-ChatWorkToken', CHATWORK_API_TOKEN);
	$endpoint = 'https://api.chatwork.com/v1/rooms/'.getenv('ROOM_ID').'/messages';
	$crawler = $cli->request('POST', $endpoint, array(
		'body' => $body,
	));
	return $crawler;
};

$body = 'Gitlab ステータスチェッカーです。'.PHP_EOL;
$do_nortificate = false;
if ($data['on_emergency'])
{
	$status_array = array();
	foreach ($data['status'] as $status)
	{
		$status_array[] = $status;
	}
	$status_array = array_unique($status_array);
	if (count($status_array) === 1 && end($status_array) === 'OK')
	{
		// 無事になりました
		$data['on_emergency'] = false;
		$data['notificated_at'] = date('Y-m-d H:i:s');

		$body .= 'Gitlab が障害から復旧したようです。'.PHP_EOL;
		$body .= PHP_EOL;
		$body .= '進捗どうですか。'.PHP_EOL;
		$do_nortificate = true;
	}
}
else
{
	foreach ($data['status'] as $status)
	{
		if ($status !== 'OK')
		{
			// 有事になりました
			$data['on_emergency'] = false;
			$data['notificated_at'] = date('Y-m-d H:i:s');

			$body .= 'Gitlab に障害発生の可能性があります。'.PHP_EOL;
			$body .= '以下ご確認をお願いします。'.PHP_EOL;
			$body .= $gitlab_status_url.PHP_EOL;
			$do_nortificate = true;
		}
	}
}

if ($do_nortificate)
{
	try
	{
		$crawler = $nortificate_to_tkg($body);
	}
	catch (Exception $e)
	{
		echo 'チャットワークの送信に失敗しました。。。';
		exit;
	}
}

// 書き戻し
$json = json_encode($data);
try
{
	file_put_contents($filename, $json);
}
catch (Exception $e)
{
	echo 'json の書き込みに失敗しました。。。';
	exit;
}

echo 'Gitlab の状態を確認しました';
