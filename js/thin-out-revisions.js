(function ($, window, document, undefined) {

	$(document).ready(function () {

		setTimeout(function () {
			if (wp && wp.revisions && wp.revisions.view && wp.revisions.view.Diff &&
					wp.revisions.Diff.revisionView) {

				TOR = {
					View: wp.revisions.view.Diff.extend({
						events: {},

						initialize: function () {
							_.bindAll(this, 'render');
							this.events = _.extend({'click #tor-thin-out': 'thinOut'},
									wp.revisions.view.Diff.prototype.events);
						},

						render: function () {
							wp.revisions.view.Diff.prototype.render.apply(this);
							var fromid = this.model.at(wp.revisions.Diff.leftDiff - 1).get('ID');
							var toid = this.model.at(wp.revisions.Diff.rightDiff - 1).get('ID')
							if (typeof(memos) !== 'undefined' && memos[fromid]) {
								$('#diff-title-from').append('[' + memos[fromid] + ']');
							}
							if (typeof(memos) !== 'undefined' && memos[toid]) {
								$('#diff-title-to').append('[' + memos[toid] + ']');
							}
							if (!wp.revisions.Diff.singleRevision) {
								$('#diff-header-to').after('<div id="tor-div" class="diff-header"><div class="diff-title"><strong>&nbsp;</strong><span id="tor-msg" style="margin: 0 10px;">Remove revisions between two revisions above</span><input id="tor-thin-out" class="button button-primary" type="submit" value="Thin Out" /></div></div>');
							}
							return this;
						},

						thinOut: function () {
							var revs = '';
							for (var i = wp.revisions.Diff.leftDiff; i < wp.revisions.Diff.rightDiff - 1; i++) {
								revs = revs + (i == wp.revisions.Diff.leftDiff ? '' : '-') + this.model.at(i).get('ID');
							}
							var fromid = this.model.at(wp.revisions.Diff.leftDiff - 1).get('ID');
							var toid = this.model.at(wp.revisions.Diff.rightDiff - 1).get('ID');

							if (revs === '') {
								alert(hm_tor_params.msg_nothing_to_remove);
								return false;
							}
							if (confirm(hm_tor_params.msg_thinout_comfirmation + ' (ID: ' + revs + ')') != true) {
								return false;
							}

							$.ajax({
								url     : hm_tor_params.ajaxurl,
								dataType: 'json',
								data    : {
									action  : 'hm_tor_do_ajax',
									posts   : revs,
									security: hm_tor_params.nonce
								}
							})
									.success(function (response) {
								alert(hm_tor_params.msg_remove_completed);
								location.replace('./revision.php?action=edit&revision=' + hm_tor_params.latest_revision);
							})
									.error(function () {
								alert(hm_tor_params.msg_ajax_error);
							});

							return false;
						}
					}) /* View */
				};
				var torview = new TOR.View({
					model: wp.revisions.Diff.revisions
				});
				wp.revisions.Diff.revisionView.undelegateEvents();
				wp.revisions.Diff.revisionView = torview;
				torview.render();
			}
			else {
				setTimeout(arguments.callee, 300);
			}
		}, 300);


	});
	/* ready */

})(jQuery, window, document);
