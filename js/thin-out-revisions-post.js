(function ($, window, document, undefined) {
	$(document).ready(function () {
		$('.post-revisions a').each(function () {
			var parse_url = /(post|revision)=([0-9]+)/;
			var result = parse_url.exec($(this).attr('href'));
			if (result && result[2] != hm_tor_params.latest_revision) {
				$(this).parent().append('<input id="tor-rm-' + result[2] + '" class="button button-primary tor-rm" type="submit" value="Delete" style="margin: 0 10px"/>');
			}
		}); // '.post-revisions a' each

		$('.tor-rm').click(function () {
			var rev = $(this).attr("id");
			var btn = this; // we need to reserve this
			rev = rev.replace('tor-rm-', '');
			if ($(btn).parent().css('text-decoration') == 'line-through') {
				return false;
			}
			if (confirm(hm_tor_params.msg_thinout_comfirmation) != true) {
				return false;
			}
			$(btn).attr('value', 'Processing...')
			$.ajax({
				url     : hm_tor_params.ajaxurl,
				dataType: 'json',
				data    : {
					action  : 'hm_tor_do_ajax',
					posts   : rev,
					security: hm_tor_params.nonce
				}
			})
					.success(function (response) {
				$(btn).parent().css('text-decoration', 'line-through');
				$(btn).attr('disabled', 'disabled');
				$(btn).attr('value', 'Deleted');
			})
					.error(function () {
				alert(hm_tor_params.msg_ajax_error);
				$(btn).attr('value', 'Delete'); /* reset */
			});

			return false;
		}); // '.tor-rm' click
	}); // ready
})(jQuery, window, document);
