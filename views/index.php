<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="frontend/bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="css/style.css">
		<script src="js/angular.min.js"></script>
	</head>
	<body>
		<div ng-app="delic">
			<div ng-view>
				<div ng-controller="login">
					
				</div>
				<div ng-controller="home">
					
				</div>
			</div>
		</div>
		<script src="js/controller.js"></script>
		<script src="js/jquery.min.js"></script>
		<script src="js/noty/jquery.noty.js"></script>
		<script src="js/noty/themes/default.js"></script>
		<script src="js/noty/layouts/top.js"></script>
		<script src="frontend/bootstrap/js/bootstrap.min.js"></script>
	</body>	
</html>