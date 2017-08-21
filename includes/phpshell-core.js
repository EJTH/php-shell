var PHPShell = {};

(function(phpshell){
    $(function(){

        var input = '';
        var history = [];
        var currentHistory = 0;
        var $pre = $('<pre>');
        $('body > *').each(function(){
          $(this).remove();
        });
        var mode = 'interactive';
        var supportedModes = ['interactive','shell_exec'];

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

        //xecho
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
                               cwd:SHELL_INFO.cwd,
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
            var cwdStr = SHELL_INFO.prompt_style;
            var cwdVars = {
                cwd      : SHELL_INFO.cwd,
                hostname : SHELL_INFO.hostname,
                user     : SHELL_INFO.user
            };

            $.each(cwdVars,function(i){
                cwdStr = cwdStr.replace('%'+i+'%',this);
            });

            write('\n<span class="cwd">'+cwdStr+'</span>');
        }

        function readProc(){
            $.post(window.location.href,{action:'proc',handle:currentHandle,cwd:SHELL_INFO.cwd}, function(response){


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
            history.push(statement);
            currentHistory = 0;
            $input.val('');
            writeln(statement);

            /*
             * Client side commands
             */

            /* Clear terminal */
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

            /* setmode ... */
            var setMode = statement.match(/^xsetmode *([a-z-_]*)/);

            if(setMode && setMode.length == 2){
                if($.inArray(setMode[1],supportedModes) > -1){
                    mode = setMode[1];
                    writeln('PHPShell mode set to '+mode);
                } else {
                    writeln('"'+setMode[1]+'" is not a valid option.\nSupported modes: '+supportedModes.join(', '));
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

            /* ... If command is not recognized as client side, then send to server */
            $.post(window.location.href,{action:'exec',cwd:SHELL_INFO.cwd,cmd:statement,mode:mode},function(response){
                if(mode == "interactive" && response.handle){
                    currentHandle = response.handle;
                    procStdIn = [];
                    readProc();
                    console.log('1');
                } else {
                    write(response.output);
                    SHELL_INFO.cwd = response.cwd;
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


        writeln(SHELL_INFO.motd);
        writeCwdLine();
    });

})(PHPShell);
