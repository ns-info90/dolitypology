<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file        class/typologyextrafieldlink.class.php
 * \ingroup     dolitypology
 * \brief       This file is a CRUD class file for TypologyExtrafieldLink (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

/**
 * Class for TypologyExtrafields
 */
class TypologyExtrafields extends Extrafields
{

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Load the array of extrafields definition $this->attributes
	 *
	 * 	@param	string		$elementtype		Type of element ('all' = all or $object->table_element like 'adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...).
	 * 	@param	boolean		$forceload			Force load of extra fields whatever is status of cache.
	 *  @param  string		$attrname           The name of the attribute.
	 *  @return array<string,string>					Array of attributes keys+label for all extra fields.
	 * in addition $this->attributes will be completed with array{label:array<string,string>,type:array<string,string>,size:array<string,string>,default:array<string,string>,computed:array<string,string>,unique:array<string,int>,required:array<string,int>,param:array<string,mixed>,perms:array<string,mixed[]>,list:array<string,int>|array<string,string>,pos:array<string,int>,totalizable:array<string,int>,help:array<string,string>,printable:array<string,int>,enabled:array<string,int>,langfile:array<string,string>,css:array<string,string>,csslist:array<string,string>,hidden:array<string,int>,mandatoryfieldsofotherentities?:array<string,string>,loaded?:int,count:int} Note: count set as present to avoid static analysis notices
	 */
	public function fetch_name_optionals_label($elementtype, $forceload = false, $attrname = '', int $typologyID = 0, int $limit = 0, int $offset = 0, string $sortorder = 'ASC', string $sortfield = 'pos')
	{
		// phpcs:enable
		global $conf;

		if (empty($elementtype)) {
			return array();
		}

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}
		if ($elementtype == 'order_supplier') {
			$elementtype = 'commande_fournisseur';
		}

		// Test cache $this->attributes[$elementtype]['loaded'] to see if we must do something
		// TODO

		$array_name_label = array();

		// We should not have several time this request. If we have, there is some optimization to do by calling a simple $extrafields->fetch_optionals() in top of code and not into subcode
		$sql = "SELECT e.rowid, e.name, e.label, e.type, e.size, e.elementtype, e.fieldunique, e.fieldrequired, e.param, e.pos, e.alwayseditable, e.perms, e.langs, e.list, e.printable, e.totalizable, e.fielddefault, e.fieldcomputed, e.entity, e.enabled, e.help,";
		$sql .= " e.css, e.cssview, e.csslist";
		$sql .= " FROM ".$this->db->prefix()."extrafields as e";
		if ($typologyID > 0) {
			$sql .= " INNER JOIN ".$this->db->prefix()."dolitypology_typologyextrafieldlink AS l ON e.rowid = l.fk_extrafield";
			$sql .= " AND l.fk_typology = ".$typologyID;
		}

		//$sql.= " WHERE entity IN (0,".$conf->entity.")";    // Filter is done later
		if ($elementtype && $elementtype != 'all') {
			$sql .= " WHERE e.elementtype = '".$this->db->escape($elementtype)."'"; // Filed with object->table_element
		}
		if ($attrname && $elementtype && $elementtype != 'all') {
			$sql .= " AND e.name = '".$this->db->escape($attrname)."'";
		}
		$sql .= $this->db->order('e.'.$sortfield, $sortorder);
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$count = 0;
			if ($this->db->num_rows($resql)) {
				while ($tab = $this->db->fetch_object($resql)) {
					if ($tab->entity != 0 && $tab->entity != $conf->entity) {
						// This field is not in current entity. We discard but before we save it into the array of mandatory fields if it is a mandatory field without default value
						if ($tab->fieldrequired && is_null($tab->fielddefault)) {
							$this->attributes[$tab->elementtype]['mandatoryfieldsofotherentities'][$tab->name] = $tab->type;
						}
						continue;
					}

					// We can add this attribute to object. TODO Remove this and return $this->attributes[$elementtype]['label']
					if ($tab->type != 'separate') {
						$array_name_label[$tab->name] = $tab->label;
					}


					$this->attributes[$tab->elementtype]['type'][$tab->name] = $tab->type;
					$this->attributes[$tab->elementtype]['label'][$tab->name] = $tab->label;
					$this->attributes[$tab->elementtype]['size'][$tab->name] = $tab->size;
					$this->attributes[$tab->elementtype]['elementtype'][$tab->name] = $tab->elementtype;
					$this->attributes[$tab->elementtype]['default'][$tab->name] = $tab->fielddefault;
					$this->attributes[$tab->elementtype]['computed'][$tab->name] = $tab->fieldcomputed;
					$this->attributes[$tab->elementtype]['unique'][$tab->name] = $tab->fieldunique;
					$this->attributes[$tab->elementtype]['required'][$tab->name] = $tab->fieldrequired;
					$this->attributes[$tab->elementtype]['param'][$tab->name] = ($tab->param ? jsonOrUnserialize($tab->param) : '');
					$this->attributes[$tab->elementtype]['pos'][$tab->name] = $tab->pos;
					$this->attributes[$tab->elementtype]['alwayseditable'][$tab->name] = $tab->alwayseditable;
					$this->attributes[$tab->elementtype]['perms'][$tab->name] = ((is_null($tab->perms) || strlen($tab->perms) == 0) ? 1 : $tab->perms);
					$this->attributes[$tab->elementtype]['langfile'][$tab->name] = $tab->langs;
					$this->attributes[$tab->elementtype]['list'][$tab->name] = $tab->list;
					$this->attributes[$tab->elementtype]['printable'][$tab->name] = $tab->printable;
					$this->attributes[$tab->elementtype]['totalizable'][$tab->name] = ($tab->totalizable ? 1 : 0);
					$this->attributes[$tab->elementtype]['entityid'][$tab->name] = $tab->entity;
					$this->attributes[$tab->elementtype]['enabled'][$tab->name] = $tab->enabled;
					$this->attributes[$tab->elementtype]['help'][$tab->name] = $tab->help;
					$this->attributes[$tab->elementtype]['css'][$tab->name] = $tab->css;
					$this->attributes[$tab->elementtype]['cssview'][$tab->name] = $tab->cssview;
					$this->attributes[$tab->elementtype]['csslist'][$tab->name] = $tab->csslist;

					$this->attributes[$tab->elementtype]['loaded'] = 1;
					$count++;
				}
			}
			if ($elementtype) {
				$this->attributes[$elementtype]['loaded'] = 1; // Note: If nothing is found, we also set the key 'loaded' to 1.
				$this->attributes[$elementtype]['count'] = $count;
			}
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_name_optionals_label ".$this->error, LOG_ERR);
		}

		return $array_name_label;
	}

	public function countExtrafields(string $elementtype, int $typologyID = 0)
	{
		if (empty($elementtype)) {
			return 0;
		}

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}
		if ($elementtype == 'order_supplier') {
			$elementtype = 'commande_fournisseur';
		}

		// We should not have several time this request. If we have, there is some optimization to do by calling a simple $extrafields->fetch_optionals() in top of code and not into subcode
		$sql = "SELECT COUNT(e.rowid) as nb FROM ".$this->db->prefix()."extrafields as e";
		if ($typologyID > 0) {
			$sql .= " INNER JOIN ".$this->db->prefix()."dolitypology_typologyextrafieldlink AS l ON e.rowid = l.fk_extrafield";
			$sql .= " AND l.fk_typology = ".$typologyID;
		}
		//$sql.= " WHERE entity IN (0,".$conf->entity.")";    // Filter is done later
		if ($elementtype && $elementtype != 'all') {
			$sql .= " WHERE e.elementtype = '".$this->db->escape($elementtype)."'"; // Filed with object->table_element
		}
		$res = $this->db->query($sql);
		if (!$res) {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_name_optionals_label ".$this->error, LOG_ERR);
			return -1;
		}
		$obj = $this->db->fetch_object($res);
		return (int) $obj->nb;
	}
}