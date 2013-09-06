<?php
function phpshell_help($args){
    foreach($GLOBALS['REGISTERED_FUNCTIONS'] as $cmd => $info){
        echo str_pad($cmd, 25);
        echo $info['help'];
        echo "\n";
    }
}
registerCommand('phpshell_help','help','The command you just called');
?>