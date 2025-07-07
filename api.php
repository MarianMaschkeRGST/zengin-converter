<?php
// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed (uncomment if calling from browser)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get URL parameters - Fixed the parameter retrieval
    $bank_code = isset($_GET['bank_code']) ? $_GET['bank_code'] : '';
    $branch_code = isset($_GET['branch_code']) ? $_GET['branch_code'] : '';
    $account_type = isset($_GET['account_type']) ? $_GET['account_type'] : '';
    $account_number = isset($_GET['account_number']) ? $_GET['account_number'] : '';
    $account_holder_kana = isset($_GET['account_holder_kana']) ? $_GET['account_holder_kana'] : '';
    $amount = isset($_GET['amount']) ? (int)$_GET['amount'] : 0;

    // Validate required parameters
    if (empty($bank_code)) {
        throw new Exception('Missing required parameter: bank_code');
    }
    
    if (empty($branch_code)) {
        throw new Exception('Missing required parameter: branch_code');
    }
    
    if (empty($account_number)) {
        throw new Exception('Missing required parameter: account_number');
    }
    


    // Process the data
    $result = processZenginData($bank_code, $branch_code, $account_type, $account_number, $account_holder_kana, $amount);

    $response = $result;
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Return error response
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'received_parameters' => [
            'bank_code' => isset($_GET['bank_code']) ? $_GET['bank_code'] : null,
            'branch_code' => isset($_GET['branch_code']) ? $_GET['branch_code'] : null,
            'account_type' => isset($_GET['account_type']) ? $_GET['account_type'] : null,
            'account_number' => isset($_GET['account_number']) ? $_GET['account_number'] : null,
            'account_holder_kana' => isset($_GET['account_holder_kana']) ? $_GET['account_holder_kana'] : null,
            'amount' => isset($_GET['amount']) ? (int)$_GET['amount'] : null
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    http_response_code(400);
    echo json_encode($response, JSON_PRETTY_PRINT);
}

/**
 * Validate and get bank information
 * @param string $bank_code
 * @return array|null
 */
function validateBankCode($bank_code) {
    $formatted_bank_code = str_pad($bank_code, 4, '0', STR_PAD_LEFT);
    
    // Load banks.json file
    $banks_file = __DIR__ . '/banks.json';
    if (!file_exists($banks_file)) {
        throw new Exception('Banks data file not found: banks.json');
    }
    
    $banks_data = json_decode(file_get_contents($banks_file), true);
    if ($banks_data === null) {
        throw new Exception('Invalid banks.json file format');
    }
    
    if (!isset($banks_data[$formatted_bank_code])) {
        throw new Exception("Invalid bank code: {$bank_code} (formatted: {$formatted_bank_code})");
    }
    
    return $banks_data[$formatted_bank_code];
}


/**
 * Validate and get branch information
 * @param string $bank_code
 * @param string $branch_code
 * @return array|null
 */
function validateBranchCode($bank_code, $branch_code) {
    $formatted_bank_code = str_pad($bank_code, 4, '0', STR_PAD_LEFT);
    $formatted_branch_code = str_pad($branch_code, 3, '0', STR_PAD_LEFT);
    
    // Load specific bank's branch file
    $branch_file = __DIR__ . "/branches/{$formatted_bank_code}.json";
    if (!file_exists($branch_file)) {
        throw new Exception("Branch data file not found for bank code: {$bank_code} (file: branches/{$formatted_bank_code}.json)");
    }
    
    $branch_data = json_decode(file_get_contents($branch_file), true);
    if ($branch_data === null) {
        throw new Exception("Invalid branch file format for bank code: {$bank_code}");
    }
    
    if (!isset($branch_data[$formatted_branch_code])) {
        throw new Exception("Invalid branch code: {$branch_code} (formatted: {$formatted_branch_code}) for bank code: {$bank_code}");
    }
    
    return $branch_data[$formatted_branch_code];
}


/**
 * Process zengin data
 * @param string $bank_code
 * @param string $branch_code
 * @param string $account_type
 * @param string $account_number
 * @param string $account_holder_kana
 * @param int $amount
 * @return array
 */
function processZenginData($bank_code, $branch_code, $account_type, $account_number, $account_holder_kana, $amount) {
    // Validate bank code and get bank information
    $bank_info = validateBankCode($bank_code);
    // Validate branch code and get branch information
    $branch_info = validateBranchCode($bank_code, $branch_code);

    // Example processing for Japanese banking data
    $processed_data = [
        'formatted_bank_code' => str_pad($bank_code, 4, '0', STR_PAD_LEFT),
        'formatted_branch_code' => str_pad($branch_code, 3, '0', STR_PAD_LEFT),
        'formatted_account_number' => $account_number,
        'account_type_description' => getAccountTypeDescription($account_type),
        'formatted_amount' => str_pad($amount, 10, '0', STR_PAD_LEFT),
        'account_holder_kana_hankaku' => mb_convert_kana($account_holder_kana, "kas"),
        'valdiate_bank_name' => $bank_info['name'],
        'valdiate_branch_name' => $branch_info['name'],
    ];
    
    return $processed_data;
}

/**
 * Get account type description
 * @param string $account_type
 * @return string
 */
function getAccountTypeDescription($account_type) {
    $types = [
        '普通' => '1',
        '当座' => '2',
        '貯蓄' => '4',
    ];
    
    return isset($types[$account_type]) ? $types[$account_type] : 'Unknown';
}
// ?>