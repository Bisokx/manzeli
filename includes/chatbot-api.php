<?php
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// ============================================================
// CONFIGURATION — Replace with your actual Claude API key
// ============================================================
define('CLAUDE_API_KEY', 'YOUR_API_KEY_HERE'); // <-- PUT YOUR KEY HERE
define('CLAUDE_MODEL', 'claude-sonnet-4-20250514');
define('CLAUDE_MAX_TOKENS', 500);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get the message from the request
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Get conversation history from session (last 10 messages for context)
if (!isset($_SESSION['chatbot_history'])) {
    $_SESSION['chatbot_history'] = [];
}

// Build the system prompt with Manzeli context
$systemPrompt = <<<PROMPT
You are Manzeli's AI assistant — a helpful, friendly chatbot for a Lebanon-based real estate platform called Manzeli (منزلي). Your job is to help users navigate the platform and answer questions about real estate in Lebanon.

About Manzeli:
- Manzeli is a real estate platform for renting, buying properties, and purchasing land in Lebanon
- Users can browse listings, filter by type (Rent/Buy/Land), location, price, bedrooms, etc.
- Guests can book rentals, send purchase inquiries, save favorites, and leave reviews
- Hosts can list their properties for rent, sale, or land sale
- The platform supports three listing types: Rent (with booking), Buy (with contact form), Land (with info request)

What you can help with:
- How to search and filter properties
- How to book a rental property
- How to send a purchase or land inquiry
- How to become a host and list a property
- How to manage bookings and listings
- General questions about renting/buying in Lebanon
- Account and profile questions

Guidelines:
- Keep responses concise (2-3 sentences when possible)
- Be warm and professional
- Use English primarily, but understand Arabic terms
- If asked about specific property prices or availability, direct users to browse the listings page
- If asked something outside your scope, politely say you can help with Manzeli-related questions
- Never make up property listings or prices
PROMPT;

// Build messages array with history
$messages = [];
foreach ($_SESSION['chatbot_history'] as $msg) {
    $messages[] = $msg;
}
$messages[] = ['role' => 'user', 'content' => $userMessage];

// Call Claude API
$response = callClaudeAPI($systemPrompt, $messages);

if (isset($response['error'])) {
    echo json_encode(['error' => $response['error']]);
    exit;
}

$assistantMessage = $response['content'][0]['text'] ?? 'Sorry, I could not process your request.';

// Update conversation history (keep last 10 exchanges = 20 messages)
$_SESSION['chatbot_history'][] = ['role' => 'user', 'content' => $userMessage];
$_SESSION['chatbot_history'][] = ['role' => 'assistant', 'content' => $assistantMessage];
if (count($_SESSION['chatbot_history']) > 20) {
    $_SESSION['chatbot_history'] = array_slice($_SESSION['chatbot_history'], -20);
}

// Log to database
try {
    $userId = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO chatbot_logs (user_id, user_message, bot_response, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$userId, $userMessage, $assistantMessage]);
} catch (PDOException $e) {
    // Don't fail the response if logging fails
    error_log('Chatbot log error: ' . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'message' => $assistantMessage
]);

// ============================================================
// Claude API Call Function
// ============================================================
function callClaudeAPI($systemPrompt, $messages) {
    $url = 'https://api.anthropic.com/v1/messages';

    $data = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => CLAUDE_MAX_TOKENS,
        'system' => $systemPrompt,
        'messages' => $messages
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . CLAUDE_API_KEY,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'Connection error. Please try again.'];
    }

    $decoded = json_decode($response, true);

    if ($httpCode !== 200) {
        error_log('Claude API error: ' . $response);
        return ['error' => 'AI service temporarily unavailable. Please try again later.'];
    }

    return $decoded;
}
?>
