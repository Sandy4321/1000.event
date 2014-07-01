var socket;
window.onload = function ()
{
	socket = io.connect('http://'+SERVER_NAME+':8080');
	var mySocketId;
	var myUrlPath = getMyUrlPath();

	socket.on('connect', function () {
		// События доступные после и при наличии подключения

		// Отправляем мой пользовательский ID и UrlPath на сервер и говорим что я online
		socket.emit('setMyParam', {'myUserId': MY_ID, 'myUrlPath': myUrlPath});

		// Получаем от сервера мой сокетId
		socket.on('getMySocketId', function (data) {
			mySocketId = data.socketId;
		});

		// Получено общее кол-во непрочитанных сообщений
		socket.on('noRearMsg', function (data) {
			setNoRearMsg(data.cnt);
		});

		// Есть информация о том, что партнер сейчас пишен новое сообщение
		socket.on('partnerWriteNewMsg', function (data) {
			//console.log('Партнер мне пишет');
			// Если мне сейчас доступна переписка с этим человеком - вставляем новые данные
			if(getPartnerUid() == data.partnerUid) {
				//console.log('Партнер мне сейчас доступна переписка с ним');
				viewPartnerWriteNewMsg(data);
			} else {
				// Я не на странице партнера
				//console.log('Партнер мне пишет, но мне сейчас не доступна переписка с ним.');
			}
		});

		// Есть информация о том, что партнер передумал писать новое сообщение
		socket.on('partnerStopWriteNewMsg', function (data) {
			// Если я на странице общения с этим человеком - удаляем блок
			if(getPartnerUid() == data.partnerUid) {
				//console.log('Партнер перестал мне писать и у меня ЕСТЬ доступ сейчас к этой переписке.');
				delPartnerWriteNewMsg();
			} else {
				//console.log('Партнер перестал мне писать и у меня нет доступа сейчас к этой переписке.');
			}
		});

		// Есть информация о том, что партнер прочитал мое сообщение
		/*socket.on('partnerReadMsg', function (data) {
			//$('#Msg34613 .text_dt').html('Прочитано!!!!!!!+++++!!!!');
			// Если я на странице общения с этим человеком - удаляем блок
			if(myUrlPath == '/user/people/profile/view/' + data.partnerUid) {
				trTextSend = (lang == 'ru') ? 'Прочитано:' : 'Read:';

				setTimeout(function () {
					$('#Msg' + (data.msg_id + 0) + ' .text_dt').html(trTextSend);
				}, 1000);

				//alert(data.partnerUid + ' - ' + data.msg_id);
			}
		});*/

		// Получено новое сообщение от партнера
		socket.on('newMsg', function(json) {
			// ВНИМАНИЕ!!! profile & partner получаем в режиме "наоборот"
			//console.log(json.data.profile.uid + ' = ' + getPartnerUid());
			// Если я на странице общения с этим человеком - вставляем новые данные
			if(getPartnerUid() == json.data.profile.uid) {
				delPartnerWriteNewMsg();

				// Добавляем сообщение в разговор
				messagesAddMsgTalk(json);

			} else {
				// Прибавляем 1 к общему кол-во непрочитанных сообщений
				plusCntNoReadMsg();// Если я на странице /user/messages

				if(myUrlPath == '/user/messages') {
					var talk_id = '#talk' + json.data.profile.id;
					badgeUser = $(talk_id + ' .bubble-count');
					badgeUserVal = badgeUser.html();
					console.log(talk_id);
					if(badgeUserVal > 0) {
						badgeUser.html(parseInt(badgeUserVal) + 1);
					} else {
						$(talk_id).prepend('<div class="bubble-count">1</div>');
					}
				}
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
	badge = $('#BubbleCountMessages');
	badgeVal = badge.html();
	if(badgeVal > 0) {
		badge.html(parseInt(badgeVal) + 1);
		setNoRearMsg(parseInt(badgeVal) + 1);
	} else {
		setNoRearMsg(1);
	}
}

/**
 * Устанавливает новое кол-во непрочитанных сообщений
 * @param cnt
 */
function setNoRearMsg(cnt) {
	$('#BubbleCountMessages').html(cnt).show().addClass('bubble-count');
	$('#BubbleCountMessagesMobile').html(cnt).show().addClass('bubble-count');
	$('#BubbleCountMessagesMobileLeft').html(cnt).show().addClass('bubble-count');
}

/**
 * Возвращает общее число не прочитанных сообщений со страницы
 * @returns {Number}
 */
function getCurrentNoReadMsg() {
	var cnt = $('#BubbleCountMessages').html();
	return (cnt > 0) ? parseInt(cnt) : 0;
}

/**
 * Полностью сбрасываем общее кол-во сообщений со страницы
 */
function resetNoRearMsg() {
	$('#BubbleCountMessages').html('').hide();
	$('#BubbleCountMessagesMobile').html('').hide();
	$('#BubbleCountMessagesMobileLeft').html('').hide();
}

/**
 * Ключ того, что я пишу новое сообщение
 * @type {boolean}
 */
var checkWriteNewMsg = false;

/**
 * Я пишу новое сообщение
 */
function iWriteNewMsg() {
	if($('#msg_text').val()) {
		if(checkWriteNewMsg == false) {
			checkWriteNewMsg = true;
			//console.log('Пишу партнеру ' + getPartnerId() + ' мой uId ' + getMyUid());

			// Отправляем ключ что я пишу сообщение
			socket.emit('setIWriteNewMsg', {'myUid': getMyUid(), 'partnerId' : getPartnerId()});
		}
	} else {
		checkWriteNewMsg = false;
		// Отправляем ключ что я НЕ пишу сообщение
		socket.emit('setStopIWriteNewMsg', {'myUid': getMyUid(), 'partnerId' : getPartnerId()});
		//console.log('Стоп, я не пишу нового сообщения');
	}
}

/**
 * Партнер начал писать сообщение
 * @param data
 */
function viewPartnerWriteNewMsg(data) {
	var trWrites = (LANG == 'ru') ? 'пишет...' : 'writing...';

	document.querySelector('#messages-talk .messages').innerHTML += '<div class="message" id="PartnerWriteNewMsg">' +
		'<a href="/user/people/profile/view/' + data.partnerUid + '" class="photo"><img src="' + getPartnerAvatar() + '"></a>' +
		'<div class="msg">' +
		'<div class="author">' + getPartnerFirstName() + '</div>' +
		'<div class="text partner-writes">' + trWrites + '</div>' +
		'</div>' +
		'</div>';

	messagesScrollDown(); // прокрутить сообщения вниз (см. messages.js)
}

/**
 * Партнер перестал писать сообщение
 */
function delPartnerWriteNewMsg() {
	$('#PartnerWriteNewMsg').remove();
}

/**
 * Возвращает ID партнера (если я на его странице) или false
 * @returns {*}
 */
function getPartnerId() {
	return (window.P_ID) ? P_ID : false;
}

/**
 * Возвращает UID партнера (если я на его странице) или false
 * @returns {*}
 */
function getPartnerUid() {
	return (window.P_UID) ? P_UID : false;
}

function getPartnerFirstName() {
	return (window.P_FIRST_NAME) ? P_FIRST_NAME : false;
}

function getPartnerAvatar() {
	return (window.P_AVATAR) ? P_AVATAR : false;
}

function getMyUid() {
	return (window.MY_UID) ? MY_UID : false;
}

function getMyId() {
	return (window.MY_ID) ? MY_ID : false;
}