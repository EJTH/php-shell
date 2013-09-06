<?php
function phpshell_lc($args){
    echo count(file($args));
}
if(PHPShell::isWindows())
registerCommand('phpshell_lc','lc','line count');
?>