<?php
function phpshell_qedit($args){

    echo '<div class="qedit" data-file="'.htmlentities($args).'"><textarea>'.htmlentities(@file_get_contents(realpath($args))).'</textarea><button class="save">Save</button></div>';


}

if(isset($_POST['qedit_content']) && isset($_POST['qedit_fn'])){
    echo file_put_contents($_POST['qedit_fn'],$_POST['qedit_content']);
}

registerCommand('phpshell_qedit','qedit','Edit various data files. supported: txt');
?>
