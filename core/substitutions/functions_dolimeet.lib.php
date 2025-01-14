<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/substitutions/functions_dolimeet.lib.php
 * \ingroup functions_dolimeet
 * \brief   File of functions to substitutions array
 */

/** Function called to complete substitution array (before generating on ODT, or a personalized email)
 * functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * is inside directory htdocs/core/substitutions
 *
 * @param  array       $substitutionarray Array with substitution key => val
 * @param  Translate   $langs             Output langs
 * @param  Object|null $object            Object to use to get values
 * @return void                           The entry parameter $substitutionarray is modified
 * @throws Exception
 */
function dolimeet_completesubstitutionarray(array &$substitutionarray, Translate $langs, ?object $object)
{
    global $conf, $db;

    if ($object->element == 'contrat') {
        // Load Saturne libraries
        require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';
        require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';

        // Load DoliMeet libraries
        require_once __DIR__ . '/../../class/session.class.php';

        saturne_load_langs();

        $session   = new Session($db);
        $signatory = new SaturneSignature($db, 'dolimeet', 'trainingsession');

        $sessions = $session->fetchAll('ASC', 'date_start', 0, 0, ['customsql' => 't.fk_contrat = ' . $object->id . " AND t.type = 'trainingsession'"]);
        if (is_array($sessions) && !empty($sessions)) {
            foreach ($sessions as $session) {
                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '<strong>' . $session->ref . ' - ' . $session->label . '</strong>';
                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '<ul><li>' . $langs->transnoentities('DateAndTime') . ' : ' . dol_strtolower($langs->transnoentities('From')) . ' ' . dol_print_date($session->date_start, 'day', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('At')) . ' ' . dol_print_date($session->date_start, 'hour', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('To')) . ' ' . dol_print_date($session->date_end, 'day', 'tzuserrel') . ' ' . dol_strtolower($langs->transnoentities('At')) . ' ' . dol_print_date($session->date_end, 'hour', 'tzuserrel') . ' (' . dol_strtolower($langs->transnoentities('Duration')) . ' : ' . (($session->duration > 0) ? convertSecondToTime($session->duration, 'allhourmin') : '00:00') . ')' . '</li>';
                $signatoriesByRole = $signatory->fetchSignatory('', $session->id, $session->type);
                if (is_array($signatoriesByRole) && !empty($signatoriesByRole)) {
                    foreach ($signatoriesByRole as $signatoryRole => $signatories) {
                        if (is_array($signatories) && !empty($signatories)) {
                            $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '<li>' . $langs->transnoentities($signatoryRole) . '(s) :';
                            foreach ($signatories as $signatory) {
                                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '<ul><li>' . strtoupper($signatory->lastname) . ' ' . $signatory->firstname . ' - <strong>' . $signatory->getLibStatut(5) . (($signatory->status == SaturneSignature::STATUS_REGISTERED) ? ' - ' . $langs->transnoentities('PendingSignature') : '') . '</strong>';
                                if ($signatoryRole != 'SessionTrainer') {
                                    $signatureUrl = dol_buildpath('/custom/saturne/public/signature/add_signature.php?track_id=' . $signatory->signature_url . '&entity=' . $conf->entity . '&module_name=dolimeet&object_type=' . $session->type . '&document_type=AttendanceSheetDocument&modal_to_open=modal-signature' . $signatory->id, 3);
                                    $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= ' - <a href=' . $signatureUrl . ' target="_blank">' . $langs->transnoentities('SignAttendanceSheetOnline') . '</a>';
                                }
                                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '</li></ul>';
                            }
                            $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '</li>';
                        }
                    }
                }
                $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_INFOS__'] .= '</ul>';
            }
        }

        $substitutionarray['__DOLIMEET_CONTRACT_LABEL__']                    = $object->array_options['options_label'];
        $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_START__']    = $object->array_options['options_trainingsession_start'];
        $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_END__']      = $object->array_options['options_trainingsession_end'];
        $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_TYPE__']     = $object->array_options['options_trainingsession_type'];
        $substitutionarray['__DOLIMEET_CONTRACT_TRAININGSESSION_LOCATION__'] = $object->array_options['options_trainingsession_location'];
    }
}
