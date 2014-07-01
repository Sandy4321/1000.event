$(document).ready(function() {
		

  	$('.feed-post .controls .btn-attach').click(function() {
		$('#attach-file').trigger('click');
	});

	$('#attach-file').change(function () {
		$('.feed-post .attaches .photo').show();

		if (this.files && this.files[0]) {
			var reader = new FileReader();
			reader.onload = function (e) {
				$('.feed-post .attaches .photo img').attr('src', e.target.result);
			}
			reader.readAsDataURL(this.files[0]);
		}
	});

	$('.feed-post .attaches .btn-delete').click(function(){
		$('#attach-file').val('');
		$('.feed-post .attaches .photo img').attr('src', '');
		$('.feed-post .attaches .photo').hide();
	});

});


// Посты пользователей

/**
 * Удалить пост
 * @param postId
 */
function postDelete(postId) {
	var msg = (LANG == 'ru') ? 'Удалить данный пост?' : 'Delete this post?';
	/*if(confirm(msg)) {
		$('#Post_' + postId).parent('.content-block').remove();
		$.ajax({
			type:'post',
			url: '/user/posts/del-post/',
			data: 'post_id='+postId,
			success: function(response) {}
		});
	}*/
	otlConfirm(msg, function(out) {
		if(out) {
			$('#Post_' + postId).parent('.content-block').remove();
			$.ajax({
				type:'post',
				url: '/user/posts/del-post/',
				data: 'post_id='+postId,
				success: function(response) {}
			});
		}
	});

	return false;
}

/**
 * Скрыть пост
 * @param postId
 */
function postHide(postId) {
	var msg = (LANG == 'ru') ? 'Скрыть данный пост?' : 'Hide this post?';
	/*if(confirm(msg)) {
		$('#Post_' + postId).parent('.content-block').remove();
		$.ajax({
			type:'post',
			url: '/user/posts/hide-post/',
			data: 'post_id='+postId,
			success: function(response) {}
		});
	}*/

	otlConfirm(msg, function(out) {
		if(out) {
			$('#Post_' + postId).parent('.content-block').remove();
			$.ajax({
				type:'post',
				url: '/user/posts/hide-post/',
				data: 'post_id='+postId,
				success: function(response) {}
			});
		}
	});
	return false;
}

/**
 * Раскрытие и вывод комментариев к посту
 * @param postId
 */
function postComment(postId)
{
	var commentsId = '#PostCommentsList' + postId;
	var arrId      ='#post-arr-'  + postId;

	// Раскрыть комментарии
	if ( $(commentsId).css('display') == 'none' ){
		// Получаем комментарии с сервера
		$.ajax({
			type:'post',
			url: '/'+ LANG +'/user/posts/comments/',
			data: 'post_id=' + postId,
			success: function(response) {
				$(commentsId).html(response);

				$(commentsId).animate({height: 'show'}, 400);
				$(commentsId + ' input[name=comment_text]').focus();
				document.getElementById(arrId).style.backgroundPosition="0px 0px";
				$(".timeago").timeago();
			}
		});

	} else { // Закрыть комментарии
		$(commentsId).animate({height: 'hide'}, 200);
		document.getElementById(arrId).style.backgroundPosition="0px 10px";
	}
}

/**
 * Добавление комментария к посту
 */
function postCommentAdd(elForm) {
	var urlAction = $(elForm).attr('action');
	var postId    = $(elForm).attr('data-post_id');

	// Увеличиваем счётчик
	postCntPlus(postId);

	$.ajax({
		type: 'post',
		url: urlAction,
		data: $(elForm).serialize(),
		success: function(response) {
			$('#PostCommentsList' + postId).html(response);
			$(".timeago").timeago();
		}
	});

	return false;
}

/**
 * Удалить комментария поста
 * @param postId
 * @param commentId
 * @returns {boolean}
 */
function postCommentDelete(postId, commentId) {
	$('#PostComment' + commentId).remove();
	postCntMinus(postId);

	$.ajax({
		type:'post',
		url: '/'+ LANG +'/user/posts/del-comment/',
		data: 'post_id=' + postId + '&comment_id=' + commentId,
		success: function(response) {}
	});

	return false;
}

/**
 * Увеличение счетчика комментариев поста
 * @param postId
 */
function postCntPlus(postId) {
	var elCnt = $('#PostCommentCnt' + postId);
	elCnt.html(parseInt(elCnt.html()) + 1);
}

/**
 * Уменьшение счетчика комментариев поста
 * @param postId
 */
function postCntMinus(postId) {
	var elCnt = $('#PostCommentCnt' + postId);
	var cnt = parseInt(elCnt.html());
	if(cnt > 0) {
		elCnt.html(cnt - 1);
	}
}