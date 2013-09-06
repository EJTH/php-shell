<?php
function phpshell_eval($args){
    if(preg_match('#;|echo\b|print[ (]|return#', $args))
        eval($args);
    else
        var_dump(eval("return $args;"));
}

registerCommand('phpshell_eval','eval','PHP eval()');
?>