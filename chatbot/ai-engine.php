<?php

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| Allow Only POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'reply'  => 'Method not allowed.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Validate API Key
|--------------------------------------------------------------------------
*/

if (
    !defined('ANTHROPIC_API_KEY') ||
    empty(ANTHROPIC_API_KEY)
) {

    echo json_encode([
        'status' => 'error',
        'reply'  => 'Anthropic API key missing.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Validate cURL
|--------------------------------------------------------------------------
*/

if (!function_exists('curl_init')) {

    echo json_encode([
        'status' => 'error',
        'reply'  => 'cURL extension is not enabled.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Get JSON Input
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents('php://input');

$data = json_decode($rawInput, true);

if (!$data || json_last_error() !== JSON_ERROR_NONE) {

    echo json_encode([
        'status' => 'error',
        'reply'  => 'Invalid JSON request.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| User Message
|--------------------------------------------------------------------------
*/

$userMessage = trim($data['message'] ?? '');

$history = $data['history'] ?? [];

if (empty($userMessage)) {

    echo json_encode([
        'status' => 'error',
        'reply'  => 'Please enter a message.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| AI System Prompt
|--------------------------------------------------------------------------
*/

$systemPrompt = "
You are a helpful AI assistant for भटभटे (BhatBhate), Nepal's vehicle rental platform.

You help users with:
- Vehicle rental
- Booking support
- Pricing
- Payment methods
- Locations in Nepal
- Account help
- Booking history

Guidelines:
- Be friendly and professional
- Keep replies concise
- Use simple English
- Use emojis occasionally
- Never ask for passwords or OTP codes
- If unsure, refer users to support:
  Email: hello@bhatbhate.com.np
  Phone: +977 9744368091
";

/*
|--------------------------------------------------------------------------
| Build Conversation Messages
|--------------------------------------------------------------------------
*/

$messages = [];

foreach (array_slice($history, -15) as $item) {

    if (
        isset($item['role']) &&
        isset($item['content'])
    ) {

        $messages[] = [
            'role' => in_array($item['role'], ['user', 'assistant'])
                ? $item['role']
                : 'user',

            'content' => (string)$item['content']
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Add Current User Message
|--------------------------------------------------------------------------
*/

$messages[] = [
    'role' => 'user',
    'content' => $userMessage
];

/*
|--------------------------------------------------------------------------
| API Payload
|--------------------------------------------------------------------------
*/

$payload = [

    // YOUR WORKING MODEL
    'model' => 'claude-sonnet-4-6',

    'max_tokens' => 500,

    'system' => $systemPrompt,

    'messages' => $messages
];

/*
|--------------------------------------------------------------------------
| Initialize cURL
|--------------------------------------------------------------------------
*/

$ch = curl_init();

curl_setopt_array($ch, [

    CURLOPT_URL => 'https://api.anthropic.com/v1/messages',

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POST => true,

    CURLOPT_POSTFIELDS => json_encode($payload),

    CURLOPT_HTTPHEADER => [

        'Content-Type: application/json',

        'x-api-key: ' . ANTHROPIC_API_KEY,

        'anthropic-version: 2023-06-01'
    ],

    CURLOPT_TIMEOUT => 60,

    CURLOPT_CONNECTTIMEOUT => 10,

    // Use TRUE on live server
    CURLOPT_SSL_VERIFYPEER => false,
]);

/*
|--------------------------------------------------------------------------
| Execute API Request
|--------------------------------------------------------------------------
*/

$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$curlError = curl_error($ch);

curl_close($ch);

/*
|--------------------------------------------------------------------------
| cURL Error
|--------------------------------------------------------------------------
*/

if ($curlError) {

    echo json_encode([
        'status' => 'error',
        'reply'  => 'Connection failed: ' . $curlError
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Decode Response
|--------------------------------------------------------------------------
*/

$result = json_decode($response, true);

/*
|--------------------------------------------------------------------------
| Success Response
|--------------------------------------------------------------------------
*/

if (
    $httpCode === 200 &&
    isset($result['content'][0]['text'])
) {

    echo json_encode([

        'status' => 'success',

        'reply' => trim(
            $result['content'][0]['text']
        )
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Error Handling
|--------------------------------------------------------------------------
*/

$errorMessage = $result['error']['message']
    ?? ('HTTP Error ' . $httpCode);

switch ($httpCode) {

    case 400:
        $reply = 'Bad request: ' . $errorMessage;
        break;

    case 401:
        $reply = 'Invalid API key.';
        break;

    case 403:
        $reply = 'Access denied.';
        break;

    case 404:
        $reply = 'Model not found.';
        break;

    case 429:
        $reply = 'Too many requests. Please wait.';
        break;

    case 500:
    case 502:
    case 503:
    case 529:
        $reply = 'Claude AI server overloaded. Try again shortly.';
        break;

    default:
        $reply = $errorMessage;
        break;
}

/*
|--------------------------------------------------------------------------
| Return Error
|--------------------------------------------------------------------------
*/

echo json_encode([

    'status' => 'error',

    'reply' => $reply
]);