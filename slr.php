<?php
// SLR v1.0
// Simple Log Rotate
// rotates log file, if log is not from today

// Config
// $logfilename = "sertm.log"; //not needed when included
$logfilestokeep = 15;

//start
if (file_exists($logfilename)) {
    if (date ("Y-m-d", filemtime($logfilename)) !== date('Y-m-d')) {
        if (file_exists($logfilename . "." . $logfilestokeep)) {
            unlink($logfilename . "." . $logfilestokeep);
        }
        for ($i = $logfilestokeep; $i > 0; $i--) {
            if (file_exists($logfilename . "." . $i)) {
                $next = $i+1;
                rename($logfilename . "." . $i, $logfilename . "." . $next);
            }
        }
        rename($logfilename, $logfilename . ".1");
    }
}
//stop
?>
