<?php
/*+**********************************************************************************
 * Office365 Redirect Bridge
 ************************************************************************************/
?>
<script type="text/javascript">
    try {
        if (window.opener && window.opener.afterRedirect) {
            window.opener.afterRedirect();
        }
    } catch (e) {
        console.error("Error calling afterRedirect:", e);
    } finally {
        window.close();
    }
</script>
<?php
exit;

