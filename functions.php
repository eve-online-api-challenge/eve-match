<?php

require_once "data.php";

function checkSessionToken(int $userID,string $token):bool{
	try{
		$query1=DB::get()->prepare("SELECT IF(`validUntil`>NOW(),1,0) AS `isValid` FROM `usersessions` WHERE `token`=? AND `userID`=?");
		$query1->execute([$token,$userID]);
		$result1=$query1->fetch();
		if(!$query1->rowCount()){
			return false;
		}
		elseif(!$result1->isValid){
			$query2=DB::get()->prepare("DELETE FROM `usersessions` WHERE `token`=?");
			$query2->execute([$token]);
			return false;
		}
		else{
			return true;
		}
	}catch(PDOException $e){
		http_response_code(500);
		exit($e->getMessage());
	}
}

function getProperAuthToken(int $userID):string{
	//Get the token information from the `usertokens` table
	//Use some SQL magic to also quickly get concise info on whether a new access token is needed
	try{
		$query1=DB::get()->prepare("SELECT `accessToken`,`refreshToken`,IF(`validUntil`<DATE_ADD(NOW(),INTERVAL 5 SECOND),1,0) AS `needsRefreshing` FROM `usertokens` WHERE `userID`=?");
		$query1->execute([$userID]);
		$result1=$query1->fetch();
	}catch(PDOException $e){
		http_response_code(500);
		exit($e->getMessage());
	}
	//If a new access token is needed, get one
	if($result1->needsRefreshing){
		$request1=curl_init(EVE_SSO_ROOT."/oauth/token");
		curl_setopt_array($request1,[
			CURLOPT_USERAGENT     =>EVE_APP_USER_AGENT,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_HTTPHEADER    =>["Authorization: Basic ".EVE_APP_AUTH],
			CURLOPT_POST          =>true,
			CURLOPT_POSTFIELDS    =>"grant_type=refresh_token&refresh_token=".urlencode($result1->refreshToken)]);
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
		//Save the new token and its expiry date
		try{
			$query2=DB::get()->prepare("UPDATE `usertokens` SET `accessToken`=?,`validUntil`=DATE_ADD(NOW(),INTERVAL ? SECOND) WHERE `userID`=?");
			$query2->execute([$response1->access_token,$response1->expires_in,$userID]);
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}
	}
	//
	return $result1->needsRefreshing?$response1->access_token:$result1->accessToken;
}

function updateUserLocation(int $userID):bool{
	$request1=curl_init(EVE_CREST_AUTH_ROOT."/characters/$userID/location/");
	curl_setopt_array($request1,[
		CURLOPT_USERAGENT     =>EVE_APP_USER_AGENT,
		CURLOPT_RETURNTRANSFER=>true,
		CURLOPT_HTTPHEADER    =>["Authorization: Bearer ".getProperAuthToken($userID)]]);
	$response1=curl_exec($request1);
	if(curl_error($request1)){
		http_response_code(500);
		exit(curl_error($request1));
	}
	$response1=json_decode($response1);
	if(!$response1||!$response1->solarSystem->id){
		try{
			$query1=DB::get()->prepare("UPDATE `userlocations` SET `responseEmpty`=1,`lastUpdated`=NOW() WHERE `userID`=?");
			$query1->execute([$userID]);
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}
		return false;
	}
	else{
		try{
			$query1=DB::get()->prepare("SELECT * FROM `systeminfo` WHERE `systemID`=?");
			$query1->execute([$response1->solarSystem->id]);
			$result1=$query1->fetch();
			$query2=DB::get()->prepare("UPDATE `userlocations` SET `systemID`=?,`knownSpace`=?,`responseEmpty`=0,`lastUpdated`=NOW() WHERE `userID`=?");
			$query2->execute([$result1->systemID,$result1->knownSpace,$userID]);
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}
		return true;
	}
}

function addContact(int $userID, int $contactID, float $standing=0):bool{
	$request=curl_init(EVE_CREST_AUTH_ROOT."/characters/".$userID."/contacts/");
	curl_setopt_array($request,[
		CURLOPT_USERAGENT     =>EVE_APP_USER_AGENT,
		CURLOPT_RETURNTRANSFER=>true,
		CURLOPT_CUSTOMREQUEST =>"POST",
		CURLOPT_POSTFIELDS    =>'{"standing":'.$standing.',"contactType":"Character","contact":{"href":"https://crest-tq.eveonline.com/characters/'.$contactID.'/"},"watched":true}',
		CURLOPT_HTTPHEADER    =>["Authorization: Bearer ".getProperAuthToken($userID),"Content-Type: application/json"]]);
	$response=curl_exec($request);
	if(curl_error($request)){
		http_response_code(500);
		exit(curl_error($request));
	}
	if($response!=""){
		http_response_code(500);
		exit($response);
	}
	return true;
}