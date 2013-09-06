<?php
function phpshell_cd($args){
    if(file_exists($args))
        chdir($args);
    elseif(file_exists(getcwd().'/'.$args))
        chdir(getcwd().'/'.$args);
    echo 'Changed directory to: '.getcwd();
}
registerCommand('phpshell_cd','cd','Change directory');
?>