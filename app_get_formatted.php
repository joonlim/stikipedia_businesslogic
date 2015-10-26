<?php
	/**
	 * Business logic for get formatted body request.
	 */
	include ("data_manager.php");
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker.txt";
	$file_back = "broker2.txt";

	$front_queue_get = "front_get_formatted"; // routing key
	$back_queue_get = "back_get";  // binding key

	/**
	 * Replace underscores with spaces and uppercase the first letter of every
	 * word.
	 */
	function refine_title($title) {

		// replace underscores with spaces
		$refined_title = RegExUtilities::replace_underscores($title);

        // Uppercase first letter of every word in the title.
        $refined_title = ucwords(strtolower($refined_title));

		return $refined_title;
	}

	function format_body($content) {

		# === Header ===
		$content = preg_replace("(===(.*?)===)", "<h4>\\1</h4>", $content);

		# == Header ==
		# <hr>
		$content = preg_replace("(==(.*?)==)", "<h3>\\1<hr></h3>", $content);

		# = Header =
		# <hr>
		$content = preg_replace("(=(.*?)=)", "<h2>\\1<hr></h2>", $content);

		# [[Title#Section|Label]]
		$content = preg_replace("(\[\[([^\]\]]*?)[#]([^\]\]]*?)[|]([^\]\]]*?)\]\])", "<a href=\"\\1#\\2\">\\3</a>", $content);

		# [[Title#Section]]
		$content = preg_replace("(\[\[([^\]\]]*?)[#]([^\]\]]*?)\]\])", "<a href=\"\\1#\\2\">\\2</a>", $content); 

		# [[Title|Label]]
		$content = preg_replace("(\[\[([^\]\]]*?)[|]([^\]\]]*?)\]\])", "<a href=\"\\1\">\\2</a>", $content); 

		# [[Title]]
		$content = preg_replace("(\[\[([^\]\]]*?)\]\])", "<a href=\"\\1\">\\1</a>", $content); 

		# replace spaces in hrefs with '_'
		/*
		pattern details:

		~
		(?>                     # open an atomic group (*)
		\bhref\s*=\s*["\']  # attribute name until the quote
		|                     # OR
		\G(?<!^)            # contiguous to a precedent match
		)                       # close the atomic group
		[^ "\']*+               # content that is not a space or quotes (optional) 
		\K                      # resets the start of the match from match result
		[ ]                     # a space
		~

		*/
		$content = preg_replace('~(?>\bhref\s*=\s*["\']|\G(?<!^))[^ "\']*+\K ~', "_", $content); # title

		# new lines
		$content = preg_replace("(\n)", "</p><p>", $content);

		# escape single quotes
		$content = preg_replace("(`)", "'", $content);

		$content = preg_replace("('''(.*?)''')", "<strong>\\1</strong>", $content);

		#$content = "<div class=\"page-header\"><h1><strong>$title</strong></h1></div>\n" . $content;

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

			echo " [x] Received from server in '$back_queue_get' : $str\n";

			$body = "NULL";
			if ($str && $str != "NULL")
				$body = format_body($str);

			echo " [x] Forwarded to client in '$front_queue_get' : $body\n";
			return $body;
		};
		// end of function

		echo " [x] Received from client in '$front_queue_get' : $msg\n";

		// replace an underscores with spaces
		$refined_title = refine_title($msg);

		echo " [x] Forwarded to server in '$back_queue_get' : $refined_title\n";

		$rpcClient = new RpcClient($file_back);
		$response = $rpcClient->call($back_queue_get, $refined_title, $func);

		return $response;

	}

	$rpcServer = new RpcServer($file_front);

	$rpcServer->start($front_queue_get, 'foward_message');

?>
