<?php
/*
Handles the basic <feature name> feature module. This module has ?? dependencies. TEMPLATE FILE, SHOULD NOT BE INCLUDED IN PRODUCTION
*/
class Evidence {
    protected $db;
    private $messages;
    private $upload_folder;
    
    //standard constructor, takes a database connection
    public function __construct(&$db) {
        $this->db=$db;
        $this->allowed_filetypes=explode('|',$this->db->query("select value from configuration where description='allowed upload file types'")[0]['value']);
        $this->allowed_filesize=$this->db->query("select value from configuration where description='maximum file upload size'")[0]['value'];
        $this->upload_folder=$this->db->query("select value from configuration where description='upload folder'")[0]['value'];
        //make sure dependancies are installed before we try to install this feature
        //include_once('./forms/basic_form.php');
        //include_once('./forms/expanding_rows_form.php');
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
        $sqls['evidence']=array();
        $sqls['evidence'][]="CREATE TABLE IF NOT EXISTS evidence ( evidence_id INT NOT NULL AUTO_INCREMENT, description VARCHAR(255) NULL, full_path_to_file BLOB NULL, file_mimetype VARCHAR(255) NULL, simplified_filetype VARCHAR(255) NULL, derived_filetype VARCHAR(255) NULL, file_size_bytes VARCHAR(255) NULL, notes BLOB NULL, date_uploaded DATETIME NULL, PRIMARY KEY (evidence_id)) ENGINE = InnoDB";
        
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
        
        #check if the uploads folder has been created, create it if not
        if (!is_dir($this->upload_folder)) {
            if (mkdir($this->upload_folder, 0755, true)) {
                $this->db->add_event("Upload folder created at location: ".$this->upload_folder);
            }
            else {
                $this->db->add_event("Error: COULD NOT CREATE UPLOAD FOLDER at location: ".$this->upload_folder);
                error_log("COULD NOT CREATE UPLOAD FOLDER at location: ".$this->upload_folder);
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
            case "UploadNewDocument":
                $result=$this->page_UploadNewDocument();
                break;
            case "ReviewUploadedDocuments":
                $result=$this->page_ReviewUploadedDocuments();
                break;
            case "SubmitUploadAFile":
                $result=$this->submit_SubmitUploadNewDocument();
                break;
            case "LoadUploadDetails":
                $result=$this->load_LoadUploadDetails();
                break;
            case "UpdateUploadDetails":
                $result=$this->update_UpdateUploadDetails();
                break;
            case "DeleteUpload":
                $result=$this->delete_DeleteUpload();
                break;
            case "UploadedDocumentsSource":
                $result=$this->ajax_UploadedDocumentsSource();
                break;
            case "ForceUpload":
                $result=$this->read_in_files_from_upload_folder();
                break;
                
        }
        return $result;
    }
    
    //takes an evidence_id and removes that entry from the Database
    public function delete_DeleteUpload() {
        $evidence_id=$this->db->clean($_REQUEST['evidence_id']);
        
        #update the data
        $sql="DELETE FROM evidence WHERE evidence_id='$evidence_id'";
        $delete_result=$this->db->query($sql);
        
        #either return the last event log or the error message
        if ($delete_result=='') {
            $this->db->add_event("Upload deleted: for evidence_id $evidence_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $delete_result;
        }
    }
    
    //takes a set of data related to an upload and updates it
    public function update_UpdateUploadDetails() {
        $evidence_id=$this->db->clean($_REQUEST['evidence_id']);
        $description=$this->db->clean($_REQUEST['description']);
        $filename=$this->db->clean($_REQUEST['this_filename']);
        $notes=$this->db->clean($_REQUEST['notes']);
        
        #update the data
        $sql="UPDATE evidence set description='$description', notes='$notes' where evidence_id='$evidence_id'";
        $update_result=$this->db->query($sql);
        
        #either return the last event log or the error message
        if ($update_result=='') {
            $this->db->add_event("Upload details updated for: $filename for evidence_id $evidence_id");
            $last_event_message=$this->db->get_last_events(1);
            return $last_event_message;
        }
        else {
            return $update_result;
        }
    }
    
    //returns the menu items associated with this feature, the format is array of arrays, each level is a sub menu of the preceeding level
    public function get_menus() {
        $result=array();
        $result['Documents']=array('Upload Doc|Upload New Document','Download Doc|Review Uploaded Documents');
        return $result;
    }
    
    //return the form contents for the dialog box when editing an upload file
    private function load_LoadUploadDetails() {
        $evidence_id=$this->db->clean($_REQUEST['evidence_id']);
        $evidence=$this->db->query("select * from evidence where evidence_id='".$evidence_id."'")[0];
        $filename=substr($evidence['full_path_to_file'], strlen($this->upload_folder)+1);
        //TODO: leverage the basicform for this form
        $html='<table>
            <form id="content_documents_upload_update_dialog_form" method="POST" >
            <tr><td><h1 colspan="2">Review Uploads</h1></td></tr>
            <tr><th style="text-align:right;" class="left_col">File Name</th><td><input disabled id="content_documents_upload_update_dialog_filename" value="'.$filename.'" style="width: 296px;" type="text" /></td></tr>
            <tr><th style="text-align:right;" class="left_col">File Type</th><td><input disabled id="content_documents_upload_update_dialog_filetype" value="'.$evidence['derived_filetype'].'" style="width: 296px;" type="text" /></td></tr>
            <tr><th style="text-align:right;" class="left_col">Description</th><td><input id="content_documents_upload_update_dialog_description" value="'.$evidence['description'].'" style="width: 296px;" type="text" /></td></tr>
            <tr><th style="text-align:right;" class="left_col">Notes</th><td><textarea id="content_documents_upload_update_dialog_notes" cols="43" rows="4">'.$evidence['notes'].'</textarea></td></tr>
            </form>
            </table>
            ';
        return $html;
    }
    
    //handle file upload submissions, check for suitability of the file and check for duplicate filenames
    private function submit_SubmitUploadNewDocument() {
        $upload_filename=$_FILES['datafile']['name'];
        $tmp_upload_file=$_FILES['datafile']['tmp_name'];
        $target_file = basename($_FILES['datafile']['name']);
        $file_size = $_FILES['datafile']['size'];
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        $uploadOk = 1;
        $result="";
        $target_folder=$this->upload_folder;
        if (substr($target_folder,-1)!='/') {
            $target_folder.="/";
        }
        $target_file=$target_folder.$target_file;
        
        // Check if file already exists
        if (file_exists($target_file)) {
            $result.= "Upload file allready exists in upload folder: ". basename( $upload_filename);
            $uploadOk=0;
        } 
        
        // Check file size
        elseif ($file_size > $this->allowed_filesize) {
            $reported_size=$this->bytes_readable($file_size);
            $result.="Upload filesize: ".$reported_size." is too large: ". basename( $upload_filename);
            $uploadOk=0;
        } 
        
        // Allow certain file formats
        elseif(!in_array($imageFileType,$this->allowed_filetypes)) {
            $result.="Upload file filetype not allowed upload error: ". basename( $upload_filename);
            $uploadOk=0;
        }
        
        // if everything is ok, try to upload file
        else {
            if (move_uploaded_file($tmp_upload_file, $target_file)) {
                $result= "Upload file uploaded successfully: ". basename( $upload_filename);
            } else {
                $result.= "Upload file internal move_uploaded_file error: ". basename( $upload_filename);
                $uploadOk=0;
            }
        }
        
        #we got this far, might as well upload the file
        if ($uploadOk) {
            $description=$this->db->clean($_REQUEST['description']);
            $full_path_to_file=$this->db->clean($target_file);
            $file_mimetype=$this->db->clean($_FILES['datafile']['type']);
            $simplified_filetype=$this->simplified_filetype_sorter($file_mimetype);
            $derived_filetype=$this->db->clean($_REQUEST['file_type']);
            $file_size_bytes=$this->db->clean($_FILES['datafile']['size']);
            $notes=$this->db->clean($_REQUEST['notes']);
            
            #insert the data
            $sql="INSERT INTO evidence (description,full_path_to_file,file_mimetype,simplified_filetype,derived_filetype,file_size_bytes,notes,date_uploaded) VALUES ('$description','$full_path_to_file','$file_mimetype','$simplified_filetype','$derived_filetype','$file_size_bytes','$notes',now())";
            $insert_result=$this->db->query($sql);
            
            #either return the last event log or the error message
            if ($insert_result=='') {
                $this->db->add_event($result);
                $last_event_message=$this->db->get_last_events(1);
                return 'success'.$last_event_message;
            }
            else {
                return 'error'.$insert_result;
            }
        }
        else {
            return 'error'.$result;
        }
    }
    
    #gets the files from the uploads folder and inserts any that arent in the DB into the DB without notes or descriptions
    #allows for bulk uploads, just put the files in the uploads folder and 
    private function read_in_files_from_upload_folder() {
        $mime_type_mapping=$this->db->query("select mime_type,name from recognised_mime_type");
        $mime_lookup=array();
        foreach($mime_type_mapping as $single_mime_type) {
            $mime_lookup[$single_mime_type['mime_type']]=$single_mime_type['name'];
        }
        $files=scandir($this->upload_folder);
        foreach($files as $file) {
            $full_path=$this->upload_folder."/".$file;
            #make sure the file isnt already in the DB
            $already_in_db=$this->db->query("select count(*) as cnt from evidence where full_path_to_file='$full_path'")[0]['cnt'];
            if(is_file($full_path) and substr($file,0,1)!='.' and !$already_in_db) {
                $mime_type=mime_content_type($full_path);
                $file_size=filesize($full_path);
                $simplified_filetype=$this->simplified_filetype_sorter($mime_type);
                $derived_filetype=$mime_lookup[$mime_type];
                $insert_result=$this->db->query("INSERT INTO evidence (full_path_to_file,file_mimetype,simplified_filetype,derived_filetype,file_size_bytes,date_uploaded) VALUES ('$full_path','$mime_type','$simplified_filetype','$derived_filetype','$file_size',now())");
            
            }
        }
    }
    
    //converts bytes into a readbale descriptive value
    private function bytes_readable($bytes) {
        $result='';
        if ($bytes>1000 and $bytes<=1000000) {
            $result=round($bytes/1000);
            $result.=" KB";
        }
        elseif($bytes>1000000 and $bytes<=1000000000) {
            $result=sprintf("%.1f",$bytes/1000000);
            $result.=" MB";
        }
        elseif($bytes>1000000000 and $bytes<=1000000000000) {
            $result=sprintf("%.1f",$bytes/1000000000);
            $result.=" GB";
        }
        elseif($bytes>1000000000000 and $bytes<=1000000000000000) {
            $result=sprintf("%.1f",$bytes/1000000000000);
            $result.=" TB";
        }
        elseif($bytes>1000000000000000 and $bytes<=1000000000000000000) {
            $result=sprintf("%.1f",$bytes/1000000000000000);
            $result.=" PB";
        }
        elseif($bytes>1000000000000000000 and $bytes<=1000000000000000000000) {
            $result=sprintf("%.1f",$bytes/1000000000000000000);
            $result.=" EB";
        }
        else {
            $result=$bytes." B";
        }
        return $result;
    }
    
    #takes an upload file extention and mime type and tries to boil it down to about 6 broad categories of file type
    #Video, Image, Audio, Text, Document, Tabular
    private function simplified_filetype_sorter($mime) {
        $result="Unknown";
        if (strpos($mime,'video')!==false) {
            $result="Video";
        }
        else if (strpos($mime,'image')!==false) {
            $result="Image";
        }
        else if (strpos($mime,'audio')!==false) {
            $result="Audio";
        }
        else if (strpos($mime,'spreadsheet')!==false or strpos($mime,'csv')!==false) {
            $result="Tabular";
        }
        else if (strpos($mime,'document')!==false or strpos($mime,'pdf')!==false) {
            $result="Document";
        }
        else if (strpos($mime,'text')!==false) {
            $result="Text";
        }
        return $result;
    }
    
    //return the page required for the uploading a new document form
    //TODO: need to move file upload handling into the basicform class and use that instead of this
    private function page_UploadNewDocument() {
        $undf=new BasicForm($this->db);
        $undf->set_formname('Upload A File');
        $undf->set_heading('Upload A File');
        $undf->set_feature('Documents');
        $undf->set_page_buttons(array('upload','cancel'));
        $undf->add_group('file');
        $undf->add_upload_field_to_group('file','file upload',296,'Filename to upload...',$this->allowed_filesize,'Choose a file to upload');
        $undf->add_annotation_field_to_group('file','file type',296,'Unrecognised','select mime_type as data_key,name as data_value from recognised_mime_type','file|file upload|mime_type');
        $undf->add_text_field_to_group('file','description',296);
        $undf->add_textarea_field_to_group('file','notes',4,43);
        return $undf->render_page();
        
        $mime_types=$this->db->query("select mime_type,name from recognised_mime_type");
        
        $js='<script>
            $(document).ready(function() {
                $("#fileUploadForm_error_message_container").hide();
                
                $("#fileUploadForm_fileToUpload").change(function(){
                    if ($("#fileUploadForm_fileToUpload")[0].files.length>0) {
                        $("#fileUploadForm_chosen_filename").val($("#fileUploadForm_fileToUpload")[0].files[0].name);
                        var raw_type=$("#fileUploadForm_fileToUpload")[0].files[0].type;
                        
                        var document_lookup={
        ';
        foreach($mime_types as $single_type) {
            $js.='"'.$single_type['mime_type'].'":"'.$single_type['name'].'",';
        }
        $js.='
                        };
                        var converted_type=document_lookup[raw_type];
                        if (converted_type==null) {
                            converted_type="Unrecognised";
                        }
                        
                        $("#content_Documents_fileUploadForm_filetype").val(converted_type);
                    }
                    $("#fileUploadForm_error_message_container").hide();
                });
                
                //handle the save button
                $("#fileUploadFormUploadButton").click(function(e) {
                    e.preventDefault();
                    if ($("#fileUploadForm_fileToUpload")[0].files.length>0) {
                        var file_size=$("#fileUploadForm_fileToUpload")[0].files[0].size;
                        var allowed_filesize=parseInt($("#fileUploadFormmax_filesize").val());
                        if(file_size<=allowed_filesize) {
                            var mydata=new FormData();
                            mydata.append("page","Documents_Submit_Upload_New_Document");
                            mydata.append("notes",$("#content_Documents_fileUploadForm_notes").val());
                            mydata.append("description",$("#content_Documents_fileUploadForm_description").val());
                            mydata.append("derived_filetype",$("#content_Documents_fileUploadForm_filetype").val());
                            mydata.append("datafile",$("#fileUploadForm_fileToUpload")[0].files[0]);
                            $( "#fileUploadForm_dialog" ).dialog({
                                autoOpen: true,
                                closeOnEscape: false,
                                title: "File uploading in progress...",
                                width: 400,
                                height: 0,
                                modal: true,
                            });
                            $.ajax({
                                type: "POST",
                                url: "page_responder.php",
                                enctype: "multipart/form-data",
                                data: mydata,
                                cache: false,
                                processData: false,
                                contentType: false,
                        
                            })
                            .done(function(e){
                                //the response may start with an error string, or success string, we need to handle wither case appropriately
                                $( "#fileUploadForm_dialog" ).dialog( "destroy" ).remove();
                                if (e.length>=5 && e.substr(0,5)=="error") {
                                    $("#fileUploadForm_error_message").html(e.substr(5));
                                    $("#fileUploadForm_error_message_container").show();
                                }
                                else if (e.length>=7 && e.substr(0,7)=="success") {
                                    var tabs=$("div#tabs ul").children();
                                    for (var i=0; i<tabs.length; i++) {
                                        if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                                            $("div#about").append(e.substr(7));
                                        }
                                    }
                                    close_current_tab();
                                }
                                
                            });
                        }
                        else {
                            var reported_file_size=file_size;
                        
                            if (file_size>1000 && file_size<=1000000) {
                                reported_file_size=Math.round(file_size/1000);
                                reported_file_size+="KB";
                            }
                            else if(file_size>1000000 && file_size<=1000000000) {
                                reported_file_size=(file_size/1000000).toFixed(1);
                                reported_file_size+="MB";
                            }
                            else if(file_size>1000000000 && file_size<=1000000000000) {
                                reported_file_size=(file_size/1000000000).toFixed(1);
                                reported_file_size+="GB";
                            }
                            $("#fileUploadForm_error_message").html("Filesize of "+reported_file_size+" is too big");
                            $("#fileUploadForm_error_message_container").show();
                        }
                    }
                    else {
                        alert("Choose a file to upload first");
                    }
                    
                });
                
                //handle the cancel button
                $("#fileUploadFormCancelButton").click(function() {
                    close_current_tab();
                });
            });
            </script>';
        $html='
            <div id="fileUploadForm_dialog"></div>
            <table class="page_container">
                <tr>
                    <td colspan="2">
                        <h1>Upload A File</h1>
                    </td>
                </tr>
                
                <tr>
                    <td>
                        <form id="fileUploadForm" method="POST">
                        <input style="display:none" type="file" id="fileUploadForm_fileToUpload" />
                        <label class="file_upload_label" for="fileUploadForm_fileToUpload">Choose a file to upload</label>
                    </td>
                    <td>
                        <input id="fileUploadForm_chosen_filename" disabled type="text" placeholder="Filename to upload..." style="width: 296px;" />
                    </td>
                </tr>
                
                <tr>
                    <td colspan="2">
                        <div id="fileUploadForm_error_message_container" class="ui-widget">
                            <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
                                <p>
                                    <span class="ui-icon ui-icon-alert" 
                                        style="float: left; margin-right: .3em;"></span>
                                    <strong>Alert:</strong><span id="fileUploadForm_error_message"></span>
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr><th class="left_col">File Type</th><td><input disabled id="content_Documents_fileUploadForm_filetype" style="width: 296px;" type="text" /></td></tr>
                <tr><th class="left_col">Description</th><td><input id="content_Documents_fileUploadForm_description" style="width: 296px;" type="text" /></td></tr>
                <tr><th class="left_col">Notes</th><td><textarea id="content_Documents_fileUploadForm_notes" cols="43" rows="4"></textarea></td></tr>
                <tr>
                    <td class="left_col button_row">
                        <button type="button" id="fileUploadFormCancelButton">Cancel</button>
                    </td>
                    <td class="right_col button_row">
                        <button type="button" id="fileUploadFormUploadButton">Upload</button>
                    </td>
                </tr>
                
                <input type="hidden" id="fileUploadFormmax_filesize" value="'.$this->allowed_filesize.'" />
                
                </form>
            </table>
        ';
        return $js.$html;
    }
    
    #provide a datatable ajax data source for uploads
    private function ajax_UploadedDocumentsSource() {
        $sql_result=$this->db->query("select * from evidence");
        $date_format=$this->db->query("select value from configuration where description='short date format'")[0]['value'];
        $data=array();
        foreach ($sql_result as $single_result) {
            $unixDate=strtotime($single_result['date_uploaded']);
            $dateString=date($date_format,$unixDate);
            $fileSize=$this->bytes_readable($single_result['file_size_bytes']);
            $fileName=substr($single_result['full_path_to_file'],strlen($this->upload_folder)+1);
            $single_row=array();
            $single_row['DT_RowId']='upload_datatable_row_evidence_id_'.$single_result['evidence_id'];
            $single_row['full_path_to_file']=$fileName;
            $single_row['file_size_bytes']=$fileSize;
            $single_row['simplified_filetype']=$single_result['simplified_filetype'];
            $single_row['description']=$single_result['description'];
            $single_row['date_uploaded']=$dateString;
            $single_row['b1']="<a target='_blank' download href='".$single_result['full_path_to_file']."'><button title='Download This File' style='width:25px;height:25px;' type='button' name='content_documents_review_download_".$single_result['evidence_id']."' class='download_button'></button></a>";
            $single_row['b2']="<button title='Edit The Details of this File' style='width:25px;height:25px;' type='button' name='content_documents_review_edit_".$single_result['evidence_id']."' class='edit_button'></button>";
            $single_row['b3']="<button title='Delete This File' style='width:25px;height:25px;' type='button' name='content_documents_review_delete_".$single_result['evidence_id']."' class='delete_button'></button>";
            $data[]=$single_row;
        }
        $wrapper=array();
        $wrapper['data']=$data;
        return json_encode($wrapper);
    }
    
    //returns the page for reviewing previously uploaded documents
    //TODO: move most of these definitions to a form handler, like BasicForm, maybe DatatableForm?
    //TODO: It can take more of the setup parameters and allow better and more succinct customisation of the datatable, including things like adding buttons, button dialog callbacks
    //TODO: columns to order on, callback function names for AJAX sources, uploads, updates submits etc, setting a row ID as well.
    private function page_ReviewUploadedDocuments() {
        $html='<div id="content_documents_upload_update_dialog_dialog"></div><table class="page_container"><tr><td><h1>Review Uploads</h1></td></tr><tr><td style="border:2px solid black;border-radius: 5px;background-color:black;padding:5px;">';
        $html.='<div id="content_documents_review_uploads" style="background-color:black;">';
        $html.='<table id="content_documents_review_uploads_table" class="display page_datatable_layout" cellspacing="0"" width="100%"><thead><tr>';
        
        #data table header
        $html.='<th>Filename</th><th>File Size</th><th>File Category</th><th>Description</th><th>Date Uploaded</th><th></th><th></th><th></th>';
        $html.='</tr></thead></tbody></tbody></table>';
        $html.='</div></td></tr></table>';
        
        #define the Javascript
        $js='<script>
            $(document).ready(function () {
                $("body").unbind("click.uploads");
                var oTable=$("#content_documents_review_uploads_table").DataTable({
                    paging: true, 
                    searching: true, 
                    destroy: true,
                    autoWidth: false,
                    ajax: "page_responder.php?page=Documents_Uploaded_Documents_Source",
                    rowId: "evidence_id",
                    aaSorting: [4,"dsc"],
                    columns: [
                            { data: "full_path_to_file" },
                            { data: "file_size_bytes" },
                            { data: "simplified_filetype" },
                            { data: "description" },
                            { data: "date_uploaded" },
                            { data: "b1" },
                            { data: "b2" },
                            { data: "b3" }
                    ],
                    columnDefs: [
                        { className: "datatable_button_styling", "targets": [5,6,7] }
                    ],
                    "fnInfoCallback": function(oSettings, iStart, iEnd, iMax, iTotal, sPre) {
                        $(".download_button").button({icons: {primary: "ui-icon-circle-arrow-s"},text:false});
                        $(".edit_button").button({icons: {primary: "ui-icon-pencil"},text:false});
                        $(".delete_button").button({icons: {primary: "ui-icon-trash"},text:false});
                    }
                    
                });
                
                //define what happens when the user clicks a delete button for one of the uploads
                $("body").on("click.uploads",".delete_button",function(e){
                    e.stopPropagation();
                    var this_buttons_name=this.name;
                    var bits=this_buttons_name.split("_");
                    var this_id=bits[bits.length-1];
                    var resp=confirm("Are you sure you want to delete this file?");
                    if(resp) {
                        $.ajax({
                            type: "POST",
                            url: "page_responder.php",
                            data: 
                            {
                                page:"Documents_Delete_Upload",
                                evidence_id:this_id,
                            }
                        })
                        .done(function(e){
                            var tabs=$("div#tabs ul").children();
                            for (var i=0; i<tabs.length; i++) {
                                if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                                    $("div#about").append(e);
                                }
                            }
                            oTable.ajax.reload( null, false );
                        });
                    }
                });
                
                //bind a click listener to ALLLLL edit buttons, both new and old
                $("body").on("click.uploads",".edit_button",function(e){
                    e.stopPropagation();
                    var this_buttons_name=this.name;
                    var bits=this_buttons_name.split("_");
                    var this_id=bits[bits.length-1];
                    
                    //setup the dialog box for this use case
                    $( "#content_documents_upload_update_dialog_dialog" ).dialog({
                        autoOpen: false,
                        width: 700,
                        modal: true,
                        buttons: [
                            {
                                text: "Update",
                                click: function() {
                                    $.ajax({
                                        type: "POST",
                                        url: "page_responder.php",
                                        data: 
                                        {
                                            page:"Documents_Update_Upload_Details",
                                            evidence_id:this_id,
                                            this_filename: $("#content_documents_upload_update_dialog_filename").val(),
                                            description: $("#content_documents_upload_update_dialog_description").val(),
                                            notes: $("#content_documents_upload_update_dialog_notes").val(),
                                        }
                                    })
                                    .done(function(e){
                                        var tabs=$("div#tabs ul").children();
                                        for (var i=0; i<tabs.length; i++) {
                                            if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                                                $("div#about").append(e);
                                            }
                                        }
                                        //reload the datatable and close the dialog box
                                        oTable.ajax.reload( null, false );
                                        $("#content_documents_upload_update_dialog_dialog").dialog( "close" );
                                    });
                                }
                            },
                            {
                                text: "Cancel",
                                click: function() {
                                    $( this ).dialog( "close" );
                                }
                            }
                        ]
                    });
                    
                    //actually put the data into the dialog box to be edited
                    $.ajax(
                    {
                        type: "POST",
                        url: "page_responder.php",
                        data: {
                            page: "Documents_Load_Upload_Details",
                            evidence_id: this_id,
                        }
                    })
                    .done(function(e)
                    {
                        $("#content_documents_upload_update_dialog_dialog").html(e);
                        $("#content_documents_upload_update_dialog_dialog").dialog("option","title","Edit Upload Details");
                        $("#content_documents_upload_update_dialog_dialog").dialog("open");
                    });
                    
                });
                
            });
            </script>';
        return $js.$html;
    }
}
?>
