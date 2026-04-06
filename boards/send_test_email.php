<?php
// Replace with your Resend API key
$apiKey = 're_AzyocZ26_Lx4bpNbTyHtUFxpikY4mBjjE';

// Email details
$data = [
    "from" => "noreply@veteranlogisticsgroup.us",
    "to" => ["tkprodesign96@gmail.com"],
    "subject" => "Test Email from Resend",
    "text" => "Hello! This is a test email sent via Resend API on page load.",
];

// Initialize cURL
$ch = curl_init("https://api.resend.com/emails");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Send request
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Show result
if ($httpcode == 200 || $httpcode == 201) {
    echo "Test email sent successfully!";
} else {
    echo "Failed to send email. Response: $response";
}
?>
