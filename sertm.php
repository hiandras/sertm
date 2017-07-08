<?php
// SERTM v1.0
// Simple Email Round Trip Monitor
// send mail, wait, check if arrives, alert, log

// requires php-imap package in php

// Config
$alertservername = "servername";
$mailfrom = "script@example.com";
$mailto = "monitoring@example.com"; // monitoring address
$pop3server = "pop3.example.com";
$pop3login = "monitoring@example.com";
$pop3password = "password";
$alertto = "alertto@example.com"; // alerting address, if empty, no mail is sent // use , for more addresses
$reportto = "reportto@example.com"; // reporting address, if empty, no mail is sent // use , for more addresses
$waitfor = 60; // seconds, before first and second download
$debug = 1; // 0 or 1, 1 means, echoing the debug information
$logfilename = "log/" . basename(__FILE__, '.php') . ".log"; // you can specify your own name
$reportlogfilename = basename(__FILE__, '.php') . "-report.log"; // you can specify your own name
$datafilename = basename(__FILE__, '.php') . ".data"; // you can specify your own name
$statusfilename = basename(__FILE__, '.php') . "-status.txt"; // you can specify your own name

// Includes
include 'slr.php';

// Functions
function mylog($text, $logfilenamef, $debugf) {
    $text = "[" . date('Y-m-d H:i:s') . "] " . $text . "\n";
    $fp = fopen($logfilenamef,"a");
    fwrite($fp,$text);
    fclose($fp);
    if (1 == $debugf) {
        echo "<p>{$text}</p>";
    }
}

function mymail($mailfromf, $mailtof, $subjectf, $messagef) {
    $headers = 'From: ' . $mailfromf . "\r\n" .
        'Reply-To: ' . $mailfromf . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($mailtof, $subjectf, $messagef, $headers);
}

//start
mylog("Started", $logfilename, $debug);
if (file_exists($datafilename)) {
    $string_data = file_get_contents($datafilename);
    $data = unserialize($string_data);
} else {
    $data = array(0, 0, 0, 0);
}

//monthly report
if (file_exists($datafilename)) {
    if (date ("Y-m", filemtime($datafilename)) !== date('Y-m')) {
        if ($reportto !== "") {
            $subject = "Monthly SERTM report of " . $alertservername;
            $message = "This is the monthly report of your Email Round-Trip Monitor of " . $alertservername . " server\r\n\r\n" .
                       "Month: " . date ("Y-m", filemtime($datafilename)) . "\r\n" .
                       "Emails sent: " . $data[0] . "\r\n" .
                       "Emails not received in " . $waitfor . " seconds: " . $data[1] . "\r\n" .
                       "Emails not received in " . $waitfor * 2 . " seconds: " . $data[2] . "\r\n" .
                       "Alerts sent: " . $data[3] . "\r\n";
            mymail($mailfrom, $reportto, $subject, $message);
            mylog("Report sent", $logfilename, $debug);
        }
        $text = "[" . date ("Y-m", filemtime($datafilename)) . "] " . "Sent: " . $data[0] . " - NotRCVD[1]: " . $data[1] . " - NotRCVD[2]: " . $data[2] . "Alert: " . $data[3] . "\n";
        $fp = fopen($reportlogfilename,"a");
            fwrite($fp,$text);
            fclose($fp);
        $data = array(0, 0, 0, 0);
    }
}

//send mail
$subject = 'monitoring email';
$message = date('Y-m-d H:i:s');
mymail ($mailfrom, $mailto, $subject, $message);
mylog ("Mail sent", $logfilename, $debug);
$data[0]++;

//wait and check mail (twice)
$j = 1;
do {
    sleep($waitfor);
    mylog ("Waited " . $waitfor . " seconds (" . $j . ")", $logfilename, $debug);

    $mbox = imap_open('{'.$pop3server.'/pop3}INBOX', $pop3login, $pop3password);
    $count = imap_num_msg($mbox);
    $arrived = 0 ;
    for($i = 1; $i <= $count; $i++) {
        $header = imap_headerinfo($mbox, $i);
        $raw_body = imap_body($mbox, $i);
        if (strpos($raw_body, $message) !== false) {
            $arrived = 1;
        }
        imap_delete($mbox, $i);
    }
    imap_expunge($mbox);
    imap_close($mbox);
    if (1 == $arrived) {
        mylog ("Mail arrived (" . $j . ")", $logfilename, $debug);
        if (!file_exists($statusfilename)) {
            if ($alertto !== "") {
                $subject = 'SERTM-UP: ' . $alertservername;
                $message .= " mail arrived\r\nNo need to check your server!\r\n";
                mymail($mailfrom, $alertto, $subject, $message);
                mylog ("UP mail sent to " . $alertto, $logfilename, $debug);
            }
            file_put_contents($statusfilename,"SERTM-OK");
        }
    } else {
        mylog ("Mail not found (" . $j . ")", $logfilename, $debug);
        if (file_exists($statusfilename) and 2 == $j) {
            if ($alertto !== "") {
                $subject = 'SERTM-DOWN: ' . $alertservername;
                $message .= " mail not arrived\r\nCheck your server!\r\n";
                mymail($mailfrom,$alertto, $subject, $message);
                mylog ("DOWN mail sent to " . $alertto, $logfilename, $debug);
                $data[3]++;
            }
            if (file_exists($statusfilename)) {
                unlink($statusfilename);
            }
        }
        $data[$j]++;
    }
$j++;
} while (!(3 == $j or 1 == $arrived));

//stop
$string_data = serialize($data);
file_put_contents($datafilename, $string_data);
mylog ("Stopped", $logfilename, $debug);
?>
