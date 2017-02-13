<?php
date_default_timezone_set('UTC');

include 'vendor/autoload.php';

$scanMode = INI_SCANNER_NORMAL;
if (version_compare(PHP_VERSION, '5.6.1') >= 0) {
    $scanMode = INI_SCANNER_TYPED;
}

$config = parse_ini_file('config.ini',true,$scanMode);

$commando = new \Commando\Command();

// Define CLI Args
$commando->option('l')
    ->aka('logname')
    ->describedAs('Single Log file to check. Must include full path. Will override the logfile Array found in the config.')
    ->must(function ($filename)
    {
        return file_exists($filename);
    });

$commando->option('g')
    ->aka('glob')
    ->describedAs('Single filepath pattern to check. Will override the globList Array found in the config.');

$commando->option('t')
    ->aka('to')
    ->describedAs('Email address to send the log to if it has changed. Will append this email to the list in the array');

if (!empty($commando['logname'])) {
    $config['log_list']['logs'] = array($commando['logname']);
}
if (!empty($commando['glob'])) {
    $config['log_list']['globList'] = array($commando['glob']);
}
if (!empty($commando['to'])) {
    $config['mailer']['to'][] = $commando['to'];
}

$now = new DateTime;

// Age in seconds.
$allowedAge = $config['log_list']['age'];

$logFiles = array();

// List of log files to monitor.
$filenames = $config['log_list']['logs'];

// Add in any files found via globbing:
$globList = $config['log_list']['globList'];
if (!empty($globList)) {
    foreach ($globList as $globPattern) {
        foreach (glob($globPattern, GLOB_BRACE) as $fileNamePath) {
            if (file_exists($fileNamePath)) {
                $filenames[] = $fileNamePath;
            }
        }
    }
}

/**
 * A Closure to send the email with any attachments.
 *
 * @param $attachments array
 */
$sendMail = function ($attachments) use ($now, $config)
{

    if (!empty($attachments)) {
        $subject = '[Log Monitor] ' . $now->format('Y-m-d H:i:s Z');
        $message = 'This email was generated by the Log File Monitor. The attached file(s) have changed within the set timeframe. This report was generated at ' . $now->format('Y-m-d H:i:s Z') . '.';

        $mail = new PHPMailer;

        $mail->isSMTP();
        $mail->Host = $config['mailer']['host'];          // Specify main and backup SMTP servers
        $mail->SMTPAuth = $config['mailer']['auth'];      // Enable SMTP authentication
        $mail->Username = $config['mailer']['username'];  // SMTP username
        $mail->Password = $config['mailer']['password'];  // SMTP password
        $mail->SMTPSecure = $config['mailer']['secure'];  // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $config['mailer']['port'];          // TCP port to connect to

        $from = (!empty($config['mailer']['fromAddress']) ? $config['mailer']['fromAddress'] : $config['mailer']['username']);
        $fromName = (!empty($config['mailer']['fromName']) ? $config['mailer']['fromAddress'] : '');

        $mail->setFrom($from, $fromName);

        foreach ($config['mailer']['to'] as $address) {
            $mail->addAddress($address);
        }

        $replyTo = (!empty($config['mailer']['replyAddress']) ? $config['mailer']['replyAddress'] : null);
        if (!is_null($replyTo)) {
            $replyName = (!empty($config['mailer']['replyName']) ? $config['mailer']['replyName'] : '');
            $mail->addReplyTo($replyTo, $replyName);
        }

        $mail->isHTML(false);

        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment->fullPath, $attachment->filename);
        }

        $mail->Subject = $subject;
        $mail->Body    = $message;


        if(!$mail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            echo 'Message has been sent';
        }

    }
};

/**
 * Closure to verify the age of a log file and add it to the attachment list if too recent.
 *
 * @param $filename string
 */
$verifyAndAttachLog = function ($filename) use (&$logFiles, $allowedAge)
{
    if (file_exists($filename)) {

        $rightNow = time();
        $fileTime = filemtime($filename);

        $fileAge = $rightNow - $fileTime;

        if ($fileAge <= $allowedAge) {
            $fileData = new stdClass;
            $fileData->filename = basename($filename) . '-' . date('YmdHis Z',$rightNow) . '.log';
            $fileData->fullPath = $filename;

            $logFiles[] = $fileData;
        }
    }
};

foreach ($filenames as $filename) {
    $verifyAndAttachLog($filename);
}

$sendMail($logFiles);
