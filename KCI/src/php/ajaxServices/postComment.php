<?php
session_start(); 

require_once dirname(__FILE__).'/../RooloClient.php';
require_once dirname(__FILE__).'/../dataModels/Comment.php';
$roolo = new RooloClient();

$commentText = $roolo->encodeContent($_REQUEST['commentText']);
//$commentText = $_REQUEST['commentText']; 
$ownerUri = $_REQUEST['ownerUri'];
$ownerType = $_REQUEST['ownerType'];
//echo $commentText;


$comment = new Comment();
$comment->set_uri('');
$comment->set_author($_SESSION['username']);
$comment->set_datecreated('');
$comment->set_datelastmodified('');
$comment->set_title($commentText);
$comment->set_ownerType($ownerType);
$comment->set_ownerUri($ownerUri);
$comment->setContent('');


$response = $roolo->addElo($comment);
$savedComment = new Comment($response);

echo $savedComment->generateHtml();
?>