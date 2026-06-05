<?php
	$id_input = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : '');
	$authfor_input = isset($_GET['authfor']) ? $_GET['authfor'] : (isset($_POST['authfor']) ? $_POST['authfor'] : '');
	
	$id = (int)$id_input;
	$allowed_authfor = array('MailConverter', 'OutgoingServer', 'MailManager', 'Calendar');
	$authfor = in_array($authfor_input, $allowed_authfor) ? $authfor_input : '';
?>
<script>
try {
	if(window.opener && window.opener.afterRedirect) {
		window.opener.afterRedirect(<?php echo htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8'); ?>);
		window.close();
	} else {
		var authfor = "<?php echo htmlspecialchars($authfor, ENT_QUOTES, 'UTF-8'); ?>";
		var id = <?php echo htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8'); ?>;
		var redirectUrl = "../index.php";
		if (authfor === "MailConverter") {
			if (id) {
				redirectUrl = "../index.php?module=MailConverter&parent=Settings&view=Edit&mode=step2&create=new&record=" + id;
			} else {
				redirectUrl = "../index.php?module=MailConverter&parent=Settings&view=List";
			}
		} else if (authfor === "OutgoingServer") {
			redirectUrl = "../index.php?module=Vtiger&parent=Settings&view=OutgoingServerDetail";
		} else if (authfor === "MailManager") {
			redirectUrl = "../index.php?module=MailManager&view=List";
		} else if (authfor === "Calendar") {
			redirectUrl = "../index.php?module=Calendar&view=Calendar";
		}
		window.location.href = redirectUrl;
	}
} catch(e) {
	console.error(e);
	window.location.href = "../index.php";
}
</script>
<?php
	exit;
?>