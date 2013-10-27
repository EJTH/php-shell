<?php
define('PHPSHELL_EOF_MARK','---EOF');
$REGISTERED_FUNCTIONS = array();

include 'phpshell-config.php';

$args=PHPShell::getArgvAssoc();
if(@$PHPSHELL_CONFIG['USE_AUTH'] && !isset($args['proc'])){
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Authentication required.';
        exit;
    }
    
    if($_SERVER['PHP_AUTH_USER'] != @$PHPSHELL_CONFIG['AUTH_USERNAME']
       || $_SERVER['PHP_AUTH_PW'] != @$PHPSHELL_CONFIG['AUTH_PASSWORD'])
    {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Authentication required.';
        exit;
    }
}

/**
 * Use this function to register functions in PHP as commands callable from the PHPShell.
 *
 * @param type $f
 * @param type $cmd
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
        
        $args=$this->getArgvAssoc();
        
        /*
         * Open a new process and keep it running even though 
         * Client disconnects. using ampersand to spawn a new thread on nix 
         * systems and by using start command on windows.
         */
        if(isset($args['proc'])){
            print_r($args);
            print_r($GLOBALS['argv']);
            
            //Appending DIRECTORY_SEPERATOR because a bug in argv.
            chdir(realpath($args['cwd']).DIRECTORY_SEPARATOR);
            
            $args['proc'] = $args['proc'];
            $this->spawnProcess($args['proc'],$args['handle']);
            exit;
        }
        
        /*
         * If no CLI arguments are catched, we respond to client requests.
         */
        if(isset($_POST['cwd'])){
            chdir($_REQUEST['cwd']);
        }
        if(isset($_POST['action'])){
            switch($_POST['action']){
                case 'exec':
                    $this->runCommand($_POST['cmd'],@$_POST['mode']);
                break;
                case 'suggest':
                    $this->tabSuggest($_POST['input']);
                break;
                case 'proc':
                    $this->readProc($_POST['handle']);
                break;
                case 'stdin':
                    $this->stdinToProc($_POST['stdin'],$_POST['handle']);
                break;
                default:
                    echo json_encode(array('error' => 'Unknown action.'));
                break;
            }
        } else {
            $__CSS = $GLOBALS['__CSS'];
            $__JS = $GLOBALS['__JS'];
            $__PHPSHELL_CONFIG = $GLOBALS['PHPSHELL_CONFIG'];
            
            //Hide these variables from the js variables.
            unset($__PHPSHELL_CONFIG['AUTH_USERNAME']);
            unset($__PHPSHELL_CONFIG['AUTH_PASSWORD']);
            
            
            include 'includes/gui.inc.php';
        }
        
        exit;
        
    }
    
    private $proc;
    private $pipes;
    private $handle;
    
    /**
     * Starts a command as an async proc. that will run forever.
     * This is done by calling phpshell from within a shell to create a worker thread
     * that can feed the client with output and feed the process with client input.
     * @param type $cmd 
     */
    private function startAsyncProc($cmd){
        //Make a unique handle name.
        $handle=md5($cmd.time());
        
        $this->handle = $handle;
        
        file_put_contents($this->getTmpFile('stdout'), '');
        
        $c = $this->getPhpPath()." ".$GLOBALS['phpshell_path']." -handle $handle -proc \"$cmd\" -cwd \"".getcwd()."\"";
        
        echo json_encode(array('handle'=>$handle,'cwd'=>getcwd()));
        chdir(dirname($GLOBALS['phpshell_path']));
        
        if(PHPShell::isWindows()){
            pclose(popen("start /MIN $c", "r"));
        } else {
            shell_exec("nohup $c > /dev/null 2>/dev/null &");
        }
        
        return $handle;
    }
    
    /**
     * Attempts to find the path to the PHP executable on the system.
     * 
     * @return type 
     */
    private function getPhpPath(){
        $pathTests = array();
        $cachedResultFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'phpshell-phpbin-path';
        
        /* Return the path from config, if its specified. */
        if(@isset($GLOBALS['PHPSHELL_CONFIG']['PHP_PATH'])
           && file_exists($GLOBALS['PHPSHELL_CONFIG']['PHP_PATH'])){
            return $GLOBALS['PHPSHELL_CONFIG']['PHP_PATH'];
        }
        
        /* Check saved result of last getPhpPath, if it exists, return it */
        if(file_exists($cachedResultFile)){
            return file_get_contents($cachedResultFile);
        } else {
            if(PHPShell::isWindows()){
                $pathTests = array(
                    'php',
                    'c:\php\php.exe',
                    'c:\php5\php.exe',
                    'c:\xampp\php\php.exe',
                    'c:\wamp\php\php.exe',
                    'c:\program files\php\php.exe'
                   
                );
            } else {
                $pathTests = array(
                    'php',
                    'php-cli',
                    '/bin/php',
                    '/bin/php-cli',
                    '/usr/bin/php',
                    '/usr/bin/php-cli'
                );
            }
            
            foreach($pathTests as $p){
                $r = shell_exec($p.' -v');
                if($r){
                    /* php executable was found */
                    file_put_contents($cachedResultFile, $p);
                    return $p;
                }
            }
        }
        file_put_contents($this->getTmpFile('stdout'), "\nERROR: Could not find php executable.".
                "\nIf you now where it is you can specify it in phpshell-config.php or with the xphppath command\n".
                PHPSHELL_EOF_MARK);
        
        return 'php';
    }
    
    /**
     * Sends stdin to the stdin tempfile
     * STDIN does not work reliably on proc_open so it is essentially unused
     * @param type $stdin
     * @param type $proc 
     */
    private function stdinToProc($stdin,$handle){
        $this->handle = $handle;
        $stdinStr = '';
        foreach($stdin as $n){            
            if($n == "\r"){
                $n = "\n";
            }
            $stdinStr .= $n;
        }
        file_put_contents($this->getTmpFile('stdin'), $stdinStr,FILE_APPEND);
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

   
    /**
     * Return the full path to the specified tempfile.
     * @param type $pipe
     * @return type 
     */
    private function getTmpFile($pipe){
        return dirname(__FILE__).DIRECTORY_SEPARATOR.$this->handle.'-'.$pipe;
    }
    
    /**
     * Spawns a new process in that runs indefinetely and pipe output to a tempfile.
     * @param type $cmd 
     */
    private function spawnProcess($cmd,$handle){
        $this->handle = $handle;
        
        file_put_contents($this->getTmpFile('stdout'), '');
        file_put_contents($this->getTmpFile('stdin'),'');
        
        $descriptors = array(
           0 => array("pipe", "r"),
           1 => array("file", $this->getTmpFile('stdout'),"a"),  // stdout is a pipe that the child will write to
           2 => array("file", $this->getTmpFile('stdout'),"a"), // stderr is a file to write to
        );
      
        //Run the command
        $this->proc = proc_open($cmd, $descriptors, $this->pipes, getcwd());
        
        //Wait a little for the process to open and begin filling stdout
        usleep(250000);
        
        $pinfo = proc_get_status($this->proc);
        $terminate = false;
        while($pinfo['running']){
            echo '.';
            
            //End process on client timeout
            //first clear statcache if long enough time has passed since the last stat
            if(time() - filemtime($this->getTmpFile('stdin')) > 60){
                clearstatcache(true, $this->getTmpFile('stdin'));
            }
            if(time() - filemtime($this->getTmpFile('stdin')) > 60){
                $terminate = true;          
                passthru('pause');
                break;
            }
            
            $stdin = @file_get_contents($this->getTmpFile('stdin'));
            if($stdin !== '' && $stdin !== false){
                
                file_put_contents($this->getTmpFile('stdin'),'');
                fwrite($this->pipes[0],$stdin);
                
            } else {
                usleep(250000);
            }
            
            
            
            $pinfo = proc_get_status($this->proc);
            
        }
        
        proc_close($this->proc);
        
        if($terminate){
           //Remove stdout file since it is never going to be read anyway because
           //the client timed out.
           @unlink($this->getTmpFile('stdout'));
        } else {
            //Indicate to the client that the proc has ended.
            file_put_contents($this->getTmpFile('stdout'),PHPSHELL_EOF_MARK,FILE_APPEND);
        }
        
        
        //remove stdin file:
        @unlink($this->getTmpFile('stdin'));
        
    }
    
    
    
    /**
     * reads the stdout tempfile and returns the result as json to the client.
     * @param type $handle 
     */
    public function readProc($handle){
        $this->handle = $handle;
        $data = "";
        $eof = false;
        $touchCounter = 0;
        
        while($data === ""){
            $data = @file_get_contents($this->getTmpFile('stdout'));
            
            if($data)
                file_put_contents($this->getTmpFile('stdout'), '');
            
            usleep (500000);
            
            //Touch the stdin file once in a while, this indicates 
            //to the process running that the client is still alive.
            if($touchCounter == 0){
                $touchCounter = 30;
                touch($this->getTmpFile('stdin'));
            }
            $touchCounter--;
        }
        if(strpos($data, PHPSHELL_EOF_MARK) !== false){
            $data = str_replace(PHPSHELL_EOF_MARK,'',$data);
            @unlink($this->getTmpFile('stdout'));
            @unlink($this->getTmpFile('stdin'));
            $eof=true;
        }
        
        $detectedEncoding = mb_detect_encoding($data,mb_list_encodings(),true);
        if($detectedEncoding != 'UTF-8'){
            $data = iconv($detectedEncoding, 'UTF-8//TRANSLIT//IGNORE', $data);
        }

        echo json_encode(array('stdin' => htmlentities($data,null,'UTF-8'),'eof'=>$eof));
    }
    
    /**
     * Run a command be it internal or shell executed
     * @param type $cmd
     * @param type $mode proc
     * 
     */
    private function runCommand($cmd,$mode="shell_exec"){
        $output = $this->processInternalCommand($cmd);
        
        $pid = 0;
        if($output === null && ($mode=="shell_exec" || !$mode) ){
            $output = shell_exec($_REQUEST['cmd'].' 2>&1');
            
            
            if($output === null) $output = 'Command not found';
            else $output = htmlspecialchars ($output,null,'UTF-8');
        } elseif($mode == "interactive-stdin" && $output === null) {
            $this->startAsyncProc($cmd);
            exit;
        }
        
        
        $detectedEncoding = mb_detect_encoding($output,  mb_list_encodings(),true);
        if($detectedEncoding != 'UTF-8'){
            $output = iconv($detectedEncoding, 'UTF-8//TRANSLIT//IGNORE', $output);
        }
        
        echo json_encode(array(
            'output' => $output,
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
        $input = explode(' ',$i);
        $cmd = '';
        if(count($input) > 1){
            $search = $input[count($input)-1];
            unset($input[count($input)-1]);
            $cmd = implode(' ',$input).' ';
        } else {
            $search = $input[0];
        }
        foreach(glob("$search*") as $f){
            $suggestions[] = $cmd.$f;
        }
        $suggestions[] = $input;
        echo json_encode(array('suggestions'=>$suggestions));
        exit;
    }
    
    /**
     * Checks if a command is an internal one, executes it if it is, returns null 
     * if it isnt.
     * @global array $REGISTERED_FUNCTIONS
     * @param type $statement
     * @return type 
     */
    private function processInternalCommand($statement){
       global $REGISTERED_FUNCTIONS;
       $statement = explode(' ', $statement,2);
       $cmd = $statement[0];
       $args = @$statement[1];
        
       if(isset($REGISTERED_FUNCTIONS[$cmd])){
           ob_start();
           $commandFunc = $REGISTERED_FUNCTIONS[$cmd]['function'];
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
            'hostname' => gethostname(),
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
