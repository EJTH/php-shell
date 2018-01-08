<?php
function phpshell_qedit($args){

    echo '<div class="qedit" data-file="'.htmlentities($args).'"><textarea>'.@str_replace(['<','>'],['&lt;','&gt;'],file_get_contents(realpath($args))).'</textarea><button class="save">Save</button></div>';


}

if(isset($_REQUEST['qedit_content']) && isset($_REQUEST['qedit_fn'])){
    echo file_put_contents($_REQUEST['qedit_fn'],$_REQUEST['qedit_content']);
    exit;
}

registerCommand('phpshell_qedit','qedit','Edit various data files. supported: txt');
?>
