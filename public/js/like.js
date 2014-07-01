// Функции отвечающие за лайки

function like(module, dataKey, dataId)
{
	// Кнопка лайка
	var elBtn = $('#'+ dataKey +'-like-' + dataId);
	elBtn.css('cursor', 'default');
	elBtn.removeAttr('onclick');

	// Меняем иконку
	$('#'+ dataKey +'-like-' + dataId +' > .btn-ico').removeClass('btn-ico-like').addClass('btn-ico-like-no');

	// Увеличиваем счетчик лайков
	var elCnt = $('#'+ dataKey +'-like-' + dataId +' > .like-cnt');
	elCnt.html(parseInt(elCnt.html()) + 1);

	$.ajax({
		type:'post',
		dataType: 'json',
		url: '/'+ LANG +'/user/'+ module +'/'+ dataKey +'-like/',
		data: 'data_id=' + dataId,
		success: function(json) {
			if(json.error) {
				otlAlert(json.error.text);
			} else {
				// Меняем текст лайка
				$('#'+ dataKey +'-like-' + dataId + ' > .like-text').html(json.msg);
			}
		}
	});

	return false;
}

function likeOpenPopupUsers(module, dataKey, dataId)
{
	popup_show(module, dataKey, dataId);
}