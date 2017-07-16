<?php
/**
 * Modified form https://www.sean.taipei/telegram
 * Auther: Sean <me@sean.taipei>
 */

function getTelegram(string $method, array $query) {
	$json = json_encode($query);

	$url = "https://api.telegram.org/bot" . TG_Token . "/{$method}";

	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $json,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/json; charset=utf-8'
		]
	]);
	$data = curl_exec($curl);   // recive JSON data
	curl_close($curl);

	$data = json_decode($data, true);   // From JSON to Array

	return $data;
}
