<?php

/**Write the error to a text file */
function logError($log_msg)
{
    date_default_timezone_set('Europe/Brussels');
    $date = date('d.m.Y H:i:s', time());
    
    // Debugging: Log the value of $_SERVER['DOCUMENT_ROOT']
    $document_root = $_SERVER['DOCUMENT_ROOT'];
    error_log("DOCUMENT_ROOT: " . $document_root); // This will log to the server's error log
    
    
    $log_filename = $document_root . "log";
    
    if (!is_dir($log_filename)) {
        // create directory/folder uploads.
        if (!mkdir($log_filename, 0777, true)) {
            error_log("Failed to create directory: " . $log_filename); // Log error if directory creation fails
            return $log_msg . ": Failed to create directory: " . $log_filename;
        }
    }
    
    
    $log_file_data = $log_filename . '/log_' . date('d_M_Y') . '.log';
    if (file_put_contents($log_file_data, $date . ": " . $log_msg . "\n", FILE_APPEND) === false) {
        error_log("Failed to write to log file: " . $log_file_data); // Log error if file writing fails
        return $log_msg . ": Failed to write to log file: " . $log_file_data;
    }
    
    return $log_msg;

}
