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

$AJAX_INCLUDE = 1;
include ("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['type']) && !empty($_POST['type'])) {
   $options = [];

   switch ($_POST['type']) {
      case 'User':
         $options = getAllDatasFromTable('glpi_users', ['is_deleted' => 0]);
         foreach ($options as $u) {
            echo "<option value='{$u['id']}'>" . getUserName($u['id']) . "</option>";
         }
         break;

      case 'Group':
         $options = getAllDatasFromTable('glpi_groups');
         foreach ($options as $g) {
            echo "<option value='{$g['id']}'>{$g['name']}</option>";
         }
         break;

      case 'Profile':
         $options = getAllDatasFromTable('glpi_profiles');
         echo "<option value='-1'>" . __('All') . "</option>";
         foreach ($options as $p) {
            echo "<option value='{$p['id']}'>{$p['name']}</option>";
         }
         break;
   }
}
