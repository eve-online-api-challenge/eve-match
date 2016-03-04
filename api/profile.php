<?php
switch($_SERVER["REQUEST_METHOD"]){
case "GET":
	//Get the profile of a user
	require "../data.php";
	try{
		$query1=DB::get()->prepare("SELECT * FROM users WHERE userID=?");
		$query1->execute([$_GET["param"]]);
		$result1=$query1->fetch();
	}catch(PDOException $e){
		http_response_code(500);
		exit($e->getMessage());
	}
	if($query1->rowCount() && $result1->profilePublic){
		$response=new stdClass();
		$response->userID=$result1->userID;
		$response->userName=$result1->userName;
		$response->profileBody=$result1->profileBody;
		http_response_code(200);
		echo json_encode($response);
		exit;
	}
	else{
		http_response_code(404);
		exit("Profile does not exist or is not public.");
	}
	break;

case "POST":
case "PUT":
	//Update the profile of a user
	require "../data.php";
	require "../functions.php";
	$request=json_decode(file_get_contents("php://input"));
	if(!$request->userID || !$request->token){
		http_response_code(401);
		exit("User ID and token required.");
	}
	elseif(!checkSessionToken($request->userID,$request->token)){
		http_response_code(401);
		exit("The supplied token is not valid.");
	}
	//Make sure the profile body isn't too long
	elseif(strlen($request->profileBody)>50000){
		http_response_code(413);
		exit("Profile body is too long.");
	}
	else{
		try{
			//If the profile body supplied in the request isn't a string, don't modify it
			if(gettype($request->profileBody)!="string"){
				$query1=DB::get()->prepare("UPDATE users SET profilePublic=? WHERE userID=?");
				$query1->execute([$request->profilePublic?1:0,$request->userID]);
			}
			else{
				$query1=DB::get()->prepare("UPDATE users SET profileBody=?,profilePublic=? WHERE userID=?");
				$query1->execute([$request->profileBody,$request->profilePublic?1:0,$request->userID]);
			}
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}
		http_response_code(200);
		exit;
	}
	break;
default:
	http_response_code(405);
	exit;
}