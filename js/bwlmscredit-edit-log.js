
jQuery(function($) {

	$( '.click-to-toggle' ).click(function(){
		var target = $(this).attr( 'data-toggle' );
		$( '#' + target ).toggle();
	});


	var bwlmscredit_delete_log_entry = function( rowid, button ) {
		$.ajax({
			type       : "POST",
			data       : {
				action    : 'bwlmscredit-delete-log-entry',
				token     : bwlmsCREDITLog.tokens.delete_row,
				row       : rowid
			},
			dataType   : "JSON",
			url        : bwlmsCREDITLog.ajaxurl,
			success    : function( response ) {

				var parentrow = button.parent().parent().parent();
				var actioncol = button.parent().parent();

				if ( response.success ) {
					actioncol.empty();
					actioncol.text( response.data );

					parentrow.addClass( 'deleted-row' );
					parentrow.fadeOut( 3000, function(){ parentrow.remove(); });
				}
				else {
					actioncol.empty();
					actioncol.text( response.data );
				}
			},
			error      : function( jqXHR, textStatus, errorThrown ) {
			}
		});
	}


	$( '.bwlmscredit-delete-row' ).click(function(){
		// Require user to confirm deletion
		if ( ! confirm( bwlmsCREDITLog.messages.delete_row ) )
			return false;

		bwlmscredit_delete_log_entry( $(this).attr( 'data-id' ), $(this) );
	});

	var log_row_id = '';
	var log_user = '';
	var log_time = '';
	var log_cred = '';

	var log_entry_raw = '';
	var log_entry = '';


	$('#edit-bwlmscredit-log-entry').dialog({
		dialogClass : 'bwlmscredit-edit-log-entry',
		draggable   : true,
		autoOpen    : false,
		title       : bwlmsCREDITLog.title,
		closeText   : bwlmsCREDITLog.close,
		modal       : true,
		width       : 500,
		height      : 'auto',
		resizable   : false,
		show        : {
			effect     : 'slide',
			direction  : 'up',
			duration   : 250
		},
		hide        : {
			effect     : 'slide',
			direction  : 'up',
			duration   : 250
		}
	});

	$( '.bwlmscredit-open-log-entry-editor' ).click( function() {

		log_row_id = $(this).attr( 'data-id' );
		log_user = $(this).parent().siblings( 'td.column-username' ).children( 'span' ).text();
		log_time = $(this).parent().siblings( 'td.column-time' ).text();
		log_cred = $(this).parent().siblings( 'td.column-credits' ).text();

		log_entry_raw = $(this).parent().siblings( 'td.column-entry' ).children( 'div.raw' ).text();
		log_entry = $(this).parent().siblings( 'td.column-entry' ).children( 'div.entry' ).text();

		$( '#edit-bwlmscredit-log-entry' ).dialog( 'open' );

		var username_el = $( '#edit-bwlmscredit-log-entry #bwlmscredit-username' );
		username_el.empty();
		username_el.text( log_user );

		var time_el = $( '#edit-bwlmscredit-log-entry #bwlmscredit-time' );
		time_el.empty();
		time_el.text( log_time );

		var credits_el = $( '#edit-bwlmscredit-log-entry #bwlmscredit-credits' );
		credits_el.empty();
		credits_el.text( log_cred );

		var entry_el = $( '#edit-bwlmscredit-log-entry #bwlmscredit-raw-entry' );
		entry_el.val( '' );
		entry_el.val( log_entry );

		var raw_entry_el = $( '#edit-bwlmscredit-log-entry #bwlmscredit-new-entry' );
		raw_entry_el.val( '' );
		raw_entry_el.val( log_entry_raw );
		
		$( 'input#bwlmscredit-log-row-id' ).val( log_row_id );

	});

	var bwlmscredit_update_log_entry = function( rowid, entry, button ) {
		var button_label = button.val();

		$.ajax({
			type       : "POST",
			data       : {
				action    : 'bwlmscredit-update-log-entry',
				token     : bwlmsCREDITLog.tokens.update_row,
				row       : rowid,
				new_entry : entry
			},
			dataType   : "JSON",
			url        : bwlmsCREDITLog.ajaxurl,
			beforeSend : function() {
			
				button.removeClass( 'button-primary' );
				button.addClass( 'button-secondary' );
				button.val( bwlmsCREDITLog.working );
			},
			success    : function( response ) {

				console.log( response );

				var effected_row = $( '#bwlmscredit-log-entry-' + response.data.row_id );
				button.removeClass( 'button-secondary' );

				if ( response.success ) {
					effected_row.addClass( 'updated-row' );
					effected_row.children( 'td.column-entry' ).children( 'div.raw' ).empty().html( response.data.new_entry_raw );

					$( '#edit-bwlmscredit-log-entry #bwlmscredit-raw-entry' ).val( response.data.new_entry );

					effected_row.children( 'td.column-entry' ).children( 'div.entry' ).empty().html( response.data.new_entry );

					$( '#edit-bwlmscredit-log-entry #bwlmscredit-new-entry' ).val( response.data.new_entry_raw );

					button.val( response.data.label );
					setTimeout(function(){ button.val( button_label ); button.addClass( 'button-primary' ); }, 5000 );
				}
				else {
					button.val( response.data );
					setTimeout(function(){ button.val( button_label ); button.addClass( 'button-primary' ); }, 5000 );
				}
			},
			error      : function( jqXHR, textStatus, errorThrown ) {
			}
		});
	}

	$( '#bwlmscredit-update-log-entry' ).click( function() {
		bwlmscredit_update_log_entry( $(this).next().val(), $( 'input#bwlmscredit-new-entry' ).val(), $(this) );
	});

	var showNotice, adminMenu, columns, validateForm, screenMeta;

	adminMenu = {
		init : function() {},
		fold : function() {},
		restoreMenuState : function() {},
		toggle : function() {},
		favorites : function() {}
	};

	columns = {
		init : function() {
			var that = this;
			$('.hide-column-tog', '#adv-settings').click( function() {
				var $t = $(this), column = $t.val();
				if ( $t.prop('checked') )
					that.checked(column);
				else
					that.unchecked(column);

				columns.saveManageColumnsState();
			});
		},

		saveManageColumnsState : function() {
			var hidden = this.hidden();
			$.post(ajaxurl, {
				action: 'hidden-columns',
				hidden: hidden,
				screenoptionnonce: $('#screenoptionnonce').val(),
				page: pagenow
			});
		},

		checked : function(column) {
			$('.' + column).show();
			this.colSpanChange(+1);
		},

		unchecked : function(column) {
			$('.' + column).hide();
			this.colSpanChange(-1);
		},

		hidden : function() {
			return $('.manage-column').filter(':hidden').map(function() { return this.id; }).get().join(',');
		},

		useCheckboxesForHidden : function() {
			this.hidden = function(){
				return $('.hide-column-tog').not(':checked').map(function() {
					var id = this.id;
					return id.substring( id, id.length - 5 );
				}).get().join(',');
			};
		},

		colSpanChange : function(diff) {
			var $t = $('table').find('.colspanchange'), n;
			if ( !$t.length )
				return;
			n = parseInt( $t.attr('colspan'), 10 ) + diff;
			$t.attr('colspan', n.toString());
		}
	};

	$(document).ready(function(){columns.init();});
});
