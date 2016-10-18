<?php
/*
Handles the basic staff feature module. This module has no dependencies.
*/
class Staff {
    protected $db;
    private $messages;
    
    //standard constructor, takes a database connection
    public function __construct(&$db) {
        $this->db=$db;
        include_once('./forms/basic_form.php');
        include_once('./forms/expanding_rows_form.php');
        
        //Install this feature
        $this->install();
    }
    
    //installs the table needed for this feature
    public function install() {
        #table definitions
        $sqls=array();
        $sqls['staffStatus']=array();
        $sqls['staffStatus'][]="CREATE TABLE IF NOT EXISTS staffStatus ( staffStatus_id INT NOT NULL AUTO_INCREMENT, description VARCHAR(50) NULL, PRIMARY KEY (staffStatus_id)) ENGINE = InnoDB";
        $sqls['staffStatus'][]="INSERT INTO staffStatus (description) VALUES ('active'), ('inactive')";
        $sqls['salutation']=array();
        $sqls['salutation'][]="CREATE TABLE IF NOT EXISTS salutation ( salutation_id INT NOT NULL AUTO_INCREMENT, gender VARCHAR(7) NULL, description VARCHAR(15) NULL, PRIMARY KEY (salutation_id)) ENGINE = InnoDB";
        $sqls['salutation'][]="INSERT INTO salutation (gender,description) VALUES ('male','Mr'), ('female','Miss'),('female','Mrs'),('female','Ms'),('female','Mz'),('unknown','Dr'),('unknown','Prof')";
        $sqls['staffRole']=array();
        $sqls['staffRole'][]="CREATE TABLE IF NOT EXISTS staffRole ( staffRole_id INT NOT NULL AUTO_INCREMENT, description VARCHAR(255) NULL, notes BLOB NULL, PRIMARY KEY (staffRole_id)) ENGINE = InnoDB";
        $sqls['staffRole'][]="INSERT INTO staffRole (description,notes) VALUES ('Basic Staff','Default job description, ideally you would add more specific job roles so that tasks can more accurately be assigned to staff.')";
        $sqls['staff']="CREATE TABLE IF NOT EXISTS staff ( staff_id INT NOT NULL AUTO_INCREMENT, salutation_id INT NOT NULL, first_name VARCHAR(255) NULL, last_name VARCHAR(255) NULL, username VARCHAR(255) NULL, password VARCHAR(255) NULL, staffStatus_id INT NOT NULL, staffRole_id INT NOT NULL, street_number VARCHAR(10) NULL, street_address VARCHAR(255) NULL, suburb VARCHAR(50) NULL, state VARCHAR(50) NULL, country VARCHAR(50) NULL, postcode VARCHAR(15) NULL, mobile_phone VARCHAR(45) NULL, home_phone VARCHAR(45) NULL, fax_phone VARCHAR(45) NULL, email VARCHAR(255) NULL, website VARCHAR(255) NULL, PRIMARY KEY (staff_id), INDEX fk_staff_staffStatus1_idx (staffStatus_id ASC), INDEX fk_staff_salutation1_idx (salutation_id ASC), INDEX fk_staff_staffRole1_idx (staffRole_id ASC), CONSTRAINT fk_staff_staffStatus1 FOREIGN KEY (staffStatus_id) REFERENCES staffStatus (staffStatus_id) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT fk_staff_salutation1 FOREIGN KEY (salutation_id) REFERENCES salutation (salutation_id) ON DELETE NO ACTION ON UPDATE NO ACTION, CONSTRAINT fk_staff_staffRole1 FOREIGN KEY (staffRole_id) REFERENCES staffRole (staffRole_id) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB";
        
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
    
    //returns a list of dependencies that are required for this feature to exist and make sense. Top level features that have no dependencies will return an empty array
    //green links in mysql workbench
    public function get_dependencies() {
        return [];
    }
    
    //returns a list of the tables for this feature which would need to be updated before a record is removed from the dependant feature
    //green links in mysql workbench
    public function get_dependant_tables() {
        return ['staffStatus','salutation','staffRole'];
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
            case "AddNewStaffMember":
                $result=$this->page_AddNewStaffMember();
                break;
            case "SubmitAddNewStaff":
                $result=$this->SubmitAddNewStaff();
                break;
            case "EditStaffDetails":
                $result=$this->page_EditStaffDetails();
                break;
            case "staffidSelectorDialog":
                $result=$this->staffidSelectorDialog();
                break;
            case "LoadEditStaffDetailsData":
                $result=$this->LoadSingleStaffDetailsData();
                break;
            case "LoadDeleteStaffMemberData":
                $result=$this->LoadSingleStaffDetailsData();
                break;
            case "UpdateEditStaffDetails":
                $result=$this->UpdateEditStaffDetails();
                break;
            case "DeleteStaffMember":
                $result=$this->page_DeleteStaffMember();
                break;
            case "DeleteDeleteStaffMember":
                $result=$this->delete_DeleteDeleteStaffMember();
                break;
            case "ManageSalutations":
                $result=$this->page_ManageSalutations();
                break;
            case "SubmitManageSalutations":
                $result=$this->submit_ManageSalutations();
                break;
            case "ManageStaffRoles":
                $result=$this->page_ManageStaffRoles();
                break;
            case "SubmitManageStaffRoles":
                $result=$this->submit_SubmitManageStaffRoles();
                break;
        }
        return $result;
    }
    
    //returns the menu items associated with this feature, the format is array of arrays, each level is a sub menu of the preceeding level
    public function get_menus() {
        $result=array();
        $result['Staff']=array('Add Staff|Add New Staff Member');
        $result['Admin']=array('Edit Staff|Edit Staff Details','Delete Staff|Delete Staff Member','Edit Titles|Manage Salutations','Edit Roles|Manage Staff Roles');
        return $result;
    }
    
    //handles the edit staff details page generation, includes the selector
    private function page_EditStaffDetails() {
        $esf=new BasicForm($this->db);
        $esf->set_formname('Edit Staff Details');
        $esf->set_heading('Edit Staff Details');
        $esf->set_feature('Admin');
        $esf->set_selector_title('Edit Staff');
        $esf->set_full_field_list(array('staff_id','title','first_name','last_name','employee_status','employee_role','number','street_name','suburb','state','postcode','mobile','home_phone','fax','email','website'));
        $esf->set_selector_field_list(array('title','name','address','suburb','state','postcode'));
        $esf->set_selector_query("select s.staff_id as staff_id, sa.description as title, concat(s.first_name,' ',s.last_name) as name, concat(s.street_number,' ',s.street_address) as address, s.suburb as suburb, s.state as state, s.postcode as postcode from staff s left join salutation sa on s.salutation_id=sa.salutation_id left join staffStatus ss on s.staffStatus_id=ss.staffStatus_id where ss.description in ('active','inactive');");
        $esf->set_id_field('staff_id');
        $esf->set_page_buttons(array('update','cancel'));
        $esf->add_group('name');
        $esf->add_combo_field_to_group('name','title','salutation|salutation_id|description');
        $esf->add_text_field_to_group('name','first name',300);
        $esf->add_text_field_to_group('name','last name',300);
        $esf->add_group('address');
        $esf->add_text_field_to_group('address','number',50);
        $esf->add_text_field_to_group('address','street name',300);
        $esf->add_text_field_to_group('address','suburb',100);
        $esf->add_text_field_to_group('address','state',50);
        $esf->add_text_field_to_group('address','postcode',50);
        $esf->add_group('contact details');
        $esf->add_text_field_to_group('contact details','mobile',100);
        $esf->add_text_field_to_group('contact details','home phone',100);
        $esf->add_text_field_to_group('contact details','fax',100);
        $esf->add_text_field_to_group('contact details','email',300);
        $esf->add_text_field_to_group('contact details','website',300);
        $esf->add_group('administrative details');
        $esf->add_combo_field_to_group('administrative details','employee status','staffStatus|staffStatus_id|description');
        $esf->add_combo_field_to_group('administrative details','employee role','staffRole|staffRole_id|description');
        return $esf->render_page();
    }
    
    //handles the preparation of a delete staff member page, with selector
    private function page_DeleteStaffMember() {
        $dsf=new BasicForm($this->db);
        $dsf->set_formname('Delete Staff Member');
        $dsf->set_heading('Delete Staff Details');
        $dsf->set_feature('Admin');
        $dsf->set_selector_title('Delete Staff');
        $dsf->set_full_field_list(array('staff_id','title','first_name','last_name','employee_status','employee_role','number','street_name','suburb','state','postcode','mobile','home_phone','fax','email','website'));
        $dsf->set_selector_field_list(array('title','name','address','suburb','state','postcode'));
        $dsf->set_selector_query("select s.staff_id as staff_id, sa.description as title, concat(s.first_name,' ',s.last_name) as name, concat(s.street_number,' ',s.street_address) as address, s.suburb as suburb, s.state as state, s.postcode as postcode from staff s left join salutation sa on s.salutation_id=sa.salutation_id");
        $dsf->set_id_field('staff_id');
        $dsf->set_page_buttons(array('delete','cancel'));
        $dsf->add_group('name');
        $dsf->add_combo_field_to_group('name','title','salutation|salutation_id|description');
        $dsf->add_text_field_to_group('name','first name',300);
        $dsf->add_text_field_to_group('name','last name',300);
        $dsf->add_group('address');
        $dsf->add_text_field_to_group('address','number',50);
        $dsf->add_text_field_to_group('address','street name',300);
        $dsf->add_text_field_to_group('address','suburb',100);
        $dsf->add_text_field_to_group('address','state',50);
        $dsf->add_text_field_to_group('address','postcode',50);
        $dsf->add_group('contact details');
        $dsf->add_text_field_to_group('contact details','mobile',100);
        $dsf->add_text_field_to_group('contact details','home phone',100);
        $dsf->add_text_field_to_group('contact details','fax',100);
        $dsf->add_text_field_to_group('contact details','email',300);
        $dsf->add_text_field_to_group('contact details','website',300);
        $dsf->add_group('administrative details');
        $dsf->add_combo_field_to_group('administrative details','employee status','staffStatus|staffStatus_id|description');
        $dsf->add_combo_field_to_group('administrative details','employee role','staffRole|staffRole_id|description');
        return $dsf->render_page();
    }
    
    //returns the actual page content for sub menu 1
    private function page_AddNewStaffMember() {
        $ansf=new BasicForm($this->db);
        $ansf->set_formname('Add New Staff');
        $ansf->set_heading('Add New Staff');
        $ansf->set_feature('Staff');
        $ansf->set_page_buttons(array('save','cancel'));
        $ansf->add_group('name');
        $ansf->add_combo_field_to_group('name','title','salutation|salutation_id|description');
        $ansf->add_text_field_to_group('name','first name',300);
        $ansf->add_text_field_to_group('name','last name',300);
        $ansf->add_group('address');
        $ansf->add_text_field_to_group('address','number',50);
        $ansf->add_text_field_to_group('address','street name',300);
        $ansf->add_text_field_to_group('address','suburb',100);
        $ansf->add_text_field_to_group('address','state',50);
        $ansf->add_text_field_to_group('address','postcode',50);
        $ansf->add_group('contact details');
        $ansf->add_text_field_to_group('contact details','mobile',100);
        $ansf->add_text_field_to_group('contact details','home phone',100);
        $ansf->add_text_field_to_group('contact details','fax',100);
        $ansf->add_text_field_to_group('contact details','email',300);
        $ansf->add_text_field_to_group('contact details','website',300);
        $ansf->add_group('administrative details');
        $ansf->add_combo_field_to_group('administrative details','employee status','staffStatus|staffStatus_id|description');
        $ansf->add_combo_field_to_group('administrative details','employee role','staffRole|staffRole_id|description');
        return $ansf->render_page();
    }
    
    //render the page for managing salutations, directly from the staff feature
    private function page_ManageSalutations() {
        $msf=new ExpandingRowsForm($this->db);
        $msf->set_feature("Admin");
        $msf->set_source_table("salutation");
        $msf->new_text_field('Salutation','description',150);
        $msf->new_text_field('Gender','gender',100);
        $msf->set_id_column("salutation_id");
        $msf->set_form_name("Manage Salutations");
        return $msf->render_form();
    }
    
    private function page_ManageStaffRoles() {
        $msr=new ExpandingRowsForm($this->db);
        $msr->set_feature("Admin");
        $msr->set_source_table("staffRole");
        $msr->new_text_field('Role Description','description',350);
        $msr->new_textarea_field('Notes','notes',4,40);
        $msr->set_id_column("staffRole_id");
        $msr->set_form_name("Manage Staff Roles");
        return $msr->render_form();
    }
    
    //loads the field data needed to populate a staff form. responds in JSON
    private function LoadSingleStaffDetailsData() {
        $staff_id=$this->db->clean($_REQUEST['staff_id']);
        return json_encode($this->db->query("select staff_id,salutation_id as title,first_name,last_name,staffStatus_id as employee_status,staffRole_id as employee_role,street_number as number,street_address as street_name,suburb,state,postcode,mobile_phone as mobile,home_phone,fax_phone as fax,email,website from staff where staff_id='$staff_id'")[0]);
    }
    
    //handles the deletion of a staff member by staff_id
    private function delete_DeleteDeleteStaffMember() {
        $staff_id=$this->db->clean($_REQUEST['staff_id']);
        
        #get the staff member details for the log message
        $select_result=$this->db->query("SELECT * FROM staff where staff_id='$staff_id'");
        $message=$select_result[0]['first_name']." ".$select_result[0]['last_name']." of ".$select_result[0]['street_number']." ".$select_result[0]['street_address'];
        
        //get the inactive staffStatus_id
        $inactive_id=$this->db->query("select staffStatus_id from staffStatus where description='inactive'")[0]['staffStatus_id'];
        
        $delete_result=$this->db->query("DELETE FROM staff where staff_id='$staff_id'");
        
        #either return the last event log or the error message
        if ($delete_result=='') {
            $this->db->add_event("Staff member DELETED: $message, staff_id $staff_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $delete_result;
        }
    }
    
    //handle an update to staff details request
    private function UpdateEditStaffDetails() {
        $email=             $this->db->clean($_REQUEST['email']);
        $employee_role=     $this->db->clean($_REQUEST['employee_role']);
        $employee_status=   $this->db->clean($_REQUEST['employee_status']);
        $fax=               $this->db->clean($_REQUEST['fax']);
        $first_name=        $this->db->clean($_REQUEST['first_name']);
        $home_phone=        $this->db->clean($_REQUEST['home_phone']);
        $last_name=         $this->db->clean($_REQUEST['last_name']);
        $mobile=            $this->db->clean($_REQUEST['mobile']);
        $number=            $this->db->clean($_REQUEST['number']);
        $postcode=          $this->db->clean($_REQUEST['postcode']);
        $staff_id=          $this->db->clean($_REQUEST['staff_id']);
        $state=             $this->db->clean($_REQUEST['state']);
        $street_name=       $this->db->clean($_REQUEST['street_name']);
        $suburb=            $this->db->clean($_REQUEST['suburb']);
        $title=             $this->db->clean($_REQUEST['title']);
        $website=           $this->db->clean($_REQUEST['website']);
        $country=           "Australia";
        
        //get the role description
        $role_desc=$this->db->query("select description from staffRole where staffRole_id=$employee_role")[0]['description'];
        
        //get the status string
        $status_string=$this->db->query("select description from staffStatus where staffStatus_id='$employee_status'")[0]['description'];
        
        #update the data
        $sql="UPDATE staff set salutation_id=$title,staffStatus_id='$employee_status',staffRole_id='$employee_role',first_name='$first_name',last_name='$last_name',street_number='$number',street_address='$street_name',suburb='$suburb',state='$state',country='$country',postcode='$postcode',
        mobile_phone='$mobile',fax_phone='$fax',email='$email',website='$website',home_phone='$home_phone' where staff_id=$staff_id";
        $update_result=$this->db->query($sql);
        
        #either return the last event log or the error message
        if ($update_result=='') {
            $this->db->add_event("Staff details updated: $first_name $last_name, $role_desc, of address $number $street_name $suburb $state $postcode $country, now: $status_string on staff_id: $staff_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $update_result;
        }
    }
    
    #takes the form data from a "new client"page and submits it to the DB
    private function SubmitAddNewStaff() {
        #sanitise the data
        $salutation=        $this->db->clean($_REQUEST['title']);
        $first_name=        $this->db->clean($_REQUEST['first_name']);
        $last_name=         $this->db->clean($_REQUEST['last_name']);
        $staff_status_id=   $this->db->clean($_REQUEST['employee_status']);
        $staff_role_id=     $this->db->clean($_REQUEST['employee_role']);
        $street_number=     $this->db->clean($_REQUEST['number']);
        $street_name=       $this->db->clean($_REQUEST['street_name']);
        $suburb=            $this->db->clean($_REQUEST['suburb']);
        $state=             $this->db->clean($_REQUEST['state']);
        $postcode=          $this->db->clean($_REQUEST['postcode']);
        $mobile_phone=      $this->db->clean($_REQUEST['mobile']);
        $home_phone=        $this->db->clean($_REQUEST['home_phone']);
        $fax_phone=         $this->db->clean($_REQUEST['fax']);
        $email=             $this->db->clean($_REQUEST['email']);
        $website=           $this->db->clean($_REQUEST['website']);
        $country=           "Australia";
        
        //get the role description
        $role_desc=$this->db->query("select description from staffRole where staffRole_id=$staff_role_id")[0]['description'];
        
        #insert the data
        $sql="INSERT INTO staff (salutation_id,first_name,last_name,staffStatus_id,staffRole_id,street_number,street_address,suburb,state,country,postcode,mobile_phone,home_phone,fax_phone,email,website) 
            values ($salutation,'$first_name','$last_name','$staff_status_id','$staff_role_id','$street_number','$street_name','$suburb','$state','$country','$postcode','$mobile_phone','$home_phone','$fax_phone','$email','$website')";
        $insert_result=$this->db->query($sql);
        #either return the last event log or the error message
        if ($insert_result=='') {
            $this->db->add_event("New Staff added: $first_name $last_name, $role_desc, of address $street_number $street_name $suburb $state $postcode $country");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $insert_result;
        }
    }
    
    //handle the submission of new employment types form responses
    private function submit_ManageSalutations() {
        $existing_data=json_decode($_REQUEST['existing_data'],true);
        $new_data=json_decode($_REQUEST['new_data'],true);
        $tobedeleted_data=json_decode($_REQUEST['tobedeleted_data'],true);
        $number_changed=0;
        $last_event_message='';
        $error='';
        
        #deal with existing data first, only update fields that have changed
        foreach ($existing_data as $single_entry) {
            $id=            $this->db->clean($single_entry['id']);
            $description=   $this->db->clean($single_entry['description']);
            $gender=        $this->db->clean($single_entry['gender']);
            
            # has it changed?
            $change_query=$this->db->query("select description,gender from salutation where salutation_id='$id'");
            $old_description=$change_query[0]['description'];
            $old_gender=$change_query[0]['gender'];
            
            if ($old_description!=$description or $old_gender!=$gender) {
                $sql="UPDATE salutation set description='$description', gender='$gender' where salutation_id='$id'";
                $update_result=$this->db->query($sql);
                if ($update_result=='') {
                    $this->db->add_event("Salutation updated from: $old_description, $old_gender to: $description, $gender on salutation_id: $id");
                    $number_changed+=1;
                    $last_event_message.=$this->db->get_last_events(1);
                }
                else {
                    $error.=$update_result;
                }
            }
        }
        
        #now delete any "to be deleted" records
        foreach ($tobedeleted_data as $single_entry) {
            $id=            $this->db->clean($single_entry['id']);
            $description=   $this->db->clean($single_entry['description']);
            $gender=        $this->db->clean($single_entry['gender']);
            
            $sql="DELETE FROM salutation WHERE salutation_id='$id'";
            $delete_result=$this->db->query($sql);
            if ($delete_result=='') {
                $this->db->add_event("Salutation deleted: $description,$gender");
                $number_changed+=1;
                $last_event_message.=$this->db->get_last_events(1);
            }
            else {
                $error.=$delete_result;
            }
        }
        
        #now insert new fields, but only if there is a non blank description
        foreach ($new_data as $single_entry) {
            $description=   $this->db->clean($single_entry['description']);
            $gender=        $this->db->clean($single_entry['gender']);
            
            if ($description!="") {
                $sql="INSERT INTO salutation (description,gender) VALUES ('$description','$gender')";
                $insert_result=$this->db->query($sql);
                if ($insert_result=='') {
                    $this->db->add_event("Salutation added: $description,$gender");
                    $number_changed+=1;
                    $last_event_message.=$this->db->get_last_events(1);
                }
                else {
                    $error.=$insert_result;
                }
            }
        }
        return $error.$last_event_message;
    }
    
    //the handler for managing the staff roles data
    private function submit_SubmitManageStaffRoles() {
        $existing_data=json_decode($_REQUEST['existing_data'],true);
        $new_data=json_decode($_REQUEST['new_data'],true);
        $tobedeleted_data=json_decode($_REQUEST['tobedeleted_data'],true);
        $number_changed=0;
        $last_event_message='';
        $error='';
        
        #deal with existing data first, only update fields that have changed
        foreach ($existing_data as $single_entry) {
            $id=            $this->db->clean($single_entry['id']);
            $description=   $this->db->clean($single_entry['description']);
            $notes=         $this->db->clean($single_entry['notes']);
            
            # has it changed?
            $change_query=      $this->db->query("select description,notes from staffRole where staffRole_id='$id'");
            $old_description=   $change_query[0]['description'];
            $old_notes=         $change_query[0]['notes'];
            
            if ($old_description!=$description or $old_notes!=$notes) {
                $sql="UPDATE staffRole set description='$description', notes='$notes' where staffRole_id='$id'";
                $update_result=$this->db->query($sql);
                if ($update_result=='') {
                    $this->db->add_event("Staff Role updated from: $old_description, $old_notes to: $description, $notes on staffRole_id: $id");
                    $number_changed+=1;
                    $last_event_message.=$this->db->get_last_events(1);
                }
                else {
                    $error.=$update_result;
                }
            }
        }
        
        #now delete any "to be deleted" records
        foreach ($tobedeleted_data as $single_entry) {
            $id=            $this->db->clean($single_entry['id']);
            $description=   $this->db->clean($single_entry['description']);
            $notes=         $this->db->clean($single_entry['notes']);
            
            $sql="DELETE FROM staffRole WHERE staffRole_id='$id'";
            $delete_result=$this->db->query($sql);
            if ($delete_result=='') {
                $this->db->add_event("Staff Role deleted: $description,$notes");
                $number_changed+=1;
                $last_event_message.=$this->db->get_last_events(1);
            }
            else {
                $error.=$delete_result;
            }
        }
        
        #now insert new fields, but only if there is a non blank description
        foreach ($new_data as $single_entry) {
            $description=   $this->db->clean($single_entry['description']);
            $notes=         $this->db->clean($single_entry['notes']);
            
            if ($description!="") {
                $sql="INSERT INTO staffRole (description,notes) VALUES ('$description','$notes')";
                $insert_result=$this->db->query($sql);
                if ($insert_result=='') {
                    $this->db->add_event("Staff Role added: $description,$notes");
                    $number_changed+=1;
                    $last_event_message.=$this->db->get_last_events(1);
                }
                else {
                    $error.=$insert_result;
                }
            }
        }
        return $error.$last_event_message;
    }
}
?>
