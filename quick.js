(function ($) {
    var date = new Date();
    var settings = {
        process_url: window.location,
        key_trigger_delay: 350,
        ajax_min_interval: 600
    };

    var state = {
        ts_last_request: date.getTime(),
        ts_lastKey_up: date.getTime(),
        last_submitted_string: '',
        request_in_progress: false,
        poller: null,
        is_over_time: false
    };

    $.phpQuick = function(options) {
        settings = $.extend(settings, options);

        if (settings.valid_post) {
            updateDomOnSuccessResponse(settings.page_request_result);
        }

        initListeners();
        refreshTimeUi();

        return this;
    };

    function displayOutputContainer() {
        $('#InputContainer').addClass('left');
        $('#OutputContainer').removeClass('hide');
    }

    function initListeners() {
        $('textarea').keyup(function() {
            initRequest($(this).val(), $('select[name=type]').val());
        });

        $('#TheCurrentTime p').hover(function() {
            state.is_over_time = true;
        }, function(){
            state.is_over_time = false;
            refreshTimeUi();
        });

        $(".selectable").click(function () {
           $(this).select();
        }).parent().mouseout(function() {
            $(this).children('.selectable').blur();
        });

        $('#TimeStampConverter input[name=ts]').keyup(function() {
            var ts = $(this).val();

            if (parseInt(ts) === parseInt(ts, 10) && parseInt(ts) == ts) {
                // valid integer entered, format and display it:
                $('#TimeStampConverter input.selectable').removeClass('hide').val(new Date(ts * 1000).toUTCString());
                $('#TimeStampConverter .note').addClass('hide');
            } else {
                $('#TimeStampConverter input.selectable').addClass('hide');
                $('#TimeStampConverter .note').removeClass('hide');
            }
        });
    }

    function refreshTimeUi() {
        var date = new Date();
        if (!state.is_over_time) {

            $('#TheCurrentTime em').text(date.toUTCString());
            $('#TheCurrentTime input').val(Math.round(date.getTime() / 1000));

            setTimeout(function() {
                refreshTimeUi();
            }, 999);
        }
    }

    function initRequest(code, type) {
        if (state.request_in_progress || code == state.last_submitted_string) {
            return;
        }

        clearTimeout(state.poller);

        var ts_now        = new Date().getTime();
        var timeout_delay = false;

        if ((ts_now - state.ts_last_request) < settings.ajax_min_interval) {
            // queue
            timeout_delay = settings.ajax_min_interval - (ts_now - state.ts_last_request);
        } else if ((ts_now - state.ts_lastKey_up) < settings.key_trigger_delay) {
            // queue
            timeout_delay = settings.key_trigger_delay - (ts_now - state.ts_lastKey_up);
        } else {
            // dispatch request now
            makeRequest(code, type);
            return;
        }


        state.poller = setTimeout(function() {
            initRequest($('textarea').val(), $("select[name=type]").val());
        }, timeout_delay );
    }

    function makeRequest(code, type) {
        code = code.trim();

        if (code == '') {
            updateDomOnSuccessResponse({success: false});
            return;
        }

        state.ts_last_request       = new Date().getTime();
        state.last_submitted_string = code;
        state.request_in_progress   = true;

        $.ajax({
            type: 'POST',
            url: settings.process_url + '?ajax',
            data: {'code': code, 'type': type},
            dataType: 'json',
            error: function(data) {
                setErrorMessage(data.responseText.replace(/in \/.*\([0-9]+\) \: eval\(\)'d/i, 'in your'));
            },
            success: function(data) {
                updateDomOnSuccessResponse(data);
            },
            complete: function() {
                state.request_in_progress = false;
            }
        });
    }

    function setErrorMessage(message) {
        if ($('#enableErrors').attr('checked') == 'checked') {
            $('#outputError').removeClass('hide').text(message);
        } else {
            $('#outputError').addClass('hide');
        }
    }

    function updateDomOnSuccessResponse(data) {
        displayOutputContainer();

        // Display the elapsed php execution time:
        if (data.elapsed_time_formatted) {
            $('#ExecutionTime').removeClass('hide');
            $('#ExecutionTime strong').text(data.elapsed_time_formatted);
        } else {
            $('#ExecutionTime').addClass('hide');
        }

        // Display the process output:
        if (data.success === true) {
            $('pre#output').html(data.body);
        } else {
            $('pre#output').html('');
        }

        // Display the error string:
        setErrorMessage(data.error ? data.error : '');
    }

}(jQuery));
