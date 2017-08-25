<?php
    ob_start();


    $BUILD_DATE = date('Y/m/d');
    $URL = 'http://github.com/EJTH/PHP-Shell';

    $revision = (int)file_get_contents('build');
    $revision++;

    echo "<?php /* PHPSHELL - (Build: $revision $BUILD_DATE - $URL */ ";
    echo "\n\$phpshell_min=true;\n\$phpshell_path = __FILE__;\n";

    function recursiveInclude($f){
        $ret = '';
        if($f){

            $ret = '/* '.$f.': */ ?>';
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
                $JS .= str_replace('$','\\$',file_get_contents($file));
            break;
        }
    }

    echo "\n\$GLOBALS['__JS'] = <<<JSHERE\n".  $JS."\nJSHERE;\n";
    echo "\n\$GLOBALS['__CSS'] = <<<CSSHERE\n". $CSS."\nCSSHERE;\n";

    echo '$GLOBALS[\'phpshell\'] = new PHPShell(); ?>';

    $contents = ob_get_clean();

    //remove comments and whitespace
    $contents = preg_replace('/[ ]+|\/\* .*?\*\//s'," ",$contents);

    $contents = preg_replace('#\n[ ]+//[^\n]+\n#',"\n",$contents);

    //Remove php close-opens casued by recursiveInclude()
    $contents = preg_replace('/\?><\?php/','',$contents);



    //Remove more whitespace
    $contents = preg_replace("#[ \t]*\n[ \t]*#","\n",$contents);
    $contents = preg_replace("#[\r\n]+#","\n",$contents);

    file_put_contents('phpshell-min.php', $contents);

    $s = str_replace(['\\', '$'],['\\\\','\\$'], gzcompress($contents));
    $gz = "<?php \$s = <<<XXX\n$s\nXXX;\n";
    $gz .= 'eval("?>".gzuncompress($s)); ?>';
    file_put_contents('phpshell-min-gz.php', $gz);

    $addons = glob('addons/*.php');
    foreach($addons as &$a){
      $a = pathinfo($a, PATHINFO_FILENAME);
    }

    file_put_contents('qpk_list',implode("\n",$addons));

    //Save bumped revision on success
    file_put_contents('build', $revision);
?>
