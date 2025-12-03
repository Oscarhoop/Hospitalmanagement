<?php
function handle_error($exception, $context = '') {
    $logMessage = date('[Y-m-d H:i:s]') . " $context - " . $exception->getMessage() .
                  " in " . $exception->getFile() . ":" . $exception->getLine() . "\n";
    
    // Log to file
    error_log($logMessage, 3, __DIR__ . '/logs/error.log');
    
    // Return JSON response for API errors
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Internal server error', 'code' => 500]);
    }
}

// Register shutdown function for fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        handle_error($exception, 'FATAL_ERROR');
    }
});
