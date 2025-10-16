<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Security check
//$langs->load("mymodule@mymodule");

if (!empty($_FILES['jsonfile']['tmp_name'])) {
	$content = file_get_contents($_FILES['jsonfile']['tmp_name']);
	$data = json_decode($content, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		setEventMessages("Fichier JSON invalide : " . json_last_error_msg(), null, 'errors');
	} else {
		// Exemple de traitement : insertion en base
		$db->begin();

		try {
			foreach ($data as $item) {
				// Exemple : insertion dans une table de ton module
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."mymodule_data(label, value)
                        VALUES ('".$db->escape($item['label'])."', '".$db->escape($item['value'])."')";
				$resql = $db->query($sql);
				if (! $resql) throw new Exception($db->lasterror());
			}

			$db->commit();
			setEventMessages("Import JSON effectué avec succès (" . count($data) . " lignes)", null, 'mesgs');

		} catch (Exception $e) {
			$db->rollback();
			setEventMessages("Erreur lors de l'import : " . $e->getMessage(), null, 'errors');
		}
	}
}

?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
	<?php echo newToken(); ?>

	<div class="center">
		<input type="file" name="jsonfile" accept="application/json" required>
		<br><br>
		<input type="submit" class="button" value="Importer">
	</div>
</form>
