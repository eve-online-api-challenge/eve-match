<?php
require_once "data.php";

function printLine(string $s){
	echo $s,"<br>\n";
	flush();
}

function printStrong(string $s){
	echo "<strong>",$s,"</strong><br>\n";
	flush();
}

function printBlank(){
	echo "<br>\n";
}

function stopInstallation(string $e){
	echo $e,"<br>\n";
	echo "<br>\n<strong>Ending installation due to errors</strong>";
	exit;
}

//These things may take a while
set_time_limit(300);

//Basic HTML
echo "<!DOCTYPE html><title>EVE Match Installation</title>\n";
ob_end_flush();
printStrong("Starting installation");
printBlank();

try{
	//Create basic tables
	printLine("Creating tables.");
	$query1=DB::get()->query(file_get_contents("sql/basicTables.sql"));
	$query1->closeCursor();
	printLine("Basic tables created.");
}catch(PDOException $e){
	stopInstallation($e->getMessage());
}

//Since it's not possible for all data to be retrieved from CREST, we might just as well save us all some bandwidth and get it all from the SDE
if(false){
	//Connect to the public CREST root and get the list of all regions
	ini_set("default_socket_timeout",3);
	printLine("Retrieving list of regions");
	$crestRootRaw=file_get_contents(EVE_CREST_PUBLIC_ROOT);
	$crestRoot=json_decode($crestRootRaw);
	if(!$crestRootRaw||!$crestRoot->regions){
		stopInstallation("Invalid response from: ".EVE_CREST_PUBLIC_ROOT);
	}
	$crestRegionsRaw=file_get_contents($crestRoot->regions->href);
	$crestRegions=json_decode($crestRegionsRaw);
	if(!$crestRegionsRaw||!$crestRegions->totalCount){
		stopInstallation("Invalid response from: ".$crestRoot->regions->href);
	}

	//Prepare the query for recording system information
	try{
		$systemQuery=DB::get()->prepare("INSERT INTO `systeminfo` (`systemID`, `systemName`, `systemSecurity`, `regionID`, `regionName`, `knownSpace`) VALUES (?,?,?,?,?,?)");
	}catch(PDOException $e){
		stopInstallation("Database error: ".$e->getMessage());
	}

	//Crawl through the list of regions and record the list of all systems
	printLine("Crawling through list of regions");
	$countRegions=$countSystems=0;
	foreach($crestRegions->items as $regionEntry){
		printLine("Started crawling region {$regionEntry->name}.");
		//What do all K-space regions have in common? They don't have a single digit in their names! Thanks CCP!
		$knownSpace=preg_match("!^[^\\d]+$!",$regionEntry->name);
		$countLocal=0;
		$regionRaw=file_get_contents($regionEntry->href);
		$region=json_decode($regionRaw);
		if(!$regionRaw||!$region->constellations){
			printLine("Invalid response, waiting and trying again.");
			sleep(1);
			$regionRaw=file_get_contents($regionEntry->href);
			$region=json_decode($regionRaw);
			if(!$regionRaw||!$region->constellations){
				stopInstallation("Invalid response from: ".$regionEntry->href);
			}
		}
		foreach($region->constellations as $constellationEntry){
			$constellationRaw=file_get_contents($constellationEntry->href);
			$constellation=json_decode($constellationRaw);
			if(!$constellationRaw||!$constellation->systems){
				printLine("Invalid response, waiting and trying again.");
				sleep(1);
				$constellationRaw=file_get_contents($constellationEntry->href);
				$constellation=json_decode($constellationRaw);
				if(!$constellationRaw||!$constellation->systems){
					stopInstallation("Invalid response from: ".$constellationEntry->href);
				}
			}
			foreach($constellation->systems as $systemEntry){
				$systemRaw=file_get_contents($systemEntry->href);
				$system=json_decode($systemRaw);
				if(!$systemRaw||!$system->name){
					printLine("Invalid response, waiting and trying again.");
					sleep(1);
					$systemRaw=file_get_contents($systemEntry->href);
					$system=json_decode($systemRaw);
					if(!$systemRaw||!$system->name){
						stopInstallation("Invalid response from: ".$systemEntry->href);
					}
				}
				try{
					$systemQuery->execute([$system->id,$system->name,$system->securityStatus,$region->id,$region->name,$knownSpace]);
				}catch(PDOException $e){
					stopInstallation("Database error: ".$e->getMessage());
				}
				$countLocal++;
				$countSystems++;
				//I guess this counts as rate limiting
				if($countSystems%50==0){
					sleep(1);
				}
			}
		}
		$countRegions++;
		printLine("Finished crawling region {$region->name}, found $countLocal systems.");
	}
	printStrong("Finished crawling $countRegions regions, found $countSystems in total.");
}

try{
	//Import some tables from the SDE
	printLine("Importing region data.");
	$query2=DB::get()->query(file_get_contents("sql/mapRegions.sql"));
	$query2->closeCursor();
	printLine("Region data imported.");

	printLine("Importing system data.");
	$query3=DB::get()->query(file_get_contents("sql/mapSolarSystems.sql"));
	$query3->closeCursor();
	printLine("System data imported.");

	printLine("Importing stargate data.");
	$query4=DB::get()->query(file_get_contents("sql/mapSolarSystemJumps.sql"));
	$query4->closeCursor();
	printLine("Stargate data imported.");

	//Use some SQL to transform those tables into something magical
	printLine("Reformatting map data.");
	$query5=DB::get()->query(file_get_contents("sql/sdeReformat.sql"));
	$query5->closeCursor();
	printLine("Map data reformatted and ready for use.");
}catch(PDOException $e){
	stopInstallation($e->getMessage());
}

$time=round(microtime(true)-$_SERVER["REQUEST_TIME_FLOAT"],2);
printBlank();
printStrong("Installation completed without any errors in $time seconds.");
exit;
