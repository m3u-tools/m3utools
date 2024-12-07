<?php
// Allow CORS for cross-domain requests (adjust this for production security)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");

// Handle OPTIONS request for CORS pre-flight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST method is allowed."]);
    exit;
}

// Parse the incoming JSON payload
$input = json_decode(file_get_contents('php://input'), true);

// Check if the 'url' field is provided in the request
if (!isset($input['url']) || empty($input['url'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing or empty 'url' field in the request."]);
    exit;
}

// Get the URL from the request
$targetUrl = $input['url'];

// Validate the URL
if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Invalid URL format."]);
    exit;
}

// Initialize cURL
$ch = curl_init($targetUrl);

// Forwarding Headers (optional, you can add more headers based on your need)
$headers = [];
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers[] = "Authorization: " . $_SERVER['HTTP_AUTHORIZATION'];
}
$headers[] = "User-Agent: M3U-Tools Proxy";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Set cURL options for the request
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set timeout limit
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (adjust for production)
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']); // Forward the method (GET, POST, PUT, etc.)
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input')); // Forward the POST data if applicable

// Execute the request
$response = curl_exec($ch);

// Error Handling
if ($response === false) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Get response status code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close the cURL session
curl_close($ch);

// Return the response to the frontend (along with the HTTP status code)
http_response_code($http_code); // Pass the status code to the client
header("Content-Type: application/json");
echo $response; // Return the response from the target server
?>