# sertm
Simple Email Round Trip Monitor (in PHP)

The script sends a test email and checks if it arrives. If not, it can alert you in many ways,
with minimum server requirements. (Basically php, with imap support.)
Need to be started from cron.

Functions:
- configurable checking interval
- log rotation implemented
- monthly reports
- can be monitorod with UpTimeRobot, Pingdom, etc. (just use http check for the status file)

What it does?

//monthly report: sends a report on the first time when run in a month from previous month activity
//send mail: sends test mail to monitoring address
//wait and check mail 1st: wait for x seconds and check if it arrived, if not logs it
//wait and check mail 2nd: wait for x seconds and check if it arrived, if not sends alert

everything is logged, daily logs kept for x days and rotated daily (slr.php)
