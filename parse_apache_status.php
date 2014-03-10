<?php
	exec('/usr/sbin/apachectl fullstatus', $output);

	$counts = $connection_seconds = $ip_counts = $vhost_counts = $url_counts = array();

	$letter_parse = true;
	$line_parse = false;
	foreach($output as $key => $line) {

		if($key < 24) {
			echo $line."\n";
		}

		if($key >= 24 && empty($line)) {
			$letter_parse = false;
			$line_parse = true;
		}

		if($letter_parse && $key >= 24) {
			$line = str_split($line);
			foreach($line as $letter) {
				if($letter == ' ')
					continue;
				++$counts[$letter];
			}
		}

		if($line_parse && $line) {
			$line = preg_replace("/ {1,}/", ' ', $line);
			$line = trim($line);
			$line = explode(' ', $line);
			if(preg_match("/[0-9]+\-0/", $line[0])) {

				++$connection_seconds[$line[5]];
				++$ip_counts[$line[10]];
				++$vhost_counts[$line[11]];

				if(in_array($line[12], array('GET', 'POST'))) {
					$url = parse_url($line[13]);
					++$url_counts[$line[11]][$url['path']];
				}

			}
			
		}
	}

	krsort($connection_seconds);
	arsort($counts);
	arsort($vhost_counts);
	arsort($ip_counts);
	asort($url_counts);

	foreach($ip_counts as $key => $value) {
		if($value === 1)
			unset($ip_counts[$key]);
	}

	foreach($url_counts as $host => $counters) {
		foreach($counters as $key => $value) {
			if($value === 1)
				unset($url_counts[$host][$key]);
		}

		arsort($url_counts[$host]);
	}

	echo "\n\nThe Counts For Each Type of Request:\n\n".
			"_ Waiting for Connection, \"S\" Starting up, \"R\" Reading Request, \"W\" Sending Reply, \"K\" Keepalive (read), \"D\" DNS Lookup,\n".
			"\"C\" Closing connection, \"L\" Logging, \"G\" Gracefully finishing, \"I\" Idle cleanup of worker, \".\" Open slot with no current process\n\n";
	print_r($counts);

	echo "\n\nThe number of seconds the connection has been held open for since starting the original request (SS)\n[number of seconds connection held open] => [number of connections that took this long]\n\n";
	print_r($connection_seconds);

	echo "\n\nConnections per Vhost\n\n";
	print_r($vhost_counts);

	echo "\n\nConnections per IP Address\n\n";
	print_r($ip_counts);

	echo "\n\nConnections Per File Per Vhost\n\n";
	print_r($url_counts);

?>