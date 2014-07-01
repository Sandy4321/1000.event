var socket;
window.onload = function ()
{
	socket = io.connect('http://'+SERVER_NAME+':8000');
	var mySocketId;
	var myUrlPath = getMyUrlPath();

	//var MYID = document.querySelector('#MYID').value;

	socket.on('connect', function () {
		// События доступные после и при наличии подключения

		// Отправляем мой пользовательский ID и UrlPath на сервер и говорим что я online
		socket.emit('setMyParam', {'myUserId': MYID, 'myUrlPath': myUrlPath});

		// Получаем от сервера мой сокетId
		socket.on('getMySocketId', function (data) {
			mySocketId = data.socketId;
		});

		// Получено кол-во непрочитанных сообщений
		socket.on('noRearMsg', function (data) {
			setNoRearMsg(data.cnt);
		});

		// Есть информация о том, что партнер сейчас пишен новое сообщение
		socket.on('partnerWriteNewMsg', function (data) {
			// Если я на странице общения с этим человеком - вствляем новые данные
			if(myUrlPath == '/user/people/profile/view/' + data.partnerUid) {
				trWrites = (lang == 'ru') ? 'пишет...' : 'writes...';
				document.querySelector('#MsgNew').innerHTML += '<div id="PartnerWriteNewMsg">' +
					'<table><tr><td>' +
					'<img style="border-radius: 10px; width: 60px; margin: 10px 10px 0 0;" class="userAvatar" src="'+ getPartnerAvatar() +'">' +
					'</td><td style="vertical-align: middle;">' +
					trWrites +
					'</td></tr></table></div>';
			}
		});

		// Есть информация о том, что партнер передумал писать новое сообщение
		socket.on('partnerStopWriteNewMsg', function (data) {
			// Если я на странице общения с этим человеком - удаляем блок
			if(myUrlPath == '/user/people/profile/view/' + data.partnerUid) {
				delPartnerWriteNewMsg();
			}
		});

		// Есть информация о том, что партнер прочитал мое сообщение
		socket.on('partnerReadMsg', function (data) {
			//$('#Msg34613 .text_dt').html('Прочитано!!!!!!!+++++!!!!');
			// Если я на странице общения с этим человеком - удаляем блок
			if(myUrlPath == '/user/people/profile/view/' + data.partnerUid) {
				trTextSend = (lang == 'ru') ? 'Прочитано:' : 'Read:';

				setTimeout(function () {
					$('#Msg' + (data.msg_id + 0) + ' .text_dt').html(trTextSend);
				}, 1000);

				//alert(data.partnerUid + ' - ' + data.msg_id);
			}
		});

		// Получено новое сообщение от партнера
		socket.on('newMsg', function (data) {
			// Если я на странице общения с этим человеком - вствляем новые данные
			if(myUrlPath == '/user/people/profile/view/' + data.UID) {
				if(data.accessRead == 'no') {
					textNoReadMsg = (lang == 'ru') ? 'У Вас нет <a href="/user/profile/balance">Клубной карты</a>, поэтому Вы сможете прочитать данное сообщение только через 72 часа.' : 'Without <a href="/en/user/profile/balance">Membership card</a> you can read this message only in 72 hours.';
					data.msg = data.msg.substring(0, 5) + '...<br><span style="font-size: 10px;color: #666666;">' + textNoReadMsg + '</span>';
					$('#NoFormSendMsg').show();
					$('#FormSendMsg').hide();

					plusCntNoReadMsg();
				} else {
					// Отправляем серверу обратно, что я прочитал это сообщение
					socket.emit('userReadMessage', {'msg_id': data.msg_id, 'myId':getMyId(), 'myUid':getMyUid(), 'partnerId':getPartnerId(), 'partnerUid': getPartnerUid()});
				}

				if (lang == 'ru') {
					trTextSend = 'Отправлено';
					//trTextClear = 'Очистить';
				} else {
					trTextSend = 'Sent';
					//trTextClear = 'Clear';
				}

				delPartnerWriteNewMsg();

				document.querySelector('#MsgNew').innerHTML += '<div id="Msg' + data.msg_id + '" class="row-fluid msg-item"><div class="span12">'+
					'<table style="width: 100%;">'+
					'<tr><td style="width: 60px;"><img style="border-radius: 10px;" class="input-block-level userAvatar" src="'+ data.avatar +'"></td>'+
					'<td style="padding-left: 10px; text-align: left;position: relative;">' +
					'<span class="text_dt">' + trTextSend + ':</span> <span class="datetime" title="' + data.send_dt + '">' + data.send_dt + '</span>' +
					'<button class="btn btn-mini" style="margin-top: 5px;right: 0;top: 0;position: absolute;" onclick="selectDeleteMsg('+ data.msg_id +')"><i class="icon-trash"></i></button>'+
					'<p>'+ data.msg +'</p></td>'+
					'</tr></table></div></div>';

			} else {
				// Прибавляем 1 к общему кол-во непрочитанных сообщений
				plusCntNoReadMsg();

				// Если я на странице /user/communication
				if(myUrlPath == '/user/communication') {
					badgeUser = $('#User_' + data.UID + ' .badge.badge-msg');
					badgeUserVal = badgeUser.html();
					if(badgeUserVal > 0) {
						badgeUser.html(parseInt(badgeUserVal) + 1);
					} else {
						$('#User_' + data.UID).prepend('<span class="badge badge-msg">1</span>');
					}
				}

				//document.querySelector('#Body').innerHTML += '<h1>' + data.msg + '</h1>';
			}
		});
	});

}

/**
 * Возвращает часть url адреса /user/tmp/....
 * @returns {string}
 */
function getMyUrlPath() {
	str = window.location.pathname;
	strHach = str.split('/');

	if(strHach[1] == 'ru' || strHach[1] == 'en') {
		strHach.splice(1, 1);
	}
	return strHach.join('/');
}

/**
 * Прибавляет +1 к общему кол-ву непрочитанных сообщений
 * @constructor
 */
function plusCntNoReadMsg() {
	// Получаем
	badge = $('.icons-userCommunication .badge.badge-menu');
	badgeVal = badge.html();
	if(badgeVal > 0) {
		badge.html(parseInt(badgeVal) + 1);
		$('.icons-phoneCommunication  .badge.badge-menu').html(parseInt(badgeVal) + 1)
	} else {
		$('.icons-userCommunication').prepend('<span class="badge badge-menu">1</span>');
		$('.icons-phoneCommunication').prepend('<span class="badge badge-menu">1</span>');
	}
}

/**
 * Устанавливает новое кол-во непрочитанных сообщений
 * @param cnt
 */
function setNoRearMsg(cnt) {
	$('.icons-userCommunication .badge.badge-menu').remove();
	$('.icons-phoneCommunication .badge.badge-menu').remove();
	$('.icons-userCommunication').prepend('<span class="badge badge-menu">'+cnt+'</span>');
	$('.icons-phoneCommunication').prepend('<span class="badge badge-menu">'+cnt+'</span>');
	//console.log(data.cnt);
}

var checkWriteNewMsg = false;
function iWriteNewMsg() {
	if($('#MsgText').val()) {
		if(!checkWriteNewMsg && $('.online').html() != null) {
			checkWriteNewMsg = true;
			//console.log('Пишу партнеру ' + getPartnerId() + ' мой uId ' + getMyUid());

			// Отправляем ключ что я пишу сообщение
			socket.emit('setIWriteNewMsg', {'myUid': getMyUid(), 'partnerId' : getPartnerId()});
		}
	} else {
		checkWriteNewMsg = false;
		// Отправляем ключ что я НЕ пишу сообщение
		socket.emit('setStopIWriteNewMsg', {'myUid': getMyUid(), 'partnerId' : getPartnerId()});
		//console.log('Стоп');
	}
}

function delPartnerWriteNewMsg() {
	$('#PartnerWriteNewMsg').remove();
}

/**
 * Возвращает ID партнера (если я на его странице) или false
 * @returns {*}
 */
function getPartnerId() {
	return parseInt($('#PartnerId').html());
}

/**
 * Возвращает UID партнера (если я на его странице) или false
 * @returns {*}
 */
function getPartnerUid() {
	/*strHach = getMyUrlPath().split('/');
	 return (strHach[5]) ? strHach[5] : false;*/
	return $('#PartnerUid').html();
}

function getPartnerAvatar() {
	return $('#PartnerAvatar').html();
}

function getMyUid() {
	return $('#MyUid').html();
}

function getMyId() {
	return $('#MyId').html();
}