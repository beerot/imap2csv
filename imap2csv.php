#!/usr/bin/env php
<?php

// Configuration file
// To create a config file, copy "config.ini.sample" to "config.ini" and
// make your changes.
$config_file = 'config/config.ini';


function main() 
{
    global $config_file;
    $orders = []; // An array that keeps one row per parsed mail

    if(($config = @get_config($config_file)) == false)
    {
        die("Could not read configuration file.\n");
    }

    // Open connection to IMAP server
    $inbox = @imap_open($config['hostname'].$config['folder'], 
        $config['username'], $config['password'])
        or die("Couldn't connect to mail server.\n");

    if(archive_folder_exists($inbox, $config['archive']) == false)
    {
        create_archive_folder($inbox, $config['archive']);    
    }
    
    // Get the emails that interest us
    $emails = imap_search($inbox, "FROM " . $config['sender']);

    // Only continue if we have any emails to process
    if($emails) {
        $orders = parse_mail($inbox, $emails, $config['fields'], $config['archive']);

        output($orders, $config['output'], $config['logfile'], $config['delimiter']);
        
        // Remove moved emails
        imap_expunge($inbox);
    } else {
        echo "No new mails.\n";
    }

    // Close imap handle
    imap_close($inbox);
}


// Encodes text to UTF-8, turns CRLF to LF and ; to :
function clean_message($text)
{
    // decode
    $text = imap_qprint($text);

    // Mail seems to arrive as Latin1
    $text = utf8_encode($text);

    // http://stackoverflow.com/questions/7836632/how-to-replace-different-newline-styles-in-php-the-smartest-way
    // \R matches newline characters in unicode by default.
    // With --enable-bsr-anycrlf it only matches CR, LR or CRLF
    $text = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", $text);

    // Replace ; with :
    $text = str_replace(';', ':', $text);

    return $text;
}


function get_fields($message, $fields)
{
    $data = [];

    foreach($fields as $field)
    {
        // /m modifier should let us use ^ and $ as start and end
        // per line instead of whole string.
        preg_match("/^$field: (.*)$/m", $message, $result);
        $data[] = $result[1];
    }

    return $data;
}


function get_config($config_file)
{
    // Read config file
    // We don't parse section names in our config file so instead of
    // $config['mail']['hostname'] we only get $config['hostname']

    // Older versions return empty array on failure while PHP 5.2.7 and
    // newer returns FALSE
    $config = parse_ini_file($config_file, false);
    if($config != false && !empty($config)) 
    {
        // Splitting the fields string into an array
        $config['fields'] = explode(',', $config['fields']);
    } else {
        $config = false;
    }

    return $config;
}


function output($orders, $output, $log, $delimiter)
{
    $fp_output = fopen($output, 'w');
    $fp_log = fopen($log, 'a');

    // Outputs array to CSV for us
    foreach($orders as $order)
    {
        fputcsv($fp_output, $order, $delimiter);
        fputcsv($fp_log, $order, $delimiter);
    }

    fclose($fp_output);
    fclose($fp_log);
}

function parse_mail($inbox, $emails, $fields, $archive_folder)
{
    $orders = [];

    // Get newest mail on top
    rsort($emails);

    echo "Processing mail";

    foreach($emails as $email_number) {
        echo ".";
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $message = clean_message(imap_fetchbody($inbox, $email_number, 1));

        // array_unshift adds to front of array
        $order = get_fields($message, $fields);
        array_unshift($order, $overview[0]->udate);
        $orders[] = $order;

        // Mark current mail for move
        imap_mail_move($inbox, $email_number, $archive_folder);
    }

    echo "\n";

    return $orders;
}

// Tests if the archive folder we requested exists
function archive_folder_exists($mail_handle, $folder)
{
    //$folder_list = imap_list($mail_handle, $

    return true;
}

// Start program
main();
?>
