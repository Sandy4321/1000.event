$(document).ready(function () {
    messages_wr();

   	messagesScrollDown();

	// Пробуем скинуть счетчик не прочитанных сообщений
	messagesResetNoRead();

	// фикс кнопки отправки сообщения для моб версии
	messagesBtnSendFix();

    /**
     * Обработка нажатия ввода
     */
    $('#msg_text').keydown(function (e) {
        if (e.keyCode == 13 && !e.shiftKey) {
            e.preventDefault();
            $(this.form).submit();
            return false;
        }
    });



    /**
     * Поиск по сообщениям
     */
    $('#messages-list-search').bind('input propertychange', function () {
	    var str = $(this).val();
	    str = str.slice(0, 1).toUpperCase() + str.slice(1);

        if ($(this).val().lenght <= 2) {
            $('#list').find('.item-talk').show();
        } else {
            $('#list').find('.item-talk').hide();
            $("#list").find(".item-talk .info .name:contains(" + str + ")").parent('.info').parent('.item-talk').show();
        }
    });

});


$(window).resize(function () {
    messages_wr();
});

// фикс кнопки отправки сообщения для моб версии
function messagesBtnSendFix() {
	if(detectmob()) {
		$('#messages-btn-submit.btn-enter').val('').html('');
	}
}

function messagesScrollDown() {
	$('#messages-talk').scrollTo($('#messages-talk').find('.messages').height(), 200);
}

function textAreaAdjust(el) {
	el.style.height = "18px";
	el.style.height = (el.scrollHeight) + "px";

	messagesLockSend(el);

	if($(el).val() == '') {
		el.style.height = "18px";
	}

	// Передача в сокет ключа о начале записи нового сообщения
	iWriteNewMsg();
}

function messagesLockSend(el) {
	if($(el).val() != '') {
		$('#messages-btn-submit').prop('disabled', false);
	}
	else {
		$('#messages-btn-submit').prop('disabled', true);
	}
}

function messages_wr() {
    ww = $(window).width();
    wh = $(window).height();

    $('#messages .container').height(wh - 51);

    $('#messages .list #list').height(wh - 101);

    if (detectmob()) {
        $('#messages .talk .messages-container').height(wh - 152);
    } else {
        //$('#messages .talk .messages-container').height(wh - 221);
        $('#messages .talk .messages-container').height(wh - 235);
    }

}

function close_talk() {
    $('#messages .list').show();
    $('#messages .talks').hide();
}

function show_talk(partnerId) {
    if (detectmob()) {
        $('#messages .list').hide();
        //$('#messages .talks').show();
        $('#messages #talks-list').show();
    }

    // Получаем переписку
    talk_load(partnerId);
}

/**
 * Удаление сообщения
 * @param msgId
 */
function messages_delete(msgId) {
    $('#Msg' + msgId).remove();
    $.ajax({
        type: 'post',
        dataType: 'json',
        url: '/' + LANG + '/user/messages/delete-msg',
        data: 'msg_id=' + msgId,
        success: function (json) {
        }
    });
}

/**
 * Удаление всей переписки с пользователем.
 * @param partnerId
 */
function messages_delete_talk(partnerId) {
    var t = (LANG == 'ru') ? 'Вы действительно хотите удалить всю переписку с этим пользователем?' : 'Do you really want to delete all correspondence with this person?';
    //if (!confirm(t)) return false;
	otlConfirm(t, function(out) {
		if(out) {
			$('#talk' + partnerId).remove();
			$.ajax({
				type: 'post',
				dataType: 'json',
				url: '/' + LANG + '/user/messages/delete-talk',
				data: 'partner_id=' + partnerId,
				success: function (json) {
				}
			});
		} else {
			return false;
		}
	});

    return false;
}

/**
 * Переписка с партнером.
 * @param partnerId
 */
function talk_load(partnerId) {
	$.ajax({
		type:    'post',
		url:     '/' + LANG + '/user/messages/talk',
		data:    'partner_id=' + partnerId,
		success: function(htmlData) {
			$('#talks-list').html(htmlData);

			var msg_text = $('#msg_text');
			msg_text.focus();

			$(".timeago").timeago();
			messages_wr();

			messagesScrollDown();

			// Пробуем скинуть счетчик не прочитанных сообщений
			messagesResetNoRead();

			messagesBtnSendFix();

			msg_text.keydown(function(event) {
				if(event.keyCode == 13) {
					$(this.form).submit();
					return false;
				}
			});
		}
	});

	return false;
}

/**
 * Сброс кол-ва непрочитанных сообщений
 */
function messagesResetNoRead() {
	if(MSG_ACCESS_READ && getPartnerId()) {
		var talkUserNoRead = $('#talk' + getPartnerId() + ' .bubble-count');
		var curVal = talkUserNoRead.html();
		if(curVal > 0) {
			curVal = parseInt(curVal);
			talkUserNoRead.remove();
			var cntAll = getCurrentNoReadMsg();
			if(cntAll > curVal) {
				setNoRearMsg(cntAll - curVal);
			} else {
				resetNoRearMsg();
			}
		}
		messagesSendNewMsgUnlock();
	} else {
		messagesSendNewMsgLock();
	}
}

function messagesSendNewMsgLock() {
	//var el_msg_text = $('#msg_text');
	//console.log('Блокируем ввод нового текста');
}

function messagesSendNewMsgUnlock() {
	//var el_msg_text = $('#msg_text');
	//console.log('Снимаем блокировку ввода нового текста');
}

/**
 * Отправка нового сообщения в переписку.
 *
 * @param elForm Элемент формы отправки сообщения
 * @returns {boolean}
 */
var talkid = null;
function message_send(elForm)
{
	var urlForm = $(elForm).attr('action');
	var data_form = $(elForm).serialize();
	$('#msg_text').val(''); // Сразу чистим текст

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      urlForm,
		data:     data_form,
		success:  function(json) {
			if(!json.error) {
				elForm.reset();

				// Для сокета скидываем ключ что я пишу
				checkWriteNewMsg = false;

				// Добавляем сообщение в переписку
				messagesAddMsgTalk(json);

				// Проверка на возможность писать новые сообщения
				if(json.data.profile.isRecordNewMsg == false) {
					$('#msg-record-no').show();
					$('#msg-form-send').hide();
				}

				// Переносим блок пользователя вверх переписки
				var talk_id = '#talk' + json.data.partner.id;

				$(talk_id + ' .last-msg').html(json.data.msg.text); // текущее сообщение переносим в последнее
				$(talk_id + ' .timeago').attr('title', json.data.msg.dt).html($.timeago(new Date()));

				if(talkid != json.data.partner.id) {
					$(talk_id).hide().prependTo('#messages #list').slideDown();
					talkid = json.data.partner.id;
				}
			} else {
				otlAlert(json.error);
			}
		}
	});

	return false;
}

function messagesAddMsgTalk(json) {
	$('#messages-talk .messages').append(message_tpl(json.data));

	$(".timeago").timeago();
	$('#msg_text').css('height', '20px');

	//Скролинг в переписке
	messagesScrollDown();
}

/**
 * Темплейт для оформления одного сообщения в переписке.
 * @param data
 * @returns {string}
 */
function message_tpl(data) {
	//console.log(data.msg.box);
	var title_btn_del = (LANG == 'ru') ? 'Удалить сообщение.' : 'Delete the message.';
	var tpl = '<div class="message" id="Msg' + data.msg.id + '">' +
		'<a href="' + data.profile.url + '" class="photo"><img src="' + data.profile.avatar + '"></a>' +
		'<div class="msg">' +
		'<div class="status">' +
		'<span class="time">' +
		'<span class="timeago" title="' + data.msg.dt + '"></span>' +
		'</span>' +
		'<button class="btn btn-ico btn-ico-delete btn-delete" onclick="messages_delete(' + data.msg.id + ')" title="' + title_btn_del + '"></button>' +
		'</div>' +
		'<div class="author">' + data.profile.user_name + '</div>' +
		'<div class="text">';

	if(data.msg.translate_text && data.msg.box != 'out') {
		data.msg.text = data.msg.translate_text;
		tpl += '<small>(' + data.msg.translate_lang + ')*</small> ';
	}

	if(data.msg.access_read == 'no' && data.msg.box != 'out') {
		textNoReadMsg = (LANG == 'ru') ? 'У Вас нет <a href="/user/profile/balance">Клубной карты</a>, поэтому Вы сможете прочитать данное сообщение только через 72 часа.' : 'Without <a href="/en/user/profile/balance">Membership card</a> you can read this message only in 72 hours.';
		data.msg.text = data.msg.text.substring(0, 5) + '...<br><span style="font-size: 10px;color: #666666;">' + textNoReadMsg + '</span>';
	}

	tpl += data.msg.text;

	tpl += '</div></div></div>';

	return tpl;
}