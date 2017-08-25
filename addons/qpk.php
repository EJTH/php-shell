<?php
function phpshell_util($args){
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
    case 'rebuild':
      $replace = in_array('--replace', $args);
      $keep = in_array('--keep', $args);

      $addons = array();
      $without = in_array('without', $args);
      $with = in_array('with', $args);

      $dest = false;

      foreach($args as $a){
        if(preg_match("#^--dest=(.+)#", $a, $dest)){
          $dest = $dest[1];
        }
      }

      if($replace && $GLOBALS['phpshell_min']){
        $dest = $GLOBALS['phpshell_path'];
      }

      if(count($args) < 3 || !($keep || $dest)){
        echo "\nYou must at least specify all addons or include / exclude and either --keep, --replace or --dest. --replace and --dest will only keep gz comp if it can";
        echo "\nExamples: ";
        echo "\nqpk rebuild with cd ls qedit qget qput qpk --replace #Replace current phpshell with light custom version";
        echo "\nqpk rebuild without qpk screenprint txttoart --keep  #Keep build folder and build a semi bloated version without qpk, screenprint and txttoart";
        echo "\nqpk rebuild all --dest=/move/build/here              #build with all addons and move build files to dest";
        exit;
      }

      $build_dir = "build_phpshell/php-shell-master";
      $addon_dir = "$build_dir/addons";


      copy('https://github.com/EJTH/php-shell/archive/master.zip','phpshell_master.zip');
      _unzip('phpshell_master.zip', 'build_phpshell/');
      if(file_exists('build_phpshell/')){
        echo "\nBuilding...\n";
        foreach(glob("$addon_dir/*.php") as $a){
          $a = pathinfo($a, PATHINFO_FILENAME);
          $in_list = in_array($a, $args);
          if(($without && $in_list) || ($with && !$in_list)){
            unlink("$addon_dir/$a.php");
          } else {
            $addons[] = $a;
          }
        }

        if(in_array('all', $args)) $addons = "all";

        echo "Building with addons: " . implode(", ",$addons);
        file_put_contents("$build_dir/phpshell-config.php",'<?php $GLOBALS[\'PHPSHELL_CONFIG\'] = json_decode(\'' . json_encode($GLOBALS['PHPSHELL_CONFIG']) . '\',true);?>');

        passthru("cd $build_dir/ && rm -f phpshell-min.php phpshell-min-gz.php && php phpshell-build.php && echo Succesfully build");
        passthru('pwd');
        if($dest){
          copy("$build_dir/phpshell-min.php", $dest);
        }
        if(!$keep){
          echo "removing build dir";
          passthru("rm -rf build_phpshell");
        }
        unlink('phpshell_master.zip');
      }


    break;
  }
}

function _unzip($s,$d){
  $zip = new ZipArchive;
  $res = $zip->open($s);
  if ($res === TRUE) {
    $zip->extractTo($d);
    $zip->close();
    return true;
  }
}
registerCommand('phpshell_util','qpk','Edit various data files. supported: txt');
