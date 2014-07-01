// Афиша - Мероприятия

/**
 * Я пойду на мероприятие
 * @param el
 * @returns {boolean}
 */
function eventIGoYes(el) {
	my = $(el).attr('data-myId');
	eventId = $(el).attr('data-eventId');

	$('#iGoYes' + eventId).hide();
	$('#iGoNo' + eventId).show();

	elCnt = $('#CntUsers_' + eventId);
	elCnt.html(parseInt(elCnt.html()) + 1);

	$('#EventsUsers_' + eventId + ' .endAvatar').parent().hide();

	elUsersList = $('#EventsUsers_' + eventId);
	elUsersList.prepend('<a class="user" id="MyIGoAvatar' + eventId + '" href="' + MY_PROFILE_URL + '"><img class="user" src="' + MY_AVATAR + '" class="userAvatarSmall"></a>');

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/user/event/set-i-go',
		data:     'event_id=' + eventId + '&go=yes',
		success:  function (json) {
			//console.log(json);
		}
	});

	return false;
}

/**
 * Я НЕ пойду на мероприятие
 * @param el
 * @returns {boolean}
 */
function eventIGoNo(el) {
	eventId = $(el).attr('data-eventId');

	$('#iGoNo' + eventId).hide();
	$('#iGoYes' + eventId).show();

	elCnt = $('#CntUsers_' + eventId);
	elCnt.html(parseInt(elCnt.html()) - 1);

	$('#MyIGoAvatar' + eventId).remove();
	$('#EventsUsers_' + eventId + ' .endAvatar').parent().show();

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/user/event/set-i-go',
		data:     'event_id=' + eventId + '&go=no',
		success:  function (json) {
			//console.log(json);
		}
	});

	return false;

}

/**
 * Я тут! На мероприятии.
 * @param el
 * @returns {boolean}
 */
function eventCheckInYes(el) {
	my = $(el).attr('data-myId');
	eventId = $(el).attr('data-eventId');

	$('#iCheckInYes' + eventId).hide();
	$('#iCheckInNo' + eventId).show();

	$('#EventsUsers_' + eventId + ' .endAvatar').parent().hide();

	elCnt = $('#CntCheckIn_' + eventId);
	elCnt.html(parseInt(elCnt.html()) + 1);

	elUsersList = $('#EventsUsers_' + eventId);
	//elUsersList.prepend('<img id="MyICheckInAvatar'+eventId+'" src="'+$('#MyAvatar').attr('src')+'" class="userAvatarSmall">');
	elUsersList.prepend('<a class="user" id="MyICheckInAvatar' + eventId + '" href="' + MY_PROFILE_URL + '"><img class="user" src="' + MY_AVATAR + '" class="userAvatarSmall"></a>');

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/user/event/set-check-in',
		data:     'event_id=' + eventId + '&checkin=yes',
		success:  function (json) {
			//console.log(json);
		}
	});

	return false;
}

/**
 * Я ушел с мероприятия
 * @param el
 * @returns {boolean}
 */
function eventCheckInNo(el) {
	eventId = $(el).attr('data-eventId');

	$('#iCheckInNo' + eventId).hide();
	$('#iCheckInYes' + eventId).show();

	elCnt = $('#CntCheckIn_' + eventId);
	elCnt.html(parseInt(elCnt.html()) - 1);

	$('#MyICheckInAvatar' + eventId).remove();
	$('#EventsUsers_' + eventId + ' .endAvatar').parent().show();

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/user/event/set-check-in',
		data:     'event_id=' + eventId + '&checkin=no',
		success:  function (json) {
			//console.log(json);
		}
	});

	return false;

}

/**
 * Удалить мероприятие
 * @param el
 * @returns {boolean}
 */
function eventDelete(el) {
	var eventId = $(el).attr('data-eventId');
	var goUrl = $(el).attr('data-openurl');

	console.log(eventId);

	var msg = (LANG == 'ru') ? 'Вы действительно хотите удалить это мероприятие?' : 'Do you really want to delete this event?';
	otlConfirm(msg, function(out) {
		if(out) {
			$('#EventID_' + eventId).remove();
			$.ajax({
				type:     'post',
				dataType: 'json',
				url:      '/' + LANG + '/user/event/delete',
				data:     'event_id=' + eventId,
				success:  function (json) {
					if(goUrl) {
						openUrl(goUrl);
					} else {
						$('#EventID_' + eventId).remove();
					}
				}
			});
		}
	});

	return false;
}

/**
 * Создание мероприятия (проверки)
 * @returns {boolean}
 */
function eventCreate() {
	var date_start_hour  = $('#date_start_hour option:selected').val();
	var date_start_day   = $('#date_start_day option:selected').val();
	var date_start_month = $('#date_start_month option:selected').val();
	var date_start_year  = $('#date_start_year option:selected').val();
	var date_start = new Date(date_start_year, date_start_month, date_start_day, date_start_hour);

	var date_close_hour  = $('#date_close_hour option:selected').val();
	var date_close_day   = $('#date_close_day option:selected').val();
	var date_close_month = $('#date_close_month option:selected').val();
	var date_close_year  = $('#date_close_year option:selected').val();
	var date_close = new Date(date_close_year, date_close_month, date_close_day, date_close_hour);

	if(date_close.getTime() <= date_start.getTime()) {
		var t = (LANG == 'ru') ? 'Дата и время начала мероприятия не может быть больше даты и времени его закрытия.' : 'The starting date and time of the event cannot be after its finish.';
		otlAlert(t);
		return false;
	}

	return true;
}

function eventInvite(el, eventId, partnerId) {
	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/api/user/event-invite',
		data:     'event_id=' + eventId + '&partner_id=' + partnerId,
		success:  function(json) {
			if(!json.error) {
				$(el).remove();
				messagesAddMsgTalk(json);
				otlAlert(json.data.ok);
			} else {
				console.log(json.error);
				otlAlert(json.error);
			}
		}
	});

	return false;
}

function eventBuyTicket(eventId) {
	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/api/user/event-buy-ticket',
		data:     'event_id=' + eventId,
		success:  function(json) {
			if(!json.error) {
				var ebt = $('#event-buy-ticket-' + eventId);
				ebt.html(ebt.attr('data-trans-ok')).removeClass('btn-dark').addClass('btn-disabled').removeAttr('onclick').parent().append(json.data.ok);
				otlAlert(json.data.ok);
			} else {
				console.log(json.error);
				otlAlert(json.error);
			}
		}
	});

	return false;
}