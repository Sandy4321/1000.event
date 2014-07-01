<?php

require('ElephantIO/Client.php');

class Models_Socket
{
	/**
	 * Отправка сообщения в сокет
	 * @param string $eventName
	 * @param array  $data
	 */
	static public function send($eventName, array $data)
	{
		$json = $data ? json_encode($data) : '{"error" : "no data"}';
		//use ElephantIO\Client as ElephantIOClient;
		$elephant = new ElephantIO\Client('http://localhost:8080', 'socket.io', 1, false, true, true);

		$elephant->init();
		$elephant->emit($eventName, $json);
		$elephant->close();
	}
}