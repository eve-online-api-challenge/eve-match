function urlParam(n){
	return decodeURIComponent((new RegExp('[?|&]'+n+'='+'([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g,'%20'))||null;
}

var login=angular.module("eveMatchLogin",["ngSanitize","ngStorage"]);
login.controller("login",function($scope,$sce,$http,$localStorage){
	$scope.title="Logging you in";
	$scope.dots=true;
	$scope.desc="";
	$scope.info="";
	$scope.link="";
	$scope.href="";
	if(urlParam("code")){
		$http.post("../api/session/",{code:urlParam("code")}).
			then(function(response){
				if(response.data.token){
					$scope.title="Logged in";
					$scope.dots=false;
					$scope.desc="You have been logged in as "+response.data.userName+".";
					$localStorage.eveMatchUser={
						loggedIn:1,
						userID:response.data.userID,
						userName:response.data.userName,
						token:response.data.token
					};
					if(window.opener){
						window.opener.focus();
						window.close();
					}
					else{
						window.location.replace("..");
					}
				}
				else{

				}
			},function(response){
				$scope.title="An error occurred";
				$scope.dots=false;
				$scope.desc="Reload the page to try again.";
				$scope.info=response.data;
			});
	}
	else{
		$scope.title="Something went wrong";
		$scope.dots=false;
		$scope.desc="Try logging in again.";
		$scope.link="Log in";
		$scope.href=".";
	}
});

setInterval(function(){
	var e=document.querySelector("#dots");
	if(e){
		if(e.innerHTML.length>=7){
			e.innerHTML=".";
		}
		else{
			e.innerHTML+=".";
		}
	}
},200);