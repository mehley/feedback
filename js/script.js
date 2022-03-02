function xorEnc (sessionId, inputString) {
    self.cryptedString = '';

    $.ajax(
        {
            type: "POST",
            url: "../ajax/xorEnc.php",
            data: {toCrypt: inputString},
            async: false,
            success: function (answer) {
                self.cryptedString = answer;
            }
        }
    );

    return (self.cryptedString);
}

$('.sendMails').on( "click", function() {
    if (confirm('Sind Sie sicher, dass Sie die E-Mails verschicken möchten?')){
        var id_email_dispatch = $(this).data('id_email_dispatch');
        var postData = {};
        var postParam1 = xorEnc(self.sessionId, 'id_email_dispatch');

        postData[postParam1] = id_email_dispatch;

        $.ajax({
            type:'POST',
            url:'../sendmail.php',
            data: postData,
            dataType: 'text',
            success:function(response){
                $('.mail_response').html(response);
                $('.recipient_list').removeClass('hidden');
            }
        });
    }
});

$('.feedbackFormSubmit').on("click", function (){
    $('.required-rating').each(function(i, obj) {
        var toCheck = $(this).attr('name');
        if (!$("input[name="+toCheck+"]:checked").val()) {
            $(this).parent().addClass('empty');
        }else{
            $(this).parent().removeClass('empty');
        }
    });

    $('.required_radio').each(function(i, obj) {
        var toCheck = $(this).attr('name');
        if (!$("input[name="+toCheck+"]:checked").val()) {
            $(this).parent().parent().addClass('empty');
        }else{
            $(this).parent().removeClass('empty');
        }
    });
});

$(document).ready(function() {
    $('.js-example-basic-multiple').select2();
});

$('.addFormElement').on('click', function (){
    var counter = $('#elementCounter').val();

    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'action');
    var postParam2 = xorEnc(self.sessionId, 'numberElement');

    postData[postParam1] = 'select_element';
    postData[postParam2] = counter;

    $.ajax({
        type:'POST',
        url:'../ajax/form_generator.php',
        data: postData,
        success:function(response){
            $('.fg_list').append(response).children().sortable({
                connectWith: ".fg_list"
            }).disableSelection();
            $(".sortList").sortable({
                connectWith: ".fg_list"
            }).disableSelection();

            var newCounter = parseInt(counter) + 1;
            $('#elementCounter').val(newCounter);
        }
    });
});

$('.settingsAddMailfromBtn').on('click', function (){
    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'action');

    postData[postParam1] = 'addMail';

    $.ajax({
        type:'POST',
        url:'../ajax/settings.php',
        data: postData,
        success:function(response){
            $('.mailsFromContainer').append(response);
        }
    });
});

$('body').on('change','.fg_element_select', function (){
    var elementNumber = $(this).data('number_element');

    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'action');
    var postParam2 = xorEnc(self.sessionId, 'numberElement');

    postData[postParam1] = $(this).val();
    postData[postParam2] = elementNumber;

    $.ajax({
        type:'POST',
        url:'../ajax/form_generator.php',
        data: postData,
        success:function(response){
            $('.fg_input_div'+elementNumber).empty().append(response);
        }
    });
});

$('.lan_de').on( "click", function() {
    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'language');

    postData[postParam1] = 'de';

    $.ajax({
        type:'POST',
        url:'../ajax/languages.php',
        data: postData,
        success:function(response){
            location.reload();
        }
    });
});

$('.lan_en').on('click', function() {
    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'language');

    postData[postParam1] = 'en';

    $.ajax({
        type:'POST',
        url:'../ajax/languages.php',
        data: postData,
        success:function(response){
            location.reload();
        }
    });
});

$('body').on('click','.delete_mailfrom', function() {
    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'action');
    var postParam2 = xorEnc(self.sessionId, 'id');

    postData[postParam1] = 'removeMail';
    postData[postParam2] = $(this).data('id_mailfrom');

    $.ajax({
        type:'POST',
        url:'../ajax/settings.php',
        data: postData,
        success:function(response){
            $('.mailsFromContainer').empty().append(response);
        }
    });
});

$('body').on('change','#selectExistingForm',function(){
    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'action');
    var postParam2 = xorEnc(self.sessionId, 'id');

    postData[postParam1] = 'loadForm';
    postData[postParam2] = $(this).val();

    $.ajax({
        type:'POST',
        url:'../ajax/form_generator.php',
        data: postData,
        success:function(response){
            $('.formGenerator').empty().append(response).children().sortable({
                connectWith: ".fg_list"
            }).disableSelection();
            $(".sortList").sortable({
                connectWith: ".fg_list"
            }).disableSelection();
        }
    });
});

$('body').on('change','#selectExistingMailTemplate',function(){
    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'action');
    var postParam2 = xorEnc(self.sessionId, 'id');

    postData[postParam1] = 'loadTemplate';
    postData[postParam2] = $(this).val();

    $.ajax({
        type:'POST',
        url:'../ajax/mail_template.php',
        data: postData,
        success:function(response){
            $('.form_mail_template').empty().append(response);
        }
    });
});

$('body').on('click','.fg_delete_form', function() {

    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'action');
    var postParam2 = xorEnc(self.sessionId, 'id');

    postData[postParam1] = 'removeFormConfirm';
    postData[postParam2] = $('#selectExistingForm').val();

    $.ajax({
        type:'POST',
        url:'../ajax/form_generator.php',
        data: postData,
        dataType: 'json',
        success:function(response){
            swal({
                title: response['titleConfirm'],
                text: response['textConfirm'],
                icon: "warning",
                buttons: ["Abbrechen", true],
            }).then((willDelete) => {
                if (willDelete) {
                    postData[postParam1] = 'removeForm';
                    $.ajax({
                        type: 'POST',
                        url: '../ajax/form_generator.php',
                        data: postData,
                        dataType: 'json',
                        success: function (response) {
                            swal({
                                title: response['title'],
                                text: response['text'],
                                icon: "success",
                                timer: 2000,
                            }).then((willDelete) => {
                                window.location.reload();
                            })
                        }
                    });
                }
            });
        }
    });
});

$('body').on('click','.fg_delete_mail_content', function() {

    if ($('#selectExistingMailTemplate').val() > 0) {
        var postData = {};
        var postParam1 = xorEnc(self.sessionId, 'action');
        var postParam2 = xorEnc(self.sessionId, 'id');

        postData[postParam1] = 'removeTemplateConfirm';
        postData[postParam2] = $('#selectExistingMailTemplate').val();

        $.ajax({
            type: 'POST',
            url: '../ajax/mail_template.php',
            data: postData,
            dataType: 'json',
            success: function (response) {
                swal({
                    title: response['titleConfirm'],
                    text: response['textConfirm'],
                    icon: "warning",
                    buttons: ["Abbrechen", true],
                }).then((willDelete) => {
                    if (willDelete) {
                        postData[postParam1] = 'removeTemplate';
                        $.ajax({
                            type: 'POST',
                            url: '../ajax/mail_template.php',
                            data: postData,
                            dataType: 'json',
                            success: function (response) {
                                swal({
                                    title: response['title'],
                                    text: response['text'],
                                    icon: "success",
                                    timer: 2000,
                                }).then((willDelete) => {
                                    window.location.reload();
                                })
                            }
                        });
                    }
                });
            }
        });
    }else{
        $('#form_mail_template').trigger("reset");
    }
});


$('body').on('click','.fg_deleteSelect ', function() {
    var elementToDelete = $(this).data('number_element');
    var postData = {};
    var postParam1 = xorEnc(self.sessionId, 'action');
    var postParam2 = xorEnc(self.sessionId, 'id');

    postData[postParam1] = 'removeSelectConfirm';
    postData[postParam2] = elementToDelete;

    $.ajax({
        type:'POST',
        url:'../ajax/form_generator.php',
        data: postData,
        dataType: 'json',
        success:function(response){
            swal({
                title: response['title'],
                text: response['text'],
                icon: "warning",
                buttons: ["Abbrechen", true],
            }).then((willDelete) => {
                if (willDelete) {
                    $('.fg_select_div'+elementToDelete).remove();
                }
            });
        }
    });
});

$(function() {
    $("#sortable").sortable();
});

$('body').on('submit','.fg_form',function(){
    var postData = $(".fg_form").serialize();

    $.ajax({
        type:'POST',
        url:'../ajax/form_generator.php',
        data: postData,
        dataType: 'json',
        success:function(answer){
            var postDataSelect = {};
            var postParamSelect1 = xorEnc(self.sessionId, 'action');
            var postParamSelect2 = xorEnc(self.sessionId, 'id_form');

            postDataSelect[postParamSelect1] = 'form_selector';
            postDataSelect[postParamSelect2] = answer['id_form'];

            $.ajax({
                type:'POST',
                url:'../ajax/form_generator.php',
                data: postDataSelect,
                success:function(response){
                    $('.fg_select_form').empty().append(response);
                }
            });

            if (answer['response'] == 'success'){
                swal({
                    title: answer['title'],
                    text: answer['text'],
                    icon: "success",
                    timer: 2000,
                })
            }

            if (answer['response'] == 'usedName'){
                swal({
                    title: answer['title'],
                    text: answer['text'],
                    icon: "error",
                    timer: 2000,
                })
            }

            if (answer['response'] == 'missingName'){
                swal({
                    title: answer['title'],
                    text: answer['text'],
                    icon: "error",
                    timer: 2000,
                })
            }
        }
    });
    event.preventDefault()
});