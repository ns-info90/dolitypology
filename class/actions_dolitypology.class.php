<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) ---Replace with your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/modulebuilder/template/class/actions_mymodule.class.php
 * \ingroup mymodule
 * \brief   Example hook overload.
 *
 * TODO: Write detailed description here.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';

/**
 * Class ActionsMyModule
 */
class ActionsDolitypology extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Errors
	 */
	public $errors = array();


	/**
	 * @var mixed[] Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	public function formObjectOptions($parameters, &$object, &$action)
	{
		global $db, $langs, $user;

		if (strpos($parameters['context'], 'productcard') !== false && !in_array($action, array('create', 'edit'))) {

			dol_include_once('/dolitypology/class/typology.class.php');
			dol_include_once('/dolitypology/class/typologyextrafieldlink.class.php');
			dol_include_once('/dolitypology/lib/dolitypology_typology.lib.php');

			$extrafields = new ExtraFields($db);
			$extrafields->fetch_name_optionals_label('product');

			$this->resprints = '';
			$extrafields_collapse_num = '';
			$extrafields_collapse_num_old = '';

			//
			// Affichage classique des extrafields non typo
			$i = 0;
			foreach ($extrafields->attributes[$object->table_element]['label'] as $tmpkeyextra => $tmplabelextra) {
				$i++;

				//var_dump($extrafields->attributes[$object->table_element]);
				if ($extrafields->attributes[$object->table_element]['typology'][$tmpkeyextra]) {
					continue;
				}

				$enabled = 1;
				if ($enabled && isset($extrafields->attributes[$object->table_element]['enabled'][$tmpkeyextra])) {
					$enabled = (int) dol_eval((string) $extrafields->attributes[$object->table_element]['enabled'][$tmpkeyextra], 1, 1, '2');
				}
				if ($enabled && isset($extrafields->attributes[$object->table_element]['list'][$tmpkeyextra])) {
					$enabled = (int) dol_eval($extrafields->attributes[$object->table_element]['list'][$tmpkeyextra], 1, 1, '2');
				}

				$perms = 1;
				if ($perms && isset($extrafields->attributes[$object->table_element]['perms'][$tmpkeyextra])) {
					$perms = (int) dol_eval($extrafields->attributes[$object->table_element]['perms'][$tmpkeyextra], 1, 1, '2');
				}
				//print $tmpkeyextra.'-'.$enabled.'-'.$perms.'<br>'."\n";

				if (empty($enabled)) {
					continue; // 0 = Never visible field
				}
				if (abs($enabled) != 1 && abs($enabled) != 3 && abs($enabled) != 5 && abs($enabled) != 4) {
					continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list <> 4 = not visible at the creation
				}
				if (empty($perms)) {
					continue; // 0 = Not visible
				}

				// Load language if required
				if (!empty($extrafields->attributes[$object->table_element]['langfile'][$tmpkeyextra])) {
					$langs->load($extrafields->attributes[$object->table_element]['langfile'][$tmpkeyextra]);
				}
				if ($action == 'edit_extras') {
					$value = (GETPOSTISSET("options_".$tmpkeyextra) ? GETPOST("options_".$tmpkeyextra) : (isset($object->array_options["options_".$tmpkeyextra]) ? $object->array_options["options_".$tmpkeyextra] : ''));
				} else {
					$value = (isset($object->array_options["options_".$tmpkeyextra]) ? $object->array_options["options_".$tmpkeyextra] : '');
				}

				// Print line tr of extra field
				if ($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra] == 'separate') {
					$extrafields_collapse_num = $tmpkeyextra;

					$this->resprints .= $extrafields->showSeparator($tmpkeyextra, $object);
					$lastseparatorkeyfound = $tmpkeyextra;
				} else {
					$collapse_group = $extrafields_collapse_num.(!empty($object->id) ? '_'.$object->id : '');

					$this->resprints .= '<tr class="trextrafields_collapse'.$collapse_group;
					if ($extrafields_collapse_num && $i == count($extrafields->attributes[$object->table_element]['label'])) {
						$this->resprints .= ' trextrafields_collapse_last';
					}
					$this->resprints .= '"';
					if (isset($extrafields->expand_display) && empty($extrafields->expand_display[$collapse_group])) {
						$this->resprints .= ' style="display: none;"';
					}
					$this->resprints .= '>';
					$extrafields_collapse_num_old = $extrafields_collapse_num;
					$this->resprints .= '<td>';
					$this->resprints .= '<table class="nobordernopadding centpercent">';
					$this->resprints .= '<tr>';

					$this->resprints .= '<td class="';
					if ((!empty($action) && ($action == 'create' || $action == 'edit')) && !empty($extrafields->attributes[$object->table_element]['required'][$tmpkeyextra])) {
						$this->resprints .= ' fieldrequired';
					}
					$this->resprints .= '">';
					if (!empty($extrafields->attributes[$object->table_element]['help'][$tmpkeyextra])) {
						// You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
						$tmptooltip = explode(':', $extrafields->attributes[$object->table_element]['help'][$tmpkeyextra]);
						$this->resprints .= $form->textwithpicto($langs->trans($tmplabelextra), $langs->trans($tmptooltip[0]), 1, 'help', '', 0, 3, (empty($tmptooltip[1]) ? '' : 'extra_'.$tmpkeyextra.'_'.$tmptooltip[1]));
					} else {
						$this->resprints .= $langs->trans($tmplabelextra);
					}
					$this->resprints .= '</td>';

					//TODO Improve element and rights detection
					//var_dump($user->rights);
					$permok = false;
					$keyforperm = $object->element;

					if ($object->element == 'fichinter') {
						$keyforperm = 'ficheinter';
					}
					if ($object->element == 'product') {
						$keyforperm = 'produit';
					}
					if ($object->element == 'project') {
						$keyforperm = 'projet';
					}
					if (isset($user->rights->$keyforperm)) {
						$permok = $user->hasRight($keyforperm, 'creer') || $user->hasRight($keyforperm, 'create') || $user->hasRight($keyforperm, 'write');
					}
					if ($object->element == 'order_supplier') {
						if (!getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
							$permok = $user->hasRight('fournisseur', 'commande', 'creer');
						} else {
							$permok = $user->hasRight('supplier_order', 'creer');
						}
					}
					if ($object->element == 'invoice_supplier') {
						if (!getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
							$permok = $user->hasRight('fournisseur', 'facture', 'creer');
						} else {
							$permok = $user->hasRight('supplier_invoice', 'creer');
						}
					}
					if ($object->element == 'shipping') {
						$permok = $user->hasRight('expedition', 'creer');
					}
					if ($object->element == 'delivery') {
						$permok = $user->hasRight('expedition', 'delivery', 'creer');
					}
					if ($object->element == 'productlot') {
						$permok = $user->hasRight('stock', 'creer');
					}
					if ($object->element == 'facturerec') {
						$permok = $user->hasRight('facture', 'creer');
					}
					if ($object->element == 'mo') {
						$permok = $user->hasRight('mrp', 'write');
					}
					if ($object->element == 'contact') {
						$permok = $user->hasRight('societe', 'contact', 'creer');
					}
					if ($object->element == 'salary') {
						$permok = $user->hasRight('salaries', 'read');
					}
					if ($object->element == 'member') {
						$permok = $user->hasRight('adherent', 'creer');
					}

					$isdraft = ((isset($object->statut) && $object->statut == 0) || (isset($object->status) && $object->status == 0));
					if (($isdraft || !empty($extrafields->attributes[$object->table_element]['alwayseditable'][$tmpkeyextra]))
						&& $permok && $enabled != 5 && ($action != 'edit_extras' || GETPOST('attribute') != $tmpkeyextra)
						&& empty($extrafields->attributes[$object->table_element]['computed'][$tmpkeyextra])) {
						$fieldid = empty($forcefieldid) ? 'id' : $forcefieldid;
						$valueid = empty($forceobjectid) ? $object->id : $forceobjectid;
						if ($object->table_element == 'societe') {
							$fieldid = 'socid';
						}

						$this->resprints .= '<td class="right"><a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?'.$fieldid.'='.$valueid.'&action=edit_extras&token='.newToken().'&attribute='.$tmpkeyextra.'&ignorecollapsesetup=1">'.img_edit().'</a></td>';
					}
					$this->resprints .= '</tr></table>';
					$this->resprints .= '</td>';

					$cssview = !empty($extrafields->attributes[$object->table_element]['cssview'][$tmpkeyextra]) ? ($extrafields->attributes[$object->table_element]['cssview'][$tmpkeyextra] . ' ') : '';
					$html_id = !empty($object->id) ? $object->element.'_extras_'.$tmpkeyextra.'_'.$object->id : '';

					$this->resprints .= '<td id="' . $html_id . '" class="valuefield ' . $cssview . $object->element . '_extras_' . $tmpkeyextra . ' wordbreakimp"' . (!empty($cols) ? ' colspan="' . $cols . '"' : '') . '>';

					// Convert date into timestamp format
					if (in_array($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra], array('date'))) {
						$datenotinstring = empty($object->array_options['options_'.$tmpkeyextra]) ? '' : $object->array_options['options_'.$tmpkeyextra];
						// $this->resprints .= 'X'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
						if (!empty($object->array_options['options_'.$tmpkeyextra]) && !is_numeric($object->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
							$datenotinstring = $db->jdate($datenotinstring);
						}
						//$this->resprints .= 'x'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
						$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(12, 0, 0, GETPOSTINT("options_".$tmpkeyextra."month"), GETPOSTINT("options_".$tmpkeyextra."day"), GETPOSTINT("options_".$tmpkeyextra."year")) : $datenotinstring;
					}
					if (in_array($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra], array('datetime'))) {
						$datenotinstring = empty($object->array_options['options_'.$tmpkeyextra]) ? '' : $object->array_options['options_'.$tmpkeyextra];
						// $this->resprints .= 'X'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
						if (!empty($object->array_options['options_'.$tmpkeyextra]) && !is_numeric($object->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
							$datenotinstring = $db->jdate($datenotinstring);
						}
						//$this->resprints .= 'x'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
						$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(GETPOSTINT("options_".$tmpkeyextra."hour"), GETPOSTINT("options_".$tmpkeyextra."min"), GETPOSTINT("options_".$tmpkeyextra."sec"), GETPOSTINT("options_".$tmpkeyextra."month"), GETPOSTINT("options_".$tmpkeyextra."day"), GETPOSTINT("options_".$tmpkeyextra."year"), 'tzuserrel') : $datenotinstring;
					}

					//TODO Improve element and rights detection
					if ($action == 'edit_extras' && $permok && GETPOST('attribute', 'restricthtml') == $tmpkeyextra) {
						// Show the extrafield in create or edit mode
						$fieldid = 'id';
						if ($object->table_element == 'societe') {
							$fieldid = 'socid';
						}
						$this->resprints .= '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"] . '?' . $fieldid . '=' . $object->id . '" method="post" name="formextra">';
						$this->resprints .= '<input type="hidden" name="action" value="update_extras">';
						$this->resprints .= '<input type="hidden" name="attribute" value="'.$tmpkeyextra.'">';
						$this->resprints .= '<input type="hidden" name="token" value="'.newToken().'">';
						$this->resprints .= '<input type="hidden" name="'.$fieldid.'" value="'.$object->id.'">';
						$this->resprints .= $extrafields->showInputField($tmpkeyextra, $value, '', '', '', 0, $object, $object->table_element);

						$this->resprints .= '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';

						$this->resprints .= '</form>';
					} else {
						// Show the extrafield in view mode

						//var_dump($tmpkeyextra.'-'.$value.'-'.$object->table_element);
						$this->resprints .= $extrafields->showOutputField($tmpkeyextra, $value, '', $object->table_element, null, $object);
					}

					$this->resprints .= '</td>';
					$this->resprints .= '</tr>'."\n";
				}
			}

			$permok = 1;
			if (!empty($object->array_options['options_dolitypo_typo'])) {
				$typologyIDArray = explode(',', $object->array_options['options_dolitypo_typo']);
				foreach ($typologyIDArray as $typologyID) {

					$t = new Typology($db);
					$t->fetch($typologyID);
					$extratest = new TypologyExtrafieldLink($db);
					$typologyExtrafields = $extratest->fetchAll('', '', 0, 0, ['customsql' => 't.fk_typology = ' . $typologyID]);
					if (!empty($typologyExtrafields)) {

						$groupUnikID = uniqid();

						$this->resprints .= '<tr id="trextrafieldseparatorpayrollusergoup_'.$groupUnikID.'" class="trextrafieldseparator trextrafieldseparatorpayrollusergoup_'.$groupUnikID.'">';
						$this->resprints .= '<td colspan="2"><span class="cursorpointer far fa-minus-square"></span>&nbsp;<strong>'.$t->label.'</strong></td>';
						$this->resprints .= '</tr>';

						$i = 0;
						foreach ($typologyExtrafields as $key => $typologyExtrafield ) {
							$i++;
							$extraf = fetch_extrafields_id($typologyExtrafield->fk_extrafield, 'product');
							$extraf = $extraf[0];
							$this->resprints .= '<tr class="trextrafields_collapsepayrollusergoup_'.$groupUnikID.'">';
							$this->resprints .= '<td>';
								$this->resprints .= '<table class="nobordernopadding centpercent">';
									$this->resprints .= '<td>'.$extraf->label.'</td>';
									$this->resprints .= '<td class="right">';
										if ($action != 'edit_extras' || $action == 'edit_extras' && GETPOST('attribute', 'restricthtml') != $extraf->name) {
											$this->resprints .= '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit_extras&token='.newToken().'&attribute='.$extraf->name.'&ignorecollapsesetup=1">'.img_edit().'</a>';
										}
									$this->resprints .= '</td>';
								$this->resprints .= '</table>';
							$this->resprints .= '</td>';
							$this->resprints .= '<td>';
								if ($action == 'edit_extras' && $permok && GETPOST('attribute', 'restricthtml') == $extraf->name) {
									$this->resprints .= '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post" name="formextra">';
									$this->resprints .= '<input type="hidden" name="action" value="update_extras">';
									$this->resprints .= '<input type="hidden" name="attribute" value="'.$extraf->name.'">';
									$this->resprints .= '<input type="hidden" name="token" value="'.newToken().'">';
									$this->resprints .= '<input type="hidden" name="id" value="'.$object->id.'">';
									$this->resprints .= $extrafields->showInputField($extraf->name, $object->array_options['options_' . $extraf->name], '', '', '', 0, $object, $object->table_element);
									$this->resprints .= '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';
									$this->resprints .= '</form>';
								} else {
									$this->resprints .= $extrafields->showOutputField($extraf->name, $object->array_options['options_' . $extraf->name], '', $object->table_element, null, $object);
								}
							$this->resprints .= '</td>';
							$this->resprints .= '</tr>';
						}
					}
				}
			}

			//
			$expand_display = 1;
			$this->resprints .= '<!-- Add js script to manage the collapse/uncollapse of extrafields separators '.$key.' -->'."\n";
			$this->resprints .= '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
			$this->resprints .= 'jQuery(document).ready(function(){'."\n";
			if (empty($disabledcookiewrite)) {
				if (!$expand_display) {
					$this->resprints .= '   console.log("Inject js for the collapsing of extrafield '.$key.' - hide");'."\n";
					$this->resprints .= '   jQuery(".trextrafields_collapse'.$collapse_group.'").hide();'."\n";
				} else {
					$this->resprints .= '   console.log("Inject js for collapsing of extrafield '.$key.' - keep visible and set cookie");'."\n";
					$this->resprints .= '   document.cookie = "DOLUSER_COLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=1; path='.$_SERVER["PHP_SELF"].'"'."\n";
				}
			}
			$this->resprints .= '   jQuery("#trextrafieldseparator'.$key.(!empty($object->id) ? '_'.$object->id : '').'").click(function(){'."\n";
			$this->resprints .= '       console.log("We click on collapse/uncollapse to hide/show .trextrafields_collapse'.$collapse_group.'");'."\n";
			$this->resprints .= '       jQuery(".trextrafields_collapse'.$collapse_group.'").toggle(100, function(){'."\n";
			$this->resprints .= '           if (jQuery(".trextrafields_collapse'.$collapse_group.'").is(":hidden")) {'."\n";
			$this->resprints .= '               jQuery("#trextrafieldseparator'.$key.(!empty($object->id) ? '_'.$object->id : '').' '.$tagtype_dyn.' span").addClass("fa-plus-square").removeClass("fa-minus-square");'."\n";
			$this->resprints .= '               document.cookie = "DOLUSER_COLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=0; path='.$_SERVER["PHP_SELF"].'"'."\n";
			$this->resprints .= '           } else {'."\n";
			$this->resprints .= '               jQuery("#trextrafieldseparator'.$key.(!empty($object->id) ? '_'.$object->id : '').' '.$tagtype_dyn.' span").addClass("fa-minus-square").removeClass("fa-plus-square");'."\n";
			$this->resprints .= '               document.cookie = "DOLUSER_COLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=1; path='.$_SERVER["PHP_SELF"].'"'."\n";
			$this->resprints .= '           }'."\n";
			$this->resprints .= '       });'."\n";
			$this->resprints .= '   });'."\n";
			$this->resprints .= '});'."\n";
			$this->resprints .= '</script>'."\n";


			return 1;
		}
		return 0;
	}

	/**
	 * Execute action
	 *
	 * @param	array<string,mixed>	$parameters	Array of parameters
	 * @param	CommonObject		$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action		'add', 'update', 'view'
	 * @return	int								Return integer <0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *											>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overload the doActions function : replacing the parent's function with the one below
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			// Do what you want here...
			// You can for example load and use call global vars like $fieldstosearchall to overwrite them, or update the database depending on $action and GETPOST values.

			if (!$error) {
				$this->results = array('myreturn' => 999);
				$this->resprints = 'A text to show';
				return 0; // or return 1 to replace standard code
			} else {
				$this->errors[] = 'Error message';
				return -1;
			}
		}

		return 0;
	}


	/**
	 * Overload the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			// @phan-suppress-next-line PhanPluginEmptyStatementForeachLoop
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}

			if (!$error) {
				$this->results = array('myreturn' => 999);
				$this->resprints = 'A text to show';
				return 0; // or return 1 to replace standard code
			} else {
				$this->errors[] = 'Error message';
				return -1;
			}
		}

		return 0;
	}


	/**
	 * Overload the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param	array<string,mixed>	$parameters     Hook metadata (context, etc...)
	 * @param	CommonObject		$object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string	$action						Current action (if set). Generally create or edit or null
	 * @param	HookManager	$hookmanager			Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("MyModuleMassAction").'</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}



	/**
	 * Execute action before PDF (document) creation
	 *
	 * @param	array<string,mixed>	$parameters	Array of parameters
	 * @param	CommonObject		$object		Object output on PDF
	 * @param	string				$action		'add', 'update', 'view'
	 * @return	int								Return integer <0 if KO,
	 *											=0 if OK but we want to process standard actions too,
	 *											>0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		// @phan-suppress-next-line PhanPluginEmptyStatementIf
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}

	/**
	 * Execute action after PDF (document) creation
	 *
	 * @param	array<string,mixed>	$parameters	Array of parameters
	 * @param	CommonDocGenerator	$pdfhandler	PDF builder handler
	 * @param	string				$action		'add', 'update', 'view'
	 * @return	int								Return integer <0 if KO,
	 * 											=0 if OK but we want to process standard actions too,
	 *											>0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		// @phan-suppress-next-line PhanPluginEmptyStatementIf
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}



	/**
	 * Overload the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	?string				$action 		Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager    Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $langs;

		$langs->load("mymodule@mymodule");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'mymodule') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("MyModule");
			$this->results['picto'] = 'mymodule@mymodule';
		}

		$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		$arrayoftypes = array();
		//$arrayoftypes['mymodule_myobject'] = array('label' => 'MyObject', 'picto'=>'myobject@mymodule', 'ObjectClassName' => 'MyObject', 'enabled' => isModEnabled('mymodule'), 'ClassPath' => "/mymodule/class/myobject.class.php", 'langs'=>'mymodule@mymodule')

		$this->results['arrayoftype'] = $arrayoftypes;

		return 0;
	}



	/**
	 * Overload the restrictedArea function : check permission on an object
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param   CommonObject    	$object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer <0 if KO,
	 *												=0 if OK but we want to process standard actions too,
	 *												>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, $object, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->hasRight('mymodule', 'myobject', 'read')) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param	array<string,mixed>	$parameters		Array of parameters
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action			'add', 'update', 'view'
	 * @param	HookManager			$hookmanager	Hookmanager
	 * @return	int									Return integer <0 if KO,
	 *												=0 if OK but we want to process standard actions too,
	 *												>0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// used to make some tabs removed
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('mymodule@mymodule');
			// used when we want to add some tabs
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if (in_array($element, ['context1', 'context2'])) {
				$datacount = 0;

				$parameters['head'][$counter][0] = dol_buildpath('/mymodule/mymodule_tab.php', 1) . '?id=' . $id . '&amp;module='.$element;
				$parameters['head'][$counter][1] = $langs->trans('MyModuleTab');
				if ($datacount > 0) {
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'mymoduleemails';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {  // @phpstan-ignore-line
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			} else {
				// From V14 onwards, $parameters['head'] is modifiable by reference
				return 0;
			}
		} else {
			// Bad value for $parameters['mode']
			return -1;
		}
	}


	/**
	 * Overload the showLinkToObjectBlock function : add or replace array of object linkable
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function showLinkToObjectBlock($parameters, &$object, &$action, $hookmanager)
	{
		$myobject = new MyObject($object->db);
		$this->results = array('myobject@mymodule' => array(
			'enabled' => isModEnabled('mymodule'),
			'perms' => 1,
			'label' => 'LinkToMyObject',
			'sql' => "SELECT t.rowid, t.ref, t.ref as 'name' FROM " . $this->db->prefix() . $myobject->table_element. " as t "),);

		return 1;
	}
	/* Add other hook methods here... */
}
