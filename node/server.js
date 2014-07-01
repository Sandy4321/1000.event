var io = require('socket.io').listen(8080);
io.set('log level', 1);
var clients = {};

// MySQL
var mysql = require('mysql');
var mysqlUtilities = require('mysql-utilities');
var connection = mysql.createConnection({
	host:     'localhost',
	user:     'onthelist',
	password: 'onthelistru',
	database: 'db_onthelist'
});
connection.connect();
mysqlUtilities.upgrade(connection);
mysqlUtilities.introspection(connection);

// При старте сервиса скидываем всех пользователей в офлайн и чистим сокеты
connection.query('TRUNCATE `users_socket`', function(err, res) {
	if(err) {
		console.log(err);
	}
});
connection.query('UPDATE users SET online="no"', [], function(err, res) {
	if(err) {
		console.log(err);
	}
});

io.sockets.on('connection', function (socket) {

	clients[(socket.id).toString()] = socket;
	var mySocketId = (socket.id).toString();
	var myUserId;
	var myUrlPath;

	// Отправляем мой ID
	socket.emit( 'getMySocketId', {'socketId': mySocketId} ); // мне

	// Пользователь прислал свой клиентский userId
	socket.on('setMyParam', function(data){
		myUserId = data.myUserId;
		myUrlPath = data.myUrlPath;
		//console.log('Пользователь прислал свой клиентский userId: ' + myUserId);
		//console.log('Сокет ID этого пользователя: ' + mySocketId);

		// Пишем в базу что пипл онлайн
		if(myUserId) {
			setUserOnline(myUserId, mySocketId, myUrlPath);
			//console.log('Пишем в базу что пипл онлайн');

			// Отправляем пользователю кол-во его не прочитанных сообщений
			connection.query('SELECT COUNT(m.id) as cnt FROM `user_msg` AS m JOIN user_msg_text AS t ON t.id=m.msg_id WHERE m.user_id = "'+parseInt(myUserId)+'" AND m.del = "no" AND `box`="in" AND `read_dt` IS NULL', [], function(err, res) {
				if(err) {console.log(err)}
				if(res[0]['cnt'] > 0) {
					socket.emit( 'noRearMsg', {'cnt': res[0]['cnt']} );
				}
				//console.log('Кол-во не прочитанных сообщений: ' + res[0]['cnt']);
			});
		}

	});

	// Пришло новое входящее сообщение для пользователя (partnerId) от apache
	// Сообщение надо перенаправить получателю
	// Это сообщение приходит только если получатель по данным apache online
	socket.on('sendNewMsg', function(json) {
		//socket.emit( 'myAction', {data: (socket.id).toString()} ); // мне
		var data = JSON.parse(json);

		// Получить открытые сокеты партнера
		connection.select('users_socket', '*', { 'user_id': data.data.partner.id}, function(err, res){
			if(res) {
				res.forEach(function(row, index) {
					// Отправить во все эти сокеты сообщение
					// (Клиент сокета должен сам разобраться где вывести сообщение,
					// а где просто прибавить число не прочитанных сообщений)
					if (clients[row.socket_id]) {
						//console.log('Отправили сообщение: ' + arrData);
						//clients[row.socket_id].emit( 'newMsg', {'msg_id': arrData.msg.id, 'UID': arrData.profile.uid, 'msg': arrData.msg.text, 'send_dt': arrData.send_dt, 'avatar':arrData.myAvatar, 'accessRead':arrData.accessRead} );
						clients[row.socket_id].emit('newMsg', data);
					};
				});
			}
		});

	});

	// Пришел ответ от клиента, что пользователь прочитал сообщение
	socket.on('userReadMessage', function(data) {
		//console.log(data.msg_id);
		connection.query('UPDATE user_msg_text SET read_dt=NOW() WHERE id='+data.msg_id, [], function(err, res) {
			if(err) {console.log(err)}
			else {
				// Получаем ключ сокета партнера
				connection.select('users_socket', 'socket_id', { 'user_id': data.partnerId, 'url_path': '/user/people/profile/view/' + data.myUid}, function(err, results) {
					//if(err) {console.log(err);}
					if(results) {
						results.forEach(function(row, index) {
							// Отправить во все эти сокеты сообщение
							if (clients[row.socket_id]) {
								//console.log('Сокет key: ' + row.socket_id);
								clients[row.socket_id].emit('partnerReadMsg', {'msg_id': data.msg_id, 'partnerUid': data.myUid});
							};
						});
					}
				});
			}
		});
	});

	// Пришло сообщение о том, что пользователь сейчас пишет сообщение
	socket.on('setIWriteNewMsg', function(data) {
		myUid = data.myUid;
		partnerId = data.partnerId;
		//console.log('Клиент: '+ myUid +' пишет сообщение пользователю ' + partnerId);

		// Получаем ключ сокета партнера
		//connection.select('users_socket', 'socket_id', { 'user_id': partnerId, 'url_path': '/user/people/profile/view/' + myUid}, function(err, results) {
		connection.query('SELECT socket_id FROM `users_socket` WHERE (user_id = "'+parseInt(partnerId)+'" AND url_path = "/user/people/profile/view/'+ myUid +'") OR (user_id = "'+parseInt(partnerId)+'" AND url_path = "/user/messages")', [], function(err, results) {
			if(err) {console.log(err);}
			if(results) {
				results.forEach(function(row, index) {
					// Отправить во все эти сокеты сообщение
					if (clients[row.socket_id]) {
						//console.log('Сокет key: ' + row.socket_id);
						clients[row.socket_id].emit('partnerWriteNewMsg', {'partnerUid': myUid});
					};
				});
			}
		});
	});

	// Пришло сообщение о том, что пользователь перестал писать сообщение
	socket.on('setStopIWriteNewMsg', function(data) {
		var myUid = data.myUid;
		var partnerId = data.partnerId;
		//console.log('Клиент '+ myUid +' НЕ пишет сообщение пользователю ' + partnerId);

		// Получаем ключ сокета партнера
		//connection.select('users_socket', 'socket_id', { 'user_id': partnerId, 'url_path': '/user/people/profile/view/' + myUid}, function(err, results) {
		connection.query('SELECT socket_id FROM `users_socket` WHERE (user_id = "'+parseInt(partnerId)+'" AND url_path = "/user/people/profile/view/'+ myUid +'") OR (user_id = "'+parseInt(partnerId)+'" AND url_path = "/user/messages")', [], function(err, results) {
			//if(err) {console.log(err);}
			if(results) {
				results.forEach(function(row, index) {
					// Отправить во все эти сокеты сообщение
					if (clients[row.socket_id]) {
						//console.log('Сокет key: ' + row.socket_id);
						clients[row.socket_id].emit('partnerStopWriteNewMsg', {'partnerUid': myUid});
					};
				});
			}

		});
	});

	// При отключении клиента - уведомляем остальных
	socket.on('disconnect', function() {
		delete clients[mySocketId];
		//console.log('Клиент: '+ myUserId +' закрыл свой сокет: ' + mySocketId);

		// Пишем в бд что пипл оффлайн
		if(myUserId) {
			setUserOffline(myUserId, mySocketId);
		}

	});

	// ID всех участников
//	for(var clientId in clients) {
//		console.log('Сокеты всех клиентов: ' + clientId);
//	}

});

function setUserOnline(userId, socketId, urlPath) {
	connection.insert('users_socket', {user_id: userId, socket_id: socketId, url_path: urlPath}, function(err, recordId) {
		//console.dir({insert:recordId});
	});
	connection.query('UPDATE users SET online="yes", online_last_dt=NOW() WHERE id='+userId, [], function(err, res) {
		if(err) {console.log(err)}
	});
}

function setUserOffline(userId, socketId) {
	connection.delete('users_socket', { socket_id:socketId }, function(err, affectedRows) {
		if(err) {console.log(err);}
		//console.log('Закрыт сокет: ' + socketId);
	});

	// Получаем все открытые сокеты клиента, и если их нет переводим человека в оффлайн
	connection.select('users_socket', 'COUNT(*) AS cnt', { 'user_id': userId}, function(err, results) {
		//console.log(results[0]['cnt']);
		if (results[0]['cnt'] == 0) {
			connection.query('UPDATE users SET online="no", online_last_dt=NOW() WHERE id='+userId, [], function(err, res) {
				if(err) {console.log(err);}
				//else {console.log('Пользователь переведен в оффлайн');}
			});
		}
	});
}


//connection.end();