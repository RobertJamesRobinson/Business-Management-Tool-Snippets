<?php
class Utilities {
    protected $db;
    private $messages;
    
    //standard constructor, takes a database connection
    public function __construct(&$db) {
        $this->db=$db;
    }
    
    //returns the messages object data
    public function get_messages() {
        return $this->messages;
    }
    
    //returns the message object data as a list of strings which can be near immediately rendered to the user
    public function get_message_strings() {
        foreach($this->messages as $singleMessage) {
            
        }
    }
    
    //converts a menu array into the HTML needed to render the menu system
    public function render_menu($menuArray) {
        $char_replace=array('/','-',' ');
        $top_level_menu_order_pref=array('Clients','Staff','Suppliers','Invoices','Documents','Admin');
        $sec_level_menu_order_pref=array('Documents'=>array(),'Staff'=>array(),'Clients'=>array('Add Client|Add Client','Edit Client|View Client Details','Delete Client|Remove Client','Add Client Contact|Add Client Contact','Edit Client Contact|Update Client Contact Details','Delete Client|Remove Client Contact','View Careplan|View Careplan','Edit Careplan|Update Careplan'),'Suppliers'=>array('Add Supplier|Add Supplier','Update Supplier|Update Supplier Detail','New Purchase|Record Purchase','Manage Supplies|Manage Overdue Supplies'),'Invoices'=>array('Care Invoice|Raise Care Related Invoice','AdHoc Invoice|Raise Ad Hoc Invoice','Current Invoices|View/Print Current Invoices','Overdue Invoices|View/Print Overdue Invoices','Edit Invoice|Edit Existing Invoices'),'Admin'=>array('Purge Client|Purge All Client Details'));
        #TODO: shift above preferences somewhere else more modular?
        
        #presort menus into the order that preferences are given. Missing preferences result in arbitrary ordering of missing prefs
        $menuArray2=array();
        foreach ($top_level_menu_order_pref as $pref1) {
            $sec=array();
            foreach ($sec_level_menu_order_pref[$pref1] as $pref2) {
                if (in_array($pref2,$menuArray[$pref1])) {
                    $sec[]=$pref2;
                }
            }
            foreach ($menuArray[$pref1] as $remain_sec) {
                if(!in_array($remain_sec,$sec)) {
                    $sec[]=$remain_sec;
                }
            }
            $menuArray2[]=array($pref1=>$sec);
            unset($menuArray[$pref1]);    
        }
        foreach ($menuArray as $key=>$remain_pri) {
            $menuArray2[]=array($key=>$remain_pri);
        }
        
        #render the menus in the order derived from above
        $result="<ul class='nav_menu'>";
        foreach ($menuArray2 as $top_level) { 
            foreach ($top_level as $name=>$value) {
                $top_menu_name=str_replace($char_replace,'_',$name);
                $result.="<li id='menu_".$top_menu_name."' class='nav_menu_top'>$name";
                if(count($value)>0) {
                    $result.="<ul class='nav_menu_sec'>";
                    foreach ($value as $subMenu) {
                        $parts=explode("|",$subMenu);
                        $tab_label='New Tab';
                        $subMenu='';
                        if (count($parts)<2) {
                            $subMenu=$parts[0];
                        }
                        else {
                            $tab_label=$parts[0];
                            $subMenu=$parts[1];
                        }
                        $second_menu_name=$top_menu_name."_".str_replace($char_replace,'_',$subMenu);
                        $result.="<li id='".$second_menu_name."' name='".$tab_label."' class='nav_menu_bot'>$subMenu</li>";
                    }
                    $result.="</ul>";
                }
                $result.="</li>";
            }
        }
        $result.="</ul>";
        return $result;
    }
    
}
?>
