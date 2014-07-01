$(document).ready(function() {

	$('.contain')
		.on('click', function() {
			if($('body').hasClass('show-right')) {
				aside_right();
			}
		})
		.on('click', '.mobile-nav-main', function(event){
			aside_right();
			event.stopPropagation();
		});
});

function aside_right() {

	$('body').toggleClass('show-right');

	if($('body').hasClass('show-right')) {

		$('.aside-right').css('height', $(window).height());
		$('.wrap').css('height', $('.aside-right').height());
	} else {
		$('.wrap').css('height', 'auto');
	}

	//alert($('.aside-left').height());

}

function wr() {
	ww = $(window).width();
	wh = $(window).height();


}

function detectmob() {
	//noinspection RedundantConditionalExpressionJS
	return (window.innerWidth <= 800 && window.innerHeight <= 600) ? true : false;
}

/**
 * Регистрация в Клубе
 * @returns {boolean}
 */
function registerClub() {
	registerClubErrorReset();

	var form = $('#form-register');
	var email = $('#email');
	var agree = $('#agree');
	console.log(form.serialize());

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      form.attr('action'),
		data:     form.serialize(),
		success:  function(json) {
			if(json.error) {
				registerClubError(json.error.field, json.error.msg);
			} else {
				window.location.href = json.redirect;
			}
		}
	});

	return false;
}

/**
 * Визуальное отображение ошибок в форме регистрации в Клубе
 * @param field
 * @param msg
 */
function registerClubError(field, msg) {
	$('#error-' + field).html(msg).show();
}

/**
 * Сброс визуального отображения ошибок в форме регистрации в Клубе
 */
function registerClubErrorReset() {
	$('#error-email').html('').hide();
	$('#error-psw').html('').hide();
	$('#error-psw_repeat').html('').hide();
	$('#error-sex').html('').hide();
	$('#error-promocode').html('').hide();
	$('#error-agree').html('').hide();
}