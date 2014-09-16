<?php
function phpshell_wget($args){
    $args = PHPShell::strToArgv($args);
    if(isset($args[0]) && filter_var($args[0],FILTER_VALIDATE_URL) !== FALSE){
        
        $filename = $args[1] ? $args[1] : pathinfo($args[0],PATHINFO_FILENAME);

        echo "Downloading: $args[0]...\n";
        file_put_contents($filename, file_get_contents($args[0]));
        echo "Saved download as '$filename'\n";
    } else echo "Invalid url\n";
}
registerCommand('phpshell_wget','dl','Download file \'dl source dest\'');
?>