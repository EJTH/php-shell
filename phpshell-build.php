<?php
    ob_start();
    
    
    $BUILD_DATE = date('Y/m/d');
    $URL = 'http://github.com/EJTH/PHP-Shell';
    
    $revision = (int)file_get_contents('build');
    $revision++;
    
    echo "<?php /* PHPSHELL - (Build: $revision $BUILD_DATE - $URL */ ";
    echo "\n\$phpshell_path = __FILE__;\n";
    
    function recursiveInclude($f){
        $ret = '';
        if($f){
            
            $ret = '/*'.$f.': */ ?>';
            $ret .= replaceIncludes(file_get_contents($f));
            $ret .= '<?php ';
        }
        
        return $ret;
    }
    
    function pregRecursiveInclude($matches){
        return recursiveInclude($matches[1]);
    }
    
    function replaceIncludes($s){
        $includes = array();
        return preg_replace_callback('#include [\'"](.*?)[\'"];#', 'pregRecursiveInclude', $s);
    }
    //Add phpshell.class.php
    echo recursiveInclude('includes/phpshell.class.php');
    
    $it = new RecursiveDirectoryIterator("addons/");
    $JS = '';
    $CSS = '';
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        $extension = pathinfo($file,PATHINFO_EXTENSION);
        switch($extension){
            case 'php':
                echo recursiveInclude($file);
            break;
            case 'css':
                $CSS .= file_get_contents($file);
            break;
            case 'js':
                $JS .= file_get_contents($file);
            break;
        }
    }
    
    echo "\n\$__JS = <<<JSHERE\n".  $JS."\nJSHERE;\n";
    echo "\n\$__CSS = <<<CSSHERE\n". $CSS."\nCSSHERE;\n";

    echo '$phpshell = new PHPShell(); ?>';
    
    $contents = ob_get_clean();
    
    //remove comments and whitespace
    $contents = preg_replace('/[ ]+|\/\*.*?\*\//s'," ",$contents);
    
    $contents = preg_replace('#\n[ ]+//[^\n]+\n#',"\n",$contents);
    
    //Remove php close-opens casued by recursiveInclude()
    $contents = preg_replace('/\?><\?php/','',$contents);
    
    
    
    //Remove more whitespace
    $contents = preg_replace("#[ \t]*\n[ \t]*#","\n",$contents);
    $contents = preg_replace("#[\r\n]+#","\n",$contents);
    
    $replacements = array(
        'PHPSHELL_CONFIG'    => 'PC',
        'registerCommand'    => 'rC',
        'phpshell_' => 'ps_',
        'isWindows'    => 'iW',
        'SHELL_INFO'   => 'SI',
        'new PHPShell' => 'new PS',
        'PHPShell::'   => 'PS::',
        'class PHPShell' => 'class PS'
    );
    
    $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);
    echo $contents;
    
    //Save bumped revision on success
    file_put_contents('build', $revision);
?>