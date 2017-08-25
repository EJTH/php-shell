<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>PHPShell</title>
        <script type="text/javascript" src="http://code.jquery.com/jquery-2.0.3.min.js"></script>
        <script type="text/javascript">
            // Variables from PHP
            var SHELL_INFO = <?php echo json_encode($this->getShellInfo());?>;

        </script>
        <script type="text/javascript">
            <?php include 'includes/phpshell-core.js'; ?>
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
