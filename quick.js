(function ($) {
    var settings = {
        ts_server: Math.round(new Date().getTime() / 1000),
        process_url: window.location,
        key_trigger_delay: 350,
        ajax_min_interval: 600
    };

    var state = {
        ts_last_request: new Date().getTime(),
        ts_lastKey_up: new Date().getTime(),
        last_submitted_string: '',
        request_in_progress: false,
        poller: null,
        is_over_time: false
    };

    $.phpQuick = function(options) {
        settings = $.extend(settings, options);
        settings.ts_offset = settings.ts_server - (new Date().getTime() / 1000);

        initListeners();
        refreshTimeUi();

        return this;
    };

    function initListeners() {
        $('textarea').keyup(function(){
            if ($(this).val() != state.last_submitted_string){
                state.last_submitted_string = $(this).val();
                initRequest($(this).val(), $('select[name=type]').val());
            } else {
                state.last_submitted_string = $(this).val();
            }
        });

        $('#TheCurrentTime p').hover(function(){
            state.is_over_time = true;
        }, function(){
            state.is_over_time = false;
            refreshTimeUi();
        });

        $("#TheCurrentTime input, #TimeStampConverter input.formatted").click(function () {
           $(this).select();
        });

        $('#TheCurrentTime p').mouseout(function() {
            $('#TheCurrentTime input').blur();
        });

        $('#TimeStampConverter label').mouseout(function() {
            $('#TimeStampConverter input.formatted').blur();
        });

        $('#TimeStampConverter input[name=ts]').keyup(function() {
            var ts = $(this).val();

            if (parseInt(ts) === parseInt(ts, 10) && parseInt(ts) == ts) {
                // valid integer entered, format it

                $('#TimeStampConverter input.formatted').show().val(new Date(ts * 1000).toUTCString());
                $('#TimeStampConverter .note').hide();
            } else {
                $('#TimeStampConverter input.formatted').hide();
                $('#TimeStampConverter .note').show();
            }
        })
    }

    function refreshTimeUi() {
        if (!state.is_over_time) {
            var date = new Date();

            $('#TheCurrentTime em').text(date.toUTCString());
            $('#TheCurrentTime input').val(Math.round((date.getTime() / 1000) + settings.ts_offset));

            setTimeout(function() {
                refreshTimeUi();
            }, 999);
        }
    }

    function initRequest(code, type) {
        if (state.request_in_progress) {
            return;
        }

        var ts_now = new Date().getTime();

        clearTimeout(state.poller);

        var delay = false;

        if ((ts_now - state.ts_last_request) < settings.ajax_min_interval) {
            // queue
            delay = settings.ajax_min_interval - (ts_now - state.ts_last_request);
        } else if ((ts_now - state.ts_lastKey_up) < settings.key_trigger_delay) {
            // queue
            delay = settings.key_trigger_delay - (ts_now - state.ts_lastKey_up);
        } else {
            // OK, dispatch
            makeRequest(code, type);

            return;
        }

        state.poller = setTimeout(function() {
            initRequest($('textarea').val(), $("select[name=type]").val());
        }, delay );
    }

    function makeRequest(code, type) {
        state.request_in_progress = true;
        state.ts_last_request     = new Date().getTime();

        $.ajax({
            type: "POST",
            url: settings.process_url + "?ajax",
            data: {'code':code,'type':type},
            dataType: 'json',
            success: function(data) {
                updateDomOnSuccessResponse(data);
            },
            complete: function() {
                state.request_in_progress = false;
            },
            error: function(data) {
                if ($('#enableErrors').attr('checked') == 'checked') {
                    if ($('#outputError').length < 1){
                        $('div.fieldsetWrapper').addClass('left').after('<div class="fieldsetWrapper right"><fieldset class="right"><legend>Out</legend><pre id="output"></pre><div id="outputError"></div></fieldset></div>');
                    }
                    var errorMsg = data.responseText.replace(/in \/.*\([0-9]+\) \: eval\(\)'d/i, 'in your');
                    $('#outputError').html('<p>' + errorMsg + '</p>');
                }
            }
        });
    }

    function updateDomOnSuccessResponse(data) {
        // Display the elapsed php execution time:
        if (data.elapsed_time_formatted) {
            if ($('#ExecutionTime').lenght < 1) {
                $('pre#output').after('<p id="ExecutionTime">Execution time: <strong></strong></p>');
            }
            $('#ExecutionTime strong').text(data.elapsed_time_formatted);
        } else {
            $('#ExecutionTime').remove();
        }

        // Display the process output:
        if (data.success === true) {
            if ($('pre#output').length < 1) {
                $('div.fieldsetWrapper').addClass('left').after('<div class="fieldsetWrapper right"><fieldset class="right"><legend>Out</legend><pre id="output"></pre><div id="outputError"></div></fieldset></div>');
            }
            $('pre#output').html(data.body);
        } else {
            $('pre#output').html('');
        }

        // Display the error string:
        if (data.error && data.error != '') {
            if ($('#enableErrors').attr('checked') == 'checked') {
                if ($('#outputError').length < 1) {
                    $('div.fieldsetWrapper').addClass('left').after('<div class="fieldsetWrapper right"><fieldset class="right"><legend>Out</legend><pre id="output"></pre><div id="outputError"></div></fieldset></div>');
                }
                $('#outputError').html('<p>' + data.error + '</p>');
            }
        } else {
            $('#outputError').html('');
        }
    }

}(jQuery));
