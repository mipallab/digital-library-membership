<?php
// Scratch script to test Google ReCAPTCHA siteverify response format
$secret = 'fake_secret_key';
$response = 'dummy_token';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret' => $secret,
    'response' => $response
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$res = curl_exec($ch);
curl_close($ch);

echo "Response from Google:\n";
echo $res . "\n";
