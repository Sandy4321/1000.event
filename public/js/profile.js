$(document).ready(function() {
	/**
	 * Изменение статуса
	 */
	$('.profile-info-main ')
		.on('click', '.status .btn-edit', function() {

			$('.profile-info-main .status .current').hide();
			$('.profile-info-main .status .edit').show();

		})
		.on('click', '.status .btn-save', function() {
			var value = $('#profile-status-text').val();
			$.ajax({
				type:     'post',
				dataType: 'json',
				url:      '/' + LANG + '/api/user/add-status/',
				data:     'status_text=' + value,
				success:  function(json) {
					if(!json.error) {
						//console.log(json);
					}
				}
			});

			$('.profile-info-main .status .current').text(value);
			$('.profile-info-main .status .current').show();
			$('.profile-info-main .status .edit').hide();

		})

	/**
	 * Изменение общей информации
	 */
	$('.btn-edit-additional-info').click(function() {
		//scroll to block

		$('html,body').animate({
				scrollTop: $('.profile-info-additional').offset().top},
			'slow');

		$('.profile-info-additional .data').hide();
		$('.profile-info-additional .data-edit').show();

		return false;
	});

	$('#profile-info-additional')
		.on('click', '.btn-edit', function() {
			$('.profile-info-additional .data').toggle();
			$('.profile-info-additional .data-edit').toggle();
		})
		.on('click', '.checkbox-lang', function() {
			var lang_name = $(this).prop('name');
			var lang_val = $(this).is(':checked') ? 'yes' : 'no';

			if(lang_val == 'no') {
				$('#' + lang_name).hide();
			} else {
				$('#' + lang_name).show();
			}

			$.ajax({
				type:     'post',
				dataType: 'json',
				url:      '/' + LANG + '/api/user/save',
				data:     lang_name + '=' + lang_val,
				success:  function(json) {
					if(!json.error) {
						console.log(json);
					}
				}
			});

			return true;
		})
		.on('click', '.btn-save', function() {
			var data = $('#profile-info-additional-form').serialize();
			console.log(data);
			$.ajax({
				type:     'post',
				dataType: 'json',
				url:      '/' + LANG + '/api/user/save/',
				data:     data,
				success:  function(json) {
					if(!json.error) {
						//console.log(json);
						location.reload();
					}
				}
			});

			$('.profile-info-additional .data').show();
			$('.profile-info-additional .data-edit').hide();

		});

	/**
	 * Изменение интересов, целей и увлечений
	 *
	 * type: hobby, targets, professionals
	 * id - id интереса
	 * action: delete, add
	 */
	$(document).on('click', '.edit-interest', function() {
		var el = $(this);
		var type = el.data('type');
		var id = el.data('id');
		var name = el.prop('name');
		var action = el.data('action');

		if(action == 'add') {
			var container = '#' + type + '-container';
			var value = el.data('value');
			var interestId = type + id;
			var interestDiv = '<a id="' + interestId + '" class="item-interest">' +
				'<span>' + value + '</span>' +
				'<button data-action="delete" data-type="'+type+'" name="'+type+'[]" data-id="'+id+'" class="edit-interest btn btn-ico btn-ico-delete btn-delete"></button>' +
				'</a>';

			$(container).append(interestDiv);
			el.data('action', 'delete');

			// Увеличиваем счетчик
			var cntEl = $('#cnt-' + type);
			var cnt = (parseInt(cntEl.html()) <= 0) ? 0 : parseInt(cntEl.html());
			cntEl.html(cnt + 1);
		}

		if(action == 'delete') {
			var interestId = '#' + type + id;

			$(interestId).remove();
			el.data('action', 'add');

			$('#popover-' + type +' input:checkbox[value='+id+']').prop('checked', false);

			// Уменьшаем счетчик
			var cntEl = $('#cnt-' + type);
			var cnt = (parseInt(cntEl.html()) <= 1) ? 1 : parseInt(cntEl.html());
			cntEl.html(cnt - 1);
		}

		$.ajax({
			type:     'post',
			dataType: 'json',
			url:      '/' + LANG + '/api/user/' + action + '-' + type,
			data:     name + '=' + id,
			success:  function(json) {
				//console.log(json);
			}
		});
	});


	/**
	 * Обработка события кнопки избранное
	 */
	$('.btn-favorite').click(function() {
		var action = $(this).data('action');
		var user_id = $(this).data('user-id');
		var page = $(this).data('page');
		var text_add = $(this).data('text-add');
		var text_del = $(this).data('text-del');

		favorite(user_id, action, $(this), function(el) {
			//console.log('f2');
			if(action == 'add') {
				$(el).data('action', 'del');
			} else {
				$(el).data('action', 'add');
			}

			if(page == 'profile') {
				if(action == 'add') {
					$(el).children('.text').text(text_del);
					$(el).children('.btn-ico').addClass('btn-ico-fav-no');

					// При добавлении в избранное происходит автоматическое исключение из чёрного списка
					$('.btn-blacklist').data('action', 'add');
					$('.btn-blacklist').attr('title', $('.btn-blacklist').data('title-add'));
					$('.btn-blacklist').children('.btn-ico').removeClass('btn-ico-block-no');

				} else {
					$(el).children('.btn-ico').removeClass('btn-ico-fav-no');
					$(el).children('.text').text(text_add);
				}
			}

			if(page == 'favorites') {
				markUserDeleted(el);
			}
		});
	});

	/**
	 * Обработка события кнопки добавления в черный список
	 */
	$('.btn-blacklist').click(function() {
		var action = $(this).data('action');
		var user_id = $(this).data('user-id');
		var page = $(this).data('page');
		var title_add = $(this).data('title-add');
		var title_del = $(this).data('title-del');

		blacklist(user_id, action, $(this), function(el) {
			if(action == 'add') {
				$(el).data('action', 'del');
			} else {
				$(el).data('action', 'add');
			}

			if(page == 'profile') {
				if(action == 'add') {
					$(el).attr('title', title_del);
					$(el).children('.btn-ico').addClass('btn-ico-block-no');

					// При добавлении в чёрный список происходит автоматическое исключение из избранного
					$('.btn-favorite').data('action', 'add');
					$('.btn-favorite').children('.text').text($('.btn-favorite').data('text-add'));
					$('.btn-favorite').children('.btn-ico').removeClass('btn-ico-fav-no');

				} else {
					$(el).children('.btn-ico').removeClass('btn-ico-block-no');
					$(el).attr('title', title_add);
				}
			}

			if(page == 'blacklist') {
				markUserDeleted(el);
			}
		});
	});

	/**
	 * Обработка события кнопки загрузки новой фотографии профиля
	 */
	$('.profile-new-avatar').click(function() {
		profileAvatarNew();
	});

	/**
	 * Обработка события кнопки загрузки новой фотографии профиля
	 */
	$('.profile-edit-avatar').click(function() {
		profileAvatarChange();
	});


	/**
	 * Обработка события кнопки загрузки новой фотографии в альбом
	 */
	$('.profile-add-photo-album').click(function() {
		profileAddPhotoAlbum();
	});

	/**
	 * Обработка действий с фотографиями
	 */
	$('#profile-photos')
		.on('click', '.photo .btn-delete', function() {
			profileDelPhotoAlbum($(this));
		})
		.on('keydown', '.photo .comment', function() {
			//if (event.keyCode == 13) {
			var comment = $(this).val();
			var picture = $(this).data('picture');
			profileAddPhotoComment(picture, comment);
			//return false;
			//}
		})
		.delegate(".item-photo", "click", function(event) {
			event.stopPropagation();
			var file_root = $(this);
			return image_gallery.call(file_root);
		});

	/**
	 * Автокомплит города
	 */
	if(jQuery().autocomplete) {
		var wizStep = $('#wizardBtnStep').html();

		$("#profile-settings-city").autocomplete('/' + LANG + '/api/site/search-city-country', {
			remoteDataType: 'json',
			queryParamName: "query",
			processData:    function(data) {
				var i, processed = [];
				for(i = 0; i < data.length; i++) {
					processed.push([data[i]["city_name"] + " - " + data[i]["country_name"], data[i]["city_id"]]);
				}
				//console.log(processed);

				/*if(wizStep) {
				 var t = (LANG == 'ru') ? 'Идет обработка...' : 'Processing...';
				 $('#wizardBtnStep').prop('disabled', true).addClass('btn-disabled').html(t);
				 }*/
				return processed;
			},
			onItemSelect:   function(data) {
				var city_id = data.data[0];
				$.ajax({
					type:     'post',
					dataType: 'json',
					url:      '/' + LANG + '/api/user/save/',
					data:     'city_id=' + city_id,
					success:  function(json) {
						if(!json.error) {
							//showAlert(json.msg);
							$("#profile-settings-city").attr('data-id', city_id).fadeTo(100, 0.1).fadeTo(200, 1.0);
							if(wizStep) {
								$('#wizardBtnStep').prop('disabled', false).removeClass('btn-disabled').html(wizStep);
							}
						}
					}
				});
			},
			onNoMatch: function() {
				var t = (LANG == 'ru') ? 'Город не найден.<br>Вы должны выбрать город из выпадающего списка.' : 'City is not found.<br>You must select a city from the dropdown list.';
				otlAlert(t);
			}
		});
	}

	/**
	 * Настройки телефона
	 */
	$('#settings-tel')
		.on('keydown', '#profile-settings-tel', function(e) {
			// Allow: backspace, delete, tab, escape, enter and .
			if($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
				// Allow: Ctrl+A
				(e.keyCode == 65 && e.ctrlKey === true) ||
				// Allow: home, end, left, right
				(e.keyCode >= 35 && e.keyCode <= 39)) {
				// let it happen, don't do anything
				return;
			}
			// Ensure that it is a number and stop the keypress
			if((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
				e.preventDefault();
			}

			$('.btn-save-tel').show(); // кнопка сохранения тел
			$('#profile-phone-check').hide(); // иконка подтвержденности тел
			$('.btn-tel-confim').hide(); // кнопка подтвердить телефон
			$('#profile-settings-tel-confim').hide(); // поле для ввода кода подтверждения
		})
		.on('click', '.btn-save-tel', function() {

			var phone_number = $('#profile-settings-tel').val();

			$.ajax({
				type:     'post',
				dataType: 'json',
				url:      '/' + LANG + '/api/user/save/',
				data:     'phone_number=' + phone_number,
				success:  function(json) {
					if(!json.error) {
						console.log(json);
						$('#profile-settings-tel-confim').show();
						$('#profile-settings-tel-confim').focus();
						$('.btn-save-tel').hide();

						// Шлем код
						$.ajax({
							type:     'post',
							dataType: 'json',
							url:      '/' + LANG + '/api/user/generate-phone-verify-code',
							success:  function(json) {
								if(!json.error) {
									//showAlert(json.msg);
									//console.log(json);
								} else {
									otlAlert(json.error);
								}
							}
						});
					} else {
						otlAlert(json.error); // Ошибки сохранения номера
					}
				}
			});
		})
		.on('keydown', '#profile-settings-tel-confim', function(e) {
			if((e.keyCode < 48 || e.keyCode > 57) &&
				(
					e.keyCode != 8 && e.keyCode != 37 && e.keyCode != 39 && e.keyCode != 46 && e.keyCode != 16 && e.keyCode != 17 && e.keyCode != 91
				)
			) {
				e.preventDefault();
				$('.btn-tel-confim').hide();
				return false;
			}

			if($('#profile-settings-tel-confim').val().length >= 5) {
				$('.btn-tel-confim').show();
			} else {
				$('.btn-tel-confim').hide();
				//return false;
			}
		})
		.on('click', '.btn-tel-confim', function() {

			var phone_verify_code = $('#profile-settings-tel-confim').val();

			$.ajax({
				type:     'post',
				dataType: 'json',
				url:      '/' + LANG + '/api/user/save/',
				data:     'phone_verify_code=' + phone_verify_code,
				success:  function(json) {
					if(!json.error) {
						$('.btn-tel-confim').hide();
						$('#profile-settings-tel-confim').hide();
						$('#profile-phone-check').show();
						//showAlert(json.msg);
					} else {
						otlAlert(json.error)
					}
				}
			});
		});

	/**
	 * Настройки уведомлений
	 */
	$('#profile-settings-notification').on('click', '.ci', function() {
		var name = $(this).prop('name');

		if($($(this)).is(':checked')) {
			var value = 'yes';
		} else {
			var value = 'no'
		}

		$.ajax({
			type:     'post',
			dataType: 'json',
			url:      '/' + LANG + '/api/user/save/',
			data:     name + '=' + value,
			success:  function(json) {
				if(!json.error) {
					//showAlert(json.msg);
				} else {
					otlAlert(json.error);
				}
			}
		});
	});

	/**
	 * Настройки знакомств, рост, дети, курение
	 */
	$('#profile-settings-dating').on('change', '.prop', function() {
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
				} else {
					otlAlert(json.error);
				}
			}
		});
	});

	/**
	 * Настройки автоматического перевода
	 */
	$('#profile-settings-lang').on('change', '.prop', function() {
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
				} else {
					otlAlert(json.error);
				}
			}
		});
	});

	/**
	 * Изменение пароля
	 */
	$('#profile-settings-password')
		.on('change', '.password', function() {
			$('.btn-change-password').parent('.controls').show();
		})
		.on('click', '.btn-change-password', function() {
			var p1 = $('#new-password').val();
			var p2 = $('#new-password2').val();
			if(p1 == p2) {
				changePassword($('#old-password').val(), p1, p2);
			} else {
				var t = (LANG == 'ru') ? 'Введенные пароли не совпадают' : 'The passwords do not match';
				otlAlert(t);
			}
		});

	/**
	 * Удаление аккаунта
	 */
	$('#profile-settings-delete-account')
		.on('keydown', '#da-password', function() {
			$('.btn-delete-account').parent('.controls').show();
		})
		.on('click', '.btn-delete-account', function() {
			var msg = (LANG == 'ru') ? 'Уверены что хотите удалить аккаунт?' : 'Delete account?';
			/*var r = confirm(msg);
			if(r == true) {
				deleteAccount($('#da-password').val());
			}*/
			otlConfirm(msg, function(out) {
				if(out) {
					deleteAccount($('#da-password').val());
				}
			});
		});




});

/**
 * Изменение пароля
 */
function changePassword(psw_current, psw_new, psw_new_confirm) {

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/api/user/change-password/',
		data:     'psw_current=' + psw_current + '&psw_new=' + psw_new + '&psw_new_confirm=' + psw_new_confirm,
		success:  function(json) {
			if(!json.error) {
				$('#old-password').val('');
				$('#new-password').val('');
				$('#new-password2').val('');
				$('.btn-change-password').parent('.controls').hide();
				otlAlert(json.msg);
			} else {
				otlAlert(json.error);
			}
		}
	});
}

/**
 * Удаление акканута
 */
function deleteAccount(psw) {

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/user/profile/delete/',
		data:     'psw=' + psw,
		success:  function(json) {
			if(!json.error) {
				openUrl('/' + LANG + '/user/login/quit');
			} else {
				// TODO: тут могуть быть ошибки. Обработай их!
			}
		}
	});
}

/**
 * Избранное, добавление и удаление.
 * @param user_id
 * @param action_name add|del
 * @returns {boolean}
 */
function favorite(user_id, action_name, el, cb)
{
	cb(el);

	var url = '/' + LANG + '/user/profile/';

	// Добавление в избранное
	if(action_name == 'add') {
		url += 'add-favorites/';
	} else {
		url += 'del-favorites/';
	}

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      url,
		data:     'user_id=' + user_id,
		success:  function(json) {
			if(!json.error) {
				//console.log('f1');
				//cb(el);
			}
		}
	});

	return false;
}

/**
 * Заблокированные (чёрный список), добавление и удаление.
 * @param user_id
 * @param action_name add|del
 * @returns {boolean}
 */
function blacklist(user_id, action_name, el, cb)
{
	cb(el);

	var url = '/' + LANG + '/user/profile/';

	// Добавление в избранное
	if(action_name == 'add') {
		url += 'add-blacklist/';
	} else {
		url += 'del-blacklist/';
	}

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      url,
		data:     'user_id=' + user_id,
		success:  function(json) {
			if(!json.error) {
				//cb(el);
			}
		}
	});

	return false;
}

/**
 * TODO: Рома где описание?
 * @param el
 */
function markUserDeleted(el) {
	if(el.parent('.item-user').hasClass('deleted')) {
		el.parent('.item-user').removeClass('deleted');
	} else {
		el.parent('.item-user').addClass('deleted');
	}
}

/**
 * Обновление аватарки в профиле
 */
function profileAvatarNew() {
	$('#new-profile-avatar').trigger('click');

	$('#new-profile-avatar').change(function() {

		$("#new-profile-avatar-form").ajaxForm({
			success: function(data) {
				if(!data.error) {
					$('.profile-avatar').attr('src', data['img-avatar']);
					profileAvatarChange();
					$('#new-profile-avatar').off('change');
				} else {
					otlAlert(data.error);
				}

			}

		}).submit();
	});
}

/**
 * Добавление фотографии в альбом пользователя
 */
function profileAddPhotoAlbum() {
	$('#new-profile-photos').trigger('click');

	$('#new-profile-photos').change(function() {

		$("#new-profile-photos-form").ajaxForm({
			success: function(data) {
				if(!data.error) {
					var t = (LANG == 'ru') ? 'Описание' : 'Description';
					var newPhoto = '<div class="photo">' +
						'<button class="btn btn-ico btn-ico-delete btn-delete" data-name="' + data.img_name + '"></button>' +
						'<a class="item-photo" data-thumbnailbig="' + data.img + '" data-name=""><img src="' + data.img + '" /></a>' +
						'<a class="btn-like"><span class="btn-ico btn-ico-like"></span><b>0</b></a>' +
						'<input type="text" class="comment" data-picture="' + data.img_name + '" placeholder="' + t + '">' +
						'</div>';

					// Определяем куда вставлять новую картинку
					if($('#profile-photos .photo-line').last().children('.photo').length < 3) {
						$('#profile-photos .photo-line').last().append(newPhoto);
					} else {
						$('#profile-photos').append('<div class="photo-line">' + newPhoto + '</div>');
					}
					//$('#profile-photos').append(newPhoto);
					$('#new-profile-photos').off('change');

					// Скрываем кнопку загрузки
					if($('#profile-photos div.photo').length >= 9) {
						$('button.profile-add-photo-album').hide();
					}
				} else {
					otlAlert(data.error);
				}

			}

		}).submit();
	});
}

/**
 * Удаление фотографии в альбоме пользователя
 * @param el
 */
function profileDelPhotoAlbum(el) {
	var name = el.data('name');
	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/user/profile/del-photo-album',
		data:     'picture=' + name,
		success:  function(json) {
			if(!json.error) {
				el.parent('.photo').remove();

				// ОТкрываем кнопку загрузки
				if($('#profile-photos div.photo').length < 9) {
					$('button.profile-add-photo-album').show();
				}
			}
		}
	});
}

/**
 * Добавление комментария
 * @param el
 */
function profileAddPhotoComment(picture, comment) {

	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/user/profile/add-photo-album-comment',
		data:     'picture=' + picture + '&comment=' + comment,
		success:  function(json) {
			if(!json.error) {

			}
		}
	});
}

/**
 * Редактирование аватарки в профиле
 */
function profileAvatarChange() {
	var jcrop_api;

	popup_show('profile', 'avatar-edit', '', function() {

		initJcrop();

		$('#popup-avatar-edit')
			.on('click', '.btn-rotate', function() {
				$.ajax({
					type:     'post',
					dataType: 'json',
					url:      '/' + LANG + '/api/user/avatar-rotate',
					data:     'rotate=90',
					success:  function(json) {
						jcrop_api.setImage(json['img-original'], function() {
							this.setOptions({
								setSelect: [ 0, 0, 200, 200 ]
							});
						});
					}
				});
			})
			.on('click', '.btn-save', function(c) {
				$("#cropForm").ajaxForm({
					success: function(data) {
						if(!data.error) {
							$('.profile-avatar').attr('src', data['img-avatar']);
						} else {
							otlAlert(data.error);
						}
						popup_close();
					}
				}).submit();
			})
			.on('click', '.controls .btn-cancel', function() {
				popup_close();
			});

	});

	function initJcrop() {
		jcrop_api = $.Jcrop('#editing-image');
		jcrop_api.setOptions({
			onSelect:    showCoords,
			boxWidth:    300,
			bgFade:      true,
			bgOpacity:   .2,
			aspectRatio: 1
		});
		jcrop_api.setImage($('#editing-image').attr('src'), function() {
			this.setOptions({
				setSelect: [ 0, 0, 200, 200 ]
			});
		});
	};

	function showCoords(c) {
		// variables can be accessed here as
		// c.x, c.y, c.x2, c.y2, c.w, c.h
		$("#cropForm").children('#x').val(c.x);
		$("#cropForm").children('#y').val(c.y);
		$("#cropForm").children('#w').val(c.w);
		$("#cropForm").children('#h').val(c.h);

	};
}

/**
 * Запросить обмен телефонами.
 *
 * @param partnerId
 * @returns {boolean} false
 */
function exchangePhone(partnerId) {
	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/user/exchange/send',
		data:     'partner_id=' + partnerId,
		success:  function(json) {
			if(!json.error) {
				otlAlert(json.msg);
				location.reload();
			} else {
				otlAlert(json.error);
			}
		}
	});

	return false;
}

/**
 * Принять предложение по обмену номерами телефонов.
 *
 * @param msgId
 * @param partnerId
 * @returns {boolean}
 */
function exchangePhoneYes(msgId, partnerId) {
	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/user/exchange/yes',
		data:     'partner_id=' + partnerId + '&msg_id=' + msgId,
		success:  function(json) {
			if(!json.error) {
				// Удаляем кнопки управления принятия решений
				$('#Msg' + msgId + ' .controls').remove();

				// Заменяем текст
				$('#Msg' + msgId + ' .text').html(json.user.first_name + ': ' + json.user.phone + '<br>' + json.msg);
			} else {
				otlAlert(json.error);
			}
		}
	});

	return false;
}

/**
 * Отклонить предложение по обмену номерами телефонов.
 *
 * @param msgId
 * @param partnerId
 * @returns {boolean}
 */
function exchangePhoneNo(msgId, partnerId) {
	// Удаляем кнопки управления принятия решений
	$('#Msg' + msgId + ' .controls').remove();

	// Выполняем запрос
	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/user/exchange/no',
		data:     'partner_id=' + partnerId + '&msg_id=' + msgId,
		success:  function(json) {
			if(!json.error) {
				// Заменяем текст
				$('#Msg' + msgId + ' .text').html(json.msg);
			} else {
				otlAlert(json.error);
				//alert('Рома. Напиши обработку ошибки!');
			}
		}
	});

	return false;
}

/**
 * Оправка отзыва о человеке.
 *
 * @returns {boolean}
 */
function peopleSendReview() {
	var elForm = $('#people-form-review');

	// Выполняем запрос
	$.ajax({
		type:     elForm.attr('method'),
		dataType: 'json',
		url:      elForm.attr('action'),
		data:     elForm.serialize(),
		success:  function(json) {
			if(!json.error) {
				popup_close();
				otlAlert(json.msg);
			} else {
				otlAlert(json.error);
			}
		}
	});

	return false;
}

/**
 * Языковые настройки профиля
 */
function profileLang(el) {
	var select_lang = $(el).val();
	$.ajax({
		type:     'post',
		dataType: 'json',
		url:      '/' + LANG + '/api/user/save/',
		data:     'user_lang=' + select_lang,
		success:  function(json) {
			if(!json.error) {
				var l = (select_lang == 'ru') ? '' : '/' + select_lang;
				openUrl(l + '/user/profile/settings');
			} else {
				showAlert(json.error);
			}
		}
	});
}