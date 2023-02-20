<?php
/* Copyright (C) 2021-2023 EVARISK <dev@evarisk.com>
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
 * \file    admin/setup.php
 * \ingroup dolimeet
 * \brief   DoliMeet setup page.
 */

// Load DoliMeet environment
if (file_exists('../dolimeet.main.inc.php')) {
	require_once __DIR__ . '/../dolimeet.main.inc.php';
} else {
	die('Include of dolimeet main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT. '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';

require_once __DIR__ . '/../lib/dolimeet.lib.php';

// Global variables definitions
global $db, $langs, $user;

$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

// Translations
saturne_load_langs();

// Get parameters
$backtopage = GETPOST('backtopage', 'alpha');

// Access control
$permissiontoread = $user->rights->dolimeet->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * View
 */

if ( ! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$title    = $langs->trans('ModuleSetup', 'DoliMeet');
$help_url = 'FR:Module_DoliMeet';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'dolimeet_color@dolimeet');

// Configuration header
$head = dolimeet_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $title, -1, 'dolimeet_color@dolimeet');

print load_fiche_titre($langs->trans('SessionConfig'), '', '');
print '<hr>';


print load_fiche_titre($langs->trans('SessionNumberingModule'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="nowrap">' . $langs->trans('Example') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '<td class="center">' . $langs->trans('ShortInfo') . '</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath('/custom/dolimeet/core/modules/dolimeet/session/');
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false ) {
			if ( ! is_dir($dir . $file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if ( ! class_exists($classname) && is_readable($dir . $filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
					// Charging the numbering class
					require_once $dir . $filebis;

					$module = new $classname($db);

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>';
						print $langs->trans($module->name);
						print "</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>' . "\n";

						print '<td class="center">';
						$numbering_module = 'DOLIMEET_' . strtoupper($module->name) . '_ADDON';
						if ($conf->global->$numbering_module == $file || $conf->global->$numbering_module . '.php' == $file) {
							print img_picto($langs->trans('Activated'), 'switch_on');
						} else {
							print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=setmod&value=' . preg_replace('/\.php$/', '', $file) . '&scan_dir=' . $module->scandir . '&label=' . urlencode($module->name) . '" alt="' . $langs->trans('Default') . '">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</a>';
						}
						print '</td>';

						// Example for listing risks action
						$htmltooltip  = '';
						$htmltooltip .= '' . $langs->trans('Version') . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval      = $module->getNextValue($object_document);
						if ("$nextval" != $langs->trans('NotAvailable')) {  // Keep " on nextval
							$htmltooltip .= $langs->trans('NextValue') . ': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
									$nextval  = $langs->trans($nextval);
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						if ($conf->global->$numbering_module . '.php' == $file) { // If module is the one used, we show existing errors
							if ( ! empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
						}
						print '</td>';
						print "</tr>\n";
					}
				}
			}
		}
		closedir($handle);
	}
}

print '</table>';

print load_fiche_titre($langs->trans('SessionTypes'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('Meeting');
print '</td><td>';
print $langs->trans('EnableObjectDescription', $langs->transnoentities('Meeting'));
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DOLIMEET_MEETING_MENU_ENABLED');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('TrainingSession');
print '</td><td>';
print $langs->trans('EnableObjectDescription', $langs->trans('TrainingSession'));
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DOLIMEET_TRAININGSESSION_MENU_ENABLED');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('Audit');
print '</td><td>';
print $langs->trans('EnableObjectDescription', $langs->trans('Audit'));
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DOLIMEET_AUDIT_MENU_ENABLED');
print '</td>';
print '</tr>';

$db->close();
llxFooter();
