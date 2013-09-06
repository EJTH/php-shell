<?php
function phpshell_dl($args){
    $basename = pathinfo($args,PATHINFO_BASENAME);
    file_put_contents($basename, file_get_contents($args));
}
registerCommand('phpshell_dl','dl','Download file from http or ftp');
?>