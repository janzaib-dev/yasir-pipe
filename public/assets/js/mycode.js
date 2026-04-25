function showAlert(title, text, icon) {
    Swal.fire({
        title: title,
        html: text,
        icon: icon,
    });
}


function logoutAndDeleteFunction(e) {
    var msg = e.getAttribute("data-msg");
    var method = e.getAttribute("data-method");
    var url = e.getAttribute("data-url");

    swal.fire({
        title: "Are you sure?",
        text: msg,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'continue',
        cancelButtonText: 'cancel',
        dangerMode: true,
    })
    .then((result) => {
        if (result.isConfirmed) {
            yourFunction(url,method);
        } else {
            swal("Your account is safe!");
        }
    });

}
function yourFunction(url,method) {
        $.ajax({
            url: url,
            type: method,
            headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                     },
            success: function(response) {
                if (response['reload'] != undefined) {
                    showAlert("Success", response.success, "success");
                    window.location.reload();
                }
                if (response['redirect'] != undefined) {
                    showAlert("Success", response.success, "success");
                    window.location.href = response['redirect'];
                }
            },
            error: function(xhr, status, error) {
                // Handle errors
            }
        });
    }

    function multipleerrorshandle(errors) {
        let message = '';
        for (var errorkey in errors) {
            message += "<span style='color:red'>" + errors[errorkey] + "</span><br>";
        }
        showAlert('Errors', message, 'error');
    }

    function ajaxErrorHandling(data, msg){
        if (data.hasOwnProperty("responseJSON")) {
            var resp = data.responseJSON;
            if (resp.message == 'CSRF token mismatch.') {
                showAlert("Page has been expired and will reload in 2 seconds", "Page Expired!", "error");
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
                return;
            }
            if (resp.error) {
                var msg = (resp.error == '') ? 'Something went wrong!' : resp.error;
                showAlert(msg, "Error!", "error");
                return;
            }
            if (resp.message != 'The given data was invalid.') {
                showAlert(resp.message, "Error!", "error");
                return;
            }
            multipleerrorshandle(resp.errors);
        } else {
            showAlert(msg + "!", "Error!", 'error');
        }
        return;
    }
    //post
    function myAjax(url, formData, method = 'post', callback, options = {}) {
        $.ajax({
            url: url,
            method: method,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            // Ensure submit buttons are re-enabled on completion to avoid stuck disabled state
            complete: function(jqXHR, textStatus) {
                try {
                    // If a form element was provided in options, enable its submit buttons
                    if (options.form) {
                        $(options.form).find(':submit').prop('disabled', false);
                        $(options.form).find('.save-btn').prop('disabled', false);
                    }
                    // Generic fallback: re-enable any save-btn or disabled submit buttons on the page
                    $(':submit:disabled').prop('disabled', false);
                    $('.save-btn:disabled').prop('disabled', false);
                } catch (e) { console.warn('myAjax completion handler error', e); }
            },
            success: function(data) {
                if (data['reload'] != undefined) {
                    showAlert("Success", data.success, "success");
                    window.location.reload();
                    return false;
                }
                if (data['redirect'] != undefined) {
                    showAlert("Success", data.success, "success");
                    window.location.href = data['redirect'];
                    return false;
                }
                if (data['error'] !== undefined) {
                    var text = "<span style='color:red'>" + data['error'] + "</span>";
                    showAlert('Error', text, 'error');
                    return false;
                }
                if (data['errors'] !== undefined) {
                    multipleerrorshandle(data['errors'])
                    return false;
                }

                callback(data)
            },
            error: function (jqXHR, textStatus, errorThrown) {
                ajaxErrorHandling(jqXHR, errorThrown);
            },

        });
    }





