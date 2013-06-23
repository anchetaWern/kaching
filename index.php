<?php
session_start();

require 'flight/Flight.php';
require 'classes/delicious.php';


Flight::route('/', function(){
	Flight::render('index');
});

Flight::route('POST /login', function(){
	$request_body = file_get_contents('php://input');
	$input = json_decode($request_body, true);
	
	$username = $input['username'];
	$password = $input['password'];

	$delicious = new Delicious($username, $password);
	$response = $delicious->get_by_offset(1, 1);

	if($response['code'] != 'access denied'){

		$_SESSION['user'] = array(
			'username' => $username,
			'password' => $password
		);

		$delicious = new Delicious($username, $password);
		$results = $delicious->get_all();
		
		$bookmarks = array();
		$bookmarks_obj = $results->post;
		foreach($bookmarks_obj as $b){
			$text = json_decode(json_encode($b['description']), true);
			$url = json_decode(json_encode($b['href']), true); 


			$bookmarks[] = array('text' => $text[0], 'url' => $url[0]);
		}

		$_SESSION['user']['bookmarks'] = $bookmarks;

		echo json_encode(array('response' => 'success'));
		
	}else{
		echo json_encode(array('response' => 'error'));
	}
	
	 

});

Flight::route('POST /get_bookmarks', function(){

	$request_body = @file_get_contents('php://input');
	$input = json_decode($request_body, true);

	if(!empty($_SESSION['user'])){

		if(empty($input['refresh'])){		
			$bookmarks = $_SESSION['user']['bookmarks'];
			echo json_encode($bookmarks);
		}else{

			$user = $_SESSION['user'];
			$username = $user['username'];
			$password = $user['password'];

			$delicious = new Delicious($username, $password);
			$results = $delicious->get_all();
			
			$bookmarks = array();
			$bookmarks_obj = $results->post;
			foreach($bookmarks_obj as $b){
				$text = json_decode(json_encode($b['description']), true);
				$url = json_decode(json_encode($b['href']), true); 


				$bookmarks[] = array('text' => $text[0], 'url' => $url[0]);
			}

			$_SESSION['user']['bookmarks'] = $bookmarks;
			echo json_encode($bookmarks);
		}

	}else{
		echo 'error';
	}
});

Flight::route('POST /download_bookmark', function(){

	if(!empty($_SESSION['user'])){
		$request_body = file_get_contents('php://input');
		$input = json_decode($request_body, true);

		$bookmark_url = $input['bookmark_url'];

		$user = $_SESSION['user'];
		$username = $user['username'];

		$illegal_chars = array('$', '%', '^', ' ', '&', '*', '(',  ')', '#', '!', '+', '=', '~');
		$user_folder = str_replace($illegal_chars, '-', $username);

		
		$url_parts = parse_url($bookmark_url);
		$scheme = $url_parts['scheme'] . '://';
		$host = $url_parts['host'];

		$temp_files = './temp_files';
		$main_dir = md5($bookmark_url);
		$user_dir = $temp_files . '/' . $user_folder;
		$location = $temp_files . '/' . $user_folder . '/' . $main_dir;

		if(!file_exists($user_dir)){
			mkdir($user_dir);
		}

		if(!file_exists($location)){
			mkdir($location);
		}

		mkdir($location . '/scripts');
		mkdir($location . '/styles');
		mkdir($location . '/images');
		
		$contents = file_get_contents($bookmark_url);

		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML($contents);
		libxml_clear_errors(); //remove errors for yucky html
		$xpath = new DOMXPath($doc);

		$images = $xpath->query('//img/@src');
		$scripts = $xpath->query('//script/@src');
		$styles = $xpath->query('//link/@href');

		$old_styles = array();
		$new_styles = array();

		$old_images = array();
		$new_images = array();

		$old_scripts = array();
		$new_scripts = array();


		//get internal styles
		
		//get internal scripts
		

		//get external scripts
		foreach($scripts as $script){

			$src = $script->value;
			$filename = substr($src, strrpos($src, '/') + 1);
			$script_version_start_index = strpos($filename, '?');
			if($script_version_start_index){
				$filename = substr($filename, 0, $script_version_start_index);
			}

			$script_source = $src;
			if(substr($src, 0, 4) != 'http'  && substr($src, 0, 2) != '//'){
				$script_source = $scheme . $host . $src;
			}else if(substr($src, 0, 2) == '//'){
				$script_source = $scheme . str_replace('//', '', $src);
			}
			

			$script_contents = @file_get_contents($script_source);

			if(!empty($script_contents)){		

				$script_position = strpos($filename, '.js');
				if(!empty($script_position)){				
					$new_script = $location . '/scripts/' . $filename;

					$handle = fopen($new_script, 'w');
					fclose($handle);
					file_put_contents($new_script, $script_contents);
					
					$old_scripts[] = $src;
					$new_scripts[] = 'scripts/' . $filename;
				}
							 
			}
		}


		//get external styles
		$style_sources = array();
		foreach($styles as $style){

			$src = $style->value;
			$filename = substr($src, strrpos($src, '/') + 1);
			$style_version_start_index = strpos($filename, '?');
			if($style_version_start_index){
				$filename = substr($filename, 0, $style_version_start_index);
			}

			$style_source = $src;
			if(substr($src, 0, 4) != 'http'){
				$style_source = $scheme . $host . $src;
			}

			$style_contents = @file_get_contents($style_source);

			if(!empty($style_contents)){
				$style_position = strpos($filename, '.css');
				if(!empty($style_position)){				
					$new_style = $location . '/styles/' . $filename;

					$handle = fopen($new_style, 'w');
					fclose($handle);
					file_put_contents($new_style, $style_contents);
					
					$style_sources[] = $new_style;

					$old_styles[] = $src;
					$new_styles[] = 'styles/' . $filename;
				}
			}
				
		}

		
		//get background images from css
		foreach($new_styles as $ns){

			$old_style_images = array();
			$new_style_images = array();

			$stylesheet_location = $location . '/' . $ns;
			$stylesheet_contents = @file_get_contents($stylesheet_location);

	
			preg_match_all('/url\(\'(?:\/|\w|\.|\?|\:)*\'\)/', $stylesheet_contents, $stylesheet_matches);

			$stylesheet_images = $stylesheet_matches[0];
			foreach($stylesheet_images as $img){
				$s_img = str_replace(array("'", "url(", ")"), '', $img);
				$filename = substr($s_img, strrpos($s_img, '/'));

				$end_pos = strpos($filename, '?');
				if(!empty($end_pos)){
					$filename = substr($filename, 0, $end_pos);
				}

				

				$style_image_source = $s_img;
				if($s_img[0] == '/'){
					$style_image_source = $scheme . $host . $s_img; 
				}
				$style_image_contents = @file_get_contents($style_image_source);

				$old_style_images[] = $s_img;
				$new_style_images[] = '../images/' . $filename;

				$new_image = $location . '/images/' . $filename;

				$handle = fopen($new_image, 'w');
				fclose($handle);
				file_put_contents($new_image, $style_image_contents);	
			}

			$new_stylesheet_contents = str_replace($old_style_images, $new_style_images, $stylesheet_contents);
			file_put_contents($location . '/' . $ns, $new_stylesheet_contents);
		}

		

		//get images
		$image_types = array('gif', 'jpeg', 'jpg', 'png');

		foreach($images as $img){

			$src = $img->value;	
			$len = strlen($src);
			$filename = substr($src, strrpos($src, '/') + 1);

			$image_source = $src;

			if(!in_array(substr($src, 0, 4), array('http', 'https'))){
				$image_source = $scheme . $host . $src;
			}
			
			$image_contents = @file_get_contents($image_source);

			if(!empty($image_contents)){			
				$image_type = substr($src, $len - 3, 3);
				if(in_array(strtolower($image_type), $image_types)){

					$new_image = $location . '/images/' . $filename;

					$handle = fopen($new_image, 'w');
					fclose($handle);
					file_put_contents($new_image, $image_contents);
					
					$old_images[] = $src;
					$new_images[] = 'images/' . $filename;
				}		
			}
		}	
		
		$new_contents = str_replace($old_styles, $new_styles, $contents);
		$new_contents = str_replace($old_images, $new_images, $new_contents);
		$new_contents = str_replace($old_scripts, $new_scripts, $new_contents);
		$new_contents = str_replace('<html', '<html manifest="page.appcache"', $new_contents);

		$new_index = $location . '/index.html';
		$handle = fopen($new_index, 'w');
		fclose($handle);
		file_put_contents($new_index, $new_contents);

		//create manifest file
		$appcache_contents = "CACHE MANIFEST\n";


		$dir = new DirectoryIterator($location);
		foreach($dir as $fileinfo){
		    if(!$fileinfo->isDot()){
	    		$filename = $fileinfo->getFilename();
	        if(is_dir($location . '/' . $filename)){
	        	$sub_dir = new DirectoryIterator($location . '/' . $filename);
	        	foreach($sub_dir as $file){
	        		if(!$file->isDot()){
	        			$sub_filename = $file->getFilename();
	        			$appcache_contents .= $filename . '/' . $sub_filename . "\n";
	        		}
	        	}
	        }else{
	        	$appcache_contents .= $filename . "\n";
	        }
		    }
		}


		$appcache = $location . '/page.appcache';
		$handle = fopen($appcache, 'w');
		fclose($handle);
		file_put_contents($appcache, $appcache_contents);

		$data = array('local_url' => $location);
		echo json_encode($data);
	}

});

Flight::route('POST /delete_bookmark', function(){

	if(!empty($_SESSION['user'])){	
		$request_body = file_get_contents('php://input');
		$input = json_decode($request_body, true);

		$user = $_SESSION['user'];
		$username = $user['username'];
		$password = $user['password'];

		$bookmark_url = $input['bookmark_url'];
		$index = $input['index'];
		$delicious = new Delicious($username, $password);
		$delicious->delete($bookmark_url);

		$bookmarks = $_SESSION['user']['bookmarks'];
		array_splice($bookmarks, $index, 1);
		$_SESSION['user']['bookmarks'] = $bookmarks;
	}
	
});



Flight::route('/test', function(){
		$bookmark_url = 'https://gist.github.com/dypsilon/5819504';

		$user_folder = 'wernancheta';
		$temp_files = './temp_files';
		$main_dir = md5($bookmark_url);
		$user_dir = $temp_files . '/' . $user_folder;
		$location = $temp_files . '/' . $user_folder . '/' . $main_dir;

		if(!file_exists($user_dir)){
			mkdir($user_dir);
		}

		if(!file_exists($location)){
			mkdir($location);
		}

		mkdir($location . '/scripts');
	
		
		$contents = file_get_contents($bookmark_url);

		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML($contents);
		libxml_clear_errors(); //remove errors for yucky html
		$xpath = new DOMXPath($doc);

		$scripts = $xpath->query('//script/@src');
		
		$old_scripts = array();
		$new_scripts = array();

		$z = array();
		//get external scripts
		foreach($scripts as $script){

			$src = $script->value;
			$filename = substr($src, strrpos($src, '/') + 1);
			$script_version_start_index = strpos($filename, '?');
			$filename = substr($filename, 0, $script_version_start_index);

			$script_source = $src;
			if(substr($src, 0, 4) != 'http'  && substr($src, 0, 2) != '//'){
				$script_source = $scheme . $host . $src;
			}else if(substr($src, 0, 2) == '//'){
				$script_source = $scheme . str_replace('//', '', $src);
			}
			
			$z[] = $script_source;
			$script_contents = @file_get_contents($script_source);

			if(!empty($script_contents)){		

				$script_position = strpos($filename, '.js');
				if(!empty($script_position)){				
					$new_script = $location . '/scripts/' . $filename;

					$handle = fopen($new_script, 'w');
					fclose($handle);
					file_put_contents($new_script, $script_contents);
					
					

					$old_scripts[] = $src;
					$new_scripts[] = 'scripts/' . $filename;
				}
							 
			}
		}

		echo "<pre>";
		print_r($z);
		echo "</pre>";
});

Flight::route('/vest', function(){

	$test = array();
	$bookmark_url = 'https://gist.github.com/dypsilon/5819504';
	$url_parts = parse_url($bookmark_url);
	$scheme = $url_parts['scheme'] . '://';
	$host = $url_parts['host'];

	$user_folder = 'wernancheta';
	$temp_files = './temp_files';
	$main_dir = 'drake';
	$user_dir = $temp_files . '/' . $user_folder;
	$location = $temp_files . '/' . $user_folder . '/' . $main_dir;


		if(!file_exists($location)){
			mkdir($location);
		}

		mkdir($location . '/scripts');
		mkdir($location . '/styles');
		mkdir($location . '/images');

	$src = 'https://gist.github.com/assets/application-7d8a13b069fc44ac7cae88ab43791eeb.js';

	$filename = substr($src, strrpos($src, '/') + 1);
	$script_version_start_index = strpos($filename, '?');
	if($script_version_start_index){
		$filename = substr($filename, 0, $script_version_start_index);
	}

	$script_source = $src;
	if(substr($src, 0, 4) != 'http'  && substr($src, 0, 2) != '//'){
		$script_source = $scheme . $host . $src;
	}else if(substr($src, 0, 2) == '//'){
		$script_source = $scheme . str_replace('//', '', $src);
	}
	
	$test[] = $filename;
	$script_contents = @file_get_contents($script_source);

	if(!empty($script_contents)){		
		
		$script_position = strpos($filename, '.js');
		if(!empty($script_position)){				
			$new_script = $location . '/scripts/' . $filename;

			$handle = fopen($new_script, 'w');
			fclose($handle);
			file_put_contents($new_script, $script_contents);
			
			$old_scripts[] = $src;
			$new_scripts[] = 'scripts/' . $filename;

			//$test[] = $new_script;
		}
					 
	}

	print_r($test);
});


Flight::start();
?>