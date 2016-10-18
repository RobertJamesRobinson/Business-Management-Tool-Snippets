<?php
/*
Handles the basic clients feature module. This module has no dependencies
*/
class Clients {
    protected $db;
    private $messages;
    
    //standard constructor, takes a database connection
    public function __construct(&$db) {
        $this->db=$db;
        $this->install();
    }
    
    //installs the table needed for this feature
    public function install() {
        #table definitions
        $sqls=array();
        $sqls['salutation']=array();
        $sqls['salutation'][]="CREATE TABLE IF NOT EXISTS salutation ( salutation_id INT NOT NULL AUTO_INCREMENT, gender VARCHAR(7) NULL, description VARCHAR(15) NULL, PRIMARY KEY (salutation_id)) ENGINE = InnoDB";
        $sqls['salutation'][]="INSERT INTO salutation (gender,description) VALUES ('male','Mr'), ('female','Miss'),('female','Mrs'),('female','Ms'),('female','Mz'),('unknown','Dr'),('unknown','Prof')";
        $sqls['client']="CREATE TABLE IF NOT EXISTS client ( client_id INT NOT NULL AUTO_INCREMENT, salutation_id INT NOT NULL, first_name VARCHAR(50) NULL, last_name VARCHAR(50) NULL, street_number VARCHAR(10) NULL, street_address VARCHAR(255) NULL, suburb VARCHAR(50) NULL, state VARCHAR(50) NULL, country VARCHAR(50) NULL, postcode VARCHAR(15) NULL, mobile_phone VARCHAR(45) NULL, home_phone VARCHAR(45) NULL, fax_phone VARCHAR(45) NULL, email VARCHAR(255) NULL, website VARCHAR(255) NULL, status VARCHAR(25) NULL, PRIMARY KEY (client_id), INDEX fk_client_salutation1_idx (salutation_id ASC), CONSTRAINT fk_client_salutation1 FOREIGN KEY (salutation_id) REFERENCES salutation (salutation_id) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB";
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
    public function get_dependant_tables() {
        return [];
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
    
    //returns the page, including JS, requested by the page_string
    public function get_page($page_string) {
        $result=NULL;
        switch ($page_string) {
            case "ViewClientDetails":
                $result=$this->page_ViewClientDetails();
                break;
            case "AddClient":
                $result=$this->page_AddClient();
                break;
            case "RemoveClient":
                $result=$this->page_RemoveClient();
                break;
            case "PermanentlyRemoveASingleClient":
                $result=$this->admin_PermanentlyRemoveASingleClient();
                break;
            case "UndeleteARetiredClient":
                $result=$this->admin_UndeleteARetiredClient();
                break;
            case "SubmitNewClient":
                $result=$this->SubmitNewClient();
                break;
            case "SubmitUpdateClient":
                $result=$this->SubmitUpdateClient();
                break;
            case "SubmitDeleteClient":
                $result=$this->SubmitDeleteClient();
                break;
            case "SubmitUndeleteClient":
                $result=$this->SubmitUndeleteClient();
                break;
            case "SubmitPurgeClient":
                $result=$this->SubmitPurgeClient();
                break;
            case "ClientSelectorDialog":
                $result=$this->ClientSelectorDialog();
                break;
            case "LoadClientData":
                $result=$this->LoadClientData();
                break;
                
        }
        return $result;
    }
    
    //returns the menu items associated with this feature
    public function get_menus() {
        $result=array();
        $result['Clients']=['Edit Client|View Client Details','Add Client|Add Client','Delete Client|Remove Client'];
        $result['Admin']=['Purge Client|Permanently Remove A Single Client','Undelete Client|Undelete A Retired Client'];
        return $result;
    }
    
    //returns the page for admin_PermanentlyRemoveASingleClient  #content_Admin_Permanently_Remove_A_Single_Client
    private function admin_PermanentlyRemoveASingleClient() {
        $salutations=$this->get_salutations();
        $salutations_render='';
        foreach ($salutations as $key=>$value) {
            $salutations_render.='<option value="'.$key.'">'.$value.'</option>';
        }
        $js='
            <script>
            
            $(document).ready(function(){
                
                //put the correct text in the dialog box
                $.ajax(
                {
                    type: "POST",
                    url: "page_responder.php",
                    data: {page:"Clients_Client_Selector_Dialog",status:"all"}
                })
                .done(function(e)
                {
                    $("#dialog").html(e);
                });
                $("#dialog").dialog("option","title","Select Client");
                $("#dialog").dialog("open");
            });
            
            //handle the save button
            $("#clientPurgeDeleteButton").click(function() {
                $.ajax({
                    type: "POST",
                    url: "page_responder.php",
                    data: 
                    {
                        page:"Clients_Submit_Purge_Client",
                        client_id:$("#content_Admin_Permanently_Remove_A_Single_Client_client_id").val(),
                    }
                })
                .done(function(e){
                    var tabs=$("div#tabs ul").children();
                    for (var i=0; i<tabs.length; i++) {
                        if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                            $("div#about").append(e);
                        }
                    }
                    close_current_tab();
                });
            });
            
            //handle the cancel button
            $("#clientPurgeCancelButton").click(function() {
                close_current_tab();
            });
            </script>';
        $html='
            <table class="page_container">
            <tr><td>
            <h1>Delete Client</h1>
            <form id="purgeClientForm" method="POST" >
                <table>
                    <tr><th class="sub_heading"><h2>Name</h2></th></tr>
                    <tr><th class="left_col">Title</th><td><select id="content_Admin_Permanently_Remove_A_Single_Client_salutation">'.$salutations_render.'</select></td></tr>
                    <tr><th class="left_col">First Name</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_firstname" type="text" /></td></tr>
                    <tr><th class="left_col">Last Name</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_lastname" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Address</h2></th></tr>
                    <tr><th class="left_col">Number</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_streetnumber" type="text" /></td></tr>
                    <tr><th class="left_col">Street Name</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_streetname" type="text" /></td></tr>
                    <tr><th class="left_col">Suburb</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_suburb" type="text" /></td></tr>
                    <tr><th class="left_col">State</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_state" type="text" /></td></tr>
                    <tr><th class="left_col">Postcode</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_postcode" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Contact Details</h2></th></tr>
                    <tr><th class="left_col">Mobile</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_mobilephone" type="text" /></td></tr>
                    <tr><th class="left_col">Home Phone</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_homephone" type="text" /></td></tr>
                    <tr><th class="left_col">Fax</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_faxphone" type="text" /></td></tr>
                    <tr><th class="left_col">Email</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_email" type="text" /></td></tr>
                    <tr><th class="left_col">Website</th><td><input id="content_Admin_Permanently_Remove_A_Single_Client_website" type="text" /></td></tr>
                    <tr><td class="left_col button_row"><button type="button" id="clientPurgeCancelButton">Cancel</button></td><td class="right_col button_row"><button type="button" id="clientPurgeDeleteButton">PURGE!</button></td></tr>
                </table>
            <input id="content_Admin_Permanently_Remove_A_Single_Client_client_id" type="hidden" value="" />
            </form>
            </td></tr>
            </table>';
        return $js.$html;
    }
    
    //returns the page for admin_UndeleteARetiredClient
    private function admin_UndeleteARetiredClient() {
        $salutations=$this->get_salutations();
        $salutations_render='';
        foreach ($salutations as $key=>$value) {
            $salutations_render.='<option value="'.$key.'">'.$value.'</option>';
        }
        $js='
            <script>
            
            $(document).ready(function(){
                
                //put the correct text in the dialog box
                $.ajax(
                {
                    type: "POST",
                    url: "page_responder.php",
                    data: {
                        page:"Clients_Client_Selector_Dialog",
                        status:"retired",
                    }
                })
                .done(function(e)
                {
                    $("#dialog").html(e);
                });
                $("#dialog").dialog("option","title","Select Client");
                $("#dialog").dialog("open");
            });
            
            //handle the save button
            $("#clientAdminUndeleteClientSaveButton").click(function() {
                $.ajax({
                    type: "POST",
                    url: "page_responder.php",
                    data: 
                    {
                        page:"Clients_Submit_Undelete_Client",
                        client_id:$("#content_Admin_Undelete_A_Retired_Client_client_id").val(),
                        salutation:$("#content_Admin_Undelete_A_Retired_Client_salutation").val(),
                        lastname:$("#content_Admin_Undelete_A_Retired_Client_lastname").val(),
                        streetnumber:$("#content_Admin_Undelete_A_Retired_Client_streetnumber").val(),
                        streetname:$("#content_Admin_Undelete_A_Retired_Client_streetname").val(),
                        suburb:$("#content_Admin_Undelete_A_Retired_Client_suburb").val(),
                        state:$("#content_Admin_Undelete_A_Retired_Client_state").val(),
                        postcode:$("#content_Admin_Undelete_A_Retired_Client_postcode").val(),
                        firstname:$("#content_Admin_Undelete_A_Retired_Client_firstname").val(),
                        mobilephone:$("#content_Admin_Undelete_A_Retired_Client_mobilephone").val(),
                        homephone:$("#content_Admin_Undelete_A_Retired_Client_homephone").val(),
                        faxphone:$("#content_Admin_Undelete_A_Retired_Client_faxphone").val(),
                        email:$("#content_Admin_Undelete_A_Retired_Client_email").val(),
                        website:$("#content_Admin_Undelete_A_Retired_Client_website").val()
                    }
                })
                .done(function(e){
                    var tabs=$("div#tabs ul").children();
                    for (var i=0; i<tabs.length; i++) {
                        if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                            $("div#about").append(e);
                        }
                    }
                    close_current_tab();
                });
            });
            
            //handle the cancel button
            $("#clientAdminUndeleteClientCancelButton").click(function() {
                close_current_tab();
            });
            </script>';
        $html='
            <table class="page_container">
            <tr><td>
            <h1>Undelete A Retired Client</h1>
            <form id="undeleteRetiredClientForm" method="POST" >
                <table>
                    <tr><th class="sub_heading"><h2>Name</h2></th></tr>
                    <tr><th class="left_col">Title</th><td><select id="content_Admin_Undelete_A_Retired_Client_salutation">'.$salutations_render.'</select></td></tr>
                    <tr><th class="left_col">First Name</th><td><input id="content_Admin_Undelete_A_Retired_Client_firstname" type="text" /></td></tr>
                    <tr><th class="left_col">Last Name</th><td><input id="content_Admin_Undelete_A_Retired_Client_lastname" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Address</h2></th></tr>
                    <tr><th class="left_col">Number</th><td><input id="content_Admin_Undelete_A_Retired_Client_streetnumber" type="text" /></td></tr>
                    <tr><th class="left_col">Street Name</th><td><input id="content_Admin_Undelete_A_Retired_Client_streetname" type="text" /></td></tr>
                    <tr><th class="left_col">Suburb</th><td><input id="content_Admin_Undelete_A_Retired_Client_suburb" type="text" /></td></tr>
                    <tr><th class="left_col">State</th><td><input id="content_Admin_Undelete_A_Retired_Client_state" type="text" /></td></tr>
                    <tr><th class="left_col">Postcode</th><td><input id="content_Admin_Undelete_A_Retired_Client_postcode" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Contact Details</h2></th></tr>
                    <tr><th class="left_col">Mobile</th><td><input id="content_Admin_Undelete_A_Retired_Client_mobilephone" type="text" /></td></tr>
                    <tr><th class="left_col">Home Phone</th><td><input id="content_Admin_Undelete_A_Retired_Client_homephone" type="text" /></td></tr>
                    <tr><th class="left_col">Fax</th><td><input id="content_Admin_Undelete_A_Retired_Client_faxphone" type="text" /></td></tr>
                    <tr><th class="left_col">Email</th><td><input id="content_Admin_Undelete_A_Retired_Client_email" type="text" /></td></tr>
                    <tr><th class="left_col">Website</th><td><input id="content_Admin_Undelete_A_Retired_Client_website" type="text" /></td></tr>
                    <tr><td class="left_col button_row"><button type="button" id="clientAdminUndeleteClientCancelButton">Cancel</button></td><td class="right_col button_row"><button type="button" id="clientAdminUndeleteClientSaveButton">Undelete</button></td></tr>
                </table>
            <input id="content_Admin_Undelete_A_Retired_Client_client_id" type="hidden" value="" />
            </form>
            </td></tr>
            </table>';
        return $js.$html;
    }
    
    //returns the actual page content for viewing the client details
    private function page_ViewClientDetails() {
        $salutations=$this->get_salutations();
        $salutations_render='';
        foreach ($salutations as $key=>$value) {
            $salutations_render.='<option value="'.$key.'">'.$value.'</option>';
        }
        $js='
            <script>
            
            $(document).ready(function(){
                
                //put the correct text in the dialog box
                $.ajax(
                {
                    type: "POST",
                    url: "page_responder.php",
                    data: {page:"Clients_Client_Selector_Dialog"}
                })
                .done(function(e)
                {
                    $("#dialog").html(e);
                });
                $("#dialog").dialog("option","title","Select Client");
                $("#dialog").dialog("open");
            });
            
            //handle the save button
            $("#clientEditSaveButton").click(function() {
                $.ajax({
                    type: "POST",
                    url: "page_responder.php",
                    data: 
                    {
                        page:"Clients_Submit_Update_Client",
                        client_id:$("#content_Clients_View_Client_Details_client_id").val(),
                        salutation:$("#content_Clients_View_Client_Details_salutation").val(),
                        lastname:$("#content_Clients_View_Client_Details_lastname").val(),
                        streetnumber:$("#content_Clients_View_Client_Details_streetnumber").val(),
                        streetname:$("#content_Clients_View_Client_Details_streetname").val(),
                        suburb:$("#content_Clients_View_Client_Details_suburb").val(),
                        state:$("#content_Clients_View_Client_Details_state").val(),
                        postcode:$("#content_Clients_View_Client_Details_postcode").val(),
                        firstname:$("#content_Clients_View_Client_Details_firstname").val(),
                        mobilephone:$("#content_Clients_View_Client_Details_mobilephone").val(),
                        homephone:$("#content_Clients_View_Client_Details_homephone").val(),
                        faxphone:$("#content_Clients_View_Client_Details_faxphone").val(),
                        email:$("#content_Clients_View_Client_Details_email").val(),
                        website:$("#content_Clients_View_Client_Details_website").val()
                    }
                })
                .done(function(e){
                    var tabs=$("div#tabs ul").children();
                    for (var i=0; i<tabs.length; i++) {
                        if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                            $("div#about").append(e);
                        }
                    }
                    close_current_tab();
                });
            });
            
            //handle the cancel button
            $("#clientEditCancelButton").click(function() {
                close_current_tab();
            });
            </script>';
        $html='
            <table class="page_container">
            <tr><td>
            <h1>Edit Client Details</h1>
            <form id="viewUpdateClientForm" method="POST" >
                <table>
                    <tr><th class="sub_heading"><h2>Name</h2></th></tr>
                    <tr><th class="left_col">Title</th><td><select id="content_Clients_View_Client_Details_salutation">'.$salutations_render.'</select></td></tr>
                    <tr><th class="left_col">First Name</th><td><input id="content_Clients_View_Client_Details_firstname" type="text" /></td></tr>
                    <tr><th class="left_col">Last Name</th><td><input id="content_Clients_View_Client_Details_lastname" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Address</h2></th></tr>
                    <tr><th class="left_col">Number</th><td><input id="content_Clients_View_Client_Details_streetnumber" type="text" /></td></tr>
                    <tr><th class="left_col">Street Name</th><td><input id="content_Clients_View_Client_Details_streetname" type="text" /></td></tr>
                    <tr><th class="left_col">Suburb</th><td><input id="content_Clients_View_Client_Details_suburb" type="text" /></td></tr>
                    <tr><th class="left_col">State</th><td><input id="content_Clients_View_Client_Details_state" type="text" /></td></tr>
                    <tr><th class="left_col">Postcode</th><td><input id="content_Clients_View_Client_Details_postcode" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Contact Details</h2></th></tr>
                    <tr><th class="left_col">Mobile</th><td><input id="content_Clients_View_Client_Details_mobilephone" type="text" /></td></tr>
                    <tr><th class="left_col">Home Phone</th><td><input id="content_Clients_View_Client_Details_homephone" type="text" /></td></tr>
                    <tr><th class="left_col">Fax</th><td><input id="content_Clients_View_Client_Details_faxphone" type="text" /></td></tr>
                    <tr><th class="left_col">Email</th><td><input id="content_Clients_View_Client_Details_email" type="text" /></td></tr>
                    <tr><th class="left_col">Website</th><td><input id="content_Clients_View_Client_Details_website" type="text" /></td></tr>
                    <tr><td class="left_col button_row"><button type="button" id="clientEditCancelButton">Cancel</button></td><td class="right_col button_row"><button type="button" id="clientEditSaveButton">Save</button></td></tr>
                </table>
            <input id="content_Clients_View_Client_Details_client_id" type="hidden" value="" />
            </form>
            </td></tr>
            </table>';
        return $js.$html;
    }
    
    //returns the actual page content for viewing the client details
    private function page_AddClient() {
        $salutations=$this->get_salutations();
        $salutations_render='';
        foreach ($salutations as $key=>$value) {
            $salutations_render.='<option value="'.$key.'">'.$value.'</option>';
        }
        $js='
            <script>
            //handle the edit button
            $("#clientAddSaveButton").click(function() {
                $.ajax({
                    type: "POST",
                    url: "page_responder.php",
                    data: 
                    {
                        page:"Clients_Submit_New_Client",
                        salutation:$("#content_Clients_Add_Client_salutation").val(),
                        firstname:$("#content_Clients_Add_Client_firstname").val(),
                        lastname:$("#content_Clients_Add_Client_lastname").val(),
                        streetnumber:$("#content_Clients_Add_Client_streetnumber").val(),
                        streetname:$("#content_Clients_Add_Client_streetname").val(),
                        suburb:$("#content_Clients_Add_Client_suburb").val(),
                        state:$("#content_Clients_Add_Client_state").val(),
                        postcode:$("#content_Clients_Add_Client_postcode").val(),
                        mobilephone:$("#content_Clients_Add_Client_mobilephone").val(),
                        homephone:$("#content_Clients_Add_Client_homephone").val(),
                        faxphone:$("#content_Clients_Add_Client_faxphone").val(),
                        email:$("#content_Clients_Add_Client_email").val(),
                        website:$("#content_Clients_Add_Client_website").val()
                    }
                })
                .done(function(e){
                    var tabs=$("div#tabs ul").children();
                    for (var i=0; i<tabs.length; i++) {
                        if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                            $("div#about").append(e);
                        }
                    }
                    close_current_tab();
                });
            });
            $("#clientAddCancelButton").click(function() {
                close_current_tab();
            });
            </script>';
        $html='
            <table class="page_container">
            <tr><td>
            <h1>Add New Client</h1>
            <form id="addNewClientForm" method="POST" >
                <table>
                    <tr><th class="sub_heading"><h2>Name</h2></th></tr>
                    <tr><th class="left_col">Title</th><td><select id="content_Clients_Add_Client_salutation">'.$salutations_render.'</select></td></tr>
                    <tr><th class="left_col">First Name</th><td><input id="content_Clients_Add_Client_firstname" type="text" /></td></tr>
                    <tr><th class="left_col">Last Name</th><td><input id="content_Clients_Add_Client_lastname" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Address</h2></th></tr>
                    <tr><th class="left_col">Number</th><td><input id="content_Clients_Add_Client_streetnumber" type="text" /></td></tr>
                    <tr><th class="left_col">Street Name</th><td><input id="content_Clients_Add_Client_streetname" type="text" /></td></tr>
                    <tr><th class="left_col">Suburb</th><td><input id="content_Clients_Add_Client_suburb" type="text" /></td></tr>
                    <tr><th class="left_col">State</th><td><input id="content_Clients_Add_Client_state" type="text" /></td></tr>
                    <tr><th class="left_col">Postcode</th><td><input id="content_Clients_Add_Client_postcode" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Contact Details</h2></th></tr>
                    <tr><th class="left_col">Mobile</th><td><input id="content_Clients_Add_Client_mobilephone" type="text" /></td></tr>
                    <tr><th class="left_col">Home Phone</th><td><input id="content_Clients_Add_Client_homephone" type="text" /></td></tr>
                    <tr><th class="left_col">Fax</th><td><input id="content_Clients_Add_Client_faxphone" type="text" /></td></tr>
                    <tr><th class="left_col">Email</th><td><input id="content_Clients_Add_Client_email" type="text" /></td></tr>
                    <tr><th class="left_col">Website</th><td><input id="content_Clients_Add_Client_website" type="text" /></td></tr>
                    <tr><td class="left_col button_row"><button type="button" id="clientAddCancelButton">Cancel</button></td><td class="right_col button_row"><button type="button" id="clientAddSaveButton">Save</button></td></tr>
                </table>
            </form>
            </td></tr>
            </table>';
        return $js.$html;
    }
    
    #takes the form data from a "new client"page and submits it to the DB
    private function SubmitNewClient() {
        #sanitise the data
        $salutation=$this->db->clean($_REQUEST['salutation']);
        $firstname=$this->db->clean($_REQUEST['firstname']);
        $lastname=$this->db->clean($_REQUEST['lastname']);
        $streetnumber=$this->db->clean($_REQUEST['streetnumber']);
        $streetname=$this->db->clean($_REQUEST['streetname']);
        $suburb=$this->db->clean($_REQUEST['suburb']);
        $state=$this->db->clean($_REQUEST['state']);
        $postcode=$this->db->clean($_REQUEST['postcode']);
        $mobilephone=$this->db->clean($_REQUEST['mobilephone']);
        $homephone=$this->db->clean($_REQUEST['homephone']);
        $faxphone=$this->db->clean($_REQUEST['faxphone']);
        $email=$this->db->clean($_REQUEST['email']);
        $website=$this->db->clean($_REQUEST['website']);
        $country="Australia";
        $status="active";
        
        #insert the data
        $sql="INSERT INTO client (salutation_id,first_name,last_name,street_number,street_address,suburb,state,country,postcode,mobile_phone,home_phone,fax_phone,email,website,status) values ($salutation,'$firstname','$lastname','$streetnumber','$streetname','$suburb','$state','$country','$postcode','$mobilephone','$homephone','$faxphone','$email','$website','$status')";
        $insert_result=$this->db->query($sql);
        
        #either return the last event log or the error message
        if ($insert_result=='') {
            $this->db->add_event("New Client added: $firstname $lastname of $streetnumber $streetname $suburb $state $postcode $country");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $insert_result;
        }
    }
    
    //handles form submission when a client is updated
    private function SubmitUpdateClient() {
        #sanitise the data
        $client_id=$this->db->clean($_REQUEST['client_id']);
        $salutation=$this->db->clean($_REQUEST['salutation']);
        $title=$this->convert_salutation_id_to_title($salutation);
        $firstname=$this->db->clean($_REQUEST['firstname']);
        $lastname=$this->db->clean($_REQUEST['lastname']);
        $streetnumber=$this->db->clean($_REQUEST['streetnumber']);
        $streetname=$this->db->clean($_REQUEST['streetname']);
        $suburb=$this->db->clean($_REQUEST['suburb']);
        $state=$this->db->clean($_REQUEST['state']);
        $postcode=$this->db->clean($_REQUEST['postcode']);
        $mobilephone=$this->db->clean($_REQUEST['mobilephone']);
        $homephone=$this->db->clean($_REQUEST['homephone']);
        $faxphone=$this->db->clean($_REQUEST['faxphone']);
        $email=$this->db->clean($_REQUEST['email']);
        $website=$this->db->clean($_REQUEST['website']);
        $country="Australia";
        
        
        #insert the data
        $sql="UPDATE client set salutation_id=$salutation,first_name='$firstname',last_name='$lastname',street_number='$streetnumber',street_address='$streetname',suburb='$suburb',state='$state',country='$country',postcode='$postcode',mobile_phone='$mobilephone',fax_phone='$faxphone',email='$email',website='$website',home_phone='$homephone' where client_id=$client_id";
        $insert_result=$this->db->query($sql);
        
        #either return the last event log or the error message
        if ($insert_result=='') {
            $this->db->add_event("Client details updated to: $title $firstname $lastname of $streetnumber $streetname $suburb $state $postcode $country for client_id $client_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $insert_result;
        }
    }
    
    //sets a client to status 'retired'
    private function SubmitDeleteClient() {
        #sanitise the data
        $client_id=$this->db->clean($_REQUEST['client_id']);
        $new_status="retired";
        
        #get existing data for the log
        $data=array();
        $sel_res=$this->db->query("select * from client where client_id='$client_id'");
        $sel_res=$sel_res[0];
        $title=$this->convert_salutation_id_to_title($sel_res['salutation_id']);
        $firstname=$sel_res['first_name'];
        $lastname=$sel_res['last_name'];
        $streetnumber=$sel_res['street_number'];
        $streetname=$sel_res['street_address'];
        $suburb=$sel_res['suburb'];
        $state=$sel_res['state'];
        $postcode=$sel_res['postcode'];
        $country=$sel_res['country'];
        
        #update the data
        $sql="UPDATE client set status='$new_status' where client_id=$client_id";
        $insert_result=$this->db->query($sql);
        
        #either return the last event log or the error message
        if ($insert_result=='') {
            $this->db->add_event("Client retired: $title $firstname $lastname of $streetnumber $streetname $suburb $state $postcode $country, client_id $client_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $insert_result;
        }
    }
    
    //handles the form submission when a client is retired (temporarily deleted)
    private function SubmitUndeleteClient() {
        #sanitise the data
        $client_id=$this->db->clean($_REQUEST['client_id']);
        $new_status="active";
        
        #get existing data for the log
        $data=array();
        $sel_res=$this->db->query("select * from client where client_id='$client_id'");
        $sel_res=$sel_res[0];
        $title=$this->convert_salutation_id_to_title($sel_res['salutation_id']);
        $firstname=$sel_res['first_name'];
        $lastname=$sel_res['last_name'];
        $streetnumber=$sel_res['street_number'];
        $streetname=$sel_res['street_address'];
        $suburb=$sel_res['suburb'];
        $state=$sel_res['state'];
        $postcode=$sel_res['postcode'];
        $country=$sel_res['country'];
        
        #update the data
        $sql="UPDATE client set status='$new_status' where client_id=$client_id";
        $insert_result=$this->db->query($sql);
        
        #either return the last event log or the error message
        if ($insert_result=='') {
            $this->db->add_event("Client undeleted: $title $firstname $lastname of $streetnumber $streetname $suburb $state $postcode $country, client_id $client_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $insert_result;
        }
    }
    
    //handles the form submission when a client is PURGED (permanently removed)
    private function SubmitPurgeClient() {
        #sanitise the data
        $client_id=$this->db->clean($_REQUEST['client_id']);
        
        #get existing data for the log
        $data=array();
        $sel_res=$this->db->query("select * from client where client_id='$client_id'");
        $sel_res=$sel_res[0];
        $title=$this->convert_salutation_id_to_title($sel_res['salutation_id']);
        $firstname=$sel_res['first_name'];
        $lastname=$sel_res['last_name'];
        $streetnumber=$sel_res['street_number'];
        $streetname=$sel_res['street_address'];
        $suburb=$sel_res['suburb'];
        $state=$sel_res['state'];
        $postcode=$sel_res['postcode'];
        $country=$sel_res['country'];
        
        #update the data
        $features=new Features($this->db);
        $dependant_tables=$features->get_feature_table_names_dependant_on_feature('Clients');
        foreach ($dependant_tables as $table_name) {
            $this->db->query("delete from $table_name where client_id='$client_id'");
        }
        $delete_result=$this->db->query("DELETE FROM client WHERE client_id='$client_id'");
        
        #either return the last event log or the error message
        if ($delete_result=='') {
            $this->db->add_event("Client PURGED: $title $firstname $lastname of $streetnumber $streetname $suburb $state $postcode $country, client_id $client_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $delete_result;
        }
    }
    
    //used for the dialog box client selector, a cut down version of these details
    private function getClientsListWithStatus($status='active') {
        $output=array();
        $sql="select c.client_id ,s.description as salutation, c.first_name, c.last_name , c.street_number , c.street_address, c.suburb, c.state, c.postcode from client as c left join salutation as s on c.salutation_id=s.salutation_id where c.status='$status'";
        if ($status=="all") {
            $sql="select c.client_id ,s.description as salutation, c.first_name, c.last_name , c.street_number , c.street_address, c.suburb, c.state, c.postcode from client as c left join salutation as s on c.salutation_id=s.salutation_id";
        }
        $result=$this->db->query($sql);
        if (isset($result) and $result!="") {
            foreach ($result as $row) {
                $output[$row['client_id']]=array(
                    'client_id'=>$row['client_id'],
                    'salutation'=>$row['salutation'],
                    'first_name'=>$row['first_name'],
                    'last_name'=>$row['last_name'],
                    'street_number'=>$row['street_number'],
                    'street_address'=>$row['street_address'],
                    'suburb'=>$row['suburb'],
                    'state'=>$row['state'],
                    'postcode'=>$row['postcode']);
            }
        }
        return $output;
    }
    
    //returns an associative array of details for a single client directly from the cient table, ie, no translations for comboboxes
    private function getClientsListRawWithStatus($status='active') {
        $output=array();
        $sql="select * from client where status='$status'";
        if($status=='all') {
            $sql="select * from client";
        }
        $result=$this->db->query($sql);
        foreach ($result as $row) {
            $output[$row['client_id']]=array(
                'client_id'=>$row['client_id'],
                'salutation_id'=>$row['salutation_id'],
                'first_name'=>$row['first_name'],
                'last_name'=>$row['last_name'],
                'street_number'=>$row['street_number'],
                'street_address'=>$row['street_address'],
                'suburb'=>$row['suburb'],
                'state'=>$row['state'],
                'postcode'=>$row['postcode'],
                'mobile_phone'=>$row['mobile_phone'],
                'home_phone'=>$row['home_phone'],
                'fax_phone'=>$row['fax_phone'],
                'email'=>$row['email'],
                'website'=>$row['website'],
                'status'=>$row['status']);
        }
        return $output;
    }
    
    //returns the code needed for the client selector dialog
    public function ClientSelectorDialog() {
        $status;
        if (isset($_REQUEST['status'])) {
            $status=$this->db->clean($_REQUEST['status']);
        }
        $status = (!isset($status) or $status=='')?'active':$status;
        $list=$this->getClientsListWithStatus($status);
        $js='
            <script>
            $(document).ready(function () {
                //define the data table
                var table=$("#clients_selector").DataTable({
                    paging: true,
                    searching: true,
                    destroy: true,
                });
                //grab the currently open page tab name for the prefix for all the fields to populate
                var prefix=$("div#tabs ul").children()[$("div#tabs").tabs("option","active")].getAttribute("aria-controls");
                //setup the dialog box for this use case
                $( "#dialog" ).dialog({
                    autoOpen: false,
                    width: 700,
                    modal: true,
                    buttons: [
                        {
                            text: "Select",
                            click: function() {
                                var rowSelected=$(".selected").attr("id");
                                if (!rowSelected) {
                                    alert("You need to select a row by clicking on it first.");
                                }
                                else {
                                    load_client_into_form(rowSelected,prefix);
                                }
                            }
                        },
                        {
                            text: "Cancel",
                            click: function() {
                                close_current_tab();
                                $( this ).dialog( "close" );
                                
                            }
                        }
                    ]
                });
                
                //toggle selected row
                $("#clients_selector tbody").on( "click", "tr", function () {
                    if ( $(this).hasClass("selected") ) {
                        $(this).removeClass("selected");
                    }
                    else {
                        table.$("tr.selected").removeClass("selected");
                        $(this).addClass("selected");
                    }
                });
                
                //load the record into the form
                function load_client_into_form(client_id,prefix) {
                    $.ajax({
                        type: "POST",
                        url: "page_responder.php",
                        data: 
                        {
                            page:"Clients_Load_Client_Data",
                            client_id:client_id,
                        }
                    })
                    .done(function(e){
                        var data=JSON.parse(e);
                        $("#"+prefix+"_client_id").val(data["client_id"]);
                        $("#"+prefix+"_salutation").val(data["salutation_id"]);
                        $("#"+prefix+"_firstname").val(data["first_name"]);
                        $("#"+prefix+"_lastname").val(data["last_name"]);
                        $("#"+prefix+"_streetnumber").val(data["street_number"]);
                        $("#"+prefix+"_streetname").val(data["street_address"]);
                        $("#"+prefix+"_suburb").val(data["suburb"]);
                        $("#"+prefix+"_state").val(data["state"]);
                        $("#"+prefix+"_postcode").val(data["postcode"]);
                        $("#"+prefix+"_mobilephone").val(data["mobile_phone"]);
                        $("#"+prefix+"_homephone").val(data["home_phone"]);
                        $("#"+prefix+"_faxphone").val(data["fax_phone"]);
                        $("#"+prefix+"_email").val(data["email"]);
                        $("#"+prefix+"_website").val(data["website"]);
                        $("#"+prefix+"_client_id").trigger("change");
                        $("#dialog").dialog( "close" );
                    });
                }
            });
            </script>';
        $html='
            <table id="clients_selector" class="display" cellspacing="0"" width="100%">
            <thead><tr><th>Title</th><th>Name</th><th>Street Address</th><th>Suburb</th><th>State</th><th>Postcode</th></tr></thead>
            <tbody>';
        
        //iterate over the clients and create the bulk of the table
        foreach ($list as $row) {
            $html.="<tr id='".$row['client_id']."'>".
                "<td>".$row['salutation']."</td>".
                "<td>".$row['first_name'].' '.$row['last_name']."</td>".
                "<td>".$row['street_number'].' '.$row['street_address']."</td>".
                "<td>".$row['suburb']."</td>".
                "<td>".$row['state']."</td>".
                "<td>".$row['postcode']."</td></tr>";
        }
        
        $html.="</tbody>";
        $html.="</table>";
        return $html.$js;
    }
    
    //returns the actual page content for viewing the client details
    private function page_RemoveClient() {
        $salutations=$this->get_salutations();
        $salutations_render='';
        foreach ($salutations as $key=>$value) {
            $salutations_render.='<option value="'.$key.'">'.$value.'</option>';
        }
        $js='
            <script>
            
            $(document).ready(function(){
                
                //put the correct text in the dialog box
                $.ajax(
                {
                    type: "POST",
                    url: "page_responder.php",
                    data: {page:"Clients_Client_Selector_Dialog"}
                })
                .done(function(e)
                {
                    $("#dialog").html(e);
                });
                $("#dialog").dialog("option","title","Select Client");
                $("#dialog").dialog("open");
            });
            
            //handle the save button
            $("#clientDeleteSaveButton").click(function() {
                $.ajax({
                    type: "POST",
                    url: "page_responder.php",
                    data: 
                    {
                        page:"Clients_Submit_Delete_Client",
                        client_id:$("#content_Clients_Remove_Client_client_id").val(),
                        
                    }
                })
                .done(function(e){
                    var tabs=$("div#tabs ul").children();
                    for (var i=0; i<tabs.length; i++) {
                        if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                            $("div#about").append(e);
                        }
                    }
                    close_current_tab();
                });
            });
            
            //handle the cancel button
            $("#clientDeleteCancelButton").click(function() {
                close_current_tab();
            });
            </script>';
        $html='
            <table class="page_container">
            <tr><td>
            <h1>Delete Client</h1>
            <form id="deleteClientForm" method="POST" >
                <table>
                    <tr><th class="sub_heading"><h2>Name</h2></th></tr>
                    <tr><th class="left_col">Title</th><td><select id="content_Clients_Remove_Client_salutation">'.$salutations_render.'</select></td></tr>
                    <tr><th class="left_col">First Name</th><td><input id="content_Clients_Remove_Client_firstname" type="text" /></td></tr>
                    <tr><th class="left_col">Last Name</th><td><input id="content_Clients_Remove_Client_lastname" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Address</h2></th></tr>
                    <tr><th class="left_col">Number</th><td><input id="content_Clients_Remove_Client_streetnumber" type="text" /></td></tr>
                    <tr><th class="left_col">Street Name</th><td><input id="content_Clients_Remove_Client_streetname" type="text" /></td></tr>
                    <tr><th class="left_col">Suburb</th><td><input id="content_Clients_Remove_Client_suburb" type="text" /></td></tr>
                    <tr><th class="left_col">State</th><td><input id="content_Clients_Remove_Client_state" type="text" /></td></tr>
                    <tr><th class="left_col">Postcode</th><td><input id="content_Clients_Remove_Client_postcode" type="text" /></td></tr>
                    <tr><th class="sub_heading"><h2>Contact Details</h2></th></tr>
                    <tr><th class="left_col">Mobile</th><td><input id="content_Clients_Remove_Client_mobilephone" type="text" /></td></tr>
                    <tr><th class="left_col">Home Phone</th><td><input id="content_Clients_Remove_Client_homephone" type="text" /></td></tr>
                    <tr><th class="left_col">Fax</th><td><input id="content_Clients_Remove_Client_faxphone" type="text" /></td></tr>
                    <tr><th class="left_col">Email</th><td><input id="content_Clients_Remove_Client_email" type="text" /></td></tr>
                    <tr><th class="left_col">Website</th><td><input id="content_Clients_Remove_Client_website" type="text" /></td></tr>
                    <tr><td class="left_col button_row"><button type="button" id="clientDeleteCancelButton">Cancel</button></td><td class="right_col button_row"><button type="button" id="clientDeleteSaveButton">Delete</button></td></tr>
                </table>
            <input id="content_Clients_Remove_Client_client_id" type="hidden" value="" />
            </form>
            </td></tr>
            </table>';
        return $js.$html;
    }
    
    //returns the list of salutations for rendering into a combo box
    public function get_salutations() {
        $result=array();
        $sql="select * from salutation";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
            $result[$row['salutation_id']]=$row['description'];
        }
        return $result;
    }
    
    //takes a salutation id and returns the string description of it
    private function convert_salutation_id_to_title($salutation_id) {
        $all_titles=$this->get_salutations();
        return $all_titles[$salutation_id];
    }
    
    //return a single clients details, from a POST REQUEST
    private function LoadClientData() {
        $client_id=$this->db->clean($_REQUEST['client_id']);
        $list=$this->db->query("select * from client where client_id='$client_id'")[0];
        return json_encode($list);
    }
}
?>
