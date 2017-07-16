<?php
/**
 * LINE to Telegram Bridge in PHP
 *
 * Author: Sean <me@sean.taipei>
 * Author URI: https://www.sean.taipei/
 * Version: 1.0
 * License: MIT
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

require('tg-lib.php');
require('config.php');

$httpRequestBody = file_get_contents('php://input');
$hash = hash_hmac('sha256', $httpRequestBody, channelSecret, true);
$signature = base64_encode($hash);
if (!hash_equals($signature, $_SERVER['HTTP_X_LINE_SIGNATURE'])) {   // Verify sender is LINE or not
	exit('Auth Fail');
}

$data = json_decode($httpRequestBody, true);

foreach ($data['events'] as $seq => $event) {   // One request may have multiple message
	$source = $event['source'];
	$group = $source["{$source['type']}Id"] ?? 'ERROR';   // If ID not isset, send to error log
	$ChatID = $CHAT[$group];

	switch ($event['type']) {
		case 'message':
			$msg = $event['message'];
			switch ($msg['type']) {
				case 'text':
					getTelegram('sendMsg', [
						'chat_id' => $ChatID,
						'text' => $msg['text'] . "\n(From LINE)",
						'disable_notification' => true
					]);
					break;

				case 'location':
					getTelegram('sendVenue', [
						'chat_id' => $ChatID,
						'latitude' => $msg['latitude'],
						'longitude' => $msg['longitude'],
						'title' => 'Location from LINE',
						'address' => $msg['address'],
						'disable_notification' => true
					]);
					break;

				case 'image':
					$file = getLineContent($msg['id']);
					$curl = curl_init();
					curl_setopt_array($curl, [
						CURLOPT_URL => 'https://api.telegram.org/bot' . TG_Token . '/sendPhoto?disable_notification=true&caption=(From+LINE)&chat_id=' . $ChatID,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_HTTPHEADER => [
							'Content-Type: multipart/form-data'
						],
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => [
							'photo' => curl_file_create($file, 'image/jpeg', 'Sean.jpg')
						]
					]);
					$result = curl_exec($curl);
					curl_close($curl);
					break;

				case 'video':
					$file = getLineContent($msg['id']);
					$curl = curl_init();
					curl_setopt_array($curl, [
						CURLOPT_URL => 'https://api.telegram.org/bot' . TG_Token . '/sendVideo?disable_notification=true&caption=(From+LINE)&chat_id=' . $ChatID,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_HTTPHEADER => [
							'Content-Type: multipart/form-data'
						],
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => [
							'video' => curl_file_create($file, 'image/mpeg', 'Sean.mp4')
						]
					]);
					$result = curl_exec($curl);
					curl_close($curl);
					break;

				case 'audio':
					$file = getLineContent($msg['id']);
					$curl = curl_init();
					curl_setopt_array($curl, [
						CURLOPT_URL => 'https://api.telegram.org/bot' . TG_Token . '/sendVoice?disable_notification=true&chat_id=' . $ChatID,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_HTTPHEADER => [
							'Content-Type: multipart/form-data'
						],
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => [
							'voice' => curl_file_create($file, 'audio/mpeg', 'Sean.mp3')
						]
					]);
					$result = curl_exec($curl);
					curl_close($curl);
					break;

				case 'file':
					$file = getLineContent($msg['id']);
					$curl = curl_init();
					curl_setopt_array($curl, [
						CURLOPT_URL => 'https://api.telegram.org/bot' . TG_Token . '/sendDocument?disable_notification=true&chat_id=' . $ChatID,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_HTTPHEADER => [
							'Content-Type: multipart/form-data'
						],
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => [
							'document' => curl_file_create($file, 'application/octet-stream', $msg['fileName'])
						]
					]);
					$result = curl_exec($curl);
					curl_close($curl);
					break;
				}
			break;
	}
}

function getLineContent(string $msgID) {
	if (preg_match('/[^0-9]/', $msgID))   // non-numeric string
		return false;

	$fileName = "/tmp/line-$msgID";
	$content = file_get_contents("https://api.line.me/v2/bot/message/{$msgID}/content", false, stream_context_create([   // Get file to varible
		'http' => [
			'header' => [
				'User-Agent: LINE PHP Bot',
				"Authorization: Bearer " . lineChannelAccessToken
			]
		]
	]));
	$file = fopen($fileName, 'w+');   // Open new file
	fwrite($file, $content);   // Write file contest
	fclose($file);
	return $fileName;
}
