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
                            {vtranslate('LBL_CREATED', $MODULE_NAME)}
                            <span class="badge badge-primary badge-pill">{$RECORDS.vtiger.create}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {vtranslate('LBL_UPDATED', $MODULE_NAME)}
                            <span class="badge badge-primary badge-pill">{$RECORDS.vtiger.update}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {vtranslate('LBL_DELETED', $MODULE_NAME)}
                            <span class="badge badge-danger badge-pill">{$RECORDS.vtiger.delete}</span>
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
                            {vtranslate('LBL_CREATED', $MODULE_NAME)}
                            <span class="badge badge-primary badge-pill">{$RECORDS.office365.create}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {vtranslate('LBL_UPDATED', $MODULE_NAME)}
                            <span class="badge badge-primary badge-pill">{$RECORDS.office365.update}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {vtranslate('LBL_DELETED', $MODULE_NAME)}
                            <span class="badge badge-danger badge-pill">{$RECORDS.office365.delete}</span>
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
    .badge-pill {
        float: right;
    }
</style>

