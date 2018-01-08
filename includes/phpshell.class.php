<?php
define('PHPSHELL_EOF_MARK','---EOF');
$GLOBALS['REGISTERED_FUNCTIONS'] = array();

include 'phpshell-config.php';

$args=PHPShell::getArgvAssoc();

function alt_auth(){
  ?>
  <form method="<?php echo $GLOBALS['PHPSHELL_CONFIG']['MODE'] == 'post' ? 'post' : 'get';?>">
    <input type="password" name="psauth"><input type="submit" value="Enter" />
  </form>
  <?php
  exit;
}

if(@$GLOBALS['PHPSHELL_CONFIG']['USE_AUTH'] && !isset($args['cmd'])){
    if(@$_REQUEST['psauth'] == $GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD']
      || @$_COOKIE['psauth'] == md5($GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD'])){
        setcookie('psauth', md5($GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD']),time()+3600);
    } else {
      if (!isset($_SERVER['PHP_AUTH_USER'])) {
          header('WWW-Authenticate: Basic realm="My Realm"');
          header('HTTP/1.0 401 Unauthorized');
          echo 'Authentication required.';
          alt_auth();
          exit;
      }

      if($_SERVER['PHP_AUTH_USER']  != @$GLOBALS['PHPSHELL_CONFIG']['AUTH_USERNAME']
         || $_SERVER['PHP_AUTH_PW'] != @$GLOBALS['PHPSHELL_CONFIG']['AUTH_PASSWORD'])
      {
          header('WWW-Authenticate: Basic realm="My Realm"');
          header('HTTP/1.0 401 Unauthorized');
          echo 'Authentication required.';
          alt_auth();
          exit;
      }
    }
}

/**
 * Use this function to register functions in PHP as commands callable from the PHPShell.
 *
 * @param type $f callable to use for this command
 * @param type $cmd command alias
 * @param type $help Description to be listed in 'help' command
 */
function registerCommand($f,$cmd,$help=''){
    $GLOBALS['REGISTERED_FUNCTIONS'][$cmd] = array('function'=>$f,'help'=>$help);
}


class PHPShell {
    /**
     * Initializes PHPShell client.
     */
    public function __construct(){
        @ob_clean();

        $args=$this::getArgvAssoc();

        /*
         * Open a new process and keep it running even though
         * Client disconnects. using ampersand to spawn a new thread on nix
         * systems and by using start command on windows.
         */
        if(isset($args['cmd'])){
            print_r($args);
            print_r($GLOBALS['argv']);

            //Appending DIRECTORY_SEPERATOR because a bug in argv.
            chdir(realpath($args['cwd']).DIRECTORY_SEPARATOR);

            $this->spawnProcess($args['cmd'],$args['handle']);
            exit;
        }

        /*
         * If no CLI arguments are catched, we respond to client requests.
         */
        if(isset($_REQUEST['cwd'])){
            chdir($_REQUEST['cwd']);
        }
        if(isset($_REQUEST['action'])){
            switch($_REQUEST['action']){
                case 'exec':
                    $this->runCommand($_REQUEST['cmd'],@$_REQUEST['mode']);
                break;
                case 'suggest':
                    $this->tabSuggest($_REQUEST['input']);
                break;
                default:
                    echo json_encode(array('error' => 'Unknown action.'));
                break;
            }
        } else {
            $__CSS = $GLOBALS['__CSS'];
            $__JS = $GLOBALS['__JS'];
            $__PHPSHELL_CONFIG = $GLOBALS['PHPSHELL_CONFIG'];

            // Hide these variables from the js variables.
            unset($__PHPSHELL_CONFIG['AUTH_USERNAME']);
            unset($__PHPSHELL_CONFIG['AUTH_PASSWORD']);


            include 'includes/gui.inc.php';
        }

        exit;

    }

    /**
     * Gets argv with simple "windows" like syntax
     * my-cli.php -stringInput 'this is a string' -int 10 -boolean_flag -another
     * returns:
     * array('stringInput' => 'this is a string', 'int'=>10,'boolean_flag'=>true,'another'=>true)
     * @global type $argv
     */
    public static function getArgvAssoc(){
        global $argv,$argc;
        $arguments = array();

        for($i=0; $i < $argc; $i++){
            $nextIsArg = strpos($argv[$i+1],'-') === 0;

            if(strpos($argv[$i],'-') === 0){
                $arguments[trim($argv[$i],'-')] = (isset($argv[$i+1]) && !$nextIsArg)
                    ? $argv[$i+1] : true;
                if(!$nextIsArg) $i++;
            }
        }

        return $arguments;
    }

    public static function strToArgv($str, $keepQuotes=false){
        preg_match_all("#\"[^\"]+\"|'[^']+'|[^ ]++#", $str, $args);

        $argv = array();
        foreach($args[0] as $arg){
            $argv[] = $keepQuotes ? $arg : str_replace(array('"',"'"), '', $arg);
        }
        return $argv;
    }

    /**
     * Run a command be it internal or shell executed
     * @param type $cmd
     * @param type $mode proc
     *
     */
    private function runCommand($cmd,$mode="shell_exec"){
        $output = $this->processInternalCommand($cmd);
        $html = true;
        if($output === null){
          $html = false;
          if($mode=="shell_exec" || !$mode){
              $output = shell_exec($_REQUEST['cmd'].' 2>&1');
          }
        }

        $detectedEncoding = mb_detect_encoding($output,  mb_list_encodings(),true);
        if($detectedEncoding != 'UTF-8'){
            $output = iconv($detectedEncoding, 'UTF-8//TRANSLIT//IGNORE', $output);
        }

        echo json_encode(array(
            'output' => $output,
            'html'   => $html,
            'cwd'    => getcwd()
        ));
        exit;
    }

    /**
     * Handles suggestion when hitting [TAB] key in the client.
     * @param type $input
     */
    private function tabSuggest($i){
        $suggestions = array();
        $input = self::strToArgv($i);
        $cmd = '';
        if(count($input) > 1){
            $search = $input[count($input)-1];
            unset($input[count($input)-1]);
            $cmd = implode(' ',$input).' ';
        } else {
            $search = $input[0];
            if(preg_match("#[ ]$#", $i)){
                $search = '';
                $cmd = $input[0].' ';
            }
        }

        foreach(glob($search.'*') as $f){
            //append directory separator on dirs
            if(is_dir($f)) $f .= DIRECTORY_SEPARATOR;

            // Encapsulate result in quotes if it contains whitespace
            if(strpos($f,' ') !== false) $f = '"'.$f.'"';

            $suggestions[] = $cmd.$f;
        }

        $suggestions[] = $i;

        echo json_encode(array('suggestions'=>$suggestions));
        exit;
    }


    /**
     * Checks if a command is an internal one, executes it if it is, returns null
     * if it isnt.
     * @global array $GLOBALS['REGISTERED_FUNCTIONS']
     * @param type $statement
     * @return type
     */
    private function processInternalCommand($statement){

       $statement = explode(' ', $statement,2);
       $cmd = $statement[0];
       $args = @$statement[1];
       if(isset($GLOBALS['REGISTERED_FUNCTIONS'][$cmd])){
           ob_start();
           $commandFunc = $GLOBALS['REGISTERED_FUNCTIONS'][$cmd]['function'];
           $commandFunc($args);
           return ob_get_clean();
       }
       return null;
    }

    /**
     * Return information about the shell to the client.
     * Current working dir, prompt format, motd.
     * @return type
     */
    private function getShellInfo(){
        return array(
            'cwd' => getcwd(),
            'motd' => $this->getMotd(),
            'user' => function_exists('posix_getlogin')
                ? posix_getlogin()
                : (
                    (function_exists('shell_exec') && PHPShell::isWindows())
                    ? trim(shell_exec('echo %USERNAME%'))
                    : '?'
                  ),
            'mode' =>  $GLOBALS['PHPSHELL_CONFIG']['MODE'],
            'requestMode' => $GLOBALS['PHPSHELL_CONFIG']['REQUEST_MODE'],
            'hostname' => function_exists('gethostname') ? gethostname() : 'unknown-host',
            'prompt_style' => $GLOBALS['PHPSHELL_CONFIG'][PHPShell::isWindows()?'WIN_PROMPT':'NIX_PROMPT']
        );
    }

    /**
     * Return the MOTD for the shell.
     * @return string
     */
    private function getMotd(){
        $motd = 'PHP-Shell - '.php_uname();

        if(isset($GLOBALS['PHPSHELL_CONFIG']['MOTD']))
            $motd .= "\n\n".$GLOBALS['PHPSHELL_CONFIG']['MOTD']."\n";

        $motd .= "\nEnter 'help' to get started.";
        return $motd;

    }

    /**
     * Returns true if OS is WIN else false and *NIX is assumed.
     * @return type
     */
    public static function isWindows(){
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }


}

?>
