{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{assign var="TITLE" value={vtranslate($TYPE, $MODULE, vtranslate($MODULE, $MODULE))}}

<style>
    #detailviewhtml .fc-overlay-modal {
        height: 100vh;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.1);
        border: none;
        border-left: 1px solid #e2e8f0;
    }
    #detailviewhtml .overlayHeader {
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 24px;
    }
    #detailviewhtml .modal-header {
        border: none;
        padding: 0;
        background: transparent;
    }
    #detailviewhtml .modal-header h4 {
        font-weight: 700;
        color: #1e293b;
        font-size: 18px;
        margin: 0;
        line-height: 1.4;
    }
    #detailviewhtml .modal-header .close {
        color: #64748b;
        opacity: 0.8;
        font-size: 20px;
        transition: color 0.2s;
        margin-top: -2px;
    }
    #detailviewhtml .modal-header .close:hover {
        color: #0f172a;
    }
    #detailviewhtml .modal-body {
        padding: 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #f8fafc;
    }
    #detailviewhtml .action-row {
        margin-bottom: 16px;
        display: flex;
        justify-content: flex-end;
    }
    #detailviewhtml #downloadCsv {
        background-color: #0078d4;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        font-weight: 600;
        font-size: 13px;
        transition: background-color 0.2s, transform 0.1s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    #detailviewhtml #downloadCsv:hover {
        background-color: #005a9e;
        color: #ffffff;
        text-decoration: none;
    }
    #detailviewhtml #downloadCsv:active {
        transform: scale(0.98);
    }
    #detailviewhtml .datacontent {
        flex: 1;
        max-height: calc(100vh - 180px) !important;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    #detailviewhtml .datacontent::-webkit-scrollbar {
        width: 6px;
    }
    #detailviewhtml .datacontent::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 8px;
    }
    #detailviewhtml .datacontent::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
    }
    #detailviewhtml .datacontent::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    #detailviewhtml .table-responsive {
        margin: 0;
        border: none;
    }
    #detailviewhtml .table-log-detail {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
        font-size: 13px;
    }
    #detailviewhtml .table-log-detail th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e2e8f0;
        padding: 12px 16px;
        text-align: left;
    }
    #detailviewhtml .table-log-detail td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }
    #detailviewhtml .table-log-detail tr:last-child td {
        border-bottom: none;
    }
    #detailviewhtml .table-log-detail tr:hover td {
        background-color: #f8fafc;
    }
    #detailviewhtml .extensionLink {
        color: #0078d4;
        font-weight: 600;
        text-decoration: underline;
        transition: color 0.15s;
    }
    #detailviewhtml .extensionLink:hover {
        color: #005a9e;
        text-decoration: underline;
    }
    #detailviewhtml .badge-module {
        background-color: #e2e8f0;
        color: #334155;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 11px;
        display: inline-block;
    }
    #detailviewhtml .text-reason {
        color: #e11d48;
        font-family: monospace;
        font-size: 12px;
        background-color: #fff1f2;
        padding: 4px 8px;
        border-radius: 4px;
        display: inline-block;
    }
</style>

<div id="detailviewhtml">
    <div class='fc-overlay-modal modal-content'>
        <div class='overlayHeader'>
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE}
        </div>

        <div class='modal-body'>
            <div class="action-row">
                <a id="downloadCsv" href="index.php?module={$SOURCE_MODULE}&view=ExportExtensionLog&logid={$LOG_ID}&type={$TYPE}" type="button" class="btn addButton btn-default downloadCsv">
                    <span class="fa fa-download" aria-hidden="true"></span> {vtranslate('LBL_DOWNLOAD_AS_CSV', $MODULE)}
                </a>
            </div>
            
            <div class='datacontent'>
                <div class="table-responsive">
                    <table class="table-log-detail">
                        <thead>
                            <tr>
                                <th> {vtranslate('LBL_SOURCE_MODULE' ,$MODULE)} </th>
                                <th> {vtranslate('LBL_RECORD_NAME' ,$MODULE)} </th>
                                {if $TYPE eq 'vt_skip' or $TYPE eq 'app_skip'}
                                    <th class="remove"> {vtranslate('LBL_REASON' ,$MODULE)} </th>
                                {/if}
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$DATA item=LOG}
                                {if $TYPE neq 'vt_delete' and $TYPE neq 'app_delete'}
                                    {assign var=RECORD_LINK value=$LOG['link']}
                                {/if}
                                <tr>
                                    <td>
                                        <span class="badge-module">{$LOG['module']}</span>
                                    </td>
                                    <td>
                                        {if !empty($RECORD_LINK)}
                                            <a class="extensionLink" href="{$RECORD_LINK}" target="_blank">{$LOG['name']}</a>
                                        {else}
                                            {$LOG['name']}
                                        {/if}
                                    </td>
                                    {if $LOG['error']}
                                        <td>
                                            <span class="text-reason">{$LOG['error']}</span>
                                        </td>
                                    {/if}
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>