jQuery(document).ready(function($) {

//	$('#title').hide();
	

	if ($('.skautbazar_intro_image_button').length > 0) {
		if ( typeof wp !== 'undefined' && wp.media && wp.media.editor) {
			$('.wrap').on('click', '.skautbazar_intro_image_button', function(e) {
				e.preventDefault();
			    var button = $(this);
			    var $img_id = $('#skautbazar_image_id');
			    var $img = $('#skautbazar_intro_image');
			            
			    wp.media.editor.send.attachment = function(props, attachment) {
			    	$img_id.val(attachment.id);
			        $img.css({'display':'inline'});
			        $img.attr('src', attachment.url);
			    };
			    wp.media.editor.open(button);

			    return false;
			});
		}
	};

	$('.skautbazar_intro_image_delete_button').click(function(e){
		e.preventDefault();
		var $img_id = $('#skautbazar_image_id');
		var $img = $('#skautbazar_intro_image');
		$img_id.val('');
		$img.css({'display':'none'});
		$img.attr('src', '#');
	});

	

	
		switch($('.skautbazar_type').val()){
			case 'exchange':						
				$('.skautbazar_row_hidden').css({'display':'none'});
				$('#skautbazar_row_exchange.skautbazar_row_hidden').css({'display':'table-row'});
			break;
			case 'price':
				$('.skautbazar_row_hidden').css({'display':'none'});
				$('#skautbazar_row_price.skautbazar_row_hidden').css({'display':'table-row'});
			break;
			default:
				$('.skautbazar_row_hidden').css({'display':'none'});
				break;
		}


	
	var $check = $('#skautbazar_status').val();	
	if($check == 2 || $check == 3 ) $('#skautbazar_row_reservation_email.skautbazar_row_hidden').css({'display':'table-row'});	

	$('#skautbazar_status').change(function(){
		if($(this).val() == 1) $('#skautbazar_row_reservation_email.skautbazar_row_hidden').css({'display':'none'});
		else $('#skautbazar_row_reservation_email.skautbazar_row_hidden').css({'display':'table-row'});
	});

	

	$('.skautbazar_type').change(function(){
		$('skautbazar_row').css({'display':'none'});
		switch($(this).val()){
			case 'exchange':						
				$('.skautbazar_row_hidden').css({'display':'none'});
				$('#skautbazar_row_exchange.skautbazar_row_hidden').css({'display':'table-row'});
			break;
			case 'price':
				$('.skautbazar_row_hidden').css({'display':'none'});
				$('#skautbazar_row_price.skautbazar_row_hidden').css({'display':'table-row'});
			break;
			default:
				$('.skautbazar_row_hidden').css({'display':'none'});
				break;
		}
	});



	$('#post').submit(function(e){
		$('.required-info, .skatubazar_error').remove();
		var error = true;
        
        $('.required').each(function(){
        	error = false;
           if( $(this).val() == '-1' || $(this).val() == '' ){ 
             $(this).after('<span class="required-info">'+ translation.fill_required_field +'</span>');
             e.preventDefault();
           }
         });

        if( $('#skautbazar_type_author').val() == 'price' && $('#skautbazar_price').val() == '' ) {
        	$('#skautbazar_price').after('<span class="required-info">'+ translation.fill_required_field +'</span>');
        	error = false;
        }

        if( $('#skautbazar_type_author').val() == 'exchange' && $('#skautbazar_exchange').val() == '' ) {
        	$('#skautbazar_exchange').after('<span class="required-info">'+ translation.fill_required_field +'</span>');
        	error = false;
        }

        if(!error) $('.wrap h2').after('<div class="error skatubazar_error"><p>'+ translation.fill_required_field +'</p></div>');
    });



    $('#skautbazar_cancel_reservation').click(function(e){
		e.preventDefault();

		$('.status_message').html( translation.active );

		$('#skautbazar_buyer_email').val('');
		$('#skautbazar_status').val(1);
		$(this).css({'display':'none'});
		$('#skautbazar_buyer_email').css({'display':'none'});
	});



});