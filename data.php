<?php

//Constants used by the app

const DB_DB_NAME="evematch";
const DB_HOST_NAME="localhost";
const DB_USER_NAME="root";
const DB_PASSWORD="";

const EVE_APP_ID="";
const EVE_APP_KEY="";
//const EVE_APP_AUTH="";
define("EVE_APP_AUTH",base64_encode(EVE_APP_ID.":".EVE_APP_KEY));
const EVE_APP_CALLBACK_URL="https://example.com/evematch/login/";
//const EVE_APP_CALLBACK_URL_ESCAPED="";
define("EVE_APP_CALLBACK_URL_ESCAPED",urlencode(EVE_APP_CALLBACK_URL));
const EVE_APP_USER_AGENT="EVE Match beta | youremail@example.com";

const EVE_SSO_ROOT="https://login.eveonline.com";
const EVE_CREST_PUBLIC_ROOT="https://public-crest.eveonline.com";
const EVE_CREST_AUTH_ROOT="https://crest-tq.eveonline.com";

const LOGIN_TOKEN_LENGTH=48;
const LOGIN_DURATION_DEFAULT=7;
const LOGIN_DURATION_MAX=30;

const MATCH_DISTANCE_MIN=5;
const MATCH_DISTANCE_MAX=25;
const MATCH_DELAY=30;

//Database singleton class

class DB{
	private static $pdo=false;

	public static function get():PDO{
		if(!self::$pdo){
			$options=[PDO::ATTR_PERSISTENT        =>true,
					  PDO::ATTR_EMULATE_PREPARES  =>false,
					  PDO::ATTR_ERRMODE           =>PDO::ERRMODE_EXCEPTION,
					  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_OBJ];
			self::$pdo=new PDO("mysql:dbname=".DB_DB_NAME.";host=".DB_HOST_NAME,DB_USER_NAME,DB_PASSWORD,$options);
		}
		return self::$pdo;
	}
}