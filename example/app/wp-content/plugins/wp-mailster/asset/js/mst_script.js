//
// @copyright (C) 2016 - 2017 Holger Brandt IT Solutions
// @license GNU/GPL, see license.txt
// WP Mailster is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License 2
// as published by the Free Software Foundation.
//
// WP Mailster is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with WP Mailster; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
// or see http://www.gnu.org/licenses/.
//

jQuery(document).ready(function() {
	var servernamed = true;
	if( !jQuery("#server_name_in").val() ||  !jQuery("#server_name_out").val() ||  !jQuery("#server_name").val()) {
		servernamed = false;
	}
	//for new mailing lists
	if( jQuery("#server_inb_id").val() == 0 ) {
		jQuery("#inbox_settings").show();
	}

	if( jQuery("#server_out_id").val() == 0 ) {
		jQuery("#outbox_settings").show();
	}

	jQuery("#server_host_in").change(function() {
		if( !servernamed ) {
			server_name = jQuery(this).val() + " (" + jQuery("#protocol_in").val() + ")";
			jQuery("#server_name_in").val(server_name);
		}
	});

	jQuery("#protocol_in").change(function() {
		if( !servernamed ) {
			server_name = jQuery("#server_host_in").val() + " (" + jQuery(this).val() + ")";
			jQuery("#server_name_in").val(server_name);
		}
	});

	jQuery("#server_host_out").change(function() {
		if( !servernamed ) {
			server_name = jQuery(this).val() + " (SMTP)";
			jQuery("#server_name_out").val(server_name);
		}
	});

	jQuery("#server_host").change(function() {
		if( !servernamed ) {
			var type = " (SMTP)";
			if( jQuery("#server_type").val() == 0 ) { //inbox
				type = " (" + jQuery("#protocol").val() + ")";
			}
			server_name = jQuery(this).val() + type;
			jQuery("#server_name").val(server_name);
		}
	});


	jQuery("#protocol").change(function() {
		if( !servernamed ) {
			server_name = jQuery("#server_host").val() + " (" + jQuery(this).val() + ")";
			jQuery("#server_name").val(server_name);
		}
	});

	/*  */

	//mailing list tools
	jQuery(".mst_container").on('click','#getInboxStatus', function(e) {
		e.preventDefault();
		var listid = jQuery("#lid").val();
		var data2send = '{ "task": "getInboxStatus", "listId": "' + listid + '" }';
		jQuery('#getInboxStatusProgressIndicator').removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');
		var data = {
			'action': 'getInboxStatus',
			'mtrAjaxData': data2send     // We pass php values differently!
		};
	    jQuery.post(ajaxurl, data, function(response) {
			var resultData = eval(response)[0];
			if(resultData){
				jQuery('#getInboxStatusProgressIndicator').removeClass('mtrActivityIndicator');
				var resultObject = eval(response)[0];

				jQuery('#tool_info_display').html(resultObject.mailboxStatusText);

			}else{
				alert("Could not complete ajax request");
			}
		}, 'json');
	});

	jQuery(".mst_container").on('click','#removeFirstMailLink', function(e){
		e.preventDefault();
		var listid = jQuery("#lid").val();
		var data2send = '{ "task": "removeFirstMailFromMailbox", "listId": "' + listid + '"}';
		jQuery('#removeFirstMailLinkProgressIndicator').removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');	
	   	var data = {
			'action': 'removeFirstMailFromMailbox',
			'mtrAjaxData': data2send     // We pass php values differently!
		};
	    jQuery.post(ajaxurl, data, function(response) {
		    var resultData = eval(response)[0];
			if(resultData){
				var resultObject = eval(response)[0];
				if(resultObject.res == 'true'){
					jQuery('#removeFirstMailLinkProgressIndicator').removeClass('mtrActivityIndicator');	
					alert('Deleted first email in inbox' );	
				}
			}else{
				alert("Could not complete ajax request");
			}
		}, 'json');
	});

	jQuery(".mst_container").on('click','#removeAllMailsLink', function(e){
		e.preventDefault();
		var listid = jQuery("#lid").val();
		var data2send = '{ "task": "removeAllMailsFromMailbox", "listId": "' + listid + '"}';
		jQuery('#removeAllMailsLinkProgressIndicator').removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');	
	    var data = {
			'action': 'removeAllMailsFromMailbox',
			'mtrAjaxData': data2send     // We pass php values differently!
		};
	    jQuery.post(ajaxurl, data, function(resultData){
			if(resultData){
				var resultObject = eval(resultData)[0];
				if(resultObject.res == 'true'){
					jQuery('#removeAllMailsLinkProgressIndicator').removeClass('mtrActivityIndicator');	
					alert('Deleted all emails in inbox');	
				}
			}else{
				alert("Could not complete ajax request");
			}
		});
	});

	jQuery(".mst_container").on('click','#removeAllMailsInSendQueue', function(e){
		var listid = jQuery("#lid").val();
		var data2send = '{ "task": "removeAllMailsInSendQueue", "listId": "' + listid + '"}';
		jQuery('#removeAllMailsInSendQueueProgressIndicator').removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');	
		var data = {
			'action': 'removeAllMailsInSendQueue',
			'mtrAjaxData': data2send     // We pass php values differently!
		};
	    jQuery.post(ajaxurl, data, function(resultData){
			if(resultData){
				var resultObject = eval(resultData)[0];
				if(resultObject.res == 'true'){
					jQuery('#removeAllMailsInSendQueueProgressIndicator').removeClass('mtrActivityIndicator');	
					alert('Removed all emails in the send queue');	
				}
			}else{
				alert("Could not complete ajax request");
			}
		});
	});

	jQuery(".mst_container").on('click','#unlockMailingList', function(e){
		var listid = jQuery("#lid").val();
		var data2send = '{ "task": "unlockMailingList", "listId":  "' + listid + '"}';
		jQuery('#unlockMailingListProgressIndicator').removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');	
	    var data = {
			'action': 'removeAllMailsInSendQueue',
			'mtrAjaxData': data2send     // We pass php values differently!
		};
	    jQuery.post(ajaxurl, data, function(resultData){
	    	if(resultData){
		        var resultObject = eval(resultData)[0];
				if(resultObject.res == 'true'){
	       			jQuery('#unlockMailingListProgressIndicator').removeClass('mtrActivityIndicator');	
					alert('Unlocked mailing list');
				}
	        } else {
	         	alert("Could not complete ajax request");
	        }
		});
	});


	jQuery("#deleteLog").click( function(e){
		jQuery('#deleteLog').removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');
	    var data = {
			'action': 'wpmst_deleteLogFile'
		};
	    jQuery.post(ajaxurl, data, function(resultData){
	    	if(resultData){
				alert(resultData);
				jQuery('#deleteLog').removeClass('mtrActivityIndicator');
	        } else {
	         	alert("Could not complete ajax request");
	        }
		});
	});

	//tabs
	jQuery( "#tabs" ).tabs();

	//list test inbox connection
	jQuery('#inboxConnectionCheck2').click(function(){
		var in_server = jQuery('#server_inb_id').val();
		var in_user = jQuery('#mail_in_user').val();
		var in_pw = jQuery('#mail_in_pw').val();
		var in_host = jQuery('#server_host_in').val();
		var in_port = jQuery('#server_port_in').val();
		var in_secure = jQuery('#secure_protocol_in').val();
		var in_sec_auth = jQuery('input:radio[name=secure_authentication_in]:checked').val();
		var in_protocol = jQuery('#protocol_in').val();
		var in_params = jQuery('#connection_parameter_in').val();
		var data2send = '{ "task": "inboxConnCheck",  "in_server": "' + in_server +'", "in_user": "' + in_user + '", "in_pw": "'+ in_pw + '", "in_host": "'+ in_host + '", "in_port": "'+ in_port + '", "in_secure": "'+ in_secure+ '", "in_sec_auth": "' + in_sec_auth + '", "in_protocol": "' + in_protocol + '", "in_params": "' + in_params + '" }';
		
		jQuery('#progressIndicator1').addClass('mtrActivityIndicator');
		

		var data = {
			'action': 'conncheck',
			'task': 'inboxConnCheck',
			'mtrAjaxData': data2send     // We pass php values differently!
		};
		// We can also pass the url value separately from ajaxurl for front end AJAX implementations
		jQuery.post(ajaxurl, data, function(response) {
			var resultObject = eval(response)[0];
			jQuery('#progressIndicator1').removeClass('mtrActivityIndicator');
	    	alert(resultObject.checkresult);
		});
	});
	jQuery('#outboxConnectionCheck2').click( function(){
		var list_name = jQuery('#name').val();
		var admin_email = jQuery('#admin_mail').val();
		var use_cms_mailer = jQuery('input:radio[name=use_cms_mailer]:checked').val();
        var out_server = jQuery('#server_out_id').val();
		var out_email = jQuery('#list_mail').val();
		var out_user = jQuery('#mail_out_user').val();
		var out_pw = jQuery('#mail_out_pw').val();
		var out_host = jQuery('#server_host_out').val();
		var out_port = jQuery('#server_port_out').val();
		var out_secure = jQuery('#secure_protocol_out').val();
		var out_sec_auth = jQuery('input:radio[name=secure_authentication_out]:checked').val();
		var data2send = '{ "task": "outboxConnCheck", "out_server": "' + out_server + '", "out_user": "' + out_user + '", "out_pw": "'+ out_pw + '", "out_email": "'+ out_email + '", "out_host": "'+ out_host + '", "out_port": "'+ out_port + '", "out_secure": "'+ out_secure+ '", "out_sec_auth": "' + out_sec_auth + '", "list_name": "'+ list_name + '", "admin_email": "'+ admin_email+ '", "use_cms_mailer": "' + use_cms_mailer+ '" }';
		var data = {
			'action': 'conncheck',
			'task': 'outboxConnCheck',
			'mtrAjaxData': data2send     // We pass php values differently!
		};
		jQuery('#progressIndicator2').addClass('mtrActivityIndicator');
		// We can also pass the url value separately from ajaxurl for front end AJAX implementations
		jQuery.post(ajaxurl, data, function(response) {
			var resultObject = eval(response)[0];
			jQuery('#progressIndicator2').removeClass('mtrActivityIndicator');
			alert(resultObject.checkresult);
		});
	});

	//show hide server settings
	jQuery("#server_inb_id").on('change', function() {
  		//show fields for server edit
		if( jQuery(this).val() == 0 ) {
  			jQuery("#inbox_settings").show();
  			jQuery("#show_settings_inb").hide();
  		} else {
			jQuery("#show_settings_inb").show();
			jQuery("#server_inb_id").removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');
			//change values on all server fields
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					action: 'wpmst_get_server_data',
					server_id:  jQuery(this).val()
				},
				timeout: 15000, //Timeout Handling
				success: function(data)
				{
					var response = jQuery.parseJSON(data);
					jQuery("#server_name_in").val(response['name']);
					jQuery("#server_host_in").val(response['server_host']);
					jQuery("#server_port_in").val(response['server_port']);
					jQuery("#protocol_in").val(response['protocol']);
					jQuery("#secure_protocol_in").val(response['secure_protocol']);
					jQuery("#secure_authentication_in").val(response['secure_authentication']);
					jQuery("#connection_parameter_in").val(response['connection_parameter']);
					jQuery("#server_inb_id").removeClass('mtrActivityIndicator');
				},
				error: function(textStatus) //post didnt work
				{
					alert('Error Occured! Please try again later');
				}
			});
  			jQuery("#inbox_settings").hide();
  		}
	});
	jQuery("#server_out_id").on('change', function() {
		//show fields for server edit
		if( jQuery(this).val() == 0 ) {
  			jQuery("#outbox_settings").show();
			jQuery("#show_settings_out").hide();
  		} else {
			jQuery("#show_settings_out").show();
			jQuery("#server_out_id").removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');
			//change values on all server fields
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {
					action: 'wpmst_get_server_data',
					server_id:  jQuery(this).val()
				},
				timeout: 15000, //Timeout Handling
				success: function(data)
				{
					var response = jQuery.parseJSON(data);
					jQuery("#server_name_out").val(response['name']);
					jQuery("#server_host_out").val(response['server_host']);
					jQuery("#server_port_out").val(response['server_port']);
					jQuery("#secure_protocol_out").val(response['secure_protocol']);
					jQuery("#secure_authentication_out").val(response['secure_authentication']);
					jQuery("#server_out_id").removeClass('mtrActivityIndicator');
				},
				error: function(textStatus) //post didnt work
				{
					alert('Error Occured! Please try again later');
				}
			});
  			jQuery("#outbox_settings").hide();
  		}
	});

	//show hide server settings in server tab in edit list page
	jQuery("#show_settings_inb").click( function(e){
		e.preventDefault();
		jQuery("#inb_edited").val(1);
		jQuery("#inbox_settings").toggle();
	});
	//show hide server settings in server tab in edit list page
	jQuery("#show_settings_out").click( function(e){
		e.preventDefault();
		jQuery("#out_edited").val(1);
		jQuery("#outbox_settings").toggle();
	});

	//show hide server settings in server add-edit page
	jQuery("#server_type").on('change', function() {
  		if( jQuery(this).val() == 0 ) {
  			jQuery("#inbox_settings").show();
  		} else {
  			jQuery("#inbox_settings").hide();
  		}
	});

	//multi selects
	jQuery(document).ready(function($) {
		$('#searchable').multiselect2side({
			search: "Search: "
		});
		$('.clickToView2').click(function() {
			$(this).parent().prevAll("select:first").toggle();
			return false;
		});

		$('.clickToView').click(function() {
			elClick = $(this);
			selEl = elClick.prevAll("select:first");

			$.ajax({
				url: 'jmultiselect2side.php',
				data: selEl.serialize() + '&SELECTNAME=' + selEl.attr("name"),
				success: function(data) {
					elClick.next().next().next().html(data);
				}
			});
			return false;
		});


	});


	jQuery('#resetTimer').click(function(e){
		e.preventDefault();
		jQuery('#progressIndicator1').removeClass('mtrActivityIndicator').addClass('mtrActivityIndicator');
		var data2send = '{ "task": "resetPlgTimer" }';
		var data = {
			'action': 'resetplgtimer',
			'mtrAjaxData': data2send
		};
		// We can also pass the url value separately from ajaxurl for front end AJAX implementations
		jQuery.post(ajaxurl, data, function(resultData){ 
			if(resultData){
				var resultObject = eval(resultData)[0];
				jQuery('#progressIndicator1').removeClass('mtrActivityIndicator');
				jQuery('#timeResetContainer').text(resultObject.checkresult);
				location.reload();
			}else{ 
				alert('Could not complete ajax request');
			}
		});

		jQuery('#resetTimer').hide();	
		jQuery('#progressIndicator1').show();	      
	});


	//mailing list edit screen
	//tab 5
	function disableFieldsTab5() {
		jQuery("#sending_recipients").attr('disabled', 'disabled');
		jQuery("#sending_admin").attr('disabled', 'disabled');
		jQuery("#sending_group").attr('disabled', 'disabled');
		jQuery("#sending_group_id").attr('disabled', 'disabled');
	}
	function enableFieldsTab5() {
		jQuery("#sending_recipients").removeAttr('disabled');
		jQuery("#sending_admin").removeAttr('disabled');
		jQuery("#sending_group").removeAttr('disabled');
		jQuery("#sending_group_id").removeAttr('disabled');
	}

	if( jQuery("#sending_public1").is(':checked') ) {
		disableFieldsTab5();
	}
	jQuery("#sending_public1").click( function() {
		disableFieldsTab5();
	});
	jQuery("#sending_public0").click( function() {
		enableFieldsTab5();
	});

	//tab6
	function disableFieldsTab61() {
		jQuery("#use_bcc_suboptions input").attr('disabled', 'disabled');
	}
	function enableFieldsTab61() {
		jQuery("#use_bcc_suboptions input").removeAttr('disabled');
	}
	if( jQuery("#use_bcc").is(':checked') ) {
		enableFieldsTab61();
	} else {
		disableFieldsTab61();
	}
	jQuery("#use_bcc").click( function() {
		enableFieldsTab61();
	});
	jQuery("#use_cc").click( function() {
		disableFieldsTab61();
	});
	jQuery("#use_to").click( function() {
		disableFieldsTab61();
	});
	function disableFieldsTab62() {
		jQuery("#bounce_mail").attr('disabled', 'disabled');
	}
	function enableFieldsTab62() {
		jQuery("#bounce_mail").removeAttr('disabled');
	}
	if( jQuery("#useNoBounceAddress").is(':checked') ) {
		disableFieldsTab62();
	} else {
		enableFieldsTab62();
	}
	jQuery("#useNoBounceAddress").click( function() {
		disableFieldsTab62();
	});
	jQuery("#useBounceAddress").click( function() {
		enableFieldsTab62();
	});
	//tab7
	function disableFieldsTab7() {
		jQuery("#public_registrationYes").prop('disabled', true);
		jQuery("#public_registrationNo").prop('disabled', true);
	}
	function enableFieldsTab7() {
		jQuery("#public_registrationYes").prop('disabled', false);
		jQuery("#public_registrationNo").prop('disabled', false);
	}
	if( jQuery("#allow_subscribeYes").is(':checked') ) {
		enableFieldsTab7();
	} else {
		disableFieldsTab7();
	}
	jQuery("#allow_subscribeNo").click( function() {
		disableFieldsTab7();
	});
	jQuery("#allow_subscribeYes").click( function() {
		enableFieldsTab7();
	});


});

function validateSubmittedForm(task)
{
	var form = document.adminForm;
	if (task == 'cancel') // check we aren't cancelling
	{	// no need to validate, we are cancelling
		submitform( task );
		return;
	}else{
		submitform( task );
	}
}
function submitbutton(task){
	validateSubmittedForm(task);
}