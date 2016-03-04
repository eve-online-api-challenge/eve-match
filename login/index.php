<?php
if(!$_GET["code"]){
	include "../data.php";
	http_response_code(307);
	header("Location: ".EVE_SSO_ROOT."/oauth/authorize?".
		"response_type=code&".
		"redirect_uri=".EVE_APP_CALLBACK_URL_ESCAPED."&".
		"scope=characterLocationRead+characterContactsRead+characterContactsWrite&".
		"client_id=".EVE_APP_ID);
	exit;
}
?>
<!DOCTYPE html>
<html ng-app="eveMatchLogin">
<head>
	<meta charset="UTF-8">
	<title>EVE Match Login</title>
	<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular-sanitize.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/ngStorage/0.3.10/ngStorage.min.js"></script>
	<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:300,400,500,700">
	<link rel="stylesheet" href="login.css">
	<script src="login.js"></script>
</head>
<body>
<div id="bg"></div>
<div id="cont" ng-controller="login">
	<h1><span ng-bind="title"></span><span id="dots" ng-show="dots">...</span></h1>
	<p ng-bind="desc"></p>
	<p><a ng-href="{{href}}" ng-bind="link"></a></p>
	<pre ng-bind="info"></pre>
</div>
</body>
</html>