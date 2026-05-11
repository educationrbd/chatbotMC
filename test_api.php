<?php
// Test file to verify API functionality
require_once 'api.php';

// Test data
$testData = [
    'message' => 'Apa itu wudhu dan bagaimana cara melakukannya?'
];

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $testData;

// Capture output
ob_start();
include 'api.php';
$output = ob_get_clean();

echo "API Test Results:\n";
echo "================\n";
echo $output;
?>
