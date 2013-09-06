<?php
    //Add phpshell.class.php
    include 'includes/phpshell.class.php';
    $__JS = '';
    $__CSS = '';
    
    $phpshell_path = __FILE__;
    
    $it = new RecursiveDirectoryIterator("addons/");
    
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        $extension = pathinfo($file,PATHINFO_EXTENSION);
        switch($extension){
            case 'php':
                include $file;
            break;
            case 'css':
                $__CSS .= file_get_contents($file);
            break;
            case 'js':
                $__JS .= file_get_contents($file);
            break;
        }
    }
    
    $phpshell = new PHPShell();
    
?>