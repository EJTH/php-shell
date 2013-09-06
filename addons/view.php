<?php
function phpshell_view($args){
    if(file_exists($args)){
        $ext = pathinfo($args,PATHINFO_EXTENSION);
        switch($ext){
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'bmp':
            case 'gif':
                $info = getimagesize($args);
                echo '<img src="?view_img='.urlencode(realpath($args)).'" style="max-width:80%" />';
                echo "\n$info[mime] ($info[0] x $info[1])";
                
            break;
            case 'php':

                echo '<div style="background:#eee; max-height:400px; overflow:auto;">';
                highlight_file($args, false);
                echo '</div>';
            break;
            default:
                $lines = file($args);
                foreach($lines as $l => $s){
                    echo str_pad($l+1,8).htmlentities($s);
                }
            break;
        }
    }
        
}
if(isset($_GET['view_img'])){
    echo file_get_contents($_GET['view_img']);
}
registerCommand('phpshell_view','view','View various data files. supported: jpeg, png, bmp, gif, txt, php');
?>