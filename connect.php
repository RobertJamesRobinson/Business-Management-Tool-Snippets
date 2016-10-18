<?php
/* connect to the DB, stores the DB connection, includes a query parser,
 * just to abstract away all DB functions to a simple interface
 */
class Connect {

    //Attributes
    protected $link=null;
    public $error;
    private $hostName='127.0.0.1';
    private $username='root';
    private $pword='';
    private $dbName='sue2';
    private $port=3306;

    //Constructor
    public function __construct() {
        $this->link=new mysqli($this->hostName,$this->username,$this->pword,$this->dbName);
        if ($this->link->connect_errno) {
            $this->error=$this->link->connect_error;
            print "could not connect to DB: ".$this->error;
        }
    }

    #adds a message to the event log
    public function add_event($description) {
        $this->query("INSERT INTO eventLog (event_time,description) values (now(),'$description')");
    }

    #returns the last $num number of event records from the event log and returns then wrapped in <p> tags
    public function get_last_events($num) {
        $sql=" select * from (select * from eventLog order by eventLog_id DESC limit $num) sub order by eventLog_id ASC";
        $result=$this->query($sql);
        $returnable='';
        foreach ($result as $single_result) {
            $returnable.='<p>'.$single_result['event_time'].' - '.$single_result['description'].'</p>';
        }
        return $returnable;
    }

    //returns the database name, useful for when features need to install
    public function get_db_name() {
        return $this->dbname;
    }

    //Destructor
    public function __destruct() {
        $this->close();
    }

    //close the DB connection, should be called when the connection is no longer needed
    public function close() {
        mysqli_close($this->link);
    }

    //escapes a variable so its ready for an sql query
    public function clean($variable) {
        return $this->link->real_escape_string($variable);
    }
    
    //process a query and return a user friendly result array
    //returns TRUE for querys that have no retyurn value
    //returns an assoc array for queries that return a value
    //returns false on failure
    public function query($myQuery) {
        $this->error='';
        $resultingArray=array();
		$this->link->autocommit(TRUE);
        $result=$this->link->query($myQuery);

        //mysql returns false on failed query
        if (is_a($result, 'mysqli_result')) {
            //resourse returned from a query that returns a value
            while($row=mysqli_fetch_assoc($result)) {
                array_push($resultingArray,$row);
            }
			
        }
		elseif ($result===False) {
			$this->error=$this->link->error;
			return $this->error;
        }
        return empty($resultingArray)?'':$resultingArray;
    }
}

?>