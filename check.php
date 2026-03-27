<?php
$js = file_get_contents('rendered.js');
$lines = explode("\n", $js);
$lvl = 0;
foreach($lines as $k => $line) {
    for($i=0; $i<strlen($line); $i++) {
        if ($line[$i] == '{') $lvl++;
        if ($line[$i] == '}') $lvl--;
    }
}
echo "lvl=$lvl\n";
