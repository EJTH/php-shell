<?php
function phpshell_txt2art($i,$ret=false){
    $args = PHPShell::strToArgv($i);
    
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
registerCommand('phpshell_txt2art','txt2art','String to "ascii-art"');
?>