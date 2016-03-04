<?php
switch($_SERVER["REQUEST_METHOD"]){
case "POST":
	//Record the reaction of a user to one of its matches and add users as mutual contacts if they reacted positively to each other
	require_once "../data.php";
	require_once "../functions.php";
	$request=json_decode(file_get_contents("php://input"));
	if(!$request->userID||!$request->token){
		http_response_code(401);
		exit("User ID and token required.");
	}
	if(!checkSessionToken($request->userID,$request->token)){
		http_response_code(401);
		exit("The supplied token is not valid.");
	}
	if(!isset($request->matchedUser)&&!isset($request->reactionPositive)){
		http_response_code(400);
		exit("Required information wasn't provided.");
	}
	try{
		$query1=DB::get()->prepare("SELECT * FROM `usermatcheslist` WHERE `userID`=? AND `matchedUser`=?");
		$query1->execute([$request->userID,$request->matchedUser]);
		if(!$query1->rowCount()){
			http_response_code(403);
			exit("You weren't matched with this user!");
		}
		$query2=DB::get()->prepare("SELECT * FROM `userreactions` WHERE `userFrom`=? AND `userTo`=?");
		$query2->execute([$request->matchedUser,$request->userID]);
		$result2=$query2->fetch();

		//If the other user already positively reacted to the other one, add them as contacts to each other
		if($result2->reactionPositive){
			addContact($request->matchedUser,$request->userID);
			addContact($request->userID,$request->matchedUser);
		}
		//Either way, record the reaction in the database and remove the user from the list of matches
		DB::get()->beginTransaction();
		$query3=DB::get()->prepare("INSERT INTO `userreactions` (`userFrom`, `userTo`, `reactionPositive`) VALUES (?,?,?)");
		$query3->execute([$request->userID,$request->matchedUser,$request->reactionPositive?1:0]);
		$query4=DB::get()->prepare("DELETE FROM `usermatcheslist` WHERE `userID`=? AND `matchedUser`=?");
		$query4->execute([$request->userID,$request->matchedUser]);
		DB::get()->commit();

		//Return the response
		$response=new stdClass();
		$response->successfulMatch=!!$result2->reactionPositive;
		$response->one=$response1;
		$response->two=$response2;
		http_response_code(200);
		echo json_encode($response);
		exit;

	}catch(PDOException $e){
		http_response_code(500);
		exit($e->getMessage());
	}
	break;

default:
	http_response_code(405);
	exit;
}