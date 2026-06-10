Vtiger_Popup_Js("Office365_Popup_Js", {}, {
    
    registerEvents: function() {
        this._super();
        this.registerSyncEvent();
        console.log("Office365_Popup_Js events registered (v8.4)");
    },
    
    registerSyncEvent: function() {
        var self = this;
        jQuery(document).off('click.office365-sync').on('click.office365-sync', '#startSyncBtn', function() {
            var sourcemodule = jQuery(this).data('sourcemodule');
            self.startSync(sourcemodule);
        });
        
        jQuery(document).off('click.office365-configure').on('click.office365-configure', '.btn-office365-configure', function() {
            var sourcemodule = jQuery(this).data('sourcemodule');
            self.configureSync(sourcemodule);
        });
    },
    
    startSync: function(sourcemodule) {
        if (!sourcemodule) sourcemodule = 'Calendar';
        console.log("Starting Office365 sync for " + sourcemodule);
        
        var msg = "Synchronization in progress...";
        if (typeof app !== 'undefined' && app.vtranslate) {
            msg = app.vtranslate('LBL_SYNC_IN_PROGRESS');
        }
        
        jQuery('#syncLoading').show();
        jQuery('#office365SyncContents .btn').hide();
        jQuery('#office365SyncContents .alert').html("<strong>" + msg + "</strong>").addClass('alert-info').removeClass('alert-warning alert-success alert-danger');
        
        if (typeof app !== 'undefined' && app.helper) {
            app.helper.showProgress();
        }

        var url = "index.php?module=Office365&view=List&operation=sync&sourcemodule=" + sourcemodule;
        AppConnector.request(url).then(function(data) {
            console.log("Sync response received");
            if (typeof app !== 'undefined' && app.helper) {
                app.helper.hideProgress();
            }
            jQuery('#office365SyncContents').html(data);
        }).fail(function(err) {
            console.error("Sync request failed", err);
            if (typeof app !== 'undefined' && app.helper) {
                app.helper.hideProgress();
            }
            jQuery('#syncLoading').hide();
            jQuery('#office365SyncContents .btn').show();
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
    },
    
    configureSync: function(sourcemodule) {
        if (!sourcemodule) sourcemodule = 'Calendar';
        var url = "index.php?module=Office365&view=List&operation=authorize&sourcemodule=" + sourcemodule;
        var authWindow = window.open(url, 'Office365Auth', 'width=600,height=600');
        
        var self = this;
        var timer = setInterval(function() { 
            if(!authWindow || authWindow.closed) {
                clearInterval(timer);
                self.afterRedirect(sourcemodule);
            }
        }, 1000);
    },
    
    afterRedirect: function(sourcemodule) {
        var url = "index.php?module=Office365&view=List&sourcemodule=" + sourcemodule;
        AppConnector.request(url).then(function(data) {
            var body = jQuery(data).find('.modal-body');
            if (body.length > 0) {
                jQuery('#office365SyncContents').html(body.html());
            } else {
                jQuery('#office365SyncContents').html(data);
            }
        });
    }
});
