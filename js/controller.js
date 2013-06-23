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
			$('#ajax_loader').show();

			$http.post(url, 
				{  
	        username: $scope.username,
	        password: $scope.password
	      }
	    )
	    .success(function(data){

	    	$('#ajax_loader').hide();
	    	
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

		$scope.act = 'download';
		$scope.username = $routeParams.username;

		var url = 'get_bookmarks';


		if(!localStorage.getItem('kaching_bookmarks')){		
			$http.post(url)
	    .success(function(data){
	    	
	   		$scope.bookmarks = data;
	    	localStorage.setItem('kaching_bookmarks', JSON.stringify(data));
	    })
	    .error(function(){
		    noty(
					{
						text: "An error occured while trying to login, please try again.",
						type: 'error'
					}
				);
	    });	
		}else{
			$scope.bookmarks = JSON.parse(localStorage.getItem('kaching_bookmarks'));
		}

    $scope.action = function(bookmark){

	    var index = $scope.bookmarks.indexOf(bookmark);
	    var bookmark_text = bookmark.text;
	    var bookmark_url = bookmark.url;

    	if($scope.act == 'delete'){


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

    		var current_link = $('input[type=checkbox]:checked ~ a');
    		$('#ajax_loader').insertAfter(current_link).show();

    		var url = 'download_bookmark/';
    		var bookmark_url = bookmark.url;

    		$http.post(url,
    			{
    				bookmark_url: bookmark_url
    			}
    		)
    		.success(function(data){
    			
    			$('#ajax_loader').hide();

    			$scope.bookmarks[index].url = data.local_url;

    			var bookmarks = JSON.parse(localStorage.getItem('kaching_bookmarks'));
    			bookmarks[index].url = data.local_url;
    			localStorage.setItem('kaching_bookmarks', JSON.stringify(bookmarks));

    			noty(
    				{
    					text: 'Downloading successful, click the link to cache it on your browser: <a href="' + data.local_url  + '" target="_blank">' + bookmark_text + '</a>',
    					type: 'success'
    				}
    			);

    			//update the localStorage for the web pages that are currently cached
    			var cached_bookmarks = [];
    			if(localStorage.getItem('kaching_cached_bookmarks')){
    				cached_bookmarks = JSON.parse(localStorage.getItem('kaching_cached_bookmarks'));
    			}

    			cached_bookmarks.push(
    				{
    					text: bookmark_text,
    					url: bookmark_url,
    					local_url: data.local_url
    				}
    			);

    			localStorage.setItem('kaching_cached_bookmarks', JSON.stringify(cached_bookmarks));
    		})
    		.error(function(){
    			noty(
    				{
    					text: 'An error occured while downloading the web page into the server, please try again',
    					type: 'error'
    				}
    			);
    		});

    	}
	    		
    };

    $scope.refresh_bookmarks = function(){

    	var url = 'get_bookmarks';

			$http.post(url,
				{
					refresh: true
				}
			)
	    .success(function(data){
	    	
	   		$scope.bookmarks = data;
	    	localStorage.setItem('kaching_bookmarks', JSON.stringify(data));
	    })
	    .error(function(){
	      console.log('error');
	    });	    	
    };
	});