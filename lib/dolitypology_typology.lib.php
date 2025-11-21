<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    lib/dolitypology_typology.lib.php
 * \ingroup dolitypology
 * \brief   Library files with common functions for Typology
 */

/**
 * Prepare array of tabs for Typology
 *
 * @param	Typology	$object		Typology
 * @return 	array					Array of tabs
 */
function typologyPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("dolitypology@dolitypology");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolitypology/typology_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath("/dolitypology/typology_relatedobjects.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("RelatedObjects").' <span class="badge">xx</span>';
	$head[$h][2] = 'relatedobjects';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/dolitypology/typology_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->dolitypology->dir_output."/typology/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/dolitypology/typology_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/dolitypology/typology_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@dolitypology:/dolitypology/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@dolitypology:/dolitypology/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'typology@dolitypology');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'typology@dolitypology', 'remove');

	return $head;
}

function fetch_extrafields($id, $elementtype) {
	global $db;

	$sql = "SELECT * ";
	$sql .= "FROM " . MAIN_DB_PREFIX . "extrafields as e ";
	$sql .= "WHERE e.name = '" . $id . "'";
	$sql .= " AND e.elementtype = '" . $elementtype . "'";

	$resql = $db->query($sql);
	$records = [];

	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$records[] = $obj;
		}
		$db->free($resql);
	}

	return $records;
}

function fetch_extrafields_id($id, $elementtype) {
	global $db;

	$sql = "SELECT * ";
	$sql .= "FROM " . MAIN_DB_PREFIX . "extrafields as e ";
	$sql .= "WHERE e.rowid = '" . $id . "'";
	$sql .= " AND e.elementtype = '" . $elementtype . "'";

	$resql = $db->query($sql);
	$records = [];

	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$records[] = $obj;
		}
		$db->free($resql);
	}

	return $records;
}

/**
 * Marquer un événement d'appel comme traité
 */
function update_extrafields($id, $mode = 'add') {
	global $db;

	$sql = "UPDATE " . MAIN_DB_PREFIX . "extrafields as e ";
	if ($mode == 'remove') {
		$sql .= "SET typology = 0 ";
	} else {
		$sql .= "SET typology = 1 ";
	}
	$sql .= "WHERE e.rowid = " . (int)$id;

	return $db->query($sql);
}
