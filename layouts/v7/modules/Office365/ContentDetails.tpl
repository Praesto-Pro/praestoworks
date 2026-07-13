<div class="sync-results-container">
    <div class="alert alert-success">
        <h4><i class="fa fa-check-circle"></i> {vtranslate('LBL_SYNC_COMPLETED', $MODULE_NAME)}</h4>
        <p>{vtranslate('LBL_LAST_SYNC_TIME', $MODULE_NAME)}: <span class="label label-success">{$SYNCTIME}</span></p>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Vtiger CRM</h3>
                </div>
                <div class="panel-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {if $RECORDS.vtiger.create > 0 && $LOG_ID}
                                <a class="syncLogDetail extensionLink" data-type="vt_create" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: underline; font-weight: bold; color: #0078d4;">{vtranslate('LBL_CREATED', $MODULE_NAME)}</a>
                                <a class="syncLogDetail badge badge-primary badge-pill" data-type="vt_create" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: none !important; color: #ffffff !important;">{$RECORDS.vtiger.create}</a>
                            {else}
                                {vtranslate('LBL_CREATED', $MODULE_NAME)}
                                <span class="badge badge-primary badge-pill">{$RECORDS.vtiger.create}</span>
                            {/if}
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {if $RECORDS.vtiger.update > 0 && $LOG_ID}
                                <a class="syncLogDetail extensionLink" data-type="vt_update" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: underline; font-weight: bold; color: #0078d4;">{vtranslate('LBL_UPDATED', $MODULE_NAME)}</a>
                                <a class="syncLogDetail badge badge-primary badge-pill" data-type="vt_update" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: none !important; color: #ffffff !important;">{$RECORDS.vtiger.update}</a>
                            {else}
                                {vtranslate('LBL_UPDATED', $MODULE_NAME)}
                                <span class="badge badge-primary badge-pill">{$RECORDS.vtiger.update}</span>
                            {/if}
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {if $RECORDS.vtiger.delete > 0 && $LOG_ID}
                                <a class="syncLogDetail extensionLink" data-type="vt_delete" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: underline; font-weight: bold; color: #d9534f;">{vtranslate('LBL_DELETED', $MODULE_NAME)}</a>
                                <a class="syncLogDetail badge badge-danger badge-pill" data-type="vt_delete" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: none !important; color: #ffffff !important;">{$RECORDS.vtiger.delete}</a>
                            {else}
                                {vtranslate('LBL_DELETED', $MODULE_NAME)}
                                <span class="badge badge-danger badge-pill">{$RECORDS.vtiger.delete}</span>
                            {/if}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Office365</h3>
                </div>
                <div class="panel-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {if $RECORDS.office365.create > 0 && $LOG_ID}
                                <a class="syncLogDetail extensionLink" data-type="app_create" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: underline; font-weight: bold; color: #0078d4;">{vtranslate('LBL_CREATED', $MODULE_NAME)}</a>
                                <a class="syncLogDetail badge badge-primary badge-pill" data-type="app_create" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: none !important; color: #ffffff !important;">{$RECORDS.office365.create}</a>
                            {else}
                                {vtranslate('LBL_CREATED', $MODULE_NAME)}
                                <span class="badge badge-primary badge-pill">{$RECORDS.office365.create}</span>
                            {/if}
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {if $RECORDS.office365.update > 0 && $LOG_ID}
                                <a class="syncLogDetail extensionLink" data-type="app_update" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: underline; font-weight: bold; color: #0078d4;">{vtranslate('LBL_UPDATED', $MODULE_NAME)}</a>
                                <a class="syncLogDetail badge badge-primary badge-pill" data-type="app_update" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: none !important; color: #ffffff !important;">{$RECORDS.office365.update}</a>
                            {else}
                                {vtranslate('LBL_UPDATED', $MODULE_NAME)}
                                <span class="badge badge-primary badge-pill">{$RECORDS.office365.update}</span>
                            {/if}
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {if $RECORDS.office365.delete > 0 && $LOG_ID}
                                <a class="syncLogDetail extensionLink" data-type="app_delete" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: underline; font-weight: bold; color: #d9534f;">{vtranslate('LBL_DELETED', $MODULE_NAME)}</a>
                                <a class="syncLogDetail badge badge-danger badge-pill" data-type="app_delete" data-id="{$LOG_ID}" style="cursor: pointer; text-decoration: none !important; color: #ffffff !important;">{$RECORDS.office365.delete}</a>
                            {else}
                                {vtranslate('LBL_DELETED', $MODULE_NAME)}
                                <span class="badge badge-danger badge-pill">{$RECORDS.office365.delete}</span>
                            {/if}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center" style="margin-top: 20px;">
        <button class="btn btn-default" id="closeSyncBtn">
            <i class="fa fa-close"></i> {vtranslate('LBL_CLOSE', $MODULE_NAME)}
        </button>
    </div>
</div>

<style>
    .sync-results-container .panel-heading {
        font-weight: bold;
    }
    .sync-results-container .list-group-item {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }
    .sync-results-container .badge {
        float: none !important;
        display: inline-block !important;
    }
</style>

