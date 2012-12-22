<?php
require __DIR__ . "/vendor/autoload.php";
use Goutte\Client;

$fc_url_prefix = 'http://fc.momoclo.net';
// login
$login_url = 'https://fc.momoclo.net/pc/login.php';
$photo_url = $fc_url_prefix . '/pc/photo/';

if ($argc < 2) {
	die("usage: php angel_images.php your_id your_password [full path to save image(default create images dir here)]\n");
}

$login_id = $argv[1];
$password = $argv[2];
$target_dir =  __DIR__ . "/images";
if (isset($argv[3])) {
	$target_dir = $argv[3];
} else {
	mkdir($target_dir);
	if(!is_dir($target_dir)) {
		die("can't create images dir here");
	}
}

$client = new Client();

$crawler = $client->request('GET', $login_url);

$form = $crawler->selectButton('logInButton')->form();
		
$crawler = $client->submit($form, array('login_id' => $login_id, 'password' => $password));

$crawler = $client->request('GET', $photo_url);

// get report url list
$report_entry_urls = array_unique($crawler->filter('ul.reportList li a')->extract('href'));
if(!$report_entry_urls) {
	die("can't get report urls maybe login failed");
}

foreach ($report_entry_urls as $report_entry_url) {
	$crawler = $client->request('GET',  $fc_url_prefix . $report_entry_url);
	$image_urls = $crawler->filter('ul#photoList li a')->extract('href');
	foreach ($image_urls as $image_url) {
		$filename = pathinfo($image_url, PATHINFO_BASENAME);
		$save_path = $target_dir . "/" . $filename;
		if(file_exists($save_path)) {
			echo "$save_path::already exists\n";
		} else {
			$crawler = $client->request('GET',  $fc_url_prefix . $image_url);
			$response = $client->getResponse();
			if ($response) {
				file_put_contents($save_path, $response->getContent());
				echo "$save_path::saved\n";
			} else {
				echo "$save_path::image get failed\n";
			}
		}
	}
}