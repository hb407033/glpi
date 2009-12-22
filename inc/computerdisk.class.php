<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Disk class
class ComputerDisk extends CommonDBChild {

   // From CommonDBTM
   public $table = 'glpi_computerdisks';
   public $type = 'ComputerDisk';

   // From CommonDBChild
   public $itemtype = 'Computer';
   public $items_id = 'computers_id';
   public $dohistory = true;


   static function getTypeName() {
      global $LANG;

      return $LANG['computers'][0];
   }

   function canCreate() {
      return haveRight('computer', 'w');
   }

   function canView() {
      return haveRight('computer', 'r');
   }

   function prepareInputForAdd($input) {
      // Not attached to software -> not added
      if (!isset($input['computers_id']) || $input['computers_id'] <= 0) {
         return false;
      }
      return $input;
   }

   function post_getEmpty () {
      $this->fields["totalsize"]='0';
      $this->fields["freesize"]='0';
   }

   /**
   * Print the version form
   *
   *@param $target form target
   *@param $ID Integer : Id of the version or the template to print
   *@param $computers_id ID of the computer for add process
   *
   *@return true if displayed  false if item not found or not right to display
   **/
   function showForm($target,$ID,$computers_id=-1) {
      global $CFG_GLPI,$LANG;

      if (!haveRight("computer","w")) {
        return false;
      }
      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $input=array('computers_id'=>$computers_id);
         $this->check(-1,'w',$input);
      }

      $this->showTabs($ID);
      $this->showFormHeader($target,$ID,'',2);

      if ($ID>0) {
        $computers_id=$this->fields["computers_id"];
      } else {
         echo "<input type='hidden' name='computers_id' value='$computers_id'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][25]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      echo "<a href='computer.form.php?id=".$computers_id."'>".
             Dropdown::getDropdownName("glpi_computers",$computers_id)."</a>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
      echo "</td><td>".$LANG['computers'][6]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("device",$this->table,"device", $this->fields["device"],40);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][5]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("mountpoint",$this->table,"mountpoint",
                              $this->fields["mountpoint"],40);
      echo "</td><td>".$LANG['computers'][4]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('FileSystem', array('value' => $this->fields["filesystems_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][3]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("totalsize",$this->table,"totalsize",
                              $this->fields["totalsize"],40);
      echo "&nbsp;".$LANG['common'][82]."</td>";

      echo "<td>".$LANG['computers'][2]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("freesize",$this->table,"freesize",
                              $this->fields["freesize"],40);
      echo "&nbsp;".$LANG['common'][82]."</td></tr>";

      $this->showFormButtons($ID,'',2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;

   }

   function defineTabs($ID,$withtemplate) {
      global $LANG,$CFG_GLPI;

      $ong[1]=$LANG['title'][26];

      return $ong;
   }

   /**
    * Print the computers disks
    *
    *@param $comp Computer
    *@param $withtemplate=''  boolean : Template or basic item.
    *
    *@return Nothing (call to classes members)
    *
    **/
   static function showForComputer(Computer $comp, $withtemplate='') {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID) || ! $comp->can($ID, "r")) {
         return false;
      }
      $canedit = $comp->can($ID, "w");

      echo "<div class='center'>";

      $query = "SELECT `glpi_filesystems`.`name` as fsname, `glpi_computerdisks`.*
                FROM `glpi_computerdisks`
                LEFT JOIN `glpi_filesystems`
                          ON (`glpi_computerdisks`.`filesystems_id` = `glpi_filesystems`.`id`)
                WHERE (`computers_id` = '$ID')";

      if ($result=$DB->query($query)) {
         echo "<table class='tab_cadre_fixe'><tr>";
         echo "<th colspan='6'>".$LANG['computers'][8]."</th></tr>";
         if ($DB->numrows($result)) {
            echo "<tr><th>".$LANG['common'][16]."</th>";
            echo "<th>".$LANG['computers'][6]."</th>";
            echo "<th>".$LANG['computers'][5]."</th>";
            echo "<th>".$LANG['computers'][4]."</th>";
            echo "<th>".$LANG['computers'][3]."</th>";
            echo "<th>".$LANG['computers'][2]."</th>";
            echo "</tr>";

            initNavigateListItems('ComputerDisk', $LANG['help'][25]." = ".
                                  (empty($comp->fields['name']) ? "($ID)" : $comp->fields['name']));

            while ($data=$DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td><a href='computerdisk.form.php?id=".$data['id']."'>".
                             $data['name'].(empty($data['name'])?$data['id']:"")."</a></td>";
               } else {
                  echo "<td>".$data['name'].(empty($data['name'])?$data['id']:"")."</td>";
               }
               echo "<td>".$data['device']."</td>";
               echo "<td>".$data['mountpoint']."</td>";
               echo "<td>".$data['fsname']."</td>";
               echo "<td class='right'>".formatNumber($data['totalsize'], false, 0)."&nbsp;".
                      $LANG['common'][82]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
               echo "<td class='right'>".formatNumber($data['freesize'], false, 0)."&nbsp;".
                      $LANG['common'][82]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";

               addToNavigateListItems('ComputerDisk',$data['id']);
            }
         } else {
            echo "<tr><th colspan='6'>".$LANG['search'][15]."</th></tr>";
         }
      if ($canedit &&!(!empty($withtemplate) && $withtemplate == 2)) {
         echo "<tr class='tab_bg_2'><th colspan='6'>";
         echo "<a href='computerdisk.form.php?computers_id=$ID&amp;withtemplate=".
                $withtemplate."'>".$LANG['computers'][7]."</a></th></tr>";
      }
      echo "</table>";
      }
      echo "</div><br>";
   }
}

?>