$(document).ready(function() {
	wr();

	$('.header-auth-nav').mouseup(function() {
		$('.header-auth-nav .sub-nav').slideToggle(1);
	});

	$(this).keydown(function (eventObject) {
		if (eventObject.which == 27) {
			popup_close();
		}
	});

	$( "#popup .cover" ).on( "click", function() { popup_close(); });
	$( "#popup .btn-close" ).on( "click", function() { popup_close(); });
	$( ".popover .btn-close" ).on( "click", function() { $(this).parent('.popover').hide(); });

  	$(".timeago").timeago();
});



$(document).mouseup(function (e) {
	var container = $('.popover-container');

	if (!container.is(e.target) // if the target of the click isn't the container...
		&& container.has(e.target).length === 0) // ... nor a descendant of the container
	{
		$('.popover').hide();
	}
});

var ww;
var wh;
$(window).resize(function() {
	wr();
});

function aside_left(){
	$('body').toggleClass('show-left');

	if($('body').hasClass('show-left')){
		$('.wrap').css('height', $('.aside-left').height());
	}else{
		$('.wrap').css('height', 'auto');
	}
}

function wr() {
	ww = $(window).width();
	wh = $(window).height();
}

/**
 * Показываем сообщение
 *
 * Вывод сообщений отключен см. тикет #120
 * @param text
 */
function showAlert(text) {

	/*$('#alerts .alert').text(text);
	$('#alerts .alert').css('display', 'inline-block');

	setTimeout(function() {
	  $("#alerts .alert").hide();
	}, 5000);*/
}

/**
 * Открытие popup
 * @param ctr
 * @param act
 * @param id
 * @returns {boolean} false
 */
function popup_show(ctr, act, id, cb) {
	popup_close();
	$.ajax({
		type:'post',
		url: '/'+ LANG +'/user/'+ctr+'/popup-'+act+'/',
		data: 'id=' + id,
		success: function(data) {
			$('body').append(data);
			document.body.style.overflow = "hidden";
			if(cb){
				cb();	
			}
			
		}
	});
	return false;
}

/**
 * Закрытие popup
 */
function popup_close() {
	$('#popup').remove();
	document.body.style.overflow = "visible";
}

function popover_show(name){
	var popover_id = '#popover-' + name;
	$(popover_id).show();

	//document.body.style.overflow = "hidden";
}

function detectmob() {
	//noinspection RedundantConditionalExpressionJS
	return (window.innerWidth <= 800 && window.innerHeight <= 600) ? true : false;
}

/**
 * Открыть url
 * @param url
 */
function openUrl(url) {
	window.location.href = url;
}

/**
 * Переход к покупке КК
 * @param msg
 */
function goBuyCard(msg) {
	var t = (LANG == 'ru') ? 'Перейти к покупке Клубной карты?' : 'Proceed to purchase a club card?';
	var l = (LANG == 'ru') ? '' : '/' + LANG;
	/*if(confirm(msg + "\n\n" + t)) {
		openUrl(l + '/user/profile/balance');
	}*/
	otlConfirm(msg + '<br>' + t, function(out) {
		if(out) {
			openUrl(l + '/user/profile/balance');
		}
	});
}

/**
 * Переход к покупке Карат
 * @param msg
 */
function goBuyKarat(msg) {
	var t = (LANG == 'ru') ? 'Перейти на страницу для покупки карат?' : 'Go to the page for buying carat?';
	var l = (LANG == 'ru') ? '' : '/' + LANG;
	/*if(confirm(msg + "\n\n" + t)) {
		openUrl(l + '/user/profile/balance');
	}*/
	otlConfirm(msg + '<br>' + t, function(out) {
		if(out) {
			openUrl(l + '/user/profile/balance');
		}
	});
}

/**
 * Переход к заполнению анкеты
 * @param msg
 */
function goWizard(msg, status) {
	if(status == 50) {
		var t = (LANG == 'ru') ? 'Перейти к заполнению анкеты для вступления в Клуб?' : 'Go to completing the questionnaire for joining the club?';
		var l = (LANG == 'ru') ? '' : '/' + LANG;
		/*if(confirm(msg + "\n\n" + t)) {
			openUrl(l + '/user/profile/wizard');
		}*/
		otlConfirm(msg + '<br>' + t, function(out) {
			if(out) {
				openUrl(l + '/user/profile/wizard');
			}
		});
	} else {
		var t = (LANG == 'ru') ? 'Ваша анкета находится на рассмотрении администратора.' : 'Your application is currently under review by the administrator of the Club.';
		otlAlert(msg+ "<br>" + t);
	}
}

/**
 * Системное сообщение, аналог alert()
 * @param msg
 * @returns {boolean}
 */
function otlAlert(msg) {
	var l = (detectmob()) ? 20 : ww/2 - 200;
	var win = '<div id="popup" class="popup otl-popup">' +
		'<div class="cover"></div>' +
			'<div class="popup-body" id="popup-otlAlert" style="left: '+l+'px">' +
				'<div class="title">' +
					'<h2>OnTheLIst</h2>' +
				'</div>' +
			'<div class="data">' + msg + '</div>' +
			'<div class="footer">' +
				'<button class="btn" onclick="popup_close()">Ок</button>' +
			'</div>' +
		'</div>' +
	'</div>';

	$('body').append(win);
	document.body.style.overflow = "hidden";

	return true;
}

/**
 * Системное сообщение, почти аналог confirm()
 * @param msg
 * @param handler
 */
function otlConfirm(msg, handler) {
	var l = (detectmob()) ? 20 : ww/2 - 200;
	var t_btn = (LANG == 'ru') ? 'Отмена' : 'Cancel';
	var win = '<div id="popup" class="popup otl-popup">' +
		'<div class="cover"></div>' +
		'<div class="popup-body" id="popup-otlAlert" style="left: '+l+'px">' +
		'<div class="title">' +
		'<h2>OnTheLIst</h2>' +
		'</div>' +
		'<div class="data">' + msg + '</div>' +
		'<div class="footer">' +
		'<button id="otl-btn-no" class="btn">' + t_btn + '</button>' +
		'<button id="otl-btn-yes" class="btn">Ок</button>' +
		'</div>' +
		'</div>' +
		'</div>';

	$('body').append(win);
	document.body.style.overflow = "hidden";

	$('#otl-btn-yes').on('click', function(evt) {
		handler(true);
		popup_close();
	});
	$('#otl-btn-no').on('click', function(evt) {
		handler(false);
		popup_close()
	});

	//return result;
}
