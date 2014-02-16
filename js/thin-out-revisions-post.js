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

		// for Revision Memo
		var memo_edited = '';

		$('#hm-tor-copy-memo').click(function () {
			var new_memo = $('#hm-tor-memo-current').html().replace(/^ \[(.*)\]$/, "$1"); // one space...
			$('#hm-tor-memo').val(new_memo);

			return false;
		}); // #hm-tor-copy-memo click

		$('body').append('<div class="hm-tor-modal-background"><div id="hm-tor-memo-editor"><input id="hm-tor-memo-input" type="text" /><input id="hm-tor-memo-input-ok" class="button" type="button" value="OK" /><input id="hm-tor-memo-input-cancel" class="button" type="button" value="Cancel" /></div></div>')

		$('.hm-tor-old-memo').click(function () {
			$('#hm-tor-memo-editor').show();
			$('.hm-tor-modal-background').show();
			$('#hm-tor-memo-editor').position({
				my: "left top",
				at: "left top",
				of: $(this)
			});
			$('#hm-tor-memo-input').val($(this).text().replace(/^ \[(.*)\]$/,"$1"));
			memo_edited = $(this).attr('id').replace(/hm-tor-memo-/, '');
		});

		$('.hm-tor-old-memo').hover(
				function () {
					$(this).css("cursor", "pointer");
				},
				function () {
					$(this).css("cursor", "default");
				}
		);

		$('#hm-tor-memo-input-ok').click(function () {
			var editor = $('#hm-tor-memo-editor');
			var new_memo = $('#hm-tor-memo-input').val();

			editor.children().css('cursor', 'wait');
			$('#hm-tor-memo-editor input').attr('disabled', 'disabled');

			function reset_attr() {
				// reset attributes
				editor.children().css('cursor', 'default');
				$('#hm-tor-memo-editor input').removeAttr('disabled');
				editor.hide();
				$('.hm-tor-modal-background').hide();
			}

			// execution
			$.ajax({
				url: hm_tor_params.ajaxurl,
				dataType: 'json',
				data: {
					action: 'hm_tor_do_ajax_update_memo',
					revision: memo_edited,
					memo: new_memo,
					security: hm_tor_params.nonce
				}
			})
			.success (function(response) {

				if (response.result == 'success') {
					// set memo
					$('#hm-tor-memo-' + memo_edited).text( ' [' + new_memo + ']');
				}
				else {
					alert(response.msg);
				}

				reset_attr();
			})
			.error (function() {
				alert(hm_tor_params.msg_ajax_error);

				reset_attr();
			});

		});

		$('#hm-tor-memo-input-cancel').click(function () {
			$('#hm-tor-memo-editor').hide();
			$('.hm-tor-modal-background').hide();
		});

	}); // ready
})(jQuery, window, document);
