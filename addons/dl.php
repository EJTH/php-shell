<?php
function _ls_formatbytes($bytes, $precision = 2) { 
    $units = array('B ', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
function phpshell_ls($ls){
    foreach(glob($ls ? $ls : '*') as $file){
        echo str_pad(
                is_dir($file) 
                    ? '--DIR--' 
                    : _ls_formatbytes(filesize($file)),10,'   ',STR_PAD_LEFT) 
                . " " . $file . "\n";
    }
}
if(PHPShell::isWindows())
registerCommand('phpshell_ls','ls','List directory content');

?>