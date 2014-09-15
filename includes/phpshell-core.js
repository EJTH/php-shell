$(function(){
    var input = '';
    var history = [];
    var currentHistory = 0;
    var $pre = $('<pre>');
    var mode = 'interactive-stdin';
    var supportedModes = ['interactive-stdin','shell_exec'];
    
    $('body').append($pre);
    var $output = $('<span class="output"></span>');
    $pre.append($output);
    var $input = $('<input class="input">');
    $pre.append($input);
    
    $('body').append($('<div class="bg"></div>'));

    $input.focus();
    $input.focusout(function(){
        setTimeout(function(){$input.focus();},1000);

    });
    var procStdIn = [];
    
    var currentHandle = false;
    
    var suggestions = [];
    var currentSuggestion = 0;
    var lastKey = 0;
    var echoKeyboard = true;
    
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
               } else
               $input.val(history[history.length-currentHistory]);


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
            
            
            if(response.stdin !== "")
                write(response.stdin);
            
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
    
    //setInterval(sendStdIn,400);
    
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
        
        /* setmode ... */
        var setMode = statement.match(/xsetmode *([a-z-_]*)/);
        
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
            if(mode == "interactive-stdin" && response.handle){
                currentHandle = response.handle;
                procStdIn = [];
                readProc();
            } else {
                write(response.output);
                SHELL_INFO.cwd = response.cwd;
                writeCwdLine();
                animateCursor();
            }
        },"JSON").error(function(r){ write(r.responseText); writeCwdLine(); });
        

    }
    
    $('body').click(function(){
        $input.focus();
    });
    
    writeln(SHELL_INFO.motd);
    writeCwdLine();
});