// Игра Флирт

function flirt(el, choice)
{
	var pId = $(el).parent().parent().children('.photo').attr('data-id');
	console.log('pId: ' + pId);

	$.ajax({
		type:'post',
		dataType: 'json',
		url: '/' + LANG + '/user/flirt/choice/',
		data: 'p_id=' + pId + '&choice=' + choice,
		success: function(json) {
			if (!json.error) {
				$('.flirt-cnt-match').html(json.sympathy);
				$('.flirt-cnt-yes').html(json.yes);
				$('.flirt-cnt-all').html(parseInt(json.no) + parseInt(json.yes));

				// Проверяем совпадения
				if(!json.match) { // нет совпадения
					//$(el).parent().parent().children('.flirtBlock').remove();
					$('#flirt-block-' + pId).remove();
					$('#flirt-controls-' + pId).remove();

					$('#FlirtData .flirtBlock:first').show();
					$('#FlirtData .controls:first').show();
				} else {
					// Есть совпадение!!!
					$('#flirt-block-' + pId + ' .info').show();
					$('#flirt-block-' + pId + ' .info .username').html(json.first_name);
					$('#flirt-block-' + pId + ' .info .telephone').html(json.phone);
					$('#flirt-controls-' + pId + ' .btn-flirt').removeAttr('onclick');
					$('.content-block.match .data').append('<div class="flirt-user-box"><a href="'+json.url_profile+'"><img class="photo" src="'+json.avatar+'"> <h1 class="username">'+json.first_name+'</h1></a> <h2 class="telephone">'+json.phone+'</h2></div>');

					/*$('#FlirtData .flirtBtn:first').hide(); // Скрываем кнопки


					if(json.club_card == true) {
						//uName.html('<a href="' + ((lang != 'ru') ? ('/' + lang) : '') + '/user/people/profile/view/' + json.uid + '">' + uName.html() + '</a> ' + json.phone);
						$('#TextCardYes .flirtUserName').html('<a href="' + ((lang != 'ru') ? ('/' + lang) : '') + '/user/people/profile/view/' + json.uid + '">' + json.first_name + '</a>');
						$('#TextCardYes .flirtUserPhone').html(json.phone);
						viewTextCardYes();
					} else {
						$('#TextCardNo .flirtUserName').html(json.first_name);
						$('#TextCardNo .flirtUserPhone').html(json.phone);
						viewTextCardNo();
					}*/

					//viewNext();
				}
			}
		}
	});

	var flirtBlockCnt = parseInt($('#FlirtData').children('.flirtBlock').length);
	console.log('CNT ' + flirtBlockCnt);
	if(flirtBlockCnt == 1) {
		flirtLoadNewData();
	}
}

function viewNext() {
	//$('#NextGames').show();
	otlAlert('loadNewData() - Получаем новые данные с сервера');
}

function flirtLoadNewData() {
	$.ajax({
		type:'post',
		url: '/' + LANG + '/user/flirt/data-list/',
		success: function(htmlData) {
			$('#FlirtData').html(htmlData);
		}
	});
}