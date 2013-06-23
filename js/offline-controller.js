angular.module('delic', [])
	.config(['$routeProvider', function($routeProvider){
		$routeProvider
		.when(
			'/',
			{
				templateUrl: 'templates/offline-home.html',
				controller: 'home'
			}
		)
	}])
	.controller('home', function($scope, $routeParams, $location, $http){

		if(!localStorage.getItem('kaching_bookmarks')){		
	    noty(
				{
					text: "Sorry but your browser doesn't seem to have stored some web pages in its cache",
					type: 'error'
				}
			);
		}else{
			$scope.bookmarks = JSON.parse(localStorage.getItem('kaching_cached_bookmarks'));
		}
	});