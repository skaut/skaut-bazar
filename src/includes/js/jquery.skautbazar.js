function validateEmail($email) {
    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    return emailReg.test($email);
}

jQuery(document).ready(function($) {
    $('.skautbazar_rezervace').find('a').click(function(e) {
        e.preventDefault();
        $('.skautbazar_emailbox_bg').css({'display': 'block'});
        $('#skautbazar_item_id').val($(this).attr('href'));

        // Restore state of the box to default one
        $('.skautbazar_message').text('');
        $('.skautbazar_email_submit').css({'display': 'inline-block'});
        $('#skautbazar_email_customer').attr('readonly', false);
        $('#skautbazar_message_customer').val('');
    });

    $('.skautbazar_email_submit').click(function(e) {
        e.preventDefault();

        $('.skautbazar_message').text('');

        var id = $('#skautbazar_item_id').val();
        var email = $('#skautbazar_email_customer').val();
        var message = $('#skautbazar_message_customer').val();

        if (email == '' || !validateEmail(email)) {
            $('.skautbazar_message').text(ajax_object.email_not_valid);
            return;
        }

        var data = {
            'action': 'skautbazar_rezervace',
            _ajax_nonce: ajax_object.ajax_nonce,
            'bazar_item_id': id,
            'bazar_item_email': email,
            'bazar_item_message': message
        };

        $.post(ajax_object.ajax_url, data, function(response) {

            if (response == 1) {
                $('.skautbazar_message').text(ajax_object.email_reserved);
                $('.skautbazar_rezervace' + id).find('a').remove();
                $('.skautbazar_rezervace' + id).append('<span class="skautbazar_rezervovano">' + ajax_object.reserved + '</span>');
                $('.skautbazar_email_submit').css({'display': 'none'});
                $('#skautbazar_email_customer').attr('readonly', true);

                $('.skautbazar_emailbox_bg').delay(2000).fadeOut();
            } else {
                $('.skautbazar_message').text(ajax_object.error_during_reservation);
            }
        });
    });

    $('.skautbazar_email_close').click(function(e) {
        e.preventDefault();
        $('.skautbazar_emailbox_bg').css({'display': 'none'});
    });
});
