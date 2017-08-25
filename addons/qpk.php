<?php
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
registerCommand('phpshell_qpk','qpk','Manage addons (list | install | remove)');
?>
