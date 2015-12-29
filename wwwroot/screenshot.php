<?php
require_once('includes/db.php');

$topic = 0;
$type = 0;
$id = 0;

if (array_key_exists('topic', $_GET))
    $topic = (int)$_GET['topic'];
if (array_key_exists('video', $_GET))
    $type = (int)$_GET['video'];
if (array_key_exists('id', $_GET))
    $id = (int)$_GET['id'];

// Force the topic id to belong to the screenshots, videos or welcome image forum
$sql = '';
$sql .= 'select physical_filename, topic_title, extension ';
$sql .= 'from phpbb3_attachments as a, phpbb3_topics as t ';
$sql .= "where (t.forum_id = 35 or t.forum_id = 34 or t.forum_id = 33) and a.topic_id = t.topic_id ";
if ($topic > 0)
    $sql .= "and a.topic_id = $topic ";
elseif ($id > 0)
    $sql .= "and a.attach_id = $id ";
else
    die('no id or topic');

if (!$type)
    $sql .= "and (extension = 'gif' or extension = 'jpg' or extension = 'jpeg' or extension = 'png')";
else
    $sql .= "and (extension = 'flv')";

$res = mysqli_query($db, $sql);
if (mysqli_num_rows($res) != 1) {
    die('No such screenshot!');
}
$row = mysqli_fetch_array($res);
$fname = 'phpbb/files/' . $row['physical_filename'];

// Perhaps the browser already has the image
$last_modified_time = filemtime($fname);
$etag = md5($fname . $last_modified_time . $topic . $type);

header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
header("Etag: $etag");

if ((array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER) &&
            @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time) ||
        (array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER) &&
            trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag))
{
    header("HTTP/1.1 304 Not Modified");
    exit();
}

// Ok then, transfer it!
$picext = $row['extension'];

$mimetype = 'image/png';
if ($picext == 'png')
        $mimetype = 'image/png';
elseif (($picext == 'jpg') || ($picext == 'jpeg'))
        $mimetype = 'image/jpeg';
elseif ($picext == 'gif')
        $mimetype = 'image/gif';
elseif ($picext == 'flv')
        $mimetype = 'video/x-flv';

header("Content-Type: $mimetype");
header('Content-Length: ' . filesize($fname));
readfile($fname);
exit();
