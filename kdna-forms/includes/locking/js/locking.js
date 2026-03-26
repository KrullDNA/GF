(function (gflocking, $) {

    $(document).ready(function () {
        gflocking.init();
    });

    var objectID, objectType, strings, hasLock, lockUI, rejectionCountdown, rejectionRequestTimeout, lockRequestInProgress = false;

    gflocking.init = function () {
        hasLock = gflockingVars.hasLock;
        objectID = gflockingVars.objectID;
        objectType = gflockingVars.objectType;
        lockUI = gflockingVars.lockUI;
        strings = gflockingVars.strings;

        initHeartbeat();

        initUI();

    };

    function lock_request_timedout() {
        $("#kdnaform-lock-request-status").html( gform.utils.escapeHtml( strings.noResponse ) );
        $("#kdnaform-lock-request-button").attr("disabled", false).text( strings.requestAgain );
        lockRequestInProgress = false;
        rejectionRequestTimeout = true;
        rejectionCountdown = false;
        wp.heartbeat.interval( 30 );
    }

    function initUI() {
        $("#kdnaform-lock-request-button").click(function () {
            var $this = $(this), key;
            $this.text("Request sent");
            $this.attr("disabled", true);
            $("#kdnaform-lock-request-status").html("");
            rejectionRequestTimeout = false;
            lockRequestInProgress = true;
            wp.heartbeat.interval( 5 );
            rejectionCountdown = setTimeout(lock_request_timedout, 120000);
            $.getJSON(ajaxurl, { action: "gf_lock_request_" + objectType, object_id: objectID })
                .done(function (json) {
                    $("#kdnaform-lock-request-status").html( gform.utils.escapeScripts( json.html ) );
                })
                .fail(function (jqxhr, textStatus, error) {
                    var err = textStatus + ', ' + error;
                    $("#kdnaform-lock-request-status").html( gform.utils.escapeScripts( strings.requestError + ": " + err ) );
                });
        });

        $("#kdnaform-reject-lock-request-button").click(function () {
            $.getJSON(ajaxurl, { action: "gf_reject_lock_request_" + objectType, object_id: objectID, object_type: objectType })
                .done(function (json) {
                    $('#kdnaform-lock-dialog').hide();
                })
                .fail(function (jqxhr, textStatus, error) {
                    var err = textStatus + ', ' + error;
                    $("#kdnaform-lock-request-status").html( gform.utils.escapeScripts( strings.requestError + ": " + err ) );
                    $('#kdnaform-lock-dialog').hide();
                });
        });


    }

    function initHeartbeat() {

        wp.heartbeat.interval( 30 );

        $( "#wpwrap" ).append( lockUI );

        // todo: refresh nonces

        var refreshLockKey = 'kdnaform-refresh-lock-' + objectType;

        var requestLockKey = 'kdnaform-request-lock-' + objectType;

        $(document).on('heartbeat-send.' + refreshLockKey, function (e, data) {
            var send = {};

            if (!objectID || !$('#kdnaform-lock-dialog').length)
                return;

            if (hasLock == 0)
                return;

            send['objectID'] = objectID;

            data[refreshLockKey] = send;
        });

        $(document).on('heartbeat-send.' + requestLockKey, function (e, data) {
            var send = {};

            if (!lockRequestInProgress)
                return data;

            send['objectID'] = objectID;

            data[requestLockKey] = send;
        });

        // update the lock or show the dialog if somebody has taken over editing

        $(document).on('heartbeat-tick.' + refreshLockKey, function (e, data) {
            var received, wrap, avatar, details;

            if (data[refreshLockKey]) {
                received = data[refreshLockKey];

                if (received.lock_error || received.lock_request) {
                    details = received.lock_error ? received.lock_error : received.lock_request;
                    wrap = $('#kdnaform-lock-dialog');
                    if (!wrap.length)
                        return;
                    if (!wrap.is(':visible')) {

                        if (details.avatar_src) {
                            avatar = $('<img class="avatar avatar-64 photo" width="64" height="64" />').attr('src', details.avatar_src.replace(/&amp;/g, '&'));
                            wrap.find('div.kdnaform-locked-avatar').empty().append(avatar);
                        }

                        wrap.show().find('.currently-editing').text(details.text);
                        if (received.lock_request) {
                            $("#kdnaform-reject-lock-request-button").show();
                        } else {
                            $("#kdnaform-reject-lock-request-button").hide();
                        }
                        wrap.find('.wp-tab-first').focus();

                    } else {

                        // dialog is already visible so the context is different

                        if (received.lock_error) {
                            if ($("#kdnaform-reject-lock-request-button").is(":visible")) {
                                if (received.lock_error.avatar_src) {
                                    avatar = $('<img class="avatar avatar-64 photo" width="64" height="64" />').attr('src', received.lock_error.avatar_src.replace(/&amp;/g, '&'));
                                    wrap.find('div.kdnaform-locked-avatar').empty().append(avatar);
                                }
                                $("#kdnaform-reject-lock-request-button").hide();
                                wrap.show().find('.currently-editing').text(received.lock_error.text);
                            }
                        } else if (received.lock_request) {
                            $("#kdnaform-lock-request-status").html( gform.utils.escapeScripts( received.lock_request.text ) );
                        }

                    }
                }
            }
        });


        $(document).on('heartbeat-tick.' + requestLockKey, function (e, data) {
            var received, wrap, status;

            if (data[requestLockKey]) {
                received = data[requestLockKey];

                if (received.status) {
                    status = received.status;
                    wrap = $('#kdnaform-lock-dialog');
                    if (!wrap.length)
                        return;

                    if (status != 'pending') {
                        clearTimeout(rejectionCountdown);
                        rejectionCountdown = false;
                        lockRequestInProgress = false
                    }

                    switch (status) {
                        case "granted" :
                            $("#kdnaform-lock-request-status").html(strings.gainedControl);
                            $("#kdnaform-take-over-button").show();
                            $("#kdnaform-lock-request-button").hide();
                            hasLock = true;
                            break;
                        case "deleted" :
                            $("#kdnaform-lock-request-button").text(strings.requestAgain).attr("disabled", false);
                            $("#kdnaform-lock-request-status").html(strings.rejected);
                            break;
                        case "pending" :
                            $("#kdnaform-lock-request-status").html(strings.pending);
                    }

                }
            }
        });
    }

}(window.gflocking = window.gflocking || {}, jQuery));
