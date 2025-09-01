<?php

/**
 * -------------------------------------------------------------------------
 * News plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of News.
 *
 * News is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * News is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with News. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2015-2022 by News plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/news
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginNewsAlert_Target extends CommonDBTM {
   static $rightname = 'reminder_public';

   static function getTypeName($nb = 0) {
      return _n('Target', 'Targets', $nb);
   }

   static function canDelete() {
      return self::canUpdate();
   }

   static function canPurge() {
      return self::canUpdate();
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'items_id':
            if (isset($values['itemtype'])
                && is_subclass_of($values['itemtype'], 'CommonDBTM')) {
               $item = new $values['itemtype'];
               if ($values['itemtype'] == "Profile"
                   && isset($values['all_items']) && $values['all_items'] == 1) {
                  return $item->getTypeName()." - ".__('All');
               }
               $item->getFromDB($values['items_id']);
               return $item->getTypeName()." - ".$item->getName();
            }
            break;

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item instanceof PluginNewsAlert) {
         $nb = countElementsInTable(
            self::getTable(),
            ['plugin_news_alerts_id' => $item->getID()]
         );
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item instanceof PluginNewsAlert) {
         self::showForAlert($item);
      }
   }


   
      static function showForAlert(PluginNewsAlert $alert) {
         global $CFG_GLPI, $DB;
   
         $searchID = $alert->getID();
   
         if (!$searchID) {
            return false;
         }
   
         $canedit = Session::haveRight("plugin_news", UPDATE);
         $rand = mt_rand();

            $types = ['Group' => __('Group'), 'Profile' => __('Profile'), 'User' => __('User')];
            
            $form = [
               'action' => Toolbox::getItemTypeFormURL('PluginNewsAlert'),
               'buttons' => [
                  [
                     'type'  => 'submit',
                     'name'  => 'addvisibility',
                     'value' => _sx('button', 'Add'),
                     'class' => 'btn btn-secondary'
                  ]
               ],
               'content' => [
                  '' => [
                     'visible' => true,
                     'inputs'  => [
                        [
                           'type'  => 'hidden',
                           'name'  => 'plugin_news_alerts_id',
                           'value' => $searchID
                        ],
                        __('Type') => [
                           'type'   => 'select',
                           'name'   => 'itemtype',
                           'id'     => 'dropdown_itemtype',
                           'values' => array_merge(['' => '-----'], $types),
                           'col_lg' => 6,
                           'hooks'  => [
                              'change' => "
                              $.ajax({
                                 url: '{$CFG_GLPI['root_doc']}/plugins/news/ajax/targets.php',
                                 type: 'POST',
                                 dataType: 'html',
                                 data: {
                                    type: $(this).val(),
                                    entities_id: '{$alert->fields['entities_id']}',
                                    is_recursive: '{$alert->fields['is_recursive']}'
                                 },
                                 success: function(data) {
                                    let cleanData = data.substring(data.indexOf('<option'));
                                    $('#dropdown_items_id').html(cleanData).prop('disabled', false);
                                 },
                                 error: function(xhr, status, error) {
                                    console.error('Erreur Ajax:', status, error, xhr.responseText);
                                    $('#dropdown_items_id_{$rand}').html('<option value=\"\">Erreur de chargement</option>');
                                 }
                              });
                              "
                           ]
                        ],
                        __('Target') => [
                           'type'     => 'select',
                           'name'     => 'items_id',
                           'id'       => 'dropdown_items_id',
                           'values'   => [''],
                           'col_lg'   => 6,
                        ],
                        __('All items') => [
                           'type'   => 'checkbox',
                           'name'   => 'all_items',
                           'value'  => 0,
                           'col_lg' => 12,
                        ]
                     ]
                  ]
               ]
            ];
            
            renderTwigForm($form);
   
            $target       = new self();
            $found_target = $target->find(['plugin_news_alerts_id' => $alert->getID()]);
            if ($nb = count($found_target) > 0) {
               Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
               $massiveactionparams
                  = ['num_displayed'    => $nb,
                     'container'        => 'mass'.__CLASS__.$rand,
                     'specific_actions' => ['delete' => _x('button', 'Delete permanently')],
                     'deprecated'       => 'true',
                     ];
               Html::showMassiveActions($massiveactionparams);
      
               echo "<table class='tab_cadre_fixehov'>";
      
               echo "<tr>";
               echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
               echo "<th>".__('Type')."</th>";
               echo "<th>".__('Recipient')."</th>";
               echo "</tr>";
      
               foreach ($found_target as $current_target) {
                  if (class_exists($current_target['itemtype'])) {
                     $item = new $current_target['itemtype'];
                     $item->getFromDB($current_target['items_id']);
                     $name = ($current_target['all_items'] == 1
                              && $current_target['itemtype'] == "Profile")
                                 ?__('All')
                                 :$item->getName(['complete' => true]);
      
                     echo "<tr class='tab_bg_2'>";
                     echo "<td>";
                           Html::showMassiveActionCheckBox(__CLASS__, $current_target["id"]);
                           echo "</td>";
                     echo "<td>".$item->getTypeName()."</td>";
                     echo "<td>$name</td>";
                     echo "</tr>";
                  }
               }
      
               echo "</table>";
      
               $massiveactionparams['ontop'] = false;
               Html::showMassiveActions($massiveactionparams);
               Html::closeForm();
            }
            echo "</div>";
      
            return true;
      }
   }
