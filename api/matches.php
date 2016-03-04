<?php
switch($_SERVER["REQUEST_METHOD"]){
case "POST":
	//Get a list of potential matches within the specified range
	require "../data.php";
	require "../functions.php";
	$request=json_decode(file_get_contents("php://input"));
	if(!$request->userID||!$request->token){
		http_response_code(401);
		exit("User ID and token required.");
	}
	if(!checkSessionToken($request->userID,$request->token)){
		http_response_code(401);
		exit("The supplied token is not valid.");
	}

	//Check if the matching delay has expired, only construct a new list of matches if it has
	try{
		$query1=DB::get()->prepare("SELECT IF(`matchDate`<DATE_SUB(NOW(),INTERVAL ? MINUTE),1,0) AS `giveNewList` FROM `usermatchesinfo` WHERE `userID`=?");
		$query1->execute([MATCH_DELAY,$request->userID]);
		$result1=$query1->fetch();
	}catch(PDOException $e){
		http_response_code(500);
		exit($e->getMessage());
	}
	if($result1->giveNewList){
		//Construct a new list of matches
		updateUserLocation($request->userID);
		try{
			$query2=DB::get()->prepare("SELECT * FROM `users` JOIN `userlocations` USING(`userID`) WHERE `userID`=?");
			$query2->execute([$request->userID]);
			$result2=$query2->fetch();
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}
		//Refuse to serve up a list of matches if the user's location wasn't received from CREST even once
		if($result2->systemID==0){
			http_response_code(403);
			exit("Can't find matches for you if we don't know where you are! Try doing this again while you are logged into EVE Online.");
		}
		try{
			DB::get()->beginTransaction();
			$query3=DB::get()->prepare("DELETE FROM `usermatcheslist` WHERE `userID`=?");
			$query3->execute([$request->userID]);
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}
		//Depending on whether the user's last known location is in k-space or not, use a different method to find matches
		if(false/*$result2->knownSpace*/){
			//In k-space, every system is connected to each other
			//Because of that, boring methods such as calculating the distance between two systems as the number of jumps between them can be safely used
			//However, I'm too lazy to implement them right now, so I'l just use the same ghetto calculations I devised for use outside of k-space
		}
		else{
			//Outside of k-space(j-space + Jovian regions), the concept of distance doesn't apply, as the connections between systems are not fixed
			//Even if a source which provided a list of connections was used, the list would be incomplete and most likely constantly outdated
			//Therefore, the obvious solution is to consider the distance between two systems to be the difference of their IDs
			//Not only is it incredibly simple, but I can also perform the entire process in a single query
			try{
				$query4=DB::get()->prepare("INSERT INTO `usermatcheslist` (`userID`, `matchedUser`, `distance`)".
					"SELECT ? AS `userID`,`userID` AS `matchedUser`,-ABS(?-`systemID`) AS `distance` FROM `userlocations`".
					"WHERE `userID` NOT IN(SELECT `userTo` FROM `userreactions` WHERE `userFrom`=?) AND `knownSpace`=? AND `userID`!=?");
				$query4->execute([
					$request->userID,
					$result2->systemID,
					$request->userID,
					$result2->knownSpace,
					$request->userID
				]);
			}catch(PDOException $e){
				http_response_code(500);
				exit($e->getMessage());
			}
		}
		//Update the record in usermatchesinfo to reflect the fresh list
		try{
			$query5=DB::get()->prepare("UPDATE `usermatchesinfo` SET `matchDate`=NOW() WHERE `userID`=?");
			$query5->execute([$request->userID]);
			DB::get()->commit();
		}catch(PDOException $e){
			http_response_code(500);
			exit($e->getMessage());
		}
	}
	try{
		$query7=DB::get()->prepare("SELECT `matchedUser`,`distance` FROM `usermatcheslist` WHERE `userID`=? ORDER BY ABS(`distance`) ASC");
		$query7->execute([$request->userID]);
		$result7=$query7->fetchAll();
	}catch(PDOException $e){
		http_response_code(500);
		exit($e->getMessage());
	}

	//Send a response
	$response=new stdClass();
	$response->count=$query7->rowCount();
	$response->matchesList=$result7;
	http_response_code(200);
	echo json_encode($response);
	exit;
	break;

default:
	http_response_code(405);
	exit;
}