<?php
switch($_SERVER["REQUEST_METHOD"]){
case "POST":
	//Initiate a session/log in
	require "../data.php";
	require "../functions.php";
	$request=json_decode(file_get_contents("php://input"));

	//SSO authentication
	if($request->code){

		//First step: exchange authentication code for tokens
		$request1=curl_init(EVE_SSO_ROOT."/oauth/token");
		curl_setopt_array($request1,[
			CURLOPT_USERAGENT     =>EVE_APP_USER_AGENT,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_HTTPHEADER    =>["Authorization: Basic ".EVE_APP_AUTH],
			CURLOPT_POST          =>true,
			CURLOPT_POSTFIELDS    =>"grant_type=authorization_code&code=".urlencode($request->code)]);
		$response1=curl_exec($request1);
		if(curl_error($request1)){
			http_response_code(500);
			exit(curl_error($request1));
		}
		$response1=json_decode($response1);
		if(!$response1->access_token){
			http_response_code(500);
			exit("SSO service hasn't properly responded.");
		}

		//Second step: use the access token to get the character name and ID
		$request2=curl_init(EVE_SSO_ROOT."/oauth/verify");
		curl_setopt_array($request2,[
			CURLOPT_USERAGENT     =>EVE_APP_USER_AGENT,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_HTTPHEADER    =>["Authorization: Bearer ".$response1->access_token]
		]);
		$response2=curl_exec($request2);
		if(curl_error($request2)){
			http_response_code(500);
			exit(curl_error($request2));
		}
		$response2=json_decode($response2);
		if(!$response2->CharacterID){
			http_response_code(500);
			exit("SSO service hasn't properly responded.");
		}

		//Record the login in the database
		$token=base64_encode(random_bytes(LOGIN_TOKEN_LENGTH));
		try{
			DB::get()->beginTransaction();
			//List the character in the users table
			$query1=DB::get()->prepare("INSERT IGNORE INTO `users` (`userID`,`userName`,`profile`) VALUES (?,?,'')");
			$query1->execute([$response2->CharacterID,$response2->CharacterName]);
			//List the character in the userlocations table
			$query2=DB::get()->prepare("INSERT IGNORE INTO `userlocations` (`userID`,`lastUpdated`) VALUES (?,NOW())");
			$query2->execute([$response2->CharacterID]);
			//List the character in the userlocations table
			$query3=DB::get()->prepare("INSERT IGNORE INTO `usermatchesinfo` (userID, matchDate) VALUES (?,DATE_SUB(NOW(),INTERVAL ? MINUTE))");
			$query3->execute([$response2->CharacterID,MATCH_DELAY]);
			//Save/update the tokens in the usertokens table
			$query4=DB::get()->prepare("INSERT INTO `usertokens` (`userID`, `refreshToken`, `accessToken`, `validUntil`) VALUES (?,?,?,DATE_ADD(NOW(),INTERVAL ? SECOND))".
				"ON DUPLICATE KEY UPDATE `refreshToken`=VALUES(`refreshToken`), `accessToken`=VALUES(`accessToken`), `validUntil`=VALUES(`validUntil`)");
			$query4->execute([$response2->CharacterID,$response1->refresh_token,$response1->access_token,$response1->expires_in]);
			//Save a session token in the usersessions table
			$query5=DB::get()->prepare("INSERT IGNORE INTO `usersessions` (`userID`, `validUntil`, `token`, `ip`) VALUES (?,DATE_ADD(NOW(),INTERVAL ? DAY),?,?)");
			$query5->execute([$response2->CharacterID,LOGIN_DURATION_DEFAULT,$token,$_SERVER["REMOTE_ADDR"]]);
			if(!$query5->rowCount()){
				//What are the chances for a duplicate token to be generated?
				DB::get()->rollBack();
				http_response_code(409);
				exit("The user was prevented from logging in by divine intervention");
			}
			DB::get()->commit();
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}

		//Return the session info
		$response=new stdClass();
		$response->userID=$response2->CharacterID;
		$response->userName=$response2->CharacterName;
		$response->token=$token;
		http_response_code(200);
		echo json_encode($response);
		exit;
	}
	//SSO authentication can't work without a code
	else{
		http_response_code(400);
		exit("This endpoint requires an authentication code.");
	}
	break;

case "DELETE":
	//Terminate a session/log out
	require "../data.php";
	$request=json_decode(file_get_contents("php://input"));
	if(!$request||!$request->token||!$request->userID){
		http_response_code(401);
		exit("Need a token to invalidate.");
	}
	else{
		try{
			$query1=DB::get()->prepare("DELETE FROM usersessions WHERE token=? AND userID=?");
			$query1->execute([$request->token,$request->userID]);
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}
		http_response_code(200);
		exit($query1->rowCount());
	}
	break;

default:
	http_response_code(405);
	exit;
}