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
    
    // Return success response with all received parameters
    $response = [
        'success' => true,
        'received_parameters' => [
            'bank_code' => $bank_code,
            'branch_code' => $branch_code,
            'account_type' => $account_type,
            'account_number' => $account_number,
            'account_holder_kana' => $account_holder_kana,
            'amount' => $amount
        ],
        'processed_data' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
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
 * Process zengin (Japanese banking) data
 * @param string $bank_code
 * @param string $branch_code
 * @param string $account_type
 * @param string $account_number
 * @param string $account_holder_kana
 * @param int $amount
 * @return array
 */
function processZenginData($bank_code, $branch_code, $account_type, $account_number, $account_holder_kana, $amount) {
    // Example processing for Japanese banking data
    $processed_data = [
        'formatted_bank_code' => str_pad($bank_code, 4, '0', STR_PAD_LEFT),
        'formatted_branch_code' => str_pad($branch_code, 3, '0', STR_PAD_LEFT),
        'formatted_account_number' => $account_number,
        'account_type_description' => getAccountTypeDescription($account_type),
        'formatted_amount' => str_pad($amount, 10, '0', STR_PAD_LEFT),
        'account_holder_kana_upper' => mb_convert_kana($account_holder_kana, "kas"),
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
        '1' => '普通',
        '2' => '当座',
        '3' => '貯蓄',
    ];
    
    return isset($types[$account_type]) ? $types[$account_type] : 'Unknown';
}
// ?>