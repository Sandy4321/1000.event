$(document).ready(function(){


	$('#search .filters-bar .tabs-select select').change(function() {

		//console.log($(this).val('tab'));
		//$('#search .options-bar .tabs li.active').removeClass('active');
		//$(this).parent('li').addClass('active');

		var tab_id = '#'+$(this).val();
		$('.filters-active').removeClass('filters-active');
		$(tab_id).addClass('filters-active');

	});

	$('#search .filters-bar .tabs-select ul li').click(function() {

		//console.log($(this).val('tab'));
		//$('#search .options-bar .tabs li.active').removeClass('active');
		//$(this).parent('li').addClass('active');

		var tab_id = '#'+$(this).data('value');
		$('.filters-active').removeClass('filters-active');
		$(tab_id).addClass('filters-active');

	});

	if(detectmob()){
		$('html,body').animate({scrollTop: $('#users').offset().top},'slow');	
	}

	// Подгрузка результатов поиска
	var scrH = $(window).height();
	var scrHP = $("#users").height();

	$(window).scroll(function() {
		var scro = $(this).scrollTop();
		var scrHP = $("#users").height();
		var scrH2 = 0;
		scrH2 = scrH + scro;
		var leftH = scrHP - scrH2;

		if(leftH < 852) {
			getNextResultSearch();
		}
	});


	// Выбор интересов
	$('.popover-content').on('change', ':checkbox', function() {
		//alert($(this).val());

		var container = $(this).parent('label')
			.parent('.popover-content')
			.parent('.popover')
			.parent('.popover-container')
			.children('.text-input')
			.children('span');

		var el_id = $(this).val();
		var el_text = $(this).parent('label').text();
		var el_remove = '.item' + el_id;

		//var el = '<span class="item item' + el_id + '">' + el_text + '</span>';
		var el = '<span class="item item' + el_id + '">' + el_text + '<button data-id="'+el_id+'" data-name="" class="btn btn-ico btn-ico-delete btn-delete" title="Удалить"></button></span>';

		if($($(this)).is(':checked')) {
			$(container).append(el);
		} else {
			$(container).children(el_remove).remove();
		}
	});

	$('button.btn-delete').click(function() {
		var el = $(this);
		var elId = el.data('id');
		var elName = el.data('name');

		el.parent().remove();
		$('#popover-' + elName + ' [value=' + elId + ']').prop('checked', false);

		return false;
	});

});

var thisPageNum = ($.cookie('search-page') == undefined) ? 2 : parseInt($.cookie('search-page'));
var thisWork = 1;
function getNextResultSearch() {
	//console.log($.cookie('search'));
	//return false;

	if(thisWork == 1) {
		var searchParams = $.parseJSON($.cookie('search'));
		//console.log($.cookie('search-page'));
		//console.log(searchParams);
		var urlSearchParam = '';
		$.each(searchParams, function(i, val) {
			urlSearchParam += i + '=' + val +'&';
		});
		/*for(var key in searchParams) {
			urlSearchParam += key + '=' + searchParams.key + '&';
		}*/
		//console.log(urlSearchParam);

		thisWork = 0;
		searchUploadOpen(); // Открываем иконку загрузки
		$.get('/' + LANG + '/user/search/ajax/?' + urlSearchParam + 'page='+thisPageNum, function(json)
		{
			if(json.data) {
				var tpl = '';
				json.data.forEach(function(user, index) {
					tpl += getTplSearchResult(user, json.search_block);
				});

				$("#users").append(tpl);

				if(LANG == 'ru') {
					setLocation('/user/search/?' + urlSearchParam + 'page='+thisPageNum);
				} else {
					setLocation('/' + LANG + '/user/search/?' + urlSearchParam + 'page='+thisPageNum);
				}

				$.cookie('search-page', thisPageNum, {path: '/user'});

				thisPageNum = thisPageNum + 1;
				thisWork = 1;
			} else {
				searchUploadClose(); // Закрываем иконку загрузки
			}
			searchUploadClose(); // Закрываем иконку загрузки
		});
	}
}

function searchUploadOpen() {
	$("#search-upload").css('display','block');
}

function searchUploadClose() {
	$("#search-upload").css('display','none');
}

function getTplSearchResult(user, search_block) {
	var tpl = '';
	//console.log(search_block);
	tpl += '<div class="item-user">';
	if(user.online == 'yes') {tpl += '<div class="online-bubble"></div>';}
	if(user.status_text) {tpl += '<div class="status-search-text">'+user.status_text+'</div>';}
	tpl += '<a href="' + user.profile_link + '" class="photo"><img src="' + user.avatar + '"></a><a href="' + user.profile_link + '" class="name">' + user.first_name + '</a>';
	tpl += '<div class="interests">';

	if(user.cntMatch != undefined && user.cntInteres != undefined) {
		if(search_block == 'target') {
			tpl += (LANG == 'ru') ? user.cntMatch +' из '+ user.cntInteres +' целей совпадает' :  user.cntMatch +' from '+ user.cntInteres +' targets are the same';
		} else {
			tpl += (LANG == 'ru') ? user.cntMatch +' из '+ user.cntInteres +' интересов совпадает' :  user.cntMatch +'from '+ user.cntInteres +' interests are the same';
		}
	} else {
		if(user.company != undefined && user.company != null && user.position_job != undefined && user.position_job != null) {
			tpl += user.company + ', ' + user.position_job;
		} else {
			if(user.company != undefined && user.company != null) {
				tpl += user.company;
			} else {
				tpl += '&nbsp;';
			}
			if(user.position_job !== undefined && user.position_job !== null) {
				tpl += user.position_job;
			} else {
				tpl += '&nbsp;';
			}
		}
	}

	tpl += '</div>';

	tpl += '</div>';

	return tpl;
}

function setLocation(curLoc){
	try {
		history.pushState(null, null, curLoc);
		return;
	} catch(e) {}
	location.hash = '#' + curLoc;
}