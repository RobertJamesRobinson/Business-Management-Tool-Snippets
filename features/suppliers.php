<?php
/*
Handles the basic Suppliers feature module. This module has no dependencies. 
*/
class Suppliers {
    protected $db;
    private $messages;
    
    //standard constructor, takes a database connection
    public function __construct(&$db) {
        $this->db=$db;
        include_once('./forms/basic_form.php');
        
        //Install this feature
        $this->install();
    }
    
    //installs the table needed for this feature
    public function install() {
        #table definitions
        $sqls=array();
        $sqls['supplierStatus']=array();
        $sqls['supplierStatus'][]="CREATE TABLE IF NOT EXISTS supplierStatus ( supplierStatus_id INT NOT NULL AUTO_INCREMENT, description VARCHAR(50) NULL, PRIMARY KEY (supplierStatus_id)) ENGINE = InnoDB";
        $sqls['supplierStatus'][]="INSERT INTO supplierStatus (description) VALUES ('active'), ('inactive')";
        $sqls['supplier']=array();
        $sqls['supplier'][]="CREATE TABLE IF NOT EXISTS supplier ( supplier_id INT NOT NULL AUTO_INCREMENT, name VARCHAR(255) NULL, street_number VARCHAR(10) NULL, street_address VARCHAR(255) NULL, suburb VARCHAR(50) NULL, state VARCHAR(50) NULL, country VARCHAR(50) NULL, postcode VARCHAR(15) NULL, mobile_phone VARCHAR(45) NULL, work_phone VARCHAR(45) NULL, fax_phone VARCHAR(45) NULL, email VARCHAR(255) NULL, website VARCHAR(255) NULL, supplierStatus_id INT NOT NULL, notes BLOB NULL, PRIMARY KEY (supplier_id), INDEX fk_supplier_supplierStatus1_idx (supplierStatus_id ASC), CONSTRAINT fk_supplier_supplierStatus1 FOREIGN KEY (supplierStatus_id) REFERENCES supplierStatus (supplierStatus_id) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB";
        
        #check if theyre installed
        $sql="show tables";
        $installed_tables=$this->db->query($sql);
        foreach ($installed_tables as $table) {
            foreach ($table as $heading=>$table_name) {
                if (array_key_exists($table_name,$sqls)) {
                    unset($sqls[$table_name]);
                }
            }
        }
        
        #install as required
        foreach ($sqls as $sql) {
            if(is_array($sql)) {
                foreach ($sql as $indiv_query) {
                    $this->db->query($indiv_query);
                }
            }
            else {
                $this->db->query($sql);
            }
        }
    }
    
    //returns a list of dependencies that are required for this feature to exist and make sense. Top level features that have no dependencies will return NULL
    public function get_dependencies() {
        return [];
    }
    
    //returns a list of the tables for this feature which would need to be updated before a record is removed from the dependant feature
    //green links in mysql workbench
    public function get_dependant_tables() {
        return ['supplierStatus'];
    }
    
    //returns the messages object data
    public function get_messages() {
        return $this->messages;
    }
    
    //returns the message object data as a list of strings which can be near immediately rendered to the user
    public function get_message_strings() {
        $result='';
        foreach($this->messages as $singleMessage) {
            $result.=$singleMessage;
        }
        return $result;
    }
    
    //returns the page, including JS, requested by the page_string
    public function get_page($page_string) {
        $result=NULL;
        switch ($page_string) {
            case "ManageOverdueSupplies":
                $result=$this->page_ManageOverdueSupplies();
                break;
            case "EditSupplierDetails":
                $result=$this->page_EditSupplierDetails();
                break;
            case "AddNewSupplier":
                $result=$this->page_AddNewSupplier();
                break;
            case "RecordPurchase":
                $result=$this->page_RecordPurchase();
                break;
            case "SubmitAddNewSupplier":
                $result=$this->submit_SubmitAddNewSupplier();
                break;
            case "LoadEditSupplierDetailsData":
                $result=$this->LoadEditSupplierDetailsData();
                break;
            case "UpdateEditSupplierDetails":
                $result=$this->submit_UpdateEditSupplierDetails();
                break;
            
        }
        return $result;
    }
    
    //returns the menu items associated with this feature, the format is array of arrays, each level is a sub menu of the preceeding level
    public function get_menus() {
        $result=array();
        $result['Suppliers']=array('Manage Supplies|Manage Overdue Supplies','Edit Supplier|Edit Supplier Details','Add Supplier|Add New Supplier','New Purchase|Record Purchase');
        return $result;
    }
    
    //returns the actual page content for managing overdue supplies
    private function page_ManageOverdueSupplies() {
        return "<p>content for the page_ManageOverdueSupplies</p>";
    }
    
    //handles submission of supplier details edits and updates
    private function submit_UpdateEditSupplierDetails() {
        $supplier_id=       $this->db->clean($_REQUEST['supplier_id']);
        $name=              $this->db->clean($_REQUEST['name']);
        $street_number=     $this->db->clean($_REQUEST['number']);
        $street_name=       $this->db->clean($_REQUEST['street_name']);
        $suburb=            $this->db->clean($_REQUEST['suburb']);
        $state=             $this->db->clean($_REQUEST['state']);
        $postcode=          $this->db->clean($_REQUEST['postcode']);
        $country=           $this->db->clean($_REQUEST['country']);
        $mobile_phone=      $this->db->clean($_REQUEST['mobile']);
        $work_phone=        $this->db->clean($_REQUEST['work_phone']);
        $fax_phone=         $this->db->clean($_REQUEST['fax']);
        $email=             $this->db->clean($_REQUEST['email']);
        $website=           $this->db->clean($_REQUEST['website']);
        $supplier_status=   $this->db->clean($_REQUEST['supplier_status']);
        $notes=             $this->db->clean($_REQUEST['notes']);
        
        //get the status string
        $status_string=$this->db->query("select description from supplierStatus where supplierStatus_id='$supplier_status'")[0]['description'];
        
        #update the data
        $sql="UPDATE supplier set name='$name',street_number='$street_number',street_address='$street_name', suburb='$suburb',state='$state',country='$country',postcode='$postcode',
        mobile_phone='$mobile_phone',work_phone='$work_phone',fax_phone='$fax_phone',email='$email',website='$website',supplierStatus_id='$supplier_status',notes='$notes' where supplier_id=$supplier_id";
        $update_result=$this->db->query($sql);
        
        #either return the last event log or the error message
        if ($update_result=='') {
            $this->db->add_event("Supplier details updated: $name of address $street_number $street_name $suburb $state $postcode $country, now: $status_string on supplier_id: $supplier_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $update_result;
        }
    }
    
    //returns the details needed for a single supplier form
    private function LoadEditSupplierDetailsData() {
        $supplier_id=$this->db->clean($_REQUEST['supplier_id']);
        return json_encode($this->db->query("select supplier_id,name,street_number as number,street_address as street_name,suburb,state,country,postcode,mobile_phone as mobile,work_phone,fax_phone as fax,email,website,supplierStatus_id as supplier_status, notes from supplier where supplier_id='$supplier_id'")[0]);
    }
    
    //returns the actual page content for editing existing supplier details
    private function page_EditSupplierDetails() {
        $esf=new BasicForm($this->db);
        $esf->set_formname('Edit Supplier Details');
        $esf->set_heading('Edit Supplier Details');
        $esf->set_feature('Suppliers');
        $esf->set_selector_title('Edit Supplier');
        $esf->set_full_field_list(array('supplier_id','name','number','street_name','suburb','state','country','postcode','mobile','work_phone','fax','email','website', 'supplier_status','notes'));
        $esf->set_selector_field_list(array('name','address','suburb','state','postcode'));
        $esf->set_selector_query("select supplier_id, name, concat(street_number,' ',street_address) as address, suburb, state, postcode from supplier");
        $esf->set_id_field('supplier_id');
        $esf->set_page_buttons(array('update','cancel'));
        $esf->add_group('name');
        $esf->add_text_field_to_group('name','name',300);
        $esf->add_group('address');
        $esf->add_text_field_to_group('address','number',50);
        $esf->add_text_field_to_group('address','street name',300);
        $esf->add_text_field_to_group('address','suburb',100);
        $esf->add_text_field_to_group('address','state',50);
        $esf->add_text_field_to_group('address','postcode',50);
        $esf->add_text_field_to_group('address','country',100);
        $esf->add_group('contact details');
        $esf->add_text_field_to_group('contact details','mobile',100);
        $esf->add_text_field_to_group('contact details','work phone',100);
        $esf->add_text_field_to_group('contact details','fax',100);
        $esf->add_text_field_to_group('contact details','email',300);
        $esf->add_text_field_to_group('contact details','website',300);
        $esf->add_group('administrative details');
        $esf->add_combo_field_to_group('administrative details','supplier status','supplierStatus|supplierStatus_id|description');
        $esf->add_textarea_field_to_group('administrative details','notes',10,50);
        return $esf->render_page();
    }
    
    //returns the actual page content for adding new supplier details
    private function page_AddNewSupplier() {
        $ansf=new BasicForm($this->db);
        $ansf->set_formname('Add New Supplier');
        $ansf->set_heading('Add New Supplier');
        $ansf->set_feature('Suppliers');
        $ansf->set_page_buttons(array('save','cancel'));
        $ansf->add_group('name');
        $ansf->add_text_field_to_group('name','name',300);
        $ansf->add_group('address');
        $ansf->add_text_field_to_group('address','number',50);
        $ansf->add_text_field_to_group('address','street name',300);
        $ansf->add_text_field_to_group('address','suburb',100);
        $ansf->add_text_field_to_group('address','state',50);
        $ansf->add_text_field_to_group('address','postcode',50);
        $ansf->add_text_field_to_group('address','country',100);
        $ansf->add_group('contact details');
        $ansf->add_text_field_to_group('contact details','mobile',100);
        $ansf->add_text_field_to_group('contact details','work phone',100);
        $ansf->add_text_field_to_group('contact details','fax',100);
        $ansf->add_text_field_to_group('contact details','email',300);
        $ansf->add_text_field_to_group('contact details','website',300);
        $ansf->add_group('administrative details');
        $ansf->add_combo_field_to_group('administrative details','supplier status','supplierStatus|supplierStatus_id|description');
        $ansf->add_textarea_field_to_group('administrative details','notes',10,50);
        return $ansf->render_page();
    }
    
    //returns the actual page content for recording new purchases
    private function page_RecordPurchase() {
        return "<p>content for the page_RecordPurchase</p>";
    }
    
    //handles form submission for adding a new supplier
    private function submit_SubmitAddNewSupplier() {
        #sanitise the data
        $name=              $this->db->clean($_REQUEST['name']);
        $street_number=     $this->db->clean($_REQUEST['number']);
        $street_name=       $this->db->clean($_REQUEST['street_name']);
        $suburb=            $this->db->clean($_REQUEST['suburb']);
        $state=             $this->db->clean($_REQUEST['state']);
        $postcode=          $this->db->clean($_REQUEST['postcode']);
        $country=           $this->db->clean($_REQUEST['country']);
        $mobile_phone=      $this->db->clean($_REQUEST['mobile']);
        $work_phone=        $this->db->clean($_REQUEST['work_phone']);
        $fax_phone=         $this->db->clean($_REQUEST['fax']);
        $email=             $this->db->clean($_REQUEST['email']);
        $website=           $this->db->clean($_REQUEST['website']);
        $supplier_status=   $this->db->clean($_REQUEST['supplier_status']);
        $notes=             $this->db->clean($_REQUEST['notes']);
        
        #insert the data
        $sql="INSERT INTO supplier (name,street_number,street_address,suburb,state,country,postcode,mobile_phone,work_phone,fax_phone,email,website,supplierStatus_id,notes) 
            values ('$name','$street_number','$street_name','$suburb','$state','$country','$postcode','$mobile_phone','$work_phone','$fax_phone','$email','$website','$supplier_status','$notes')";
        $insert_result=$this->db->query($sql);
        #either return the last event log or the error message
        if ($insert_result=='') {
            $this->db->add_event("New Supplier added: $name of address $street_number $street_name $suburb $state $postcode $country");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $insert_result;
        }
    }
    
}
?>
