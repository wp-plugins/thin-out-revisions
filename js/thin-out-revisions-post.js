/*
 thin-out-revisions-post.js
 Copyright 2013, 2014 Hirokazu Matsui (blogger323)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 for the Edit Post/Page screen (wp-admin/post.php)
 */

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
			$(btn).attr('value',  hm_tor_params.msg_processing);
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
            // TODO: i18n for 'Delete' and 'Deleted'

			return false;
		}); // '.tor-rm' click

		// for Revision Memo

        /*
         Memo Copy
         copy the previous memo for the next update
         */
		$('#hm-tor-copy-memo').click(function () {
			var new_memo = $('#hm-tor-memo-current').html().replace(/^ \[(.*)\]$/, "$1"); // one space...
			$('#hm-tor-memo').val(new_memo);

			return false;
		}); // #hm-tor-copy-memo click

        var memo_edited = ''; // a subject to edit

        /*
          Memo Editor
          editor for old memos
         */
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
            $('#hm-tor-memo-input').focus();
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
            edit_ok();
        });

        $('#hm-tor-memo-input-cancel').click(function () {
            edit_cancel();
        });

        $('#hm-tor-memo-editor').keypress(function(e) {
            // It seems that the background cannot handle keypress events.

            if (e.keyCode == $.ui.keyCode.ENTER) {
                // To avoid multiple requests, do not use edit_ok().
                $('#hm-tor-memo-input-ok').click();
            }
            else if (e.keyCode == $.ui.keyCode.ESCAPE) {
                $('#hm-tor-memo-input-cancel').click();
            }
        });

        function edit_ok() {
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

		}

        function edit_cancel() {
            $('#hm-tor-memo-editor').hide();
            $('.hm-tor-modal-background').hide();
        }

	}); // ready
})(jQuery, window, document);
