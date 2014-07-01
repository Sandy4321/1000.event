$(document).ready(function() {

	/**
	 * Обработка событий в балансе
	 */
	$('#buy-card')
		.on('click', '.data .plans .list label', function() {
			//alert('ads');
			var price = $(this).data('price');
			var amount = $(this).data('amount');

			$('#buy-card-price').text(price);
			$('.btn-buy-card b').text($(this).text());
			$('#btn-pay-card').attr('data-amount', amount);
		});

	$('#buy-karat')
		.on('click', '.data .plans .list label', function() {
			//alert('ads');
			var price = $(this).data('price');
			var amount = $(this).data('amount');
			$('#buy-karat-price').text(price);
			$('.btn-buy-karat b').text($(this).text());
			$('#btn-pay-karat').attr('data-amount', amount);
		});
});

/**
 * Создание счёта на оплату
 * @param elBtn
 */
function pay(elBtn) {
	var itemType     = $(elBtn).data('pay'); // тип выбранного платежа
	var itemQuantity = $(elBtn).data('amount'); // кол-во единиц для покупки

	$.ajax({
		type:     'post',
		dataType: 'json',
		async:    false,
		url:      '/' + LANG + '/api/payment/pay-uniteller',
		data:     'item_type=' + itemType + '&item_quantity=' + itemQuantity,
		/*beforeSend: function() {
		 loadPlay.html('');
		 },*/
		success:  function(json) {
			if(json.error) {
				otlAlert(json.error);
			} else {
				var form = $(json.form);
				$('body').append(form);
				$(form).submit();
			}
		}
	});
}

function recurrentPayment(el) {
	var res = ($(el).prop('checked')) ? 'yes' : 'no';

	$.ajax({
		type:     'post',
		dataType: 'json',
		async:    false,
		url:      '/' + LANG + '/api/payment/recurrent',
		data:     'key=' + res,
		success:  function(json) {
			if(!json.error) {

			} else {
				// Возвращаем значение в предыдущее состояние
				(res == 'yes') ? $(el).prop('checked', false) : $(el).prop('checked', true);
				otlAlert(json.error);
			}
		}
	});
}