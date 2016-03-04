<?php
//Update the location info of all users which were active last time their location was checked
require "../data.php";
require "../functions.php";
try{
	$query1=DB::get()->query("SELECT `userID` FROM `userlocations` WHERE `responseEmpty`=0");
	$result=$query1->fetchAll();
}catch(PDOException $e){
	error_log($e->getMessage());
	exit;
}
foreach($result as $user){
	updateUserLocation($user->userID);
}