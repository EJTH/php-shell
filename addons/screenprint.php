<?php
function phpshell_prntscrn($args){
    echo '<img src="?printscreen='.time().'" alt="[LOADING SCREENSHOT]" style="max-width:80%" />';
}
if(isset($_GET['printscreen'])){
    $im = imagegrabscreen();
    imagepng($im);
}
if(PHPShell::isWindows())
registerCommand('phpshell_prntscrn','prntscrn','Print screenshot to shell');
?>