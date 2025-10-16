<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       typology_card.php
 *		\ingroup    dolitypology
 *		\brief      Page to create/edit/view typology
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/dolitypology/class/typology.class.php');
dol_include_once('/dolitypology/class/typologyextrafieldlink.class.php');
dol_include_once('/dolitypology/lib/dolitypology_typology.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("dolitypology@dolitypology", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'typologycard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)

// Initialize technical objects
$object = new Typology($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->dolitypology->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('typologycard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
//$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


$permissiontoread = $user->rights->dolitypology->typology->read;
$permissiontoadd = $user->rights->dolitypology->typology->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->dolitypology->typology->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->dolitypology->typology->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->dolitypology->typology->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->dolitypology->multidir_output[isset($object->entity) ? $object->entity : 1].'/typology';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->dolitypology->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/dolitypology/typology_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/dolitypology/typology_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'DOLITYPOLOGY_TYPOLOGY_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ($massaction == 'add_typology') {
		$objecttmp = new TypologyExtrafieldLink($db);

		foreach ($toselect as $toselectid) {
			$extraId = fetch_extrafields($toselectid, $object->element_type);
			$objecttmp->fk_typology   = $object->id;
			$objecttmp->fk_extrafield = $extraId[0]->rowid;
			$objecttmp->create($user);
		}

		setEventMessages(null, $object->errors, 'errors');
		header("Location: " . $backtopage);
		exit;
	}

	if ($massaction == 'unlink_typology') {
		$objecttmp = new TypologyExtrafieldLink($db);

		foreach ($toselect as $toselectid) {
			$extraId = fetch_extrafields($toselectid, $object->element_type);
			$objecttmp->fetch(0, 0, ' AND fk_typology = ' . $object->id  . ' AND fk_extrafield = ' . $extraId[0]->rowid);
			$objecttmp->delete($user);
		}

		setEventMessages(null, $object->errors, 'errors');
		header("Location: " . $backtopage);
		exit;
	}
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Typology");
$help_url = '';
llxHeader('', $title, $help_url);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Typology")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Typology"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = typologyPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Workstation"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteTypology'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/dolitypology/typology_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->projet->enabled)) {
	 $langs->load("projects");
	 $morehtmlref .= '<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd) {
	 //if ($action != 'classify') $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> ';
	 $morehtmlref .= ' : ';
	 if ($action == 'classify') {
	 //$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref .= '<input type="hidden" name="action" value="classin">';
	 $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref .= '</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref .= ': '.$proj->getNomUrl();
	 } else {
	 $morehtmlref .= '';
	 }
	 }
	 }*/
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'rowid', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end(); ?>

	<?php
	print '<div class="tabsAction">';
	print '<a class="butAction" href="#" id="openImportDialog">Importer un fichier JSON</a>';
	print '</div>';

	?>

	<script type="text/javascript">
		$(document).ready(function() {
			$('#openImportDialog').on('click', function(e) {
				e.preventDefault();
				dol("Importer un fichier JSON", "<?php echo dol_buildpath('/dolitypology/import_json_dialog.php', 1); ?>");
			});
		});
	</script>

	<?php $arrayofselected = is_array($toselect) ? $toselect : array();

	// List of mass actions available
	$arrayofmassactions['add_typology'] = $langs->trans("Ajouter à la typologie");
	$arrayofmassactions['unlink_typology'] = $langs->trans("Supprimer à la typologie");
	if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"]. '?id=' . $object->id . '">'."\n";

	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print '<div class="centpercent tagtable marginbottomonly">';
	print '<div class="tagtr">';
	print '<div class="tagtd inline-block valignmiddle hideonsmartphoneimp">'.$title.'</div>';
	print '<div class="tagtd right inline-block valignmiddle"">'.$newcardbutton.'</div>';
	print '</div>';
	print '</div>';

	//if ($action != 'create' && $action != 'edit') {
	$newcardbutton = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewAttribute'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=create', '', 1);
	/*} else {
		$newcardbutton = '';
	}*/

	print_barre_liste('Liste des extrafields', 0, $_SERVER["PHP_SELF"], '', '', '', $massactionbutton, -1, '', '', 0, $newcardbutton, '', -1, 0, 0, 1);

	$title = '<span class="opacitymedium">'.$langs->trans("DefineHereComplementaryAttributes", empty($textobject) ? '' : $textobject).'</span><br>'."\n";


// Load $extrafields->attributes
	$elementtype = $object->element_type;
	$extrafields->fetch_name_optionals_label($elementtype);

	print '<div class="div-table-responsive">';
	print '<table summary="listofattributes" class="noborder centpercent small">';

	print '<tr class="liste_titre">';
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td width="80">&nbsp;</td>';
	}
	print '<td class="left">'.$langs->trans("Position");
	print '<span class="nowrap">';
	print img_picto('A-Z', '1downarrow.png');
	print '</span>';
	print '</td>';
	print '<td>'.$langs->trans("LabelOrTranslationKey").'</td>';
//print '<td>'.$langs->trans("TranslationString").'</td>';
	print '<td>'.$langs->trans("AttributeCode").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td class="right">'.$langs->trans("Size").'</td>';
	print '<td>'.$langs->trans("ComputedFormula").'</td>';
	print '<td class="center">'.$langs->trans("Unique").'</td>';
	print '<td class="center">'.$langs->trans("Mandatory").'</td>';
	print '<td class="center">'.$form->textwithpicto($langs->trans("AlwaysEditable"), $langs->trans("EditableWhenDraftOnly")).'</td>';
	print '<td class="center">'.$form->textwithpicto($langs->trans("EmptyOnClone"), $langs->trans("EmptyOnCloneDesc")).'</td>';
	print '<td class="center">'.$form->textwithpicto($langs->trans("Visibility"), $langs->trans("VisibleDesc").'<br><br>'.$langs->trans("ItCanBeAnExpression")).'</td>';
	print '<td class="center">'.$form->textwithpicto($langs->trans("DisplayOnPdf"), $langs->trans("DisplayOnPdfDesc")).'</td>';
	print '<td class="center">'.$form->textwithpicto($langs->trans("Totalizable"), $langs->trans("TotalizableDesc")).'</td>';
	print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnEdit"), $langs->trans("HelpCssOnEditDesc")).'</td>';
	print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnView"), $langs->trans("HelpCssOnViewDesc")).'</td>';
	print '<td class="center">'.$form->textwithpicto($langs->trans("CssOnList"), $langs->trans("HelpCssOnListDesc")).'</td>';
	if (isModEnabled('multicompany')) {
		print '<td class="center">'.$langs->trans("Entity").'</td>';
	}
// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td width="80">&nbsp;</td>';
	}
	print "</tr>\n";

	if (isset($extrafields->attributes[$elementtype]['type']) && is_array($extrafields->attributes[$elementtype]['type']) && count($extrafields->attributes[$elementtype]['type'])) {
		foreach ($extrafields->attributes[$elementtype]['type'] as $key => $value) {
			/*if (! (int) dol_eval($extrafields->attributes[$elementtype]['enabled'][$key], 1, 1, '1')) {
				// TODO Uncomment this to exclude extrafields of modules not enabled. Add a link to "Show extrafields disabled"
				// continue;
			}*/

			// Load language if required
			if (!empty($extrafields->attributes[$elementtype]['langfile'][$key])) {
				$langs->load($extrafields->attributes[$elementtype]['langfile'][$key]);
			}

			$extrafieldlink = new TypologyExtrafieldLink($db);

			$tmpextra = fetch_extrafields($key, $elementtype);
			$extrafieldlink->fetch(0, 0, ' AND fk_extrafield = ' . $tmpextra[0]->rowid . ' AND fk_typology = ' . $object->id);

			print '<tr class="oddeven"' . ($extrafieldlink->id > 0 ? ' style="background: #0a53be;"' : '') . '>';
			// Actions
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="center nowraponall">';
				//print '<input type="checkbox" name="toselect[]" class="flat" value="'.dol_escape_htmltag($key).'">';
				if (1) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					$selected = 0;
					if (in_array($key, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$key.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$key.'"'.($selected ? ' checked="checked"' : '').'>';
				}
				print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&attrname='.urlencode($key).'#formeditextrafield">'.img_edit().'</a>';
				print '&nbsp; <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&attrname='.urlencode($key).'">'.img_delete().'</a>';
				if ($extrafields->attributes[$elementtype]['type'][$key] == 'password' && !empty($extrafields->attributes[$elementtype]['param'][$key]['options']) && array_key_exists('dolcrypt', $extrafields->attributes[$elementtype]['param'][$key]['options'])) {
					print '&nbsp; <a class="aaa" href="'.$_SERVER["PHP_SELF"].'?action=encrypt&token='.newToken().'&attrname='.urlencode($key).'" title="'.dol_escape_htmltag($langs->trans("ReEncryptDesc")).'">'.img_picto('', 'refresh').'</a>';
				}
				print '</td>'."\n";
			}
			// Position
			print "<td>".dol_escape_htmltag((string) $extrafields->attributes[$elementtype]['pos'][$key])."</td>\n";
			// Label and label translated
			print '<td title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['label'][$key]).'" class="tdoverflowmax150 subtitle">';
			print dol_escape_htmltag($extrafields->attributes[$elementtype]['label'][$key]);
			if ($langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key]) != $extrafields->attributes[$elementtype]['label'][$key]) {
				print '<br><span class="subtitle small opacitymedium inline-block" title="'.dolPrintHTMLForAttribute($langs->trans("LabelTranslatedInCurrentLanguage")).'">';
				print $langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key]);
			}
			print '</span>';
			print "</td>\n"; // We don't translate here, we want admin to know what is the key not translated value
			// Label translated
			//print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key])).'">'.dol_escape_htmltag($langs->transnoentitiesnoconv($extrafields->attributes[$elementtype]['label'][$key]))."</td>\n";
			// Key
			print '<td title="'.dol_escape_htmltag($key).'" class="tdoverflowmax100">'.dol_escape_htmltag($key)."</td>\n";
			// Type
			$typetoshow = $type2label[$extrafields->attributes[$elementtype]['type'][$key]];
			print '<td title="'.dol_escape_htmltag($typetoshow).'" class="tdoverflowmax100">';
			print getPictoForType($extrafields->attributes[$elementtype]['type'][$key]);
			print dol_escape_htmltag($typetoshow);
			print "</td>\n";
			// Size
			print '<td class="right">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['size'][$key])."</td>\n";
			// Computed field
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['computed'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['computed'][$key])."</td>\n";
			// Is unique ?
			print '<td class="center">'.yn($extrafields->attributes[$elementtype]['unique'][$key])."</td>\n";
			// Is mandatory ?
			print '<td class="center">'.yn($extrafields->attributes[$elementtype]['required'][$key])."</td>\n";
			// Can always be editable ?
			print '<td class="center">'.yn($extrafields->attributes[$elementtype]['alwayseditable'][$key])."</td>\n";
			// Will be emptied on clone ?
			print '<td class="center">'.yn($extrafields->attributes[$elementtype]['emptyonclone'][$key])."</td>\n";
			// Visible
			print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['list'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['list'][$key])."</td>\n";
			// Print on PDF
			print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag((string) $extrafields->attributes[$elementtype]['printable'][$key]).'">'.dol_escape_htmltag((string) $extrafields->attributes[$elementtype]['printable'][$key])."</td>\n";
			// Summable
			print '<td class="center">'.yn($extrafields->attributes[$elementtype]['totalizable'][$key])."</td>\n";
			// CSS
			print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['css'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['css'][$key])."</td>\n";
			// CSS view
			print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['cssview'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['cssview'][$key])."</td>\n";
			// CSS list
			print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($extrafields->attributes[$elementtype]['csslist'][$key]).'">'.dol_escape_htmltag($extrafields->attributes[$elementtype]['csslist'][$key])."</td>\n";
			// Multicompany
			if (isModEnabled('multicompany')) {
				print '<td class="center tdoverflowmax100">';
				if (empty($extrafields->attributes[$elementtype]['entityid'][$key])) {
					print $langs->trans("All");
				} else {
					global $multicompanylabel_cache;
					if (!is_array($multicompanylabel_cache)) {
						$multicompanylabel_cache = array();
					}
					if (empty($multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]])) {
						global $mc;
						if (is_object($mc) && method_exists($mc, 'getInfo')) {
							$mc->getInfo($extrafields->attributes[$elementtype]['entityid'][$key]);
							$multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]] = $mc->label ? $mc->label : $extrafields->attributes[$elementtype]['entityid'][$key];
						}
					}
					print $multicompanylabel_cache[$extrafields->attributes[$elementtype]['entityid'][$key]];
				}
				print '</td>';
			}
			// Actions
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="right nowraponall">';
				print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&attrname='.urlencode($key).'#formeditextrafield">'.img_edit().'</a>';
				print '&nbsp; <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&attrname='.urlencode($key).'">'.img_delete().'</a>';
				if ($extrafields->attributes[$elementtype]['type'][$key] == 'password' && !empty($extrafields->attributes[$elementtype]['param'][$key]['options']) && array_key_exists('dolcrypt', $extrafields->attributes[$elementtype]['param'][$key]['options'])) {
					print '&nbsp; <a class="aaa" href="'.$_SERVER["PHP_SELF"].'?action=encrypt&token='.newToken().'&attrname='.urlencode($key).'" title="'.dol_escape_htmltag($langs->trans("ReEncryptDesc")).'">'.img_picto('', 'refresh').'</a>';
				}
				print '</td>'."\n";
			}
			print "</tr>";
		}
	} else {
		$colspan = 17;
		if (isModEnabled('multicompany')) {
			$colspan++;
		}

		print '<tr class="oddeven">';
		print '<td colspan="'.$colspan.'"><span class="opacitymedium">';
		print $langs->trans("None");
		print '</span></td>';
		print '</tr>';
	}

	print "</table>";
	print '</div>';
	print '</form>';


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				if (empty($reshook))
					$object->formAddObjectLine(1, $mysoc, $soc);
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&token='.newToken().'#formmailbeforetitle');
			}

			// Back to draft
			if ($object->status == $object::STATUS_ENABLED) {
				print dolGetButtonAction($langs->trans('SetToActif'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			if ($object->status == $object::STATUS_DISABLED) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Clone
			print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&token='.newToken(), '', $permissiontoadd);

			/*
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction($langs->trans('Disable'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction($langs->trans('Enable'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction($langs->trans('Cancel'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction($langs->trans('Re-Open'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}
			*/

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 0;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->dolitypology->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->rights->dolitypology->typology->read; // If you can read, you can build the PDF to read content
			$delallowed = $user->rights->dolitypology->typology->write; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('dolitypology:Typology', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('typology'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="'.dol_buildpath('/dolitypology/typology_agenda.php', 1).'?id='.$object->id.'">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'typology';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->dolitypology->dir_output;
	$trackid = 'typology'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
