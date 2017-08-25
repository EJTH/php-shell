<?php
    //Add phpshell.class.php
    include 'includes/phpshell.class.php';
    $GLOBALS['__CSS'] = '';
    $GLOBALS['__JS'] = '';

    $GLOBALS['phpshell_path'] = __FILE__;

    $it = new RecursiveDirectoryIterator("addons/");

    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        $extension = pathinfo($file,PATHINFO_EXTENSION);
        switch($extension){
            case 'php':
                include_once $file;
            break;
            case 'css':
                @$GLOBALS['__CSS'] .= "\n" .  file_get_contents($file);
            break;
            case 'js':
                @$GLOBALS['__JS'] .= "\n" . file_get_contents($file);
            break;
        }
    }

    $GLOBALS['phpshell'] = new PHPShell();


?>
