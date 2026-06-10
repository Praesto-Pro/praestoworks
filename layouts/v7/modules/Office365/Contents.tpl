<style>
    .office365-sync-modal .modal-content {
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,.5);
    }
    .office365-sync-modal .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    .sync-status-container {
        padding: 20px;
        text-align: center;
    }
    .sync-icon-container {
        font-size: 48px;
        margin-bottom: 20px;
        color: #0078d4; /* Microsoft Blue */
    }
    .sync-loading-spinner {
        display: none;
        margin: 20px auto;
        width: 40px;
        height: 40px;
        border: 4px solid rgba(0, 120, 212, 0.1);
        border-left-color: #0078d4;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .btn-office365 {
        background-color: #0078d4;
        color: white;
        border: none;
        padding: 10px 25px;
        font-weight: 600;
        transition: background-color 0.2s;
    }
    .btn-office365:hover {
        background-color: #005a9e;
        color: white;
    }

    /* Full Page Settings Dashboard Layout */
    .office365-full-page-card {
        max-width: 800px;
        margin: 50px auto 100px auto !important;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }
    .office365-full-page-card .modal-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 20px 24px;
    }
    .office365-full-page-card .modal-body {
        padding: 40px 24px;
        background: #ffffff;
    }
    .office365-full-page-card .sync-status-container {
        padding: 0;
    }

    /* Center aligning container elements */
    .office365-full-page-card .sync-status-container hr {
        margin: 25px 0;
        border-color: #f1f5f9;
    }
</style>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h4 class="modal-title"><i class="fa fa-calendar-check-o"></i> {vtranslate('LBL_OFFICE365_SYNC', $MODULE_NAME)}</h4>
</div>
<div class="modal-body">
    <div id="office365SyncContents" class="sync-status-container">
        {if $FIRSTTIME}
            <div class="sync-icon-container">
                <i class="fa fa-refresh"></i>
            </div>
            <div class="alert alert-info">
                <strong>{vtranslate('LBL_READY_TO_SYNC', $MODULE_NAME)}</strong><br/>
                {vtranslate('LBL_LAST_SYNC_TIME', $MODULE_NAME)}: <span class="label label-default">{$SYNCTIME}</span><br/>
                {if $CONNECTED_EMAIL}
                    <div style="margin-top: 10px; margin-bottom: 12px;">
                        <small>{vtranslate('LBL_CONNECTED_AS', $MODULE_NAME)}:</small><br/>
                        <strong>{$CONNECTED_EMAIL}</strong><br/>
                        <button type="button" class="btn btn-danger btn-xs btn-office365-disconnect" data-sourcemodule="{$SOURCEMODULE}" style="margin-top: 8px;">
                            <i class="fa fa-power-off"></i> {vtranslate('LBL_DISCONNECT', $MODULE_NAME)}
                        </button>
                    </div>
                {/if}
            </div>
            <button type="button" class="btn btn-office365" id="startSyncBtn" data-sourcemodule="{$SOURCEMODULE}">
                <i class="fa fa-play"></i> {vtranslate('LBL_SYNC_NOW', $MODULE_NAME)}
            </button>
        {else}
            <div class="sync-icon-container">
                <i class="fa fa-cog fa-spin"></i>
            </div>
            <div class="alert alert-warning">
                {vtranslate('LBL_OFFICE365_SYNC_NOT_CONFIGURED', $MODULE_NAME)}
            </div>
            <p>{vtranslate('LBL_SYNC_EXPLANATION', $MODULE_NAME)}</p>
            <button type="button" class="btn btn-primary btn-office365-configure" data-sourcemodule="{$SOURCEMODULE}">
                <i class="fa fa-key"></i> {vtranslate('LBL_CONFIGURE', $MODULE_NAME)}
            </button>
        {/if}
        <div id="syncLoading" class="sync-loading-spinner"></div>
    </div>

    <script type="text/javascript">
        {literal}
        (function() {
            console.log("Office365 Contents Script Loading (v8.4)...");

            var startSync = function(sourcemodule) {
                console.log("Starting Office365 sync for " + sourcemodule);
                if (!sourcemodule) sourcemodule = 'Calendar';
                
                var msg = "Synchronization in progress...";
                var msg = "Synchronization in progress...";
                
                jq('#syncLoading').show();
                jq('#office365SyncContents .btn').hide();
                jq('#office365SyncContents .alert').html("<strong>" + msg + "</strong>").addClass('alert-info').removeClass('alert-warning alert-success alert-danger');
                
                if (typeof app !== 'undefined' && app.helper) {
                    app.helper.showProgress();
                }

                var url = "index.php?module=Vtiger&action=Office365Sync&operation=sync&sourcemodule=" + sourcemodule;
                console.log("Calling URL: " + url);
                
                AppConnector.request(url).then(function(data) {
                    console.log("Sync response received.");
                    if (typeof app !== 'undefined' && app.helper) {
                        app.helper.hideProgress();
                    }
                    // AppConnector returns {success: true, result: "html"}
                    var html = (typeof data === 'object' && data.result) ? data.result : data;
                    jq('#office365SyncContents').html(html);
                }).fail(function(err, status, error) {
                    console.error("Sync request failed. Status: " + status + ", Error: " + error);
                    console.log("Error object:", err);
                    if (typeof app !== 'undefined' && app.helper) {
                        app.helper.hideProgress();
                    }
                    jq('#syncLoading').hide();
                    jq('#office365SyncContents .btn').show();
                    var failMsg = "Synchronization failed.";
                    if (typeof app !== 'undefined' && app.vtranslate) {
                        failMsg = app.vtranslate('LBL_SYNC_FAILED');
                    }
                    if (typeof app !== 'undefined' && app.helper) {
                        app.helper.showAlertNotification({'message' : failMsg});
                    } else {
                        alert(failMsg);
                    }
                });
            };

            var configureSync = function(sourcemodule) {
                console.log("configureOffice365Sync called");
                if (!sourcemodule) sourcemodule = 'Calendar';
                var url = "index.php?module=Office365&view=List&operation=authorize&sourcemodule=" + sourcemodule;
                var authWindow = window.open(url, 'Office365Auth', 'width=600,height=600');
                
                var timer = setInterval(function() { 
                    if(!authWindow || authWindow.closed) {
                        clearInterval(timer);
                        afterRedirect(sourcemodule);
                    }
                }, 1000);
            };

            var applyFullPageLayout = function() {
                var contents = jq('#office365SyncContents');
                var modalContainer = contents.closest('.modal');
                if (modalContainer.length === 0) {
                    var header = contents.closest('.modal-body').prev('.modal-header');
                    var body = contents.closest('.modal-body');
                    if (header.length > 0 && body.length > 0 && !body.parent().hasClass('office365-full-page-card')) {
                        var wrapper = jq('<div class="office365-full-page-card"></div>');
                        header.before(wrapper);
                        wrapper.append(header).append(body);
                    }

                    var closeBtn = jq('.modal-header .close');
                    if (closeBtn.length > 0 && closeBtn.find('.fa-arrow-left').length === 0) {
                        closeBtn.html('<i class="fa fa-arrow-left"></i> Back to Calendar').css({
                            'font-size': '13px',
                            'opacity': '0.9',
                            'margin-top': '2px',
                            'background-color': '#f1f5f9',
                            'color': '#475569',
                            'padding': '6px 12px',
                            'border-radius': '6px',
                            'font-weight': '600',
                            'border': '1px solid #cbd5e1',
                            'transition': 'all 0.2s',
                            'cursor': 'pointer'
                        }).hover(
                            function() { jq(this).css({'background-color': '#e2e8f0', 'color': '#1e293b'}); },
                            function() { jq(this).css({'background-color': '#f1f5f9', 'color': '#475569'}); }
                        );
                    }
                }
            };

            var afterRedirect = function(sourcemodule) {
                console.log("afterRedirect called");
                var url = "index.php?module=Office365&view=List&sourcemodule=" + sourcemodule;
                AppConnector.request(url).then(function(data) {
                    var parsed = jq('<div>' + data + '</div>');
                    var contents = parsed.find('#office365SyncContents');
                    if (contents.length > 0) {
                        jq('#office365SyncContents').html(contents.html());
                    } else {
                        var body = parsed.find('.modal-body');
                        if (body.length > 0) {
                            jq('#office365SyncContents').html(body.html());
                        } else {
                            jq('#office365SyncContents').html(data);
                        }
                    }
                    applyFullPageLayout();
                });
            };

            // Event Binding diagnostics
            var jq = (typeof jQuery !== 'undefined') ? jQuery : (typeof $ !== 'undefined' ? $ : null);
            if (!jq && window.parent && typeof window.parent.jQuery !== 'undefined') {
                jq = window.parent.jQuery;
            }

            if (!jq) {
                // Try to find ANY jQuery
                for (var key in window) {
                    if (window[key] && typeof window[key].fn && window[key].fn.jquery) {
                        jq = window[key];
                        break;
                    }
                }
            }

            if (!jq) {
                console.error("Office365 Sync: jQuery not found.");
                return;
            }

            applyFullPageLayout();

            jq(document).off('click', '#startSyncBtn').on('click', '#startSyncBtn', function(e) {
                if (e.originalEvent && e.originalEvent.office365Handled) {
                    return;
                }
                if (e.originalEvent) {
                    e.originalEvent.office365Handled = true;
                }
                var sourcemodule = jq(this).data('sourcemodule');
                startSync(sourcemodule);
            });

            jq(document).off('click', '.btn-office365-configure').on('click', '.btn-office365-configure', function(e) {
                if (e.originalEvent && e.originalEvent.office365Handled) {
                    return;
                }
                if (e.originalEvent) {
                    e.originalEvent.office365Handled = true;
                }
                var sourcemodule = jq(this).data('sourcemodule');
                configureSync(sourcemodule);
            });

            jq(document).off('click', '.btn-office365-disconnect').on('click', '.btn-office365-disconnect', function(e) {
                if (e.originalEvent && e.originalEvent.office365Handled) {
                    return;
                }
                if (e.originalEvent) {
                    e.originalEvent.office365Handled = true;
                }
                
                var btnDisconnect = jq(this);
                var sourcemodule = btnDisconnect.data('sourcemodule');
                console.log("Disconnect clicked for sourcemodule: " + sourcemodule);
                
                var message = "Are you sure you want to disconnect your Office365 account?";
                
                var performDisconnect = function() {
                    btnDisconnect.prop('disabled', true).html("<i class='fa fa-spinner fa-spin'></i> Disconnecting...");
                    if (typeof app !== 'undefined' && app.helper) {
                        app.helper.showProgress();
                    }
                    
                    var url = "index.php?module=Vtiger&action=Office365Sync&operation=disconnect&sourcemodule=" + sourcemodule;
                    AppConnector.request(url).then(function(data) {
                        console.log("Disconnect successful.");
                        try {
                            if (typeof app !== 'undefined' && app.helper) {
                                app.helper.hideProgress();
                                app.helper.showSuccessNotification({'message': 'Office365 account successfully disconnected!'});
                            }
                        } catch (e) {
                            console.error("Notification failed", e);
                        }
                        
                        // Cleanly reload the page to render the fresh disconnected state!
                        try {
                            var modalContainer = btnDisconnect.closest('.modal');
                            if (modalContainer.length > 0 && typeof app !== 'undefined' && app.hideModalWindow) {
                                app.hideModalWindow();
                            }
                        } catch (e) {
                            console.error("Modal close failed", e);
                        }
                        
                        window.location.reload();
                    }).fail(function(err) {
                        console.error("Disconnect failed", err);
                        if (typeof app !== 'undefined' && app.helper) {
                            app.helper.hideProgress();
                        }
                        btnDisconnect.prop('disabled', false).html("<i class='fa fa-power-off'></i> Disconnect Account");
                        alert("Failed to disconnect account. Please try again.");
                    });
                };

                if (typeof app !== 'undefined' && app.helper && app.helper.showConfirmationBox) {
                    app.helper.showConfirmationBox({'message': message}).then(function() {
                        performDisconnect();
                    }, function() {
                        // Cancelled, do nothing
                    });
                } else {
                    if (confirm(message)) {
                        performDisconnect();
                    }
                }
            });

            jq(document).off('click.office365-close').on('click.office365-close', '.modal-header .close, #closeSyncBtn', function(e) {
                if (e.originalEvent && e.originalEvent.office365Handled) {
                    return;
                }
                if (e.originalEvent) {
                    e.originalEvent.office365Handled = true;
                }
                console.log("Close button clicked");
                // Check if we are in a modal div
                var modalContainer = jq(this).closest('.modal');
                if (modalContainer.length > 0 && typeof app !== 'undefined' && app.hideModalWindow) {
                    app.hideModalWindow();
                } else {
                    // Fallback: Redirect back to Calendar
                    window.location.href = "index.php?module=Calendar&view=List";
                }
            });
        })();
        {/literal}
    </script>
</div>









