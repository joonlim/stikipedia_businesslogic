<?php
	/**
	 * Business logic for GET REST call.
	 * Given a title, return
	 * {"title":"Title","body","Body"}
	 */
	include ("data_manager.php");
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker.txt";
	$file_back = "broker2.txt";

	$front_queue_get = "front_GET_REST"; // routing key
	$back_queue_get = "back_get";  // binding key

	$title;

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
	 * Given a $title and $body, return a JSON string.
	 */
	function create_json($title, $body) {
		// send msg in form {"title":"$title","body":"$body"}
	    $data = array(
	        "title"  => $title,
	        "body" => $body
	    );

	    return json_encode($data);
	}

	/**
	 * Function called when message is received from producer.
	 * This function sends another message to the message broker.
	 */
	function foward_message($msg) {

		global $front_queue_get, $back_queue_get, $file_back, $title;

		// this is what will be called when a new message is received from
		// the next consumer.
		$func = function($str) {
            global $front_queue_get, $back_queue_get, $title;

			echo " [x] Received from server in '$back_queue_get' : $str\n";

			$json = "null"; // default no body
			if ($str && $str != "NULL")
				$json = create_json($title, $str);

			echo " [x] Forwarded to client in '$front_queue_get' : $json\n";
			return $json;
		};
		// end of function

		echo " [x] Received from client in '$front_queue_get' : $msg\n";

		// replace an underscores with spaces
		$refined_title = refine_title($msg);
		$title = $refined_title;

		echo " [x] Forwarded to server in '$back_queue_get' : $refined_title\n";

		$rpcClient = new RpcClient($file_back);
		$response = $rpcClient->call($back_queue_get, $refined_title, $func);

		return $response;

	}

	$rpcServer = new RpcServer($file_front);

	$rpcServer->start($front_queue_get, 'foward_message');

?>
