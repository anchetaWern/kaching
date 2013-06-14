angular.module('delic', [])
	.config(['$routeProvider', function($routeProvider){
		$routeProvider
		.when(
			'/', 
			{
				templateUrl: 'templates/login.html',
				controller: 'login'
			}
		)
		.when(
			'/login',
			{
				templateUrl: 'templates/login.html',
				controller: 'login'
			}
		)
		.when(
			'/home/:username',
			{
				templateUrl: 'templates/home.html',
				controller: 'home'
			}
		)
	}])
	.controller('login', function($scope, $location, $http){
		$scope.login_user = function(){
			
			var url = 'login';

			$http.post(url, 
				{  
	        username: $scope.username,
	        password: $scope.password
	      }
	    )
	    .success(function(data){
	    	
	    	if(data.response == 'success'){

	   			$location.path('/home/' + $scope.username);     
	    	}
	    })
	    .error(function(){
	      console.log('error');
	    });

		};
	})
	.controller('home', function($scope, $routeParams, $location, $http){

		$scope.username = $routeParams.username;

		var url = 'get_bookmarks';

		$http.post(url)
    .success(function(data){
    	
   		$scope.bookmarks = data;
    })
    .error(function(){
      console.log('error');
    });	

    $scope.action = function(bookmark){

    	if($scope.act == 'delete'){

	    	var index = $scope.bookmarks.indexOf(bookmark);
	    	var bookmark_url = bookmark.url;

	    	var url = 'delete_bookmark/';

		   	$http.post(url,
					{
						bookmark_url: bookmark_url,
						index: index
					}
		   	)
		    .success(function(data){
		    	
		   		$scope.bookmarks.splice(index, 1);	
		    })
		    .error(function(){
		      console.log('error');
		    });
    	}else if($scope.act == 'download'){

    		var url = 'download_bookmark/';
    		var bookmark_url = bookmark.url;

    		$http.post(url,
    			{
    				bookmark_url: bookmark_url
    			}
    		)
    		.success(function(data){
    			console.log(data);
    		})
    		.error(function(){

    		});

    	}
	    		
    };
	});