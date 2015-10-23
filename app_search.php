<?php
	/**
	 * Business logic for get raw body request.
	 */
	include ("data_manager.php");
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker_front.txt";
	$file_back = "broker_back.txt";

	$front_queue_get = "front_search"; // routing key
	$back_queue_get = "back_search";  // binding key

	/**
	 * Replace underscores with spaces and uppercase the first letter of every
	 * word.
	 */
	function refine_title($title) {

		// replace underscores with spaces
		$refined_title = RegExUtilities::replace_underscores($title);

        // Uppercase first letter of every word in the title.
        $refined_title = ucwords($refined_title);

		return $refined_title;
	}

	/**
	* Create a list of article links
	*/
	function make_list($article_array) {

	$size = sizeOf($article_array);
	$resultPlural = " results";

	if ($article_array[0] == "<br/>") {

		$size = 0;
	}


	if ($size == 1) {

		$resultPlural = " results";
	}

	$address_prefix = "~/stikipedia/search_test.php?title=";

	$content = '<div class="page-header">
				<h1>Search results<small> ' . $size . $resultPlural . ' found</small></h1>
				</div>';

	if ($article_array[0] != "<br/>") {

		$content .= "\n<ul>";

		foreach($article_array as $article){

			echo "\n";
			$url_title = RegExUtilities::replace_spaces($article);
			$content .= "\n";
			$content .= '<li><a href= "'.$url_title.'" >'.$url_title.'</a></li>';
		
		}

		$content .=  "\n</ul>"; 

	}


	return $content;
	}  

	/**
	 * Function called when message is received from producer.
	 * This function sends another message to the message broker.
	 */
	function foward_message($msg) {

		global $front_queue_get, $back_queue_get, $file_back;

		// this is what will be called when a new message is received from
		// the next consumer.
		$func = function($str) {
            global $front_queue_get, $back_queue_get;

			echo " [x] Received from server in  '$back_queue_get' : $str\n";

			// // replace an underscores with spaces in the search results
			// $str = refine_title($str);

			// Create search results to display on page
			$title_array = split("\n", $str);

			//echo var_dump($title_array);
			$str = make_list($title_array);

			echo " [x] Forwarded to client in '$front_queue_get' : $str\n";	// no formatting done

			return $str;
		};
		// end of function

		echo " [x] Received from server in '$front_queue_get' : $msg\n";

		echo " [x] Forwarded to client in '$back_queue_get' : $msg\n";

		$rpcClient = new RpcClient($file_back);
		$response = $rpcClient->call($back_queue_get, $msg, $func);

		return $response;

	}

	$rpcServer = new RpcServer($file_front);

	$rpcServer->start($front_queue_get, 'foward_message');

?>