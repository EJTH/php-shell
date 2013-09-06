<?php
function phpshell_tail($args){
    
    $data = file($args);
    if(!$data){
        echo 'Could not open file:'.$args;
        return;
    }
    $modifier = array();
    $lc = 25;
    if(preg_match("#-n ([0-9]+)#", $args, $modifier)){
        $lc = $modifier[1];
    }
    
    $count = count($data);
    for($i=max($count-$lc,0);$i<$count;$i++){
        echo str_pad($i+1,5).htmlentities($data[$i]);
    }
}
if(PHPShell::isWindows())
registerCommand('phpshell_tail','tail','Get last lines from file (-n [lc])');
?>