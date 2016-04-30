<fieldset id="payment"></fieldset>
<div class="buttons">
  <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary" />
  </div>
</div>
<script type="text/javascript"><!--
$('#button-confirm').bind('click', function() {
	$.ajax({
		type: 'GET',
		dataType: 'json',
		url: 'index.php?route=payment/zarinpal/confirm',
		//cache: false,
		data: $('#payment :input'),
		beforeSend: function() {
			$('#button-confirm').attr('disabled', true);
			$('#payment').before('<div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $text_wait; ?></div>');
		},
		complete: function() {
			$('#button-confirm').attr('disabled', false);
			$('.alert-info').remove();
		},
		success: function(json) {
			if (json['error']) {
				$('#payment').before('<div class="alert alert-danger"><i class="fa fa-info-exclamation"></i>' + json['error'] + '</div>');
				$('#button-confirm').attr('disabled', false);
			}
			
			$('.alert-info').remove();
			if (json['success']) {
				$('#payment').before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $text_connect; ?></div>');
				location = json['success'];
			}
		}
	});
});
//--></script>