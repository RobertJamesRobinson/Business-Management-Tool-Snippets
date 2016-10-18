<?php
/*
Handles the basic <feature name> feature module. This module has ?? dependencies. TEMPLATE FILE, SHOULD NOT BE INCLUDED IN PRODUCTION
*/
class Template {
    protected $db;
    private $messages;
    
    //standard constructor, takes a database connection
    public function __construct(&$db) {
        $this->db=$db;
        include_once('./forms/basic_form.php');
        include_once('./forms/expanding_rows_form.php');
        
        //make sure dependancies are installed before we try to install this feature
        include_once('clients.php');
        $clientObj=new Clients($db);
        $clientObj->install();
        
        //Install this feature
        $this->install();
        
        //remove temporary dependant objects
        unset($clientObj);
    }
    
    //installs the table needed for this feature
    public function install() {
        #table definitions
        $sqls=array();
        $sqls['salutation']=array();
        $sqls['salutation'][]="CREATE TABLE IF NOT EXISTS salutation ( salutation_id INT NOT NULL AUTO_INCREMENT, gender VARCHAR(6) NULL, description VARCHAR(15) NULL, PRIMARY KEY (salutation_id)) ENGINE = InnoDB";
        $sqls['agedCareClientCondition']="CREATE TABLE IF NOT EXISTS agedCareClientCondition (agedCareClientCondition_id INT NOT NULL AUTO_INCREMENT, client_id INT NOT NULL, record_date DATETIME NULL, description BLOB NULL, PRIMARY KEY (agedCareClientCondition_id), INDEX fk_agedCareClientCondition_client_idx (client_id ASC), CONSTRAINT fk_agedCareClientCondition_client FOREIGN KEY (client_id) REFERENCES client (client_id) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB";
        $sqls['agedCareClientProgressNote']="CREATE TABLE IF NOT EXISTS agedCareClientProgressNote ( agedCareClientProgressNote_id INT NOT NULL AUTO_INCREMENT, client_id INT NOT NULL, record_date DATETIME NULL, description BLOB NULL, PRIMARY KEY (agedCareClientProgressNote_id), INDEX fk_agedCareClientProgressNote_client1_idx (client_id ASC), CONSTRAINT fk_agedCareClientProgressNote_client1 FOREIGN KEY (client_id) REFERENCES mydb.client (client_id) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB";
        $sqls['agedCareClientCareRequirements']="CREATE TABLE IF NOT EXISTS agedCareClientCareRequirements ( agedCareClientCareRequirements_id INT NOT NULL AUTO_INCREMENT, client_id INT NOT NULL, date_added DATETIME NULL, date_removed DATETIME NULL, Description VARCHAR(255) NULL, PRIMARY KEY (agedCareClientCareRequirements_id), INDEX fk_agedCareClientCareRequirements_client1_idx (client_id ASC), CONSTRAINT fk_agedCareClientCareRequirements_client1 FOREIGN KEY (client_id) REFERENCES mydb.client (client_id) ON DELETE NO ACTION ON UPDATE NO ACTION) ENGINE = InnoDB";
        
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
            case "SubMenu1Label":
                $result=$this->page_SubMenu1Label();
                break;
        }
        return $result;
    }
    
    //returns the menu items associated with this feature, the format is array of arrays, each level is a sub menu of the preceeding level
    public function get_menus() {
        $result=array();
        $result['Menu 1']=array('Tab Name 1|Sub Menu 1 Label','Tab Name 2|Sub Menu 2 Label');
        $result['Menu 2']=array('Tab name 1|Sub Menu 1 Label','Tab Name 2|Sub Menu 2 Label');
        return $result;
    }
    
    //returns the actual page content for sub menu 1
    private function page_SubMenu1Label() {
        return "<p>Sample Text for page_SubMenu1Label</p>";
    }
}
?>
