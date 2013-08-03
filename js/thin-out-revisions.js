(function ($, window, document, undefined) {

	$(document).ready(function () {

		setTimeout(function () {
			if (wp && wp.revisions && wp.revisions.view && wp.revisions.view.Metabox &&
					wp.revisions.view.Controls && wp.revisions.view.frame.model && wp.revisions.view.frame.model.get('from')) {
				var slider_model = 0;
				TOR = {
					View: wp.revisions.view.Metabox.extend({
						events: {},

						initialize: function () {
							wp.revisions.view.Metabox.prototype.initialize.apply(this);
							_.bindAll(this, 'render');
							this.events = _.extend({'click #tor-thin-out': 'thinOut'},
									wp.revisions.view.Metabox.prototype.events);
							this.listenTo(this.model, 'change:compareTwoMode', this.render);

							if (slider_model) {
								this.listenTo(slider_model, 'update:slider', this.render);
							}
						},

						render: function () {

							wp.revisions.view.Metabox.prototype.render.apply(this);

							if (this.model.get('compareTwoMode')) {
								this.$el.html(this.$el.html() + '<div id="tor-div" class="diff-header"><div class="diff-title"><strong>&nbsp;</strong><span id="tor-msg" style="margin: 0 10px;">'
								+ hm_tor_params.msg_thin_out + '</span><input id="tor-thin-out" class="button button-primary" type="submit" value="Thin Out" /></div></div>');

							}

							var fromid = this.model.get('from').get('id');
							var toid = this.model.get('to').get('id');
							if (typeof(memos) !== 'undefined' && memos[fromid]) {
								var $f = $('.diff-meta-from .diff-title');
								if (! /\[/.test($f.text())) { // avoid duplicated memos
									$f.append('[' + memos[fromid] + ']');
								}
							}
							if (typeof(memos) !== 'undefined' && memos[toid]) {
								var $t = 	$('.diff-meta-to .diff-title');
								if (! /\[/.test($t.text())) {
									$t.append('[' + memos[toid] + ']');
								}
							}
							return this;
						},

						thinOut: function () {
							var revs = '';
							var revs_disp = '';
							var from = this.model.revisions.indexOf(this.model.get('from')) + 1;
							var to   = this.model.revisions.indexOf(this.model.get('to'));

							for (var i = from; i < to; i++) {
								revs = revs + (i === from ? '' : '-') + this.model.revisions.at(i).get('id');
								revs_disp = revs_disp + (i === from ? '' : ',') + this.model.revisions.at(i).get('id');
							}

							if (revs === '') {
								alert(hm_tor_params.msg_nothing_to_remove);
								return false;
							}
							if (confirm(hm_tor_params.msg_thinout_comfirmation + ' (ID: ' + revs_disp + ')') != true) {
								return false;
							}

							$('#tor-thin-out').attr('value', 'Processing...').attr('disabled', 'disabled');

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
								//location.replace('./revision.php?from=' + from + '&to=' + to); // it doesn't work...
								location.replace('./revision.php?revision=' + hm_tor_params.latest_revision);
							})
									.error(function () {
								alert(hm_tor_params.msg_ajax_error);
							});

							return false;
						}
					}) /* View */
				}; /* TOR */

				var cv = (wp.revisions.view.frame.views.get('.revisions-control-frame'))[0];
				var mv = 0;
				var sv = 0;

				var i;
				for (i = 0; i < cv.views._views[''].length; i++) {
					if (cv.views._views[''][i].className === 'revisions-meta') {
						mv = cv.views._views[''][i];
					}
					else if (cv.views._views[''][i].className === 'wp-slider') {
						sv = cv.views._views[''][i];
					}
				}
				if (mv) {
					mv.remove();
				}
				if (sv) {
					slider_model = sv.model;
				}

				var torview = new TOR.View({
					model: wp.revisions.view.frame.model
				});
				cv.views.add(torview);
				torview.render();
			}
			else {
				setTimeout(arguments.callee, 300);
			}
		}, 300);


	});
	/* ready */

})(jQuery, window, document);
