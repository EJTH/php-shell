<?php
function phpshell_qpk($args){
  $args = PHPShell::strToArgv($args);
  print_r($args);
  switch($args[0]){
    case 'install':
      $addon_path = $GLOBALS['phpshell_path'] . '/addons/';
      $addon = "https://raw.githubusercontent.com/EJTH/php-shell/master/addons/$addon.php";
      if(file_exists($addon_path)){
        error_level(E_ALL);
        if( copy($addon,$addon_path . $args[1]) ){
          echo "Installed $addon";
        }
        echo "Failed to install $addon";
      } else {
        echo "No addon folder found. Please create writeable directory at $addon_path";
      }
    break;
    case 'list':
      $list = explode("\n",file_get_contents("https://raw.githubusercontent.com/EJTH/php-shell/master/qpk_list"));
      foreach($list as $a){
        if(strpos($a, $args[1]) !== false || empty($args[1])){
          echo "\n$a";
        }
      }
    break;
    default:
      echo "list | install | remove";
    break;
  }
}
registerCommand('phpshell_qpk','qpk','Manage addons (list | install | remove)');
?>
