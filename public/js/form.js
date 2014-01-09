(function (w, d, $) {

    $(d)
        .on('click', '.submit-form', function() {
            Form.clickSubmit(this);
        })
    ;

    w.gogogo = function() {

    };

    var Form = {

        clickSubmit: function(button) {
            button = $(button);
            var form = button.closest('form');
            var formData = {};
            $.each(form.serializeArray(), function(_, kv) {
                if (formData.hasOwnProperty(kv.name)) {
                    formData[kv.name] = $.makeArray(formData[kv.name]);
                    formData[kv.name].push(kv.value);
                } else {
                    formData[kv.name] = kv.value;
                }
            });
            formData.format = 'json';
            $.ajax({
                url: form.attr('action'),
                data: formData,
                success: function(response) {
                    if (typeof response.errors == 'undefined') {
                        form.trigger('success', response);
                    } else {
                        Form.showErrors(form, response.errors);
                    }
                }
            });
        },

        showErrors: function(form, errors) {
            form = $(form);
            Form.clearMessages(form);
            var formMessageBlock = '';
            for (var i in errors) {
                /*
                errors = {
                    name: "login", // field name
                    title: "Login field", // field title
                    text: "Wrong login" // error text
                }
                 */
                if (errors.hasOwnProperty(i)) {
                    var error = errors[i],
                        field = form.find('field-' + error.name),
                        fieldMessageBlock = field.find('.message'),
                        errorMessage = error.text
                    ;
                    if (field.length && fieldMessageBlock.length) {
                        field.addClass('error');
                        fieldMessageBlock.html(errorMessage).show();
                    } else {
                        errorMessage = error.title + ': ' + errorMessage;
                        formMessageBlock += '<li>' + errorMessage + '</li>';
                    }
                }
            }
            if (formMessageBlock != '') {
                form.find('.form-message').addClass('error').append('<ul>' + formMessageBlock + '</ul>').show();
            }
        },

        clearMessages: function(form) {
            form = $(form);
            form.find(".form-message").removeClass('error').removeClass('success').html('').hide();
            form.find('.form-field').removeClass('error').removeClass('success');
            form.find('.form-field .message').html('').hide();
        }

    };

    w.Form = Form;

})(this, this.document, this.jQuery);