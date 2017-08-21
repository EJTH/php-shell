<?php
$GLOBALS['PHPSHELL_CONFIG'] = array(

    /*
     * A MOTD to users
     */
    'MOTD'              => "",

    /*
     * If you know the real path to the php CLI executable, then specify it here,
     * if you don't phpshell will try to guess it. The path to PHP is important
     * for interactive-stdin support,
     * but if php is in PATH env variable, then don't worry
     */
    'PHP_PATH'          => '',

    /*
     * Default mode for PHPShell to work in
     * Possible values:
     * shell_exec         shell_exec will by used for executing commands
     *                    This works as long as shell_exe isn't disabled.
     *                    Downside is that interactive-stdin is not possible.
     *
     * interactive        proc_open will be used together with some dark php
     *                    magic to give a very rough interactive support
     *                    through simple stdin pipes. tty programs will
     *                    not work as expected, if at all.
     *
     *
     */
    'MODE'              => 'interactive-stdin',

    /*
     * Prompt layout:
     * possible variables:
     * %cwd%        Current working directory
     * %hostname%   Hostname
     * %user%       The user running phpshell
     */

    'WIN_PROMPT'        => '%cwd%> ', //Classic DOS style
    //'WIN_PROMPT'        => '(%user%@%hostname%) %cwd%>',

    'NIX_PROMPT'        => '%user%@%hostname%:%cwd% #',

    /*
     * HTTP AUTHENTICATION
     * You might want to protect PHPShell with simple http authentication
     */
    'USE_AUTH'          => true,
    'AUTH_USERNAME'     => 'phpshell',
    'AUTH_PASSWORD'     => 'phpshell',

    'ENV' => array(
        'WIN' => array(),
        'NIX' => array()

    )

);
?>
