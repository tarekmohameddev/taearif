<?php

/**
 * Property Import Tester Script
 *
 * This script tests the incomplete properties import feature by:
 * 1. Reading CSV test files
 * 2. Converting them to Excel format
 * 3. Calling the import API
 * 4. Validating responses
 * 5. Reporting errors
 *
 * Usage:
 *   PROPERTY_IMPORT_TEST_TOKEN=your-token php PropertyImportTester.php [base_url]
 *   php PropertyImportTester.php [base_url] [token]
 *   php PropertyImportTester.php [base_url] [token] --debug
 *
 * Token is required via the second CLI argument or PROPERTY_IMPORT_TEST_TOKEN env var.
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PropertyImportTester
{
    private $baseUrl;
    private $token;
    private $testFilesDir;
    private $results = [];
    
    public function __construct($baseUrl = 'http://localhost', $token = null)
    {
        $resolvedToken = $token !== null && $token !== ''
            ? (string) $token
            : (string) (getenv('PROPERTY_IMPORT_TEST_TOKEN') ?: '');

        if ($resolvedToken === '') {
            throw new \InvalidArgumentException(
                'Sanctum token required. Pass it as the second constructor/CLI argument '
                . 'or set PROPERTY_IMPORT_TEST_TOKEN in the environment.'
            );
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $resolvedToken;
        $this->testFilesDir = __DIR__ . '/../docs/test-files/';
    }
    
    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "========================================\n";
        echo "Property Import Tester\n";
        echo "========================================\n\n";
        
        $testFiles = [
            '01_SUCCESS_All_Complete_Properties.csv' => [
                'expected_status' => 'success',
                'expected_imported' => 3,
                'expected_incomplete' => 0
            ],
            '02_INCOMPLETE_Missing_Required_Fields.csv' => [
                'expected_status' => 'partial_success',
                'expected_imported' => 0,
                'expected_incomplete' => 10
            ],
            '03_MIXED_Complete_and_Incomplete.csv' => [
                'expected_status' => 'partial_success',
                'expected_imported' => 3,
                'expected_incomplete' => 5
            ],
            '04_VALIDATION_ERRORS_Invalid_Data.csv' => [
                'expected_status' => 'partial_success', // May have validation errors
                'expected_imported' => 0,
                'expected_incomplete' => 0 // May fail validation
            ],
            '05_PARTIAL_DATA_Some_Fields_Missing.csv' => [
                'expected_status' => 'partial_success',
                'expected_imported' => 1,
                'expected_incomplete' => 5
            ]
        ];
        
        foreach ($testFiles as $filename => $expectations) {
            echo "Testing: {$filename}\n";
            echo str_repeat('-', 50) . "\n";
            
            $result = $this->testFile($filename, $expectations);
            $this->results[$filename] = $result;
            
            $this->printResult($result);
            echo "\n";
        }
        
        $this->printSummary();
    }
    
    /**
     * Test a single file
     */
    private function testFile($filename, $expectations)
    {
        $filePath = $this->testFilesDir . $filename;
        
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => "File not found: {$filePath}",
                'file' => $filename
            ];
        }
        
        try {
            // Convert CSV to Excel
            $excelPath = $this->convertCsvToExcel($filePath);
            
            if (!$excelPath) {
                return [
                    'success' => false,
                    'error' => 'Failed to convert CSV to Excel',
                    'file' => $filename
                ];
            }
            
            // Call import API
            $response = $this->callImportApi($excelPath);
            
            // Clean up temp file
            if (file_exists($excelPath)) {
                unlink($excelPath);
            }
            
            // Validate response
            $validation = $this->validateResponse($response, $expectations);
            
            return [
                'success' => $validation['success'],
                'file' => $filename,
                'response' => $response,
                'expectations' => $expectations,
                'validation' => $validation,
                'errors' => $validation['errors'] ?? []
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $filename,
                'trace' => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Convert CSV to Excel format
     */
    private function convertCsvToExcel($csvPath)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Read CSV
            $file = fopen($csvPath, 'r');
            if (!$file) {
                return false;
            }
            
            $row = 1;
            while (($data = fgetcsv($file)) !== false) {
                $col = 'A';
                foreach ($data as $value) {
                    $worksheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }
            fclose($file);
            
            // Save as Excel
            $excelPath = sys_get_temp_dir() . '/' . uniqid('test_import_', true) . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($excelPath);
            
            return $excelPath;
            
        } catch (\Exception $e) {
            echo "Error converting CSV: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Call the import API
     */
    private function callImportApi($excelPath)
    {
        $url = $this->baseUrl . '/api/properties/bulk-import';
        
        // Use CURLFile for proper file upload
        if (class_exists('CURLFile')) {
            $cfile = new \CURLFile($excelPath, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', basename($excelPath));
            $postData = ['file' => $cfile];
        } else {
            // Fallback for older PHP versions
            $boundary = uniqid();
            $delimiter = '-------------' . $boundary;
            
            $postData = '';
            $postData .= "--" . $delimiter . "\r\n";
            $postData .= 'Content-Disposition: form-data; name="file"; filename="' . basename($excelPath) . '"' . "\r\n";
            $postData .= 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' . "\r\n\r\n";
            $postData .= file_get_contents($excelPath) . "\r\n";
            $postData .= "--" . $delimiter . "--\r\n";
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/json'
        ];
        
        if (!class_exists('CURLFile')) {
            $headers[] = 'Content-Type: multipart/form-data; boundary=' . $delimiter;
            $headers[] = 'Content-Length: ' . strlen($postData);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("cURL Error: " . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response: " . json_last_error_msg() . "\nResponse: " . substr($response, 0, 500));
        }
        
        return [
            'http_code' => $httpCode,
            'raw_response' => $response,
            'data' => $decoded,
            'success' => $httpCode >= 200 && $httpCode < 300,
            'curl_info' => $curlInfo
        ];
    }
    
    /**
     * Validate API response against expectations
     */
    private function validateResponse($response, $expectations)
    {
        $errors = [];
        $warnings = [];
        
        if (!$response['success']) {
            $errors[] = "HTTP request failed with code: " . $response['http_code'];
            return ['success' => false, 'errors' => $errors];
        }
        
        $data = $response['data'] ?? [];
        
        // Check status
        $actualStatus = $data['status'] ?? 'unknown';
        if ($actualStatus !== $expectations['expected_status']) {
            $errors[] = "Status mismatch. Expected: {$expectations['expected_status']}, Got: {$actualStatus}";
        }
        
        // Check imported count
        $actualImported = $data['imported_count'] ?? 0;
        if ($actualImported !== $expectations['expected_imported']) {
            $warnings[] = "Imported count mismatch. Expected: {$expectations['expected_imported']}, Got: {$actualImported}";
        }
        
        // Check incomplete count
        $actualIncomplete = $data['incomplete_count'] ?? 0;
        if ($actualIncomplete !== $expectations['expected_incomplete']) {
            $warnings[] = "Incomplete count mismatch. Expected: {$expectations['expected_incomplete']}, Got: {$actualIncomplete}";
        }
        
        // Check for incomplete_properties array if incomplete_count > 0
        if ($actualIncomplete > 0) {
            if (!isset($data['incomplete_properties']) || !is_array($data['incomplete_properties'])) {
                $errors[] = "Missing 'incomplete_properties' array in response";
            } else {
                if (count($data['incomplete_properties']) !== $actualIncomplete) {
                    $count = count($data['incomplete_properties']);
                    $warnings[] = "Incomplete properties array count ({$count}) doesn't match incomplete_count ({$actualIncomplete})";
                }
                
                // Validate incomplete property structure
                foreach ($data['incomplete_properties'] as $index => $property) {
                    if (!isset($property['id'])) {
                        $errors[] = "Incomplete property #{$index} missing 'id' field";
                    }
                    if (!isset($property['missing_fields']) || !is_array($property['missing_fields'])) {
                        $errors[] = "Incomplete property #{$index} missing or invalid 'missing_fields' array";
                    }
                }
            }
        }
        
        // Check for errors array if status is error or partial_success
        if (in_array($actualStatus, ['error', 'partial_success']) && isset($data['errors'])) {
            if (!is_array($data['errors'])) {
                $warnings[] = "'errors' field is not an array";
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'actual_status' => $actualStatus,
            'actual_imported' => $actualImported,
            'actual_incomplete' => $actualIncomplete
        ];
    }
    
    /**
     * Print test result
     */
    private function printResult($result)
    {
        if (isset($result['error'])) {
            echo "❌ ERROR: " . $result['error'] . "\n";
            if (isset($result['trace'])) {
                echo "Trace: " . substr($result['trace'], 0, 200) . "...\n";
            }
            return;
        }
        
        $validation = $result['validation'] ?? [];
        $response = $result['response'] ?? [];
        
        if ($validation['success']) {
            echo "✅ TEST PASSED\n";
        } else {
            echo "❌ TEST FAILED\n";
        }
        
        echo "HTTP Code: " . ($response['http_code'] ?? 'N/A') . "\n";
        
        // Show raw response if there's an error
        if (($response['http_code'] ?? 0) >= 400) {
            $data = $response['data'] ?? [];
            if (isset($data['message'])) {
                echo "Error Message: " . $data['message'] . "\n";
            }
            if (isset($data['details'])) {
                echo "Details: " . json_encode($data['details'], JSON_PRETTY_PRINT) . "\n";
            }
        }
        
        echo "Status: " . ($validation['actual_status'] ?? 'N/A') . "\n";
        echo "Imported: " . ($validation['actual_imported'] ?? 0) . "\n";
        echo "Incomplete: " . ($validation['actual_incomplete'] ?? 0) . "\n";
        
        if (!empty($validation['errors'])) {
            echo "\nErrors:\n";
            foreach ($validation['errors'] as $error) {
                echo "  - {$error}\n";
            }
        }
        
        if (!empty($validation['warnings'])) {
            echo "\nWarnings:\n";
            foreach ($validation['warnings'] as $warning) {
                echo "  ⚠ {$warning}\n";
            }
        }
        
        // Show incomplete properties if any
        $data = $response['data'] ?? [];
        if (!empty($data['incomplete_properties'])) {
            echo "\nIncomplete Properties:\n";
            foreach ($data['incomplete_properties'] as $prop) {
                echo "  - ID: {$prop['id']}, Missing: " . implode(', ', $prop['missing_fields'] ?? []) . "\n";
            }
        }
    }
    
    /**
     * Print summary of all tests
     */
    private function printSummary()
    {
        echo "\n========================================\n";
        echo "Test Summary\n";
        echo "========================================\n\n";
        
        $passed = 0;
        $failed = 0;
        $totalErrors = 0;
        
        foreach ($this->results as $filename => $result) {
            if (isset($result['error'])) {
                $failed++;
                $totalErrors++;
                echo "❌ {$filename}: {$result['error']}\n";
            } elseif (($result['validation']['success'] ?? false)) {
                $passed++;
                echo "✅ {$filename}: PASSED\n";
            } else {
                $failed++;
                $errors = count($result['validation']['errors'] ?? []);
                $totalErrors += $errors;
                echo "❌ {$filename}: FAILED ({$errors} errors)\n";
            }
        }
        
        echo "\n";
        echo "Total Tests: " . count($this->results) . "\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        echo "Total Errors: {$totalErrors}\n";
        
        if ($failed > 0) {
            echo "\n⚠️  Please review the errors above and fix them.\n";
        } else {
            echo "\n✅ All tests passed!\n";
        }
    }
}

// Run tests
if (php_sapi_name() === 'cli') {
    $baseUrl = $argv[1] ?? 'http://localhost';
    $tokenArg = $argv[2] ?? null;
    if ($tokenArg === '--debug') {
        $tokenArg = null;
    }
    $token = ($tokenArg !== null && $tokenArg !== '')
        ? $tokenArg
        : (getenv('PROPERTY_IMPORT_TEST_TOKEN') ?: null);

    // Check if vendor/autoload.php exists
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        echo "ERROR: vendor/autoload.php not found!\n";
        echo "Please run 'composer install' first.\n";
        exit(1);
    }

    // Check if test files directory exists
    $testFilesDir = __DIR__ . '/../docs/test-files/';
    if (!is_dir($testFilesDir)) {
        echo "ERROR: Test files directory not found: {$testFilesDir}\n";
        exit(1);
    }

    if ($token === null || $token === '') {
        echo "ERROR: Sanctum token required.\n";
        echo "Pass it as the second argument or set PROPERTY_IMPORT_TEST_TOKEN.\n";
        echo "Usage: php PropertyImportTester.php [base_url] [token] [--debug]\n";
        echo "Example: PROPERTY_IMPORT_TEST_TOKEN=your-token php PropertyImportTester.php http://localhost:8000\n";
        echo "Example: php PropertyImportTester.php http://localhost:8000 \"your-token\" --debug\n";
        exit(1);
    }

    echo "Base URL: {$baseUrl}\n";
    echo "Test Files Directory: {$testFilesDir}\n";
    echo "\n";

    try {
        $tester = new PropertyImportTester($baseUrl, $token);
        $tester->runAllTests();
    } catch (\Exception $e) {
        echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        $debug = in_array('--debug', $argv, true);
        if ($debug) {
            echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
        }
        exit(1);
    }
} else {
    echo "This script must be run from command line.\n";
    echo "Usage: php PropertyImportTester.php [base_url] [token] [--debug]\n";
    echo "Example: PROPERTY_IMPORT_TEST_TOKEN=your-token php PropertyImportTester.php http://localhost:8000\n";
    echo "Example: php PropertyImportTester.php http://localhost:8000 \"your-token\" --debug\n";
}
