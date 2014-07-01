$(document).ready(function() {

	// На шаге 2 изначально скрываем кнопку вращения аватарки
	if($('#wizard-step2').css('display') != 'none') {
		$('.btn-ico-rotate-g').hide();

		// Но если у нас есть на странице уже загруженная аватарка - показываем кнопку вращения
		if($('#photo-avatar').attr('src') != undefined) {
			$('.btn-ico-rotate-g').show();
		}

		// Активируем шаг два, так как с сервера пришла страничка его разрешающая
		wizardInitStep2();
	}

	/**
	 * Загрузка аватарки пользователя
	 */
	$('#photo-input').change(function () {
		if (this.files && this.files[0]) {
			var reader = new FileReader();
			reader.onload = function (e) {
				var startSrc = $('#photo-avatar').attr('src');
				$('#photo-avatar').attr('src', e.target.result).height(200).show();

				$("#wizard-form-photo-avatar").ajaxForm({
					success: function(json) {
						if(!json.error) {
							//console.log(json);
							// аватарка профиля вверху справа
							$('#photo-avatar').attr('src', json['img-avatar'] + '?' + new Date().getTime());
							$('#header img.profile-avatar').attr('src', json['img-avatar'] + '?' + new Date().getTime());
							$('.btn-ico-rotate-g').show();
						} else {
							//console.log(json);
							otlAlert(json.error);
							if(startSrc != '' &&  startSrc != undefined) {
								$('#photo-avatar').attr('src', startSrc);
							} else {
								$('#photo-avatar').attr('src', '').hide();
								$('.btn-ico-rotate-g').hide();
							}
							//$('#photo-avatar').attr('src', 'http://www.placehold.it/200x200/eee/aaa&amp;text=error-image').height(200).show();
							//$('#photo-avatar').attr('src', '').hide();
							//$('.btn-ico-rotate-g').hide();
						}
					}
				}).submit();
			}
			reader.readAsDataURL(this.files[0]);
		}
	});

	/**
	 * Загрузка резюме
	 */
	$('#resume-input').change(function () {
		if (this.files && this.files[0]) {
			var reader = new FileReader();
			reader.onload = function (e) {

				$("#wizard-form-resume").ajaxForm({
					success: function(json) {
						if(!json.error) {
							//console.log(json);
							resumeIsUpload = true;
						} else {
							otlAlert(json.error);
							//console.log(json);

						}
					}
				}).submit();
			}
			reader.readAsDataURL(this.files[0]);
		}
	});

	/**
	 * Настройки рост, дети, курение
	 */
	$('#wizard-step2').on('change', '.prop', function() {
		var name = $(this).prop('name');
		var value = $(this).prop('value');

		if($(this).hasClass('prop-checkbox')) {
			if($($(this)).is(':checked')) {
				var value = 'yes';
			} else {
				var value = 'no';
			}
		}

		$.ajax({
			type:     'post',
			dataType: 'json',
			url:      '/' + LANG + '/api/user/save/',
			data:     name + '=' + value,
			success:  function(json) {
				if(!json.error) {
					//showAlert(json.msg);
					//console.log(json);
				} else {
					otlAlert(json.error);
				}
			}
		});
	});

});

var resumeIsUpload = false;

/**
 * Инициализация и визульное разрешение показать шаг два.
 */
function wizardInitStep2()
{
	$('#wizard-step1').hide();
	$('#wizard-step2').show();
}

/**
 * Первый шаг визарда
 */
function wizardStep1(text_error) {
	var first_name = $('#first_name').val();
	var last_name  = $('#last_name').val();
	var city_id    = $('#profile-settings-city').attr('data-id');
	var birthday_day   = $('#birthday_day option:selected').val();
	var birthday_month = $('#birthday_month option:selected').val();
	var birthday_year  = $('#birthday_year option:selected').val();
	var birthday       = $('#birthday').val();

	//console.log(first_name + ' - ' + last_name + ' - ' + city_id + ' - ' + birthday_day + ' - ' + birthday_month + ' - ' + birthday_year + ' - ' + birthday);

	if(first_name && last_name && city_id && ((birthday_day && birthday_month && birthday_year) || birthday)) {
		// Формируем запрос для сохранения в зависимости от наличия ДР
		var saveParam = (birthday) ?
			'first_name=' + first_name + '&last_name=' + last_name :
			'first_name=' + first_name + '&last_name=' + last_name + '&birthday_day=' + birthday_day + '&birthday_month=' + birthday_month + '&birthday_year=' + birthday_year;
			//console.log(saveParam);
			$.ajax({
			type:     'post',
			dataType: 'json',
			url:      '/' + LANG + '/api/user/save',
			data:     saveParam,
			beforeSend: function(){
				wizardLockStep1();
			},
			success:  function(json) {
				if(!json.error) {
					wizardInitStep2();
					wizardUnlockStep1();
				} else {
					otlAlert(json.error);
					wizardUnlockStep1();
				}
			}
		});
	} else {
		// смотрим что не заполненно
		if(!city_id) {
			var t = (LANG == 'ru') ? 'Пожалуйста, начните печатать название Вашего города, а затем выберите его из списка.' : 'Please, start typing the name of your city and then select it from the list.';
			otlAlert(t);
		} else {
			otlAlert(text_error);
		}
	}

	return false;
}

/**
 * Второй шаг визарда
 */
var wizardCheck = true; // ключ проверки формы
function wizardStep2()
{
	wizardLockBtnCheckData(); // Блокируем

	wizardCheck = true; // Изначально, проверка всегда ОК

	var dataSave = (LANG == 'ru') ? 'lang=ru' : 'lang=' + LANG;

	if(!$('#photo-avatar').attr('src')) {
		wizardCheck = false;
		var t = (LANG == 'ru') ? 'Загрузите фотографию для своего профиля.' : 'No load profile photo.';
		otlAlert(t);
		return false;
	}

	var company      = $('#company');
	var position_job = $('#position_job');
	var education    = $('#education');

	// Компания и должность ИЛИ ВУЗ
	if((wizardIsValue(company) && wizardIsValue(position_job)) || wizardIsValue(education)) // Основное условие выполненно
	{
		var comAndJob = false;

		// Так как основное условие выполнно скидываем поля в состоние ОК
		wizardResetField(company);
		wizardResetField(position_job);
		wizardResetField(education);

		// Смотрим какие именно условия выполненны
		// Проверяем компанию и должность
		if(wizardIsValue(company) && wizardIsValue(position_job)) {
			wizardSaveOk(company);
			wizardSaveOk(position_job);
			dataSave += '&company=' + company.val();
			dataSave += '&position_job=' + position_job.val();

			// компания и должность сохранены
			comAndJob = true;
		}

		// проверяем ВУЗ
		if(wizardIsValue(education)) {
			wizardSaveOk(education);
			dataSave += '&education=' + education.val();

			// На всякий случай проверяем, не заполненно ли случайно еще какое то поле кроме вуза
			if(!comAndJob) {
				if(wizardIsValue(company)){
					wizardSaveOk(company);
					dataSave += '&company=' + company.val();
				}
				if(wizardIsValue(position_job)) {
					wizardSaveOk(position_job);
					dataSave += '&position_job=' + position_job.val();
				}
			}
		}
	}
	else
	{
		// Основное правило проверки провалилось
		var t = (LANG == 'ru') ? 'Заполните поля: Компания и Должность или ВУЗ.' : 'Fill in the fields and position the company or university.';
		otlAlert(t);

		if(!wizardIsValue(company))      wizardSaveError(company);
		if(!wizardIsValue(position_job)) wizardSaveError(position_job);
		if(!wizardIsValue(education))    wizardSaveError(education);

		wizardCheck = false; // проверка провалена!

		return false;
	}

	// Если блок с соц. сетями и формой резюме визуально доступен - проверим его
	if($('#wizardSocBlock').css('display') != 'none') {
		// Соц. сети
		var link_vk = $('#link_vk');
		var link_fb = $('#link_fb');
		var link_ln = $('#link_ln');

		// Если есть любая из ссылок или было загружено резюме
		if(wizardIsValue(link_vk) || wizardIsValue(link_fb) || wizardIsValue(link_ln) || resumeIsUpload)
		{
			// Так как основное условие выполнно скидываем поля в нейтральное состоние
			wizardResetField(link_vk);
			wizardResetField(link_fb);
			wizardResetField(link_ln);

			// смотрим какие(ое) из полей реально заполненно и сохраняем эти данные
			if(wizardIsValue(link_vk)) {
				wizardSaveOk(link_vk);
				dataSave += '&link_vk=' + link_vk.val();
			}

			if(wizardIsValue(link_fb)) {
				wizardSaveOk(link_fb);
				dataSave += '&link_fb=' + link_fb.val();
			}

			if(wizardIsValue(link_ln)) {
				wizardSaveOk(link_ln);
				dataSave += '&link_ln=' + link_ln.val();
			}
		}
		else // Основная проверка соц. блока провалена
		{
			var t = (LANG == 'ru') ? 'Необходимо указать ссылку на свой профиль в одной из соц. сетей либо прикрепть резюме.' : 'You must provide a link to your profile in one of the soc. networks or upload a resume.';
			otlAlert(t);

			wizardSaveError(link_fb);
			wizardSaveError(link_vk);
			wizardSaveError(link_ln);

			wizardCheck = false; // проверка провалена!

			return false;
		}
	}

	//console.log(wizardCheck + ' Все клиентсике проверки пройдены, переходим к серверным');

	// Смортим, если все проверки пройдены, то ключ проверок будет true
	if(wizardCheck == true) {
		//console.log('всё ок = ПИШЕМ данные на сервер!!!: ' + dataSave);
		$.ajax({
			//async: false,
			type:     'post',
			dataType: 'json',
			url:      '/' + LANG + '/api/user/save',
			data:     dataSave,
			beforeSend: function(){
				wizardLockBtnCheckData();
			},
			success:  function(json) {
				if(!json.error) {
					//console.log('И вот тут мы и Перезагружаем страницу!!!');
					// Перезагружаем страницу
					location.reload();
				} else {
					var el = $('#' + json.field);
					el.parent().append('<span class="helper-field-error">'+ json.error +'</span>');
					//el.parent().prepend('<span class="helper-field-error">'+ json.error +'</span>');
					wizardSaveError(el);
				}
			},
			error: function(req, status) {
				wizardUnlockBtnCheckData(); // снимаем блокировку с кнопки (чтобы могли исправить и отправить снова)
				otlAlert(status);
			}
		});
	} else {
		wizardUnlockBtnCheckData(); // снимаем блокировку с кнопки (чтобы могли исправить и отправить снова)
		//console.log('есть ошибки');
	}

	return false;
}

/**
 * Гарантированная проверка наличия в заданном поле хоть какого то не пустово значения
 */
function wizardIsValue(el) {
	return (el.val() && el.val() != '' && el.val() !== undefined) ? true : false;
}

/**
 * Маркировка заданного поля при прохождении верификации данных
 */
function wizardSaveOk(el) {
	el.addClass('wizard-save-ok').removeClass('wizard-save-error');
	el.parent().children('span').remove('.helper-field-error');
}

/**
 * Маркировка заданного поля при наличии в нем ошибки
 */
function wizardSaveError(el) {
	el.addClass('wizard-save-error').removeClass('wizard-save-ok');
	wizardCheck = false;

	wizardUnlockBtnCheckData(); // снимаем блокировку с кнопки (чтобы могли исправить и отправить снова)
}

/**
 * Сброс в нейтральное состояние заданного поля формы
 */
function wizardResetField(el) {
	el.removeClass('wizard-save-error').removeClass('wizard-save-ok');
}

/**
 * Вращение аватарки
 */
function wizard_avatar_rotate() {
	$.ajax({
		type:     'get',
		dataType: 'json',
		url:      '/' + LANG + '/api/user/avatar-rotate',
		data:     'rotate=90',
		success:  function(json) {
			if(!json.error) {
				$('#photo-avatar').attr('src', json['img-avatar']);
				// аватарка профиля вверху справа
				$('#header img.profile-avatar').attr('src', json['img-avatar']);
			} else {
				otlAlert(json.error);
			}
		}
	});

	return false;
}

/**
 * Блокировка кнопки проверки данных
 */
function wizardLockBtnCheckData() {
	var t = (LANG == 'ru') ? 'Идет обработка...' : 'Processing...';
	$('#wizardCheckData').prop('disabled', true).addClass('btn-disabled').html(t);
	//console.log('отправка - блок');
}

/**
 * Разблокировка кнопки проверки данных
 */
function wizardUnlockBtnCheckData() {
	var t = (LANG == 'ru') ? 'Отправить анкету администратору' : 'Send profile to the administrator';
	$('#wizardCheckData').prop('disabled', false).removeClass('btn-disabled').html(t);
	//console.log('разблакировка');
}

/**
 * Блокировка/разблокировка при вводе города
 * Окончательную брокировку снимает функция автокомплита города
 */
function wizardCitySelect(el) {
	if($(el).val() == '') {
		wizardUnlockStep1();
	} else {
		wizardLockStep1();
	}
}

/**
 * Блокировка кнопки перехода на следующий шаг
 */
function wizardLockStep1() {
	var t = (LANG == 'ru') ? 'Идет обработка...' : 'Processing...';
	$('#wizardBtnStep').prop('disabled', true).addClass('btn-disabled').html(t);
}

/**
 * Разблокировка кнопки перехода на следующий шаг
 */
function wizardUnlockStep1() {
	var t = (LANG == 'ru') ? 'Сохранить и продолжить' : 'Save and continue';
	$('#wizardBtnStep').prop('disabled', false).removeClass('btn-disabled').html(t);
}