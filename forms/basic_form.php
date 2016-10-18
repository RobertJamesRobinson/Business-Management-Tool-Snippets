<?php
class BasicForm {
    protected $db;
    private $messages;
    private $page_fields;
    private $heading;
    private $buttons;
    private $id_field;
    private $id_field_value;
    private $feature;
    private $selector_title;
    private $selector_fields;
    private $available_buttons;
    private $form_name;
    private $full_field_list;
    private $selector_field_list;
    private $selector_query;
    private $annotations;
    private $validators;
    
    //standard constructor, takes a database connection
    public function __construct(&$db) {
        $this->db=$db;
        $this->heading='';
        $this->buttons=array();
        $this->page_fields=array();
        $this->id_field='';
        $this->id_field_value='';
        $this->feature='';
        $this->available_buttons=array('cancel','update','save','delete','upload');
        $this->selector_title='';
        $this->selector_fields=array();
        $this->full_field_list=array();
        $this->selector_field_list=array();
        $this->form_name='';
        $this->selector_query='';
        $this->annotations=array();
        $this->validators=array();
        //when the form becomes an upload form, the form submission method needs to change
        //TODO: makle the form submission method the upload form type always
    }
    
    //returns the messages object data
    public function get_messages() {
        return $this->messages;
    }
    
    public function set_selector_query($query) {
        $this->selector_query=$query;
    }
    
    public function set_full_field_list($list) {
        $this->full_field_list=$list;
    }
    
    public function set_selector_field_list($list) {
        $this->selector_field_list=$list;
    }
    
    //returns the message object data as a list of strings which can be near immediately rendered to the user
    public function get_message_strings() {
        foreach($this->messages as $singleMessage) {
            
        }
    }
    
    //set the name of this form, the name used in id's across the page
    public function set_formname($name) {
        $this->form_name=$name;
    }
    
    //add a single field to our list of fields to be used for the selector dialog
    public function add_selector_field($field) {
        $this->selector_fields[]=$field;
    }
    
    //set all the selector fields at once
    public function set_selector_fields($fields) {
        $this->selector_fields=$fields;
    }
    
    public function set_id_field($set_id) {
        $this->id_field=$set_id;
    }
    
    public function set_id_field_value($id_field_value) {
        $this->id_field_value=$id_field_value;
    }
    
    public function set_selector_title($title) {
        $this->selector_title=$title;
    }
    
    //set a page heading for this page level form
    public function set_heading($heading) {
        $this->heading=$heading;
        if (!isset($this->form_name) or $this->form_name=='') {
            $this->form_name=$heading;
            error_log("BasicForm: Had to revert form name to form heading");
        }
    }
    
/*  add a page level button, examples are "update","save","cancel","delete"
    by setting one of these buttons on the page you are basically defining the pages behavior. 
    update performs a DB update on the fields mentioned in the form
    save performs an insert on the fields
    cancel always cancels all page activity and closes the page
    delete takes the primary key field mentioned on the page and deletes it from the database */
    public function add_page_button($button) {
        $this->buttons[]=$button;
    }
    
    //same as above except assumes a simple array of button strings has been passed in
    public function set_page_buttons($buttons) {
        $this->buttons=$buttons;
    }
    
    //sets the feature name, should be capitalised. is used by the page responder to determine which dffeature to target for page responses
    public function set_feature($feature_name) {
        $this->feature=ucwords($feature_name);
    }
    
    //sets a hidden field, using the passed in field name and value pair, so that this form can pass a distinct entity id back to the consuming page responder
    public function set_id_field_and_value($fieldname,$value) {
        $this->id_field=$fieldname;
        $this->id_field_value=$value;
    }
    
    //takes a 3 dimensional array where the first value is always the forms sub heading, if the first value is a blank string, then the subheading will be skipped
    //for example: [0=>['Name','First Name','Last Name'], 1=>['Address','Street Number','Street Name']]
    public function set_page_fields($pfields) {
        $this->page_fields=$pfields;
    }
    
    //add a single sub heading group
    public function add_group($group_name) {
        $this->page_fields[]=array('sub_heading'=>$group_name,'fields'=>array());
    }
    
    //adds a validation step to a field
    public function add_validation_to_field($group_name, $field_name) {
        foreach(array_keys($this->page_fields) as $i) {
            if ($this->page_fields[$i]['sub_heading']==$group_name) {
                $this->validators[]=str_replace(' ','_',$field_name);
                return true;
            }
        }
        error_log("BasicForm: add_validation_to_field: Attempt was made to add a validation step to a field ($field_name), group ($group_name) which couldnt be found");
        return false;
    }
    
    //add a single text field to an existing sub heading group
    public function add_text_field_to_group($group_name,$field_name,$field_length) {
        foreach(array_keys($this->page_fields) as $i) {
            if ($this->page_fields[$i]['sub_heading']==$group_name) {
                $this->page_fields[$i]['fields'][]=array('field_name'=>$field_name,'field_type'=>'text','length'=>$field_length);
                return true;
            }
        }
        error_log("BasicForm: add_text_field_to_group: Attempt was made to add a text field ($field_name) to a group which couldnt be found ($group_name)");
        return false;
    }
    
    //add a single text field to an existing sub heading group
    public function add_currency_field_to_group($group_name,$field_name,$field_length) {
        foreach(array_keys($this->page_fields) as $i) {
            if ($this->page_fields[$i]['sub_heading']==$group_name) {
                $this->page_fields[$i]['fields'][]=array('field_name'=>$field_name,'field_type'=>'currency','length'=>$field_length);
                return true;
            }
        }
        error_log("BasicForm: add_currency_field_to_group: Attempt was made to add a currency field ($field_name) to a group which couldnt be found ($group_name)");
        return false;
    }
    
    //add a single combo box field to an existing group. the data source field should be in the format "<db_table>|<key_column>|<value_column>"
    public function add_combo_field_to_group($group_name,$field_name,$combo_data_source_defn) {
        foreach(array_keys($this->page_fields) as $i) {
            if ($this->page_fields[$i]['sub_heading']==$group_name) {
                $this->page_fields[$i]['fields'][]=array('field_name'=>$field_name,'field_type'=>'combo','combodata'=>$combo_data_source_defn);
                return true;
            }
        }
        error_log("BasicForm: add_combo_field_to_group: Attempt was made to add a combo field ($field_name) to a group which couldnt be found ($group_name)");
        return false;
    }
    
    //add a single textarea field to an existing sub heading group
    public function add_textarea_field_to_group($group_name,$field_name,$rows,$columns) {
        foreach(array_keys($this->page_fields) as $i) {
            if ($this->page_fields[$i]['sub_heading']==$group_name) {
                $this->page_fields[$i]['fields'][]=array('field_name'=>$field_name,'field_type'=>'textarea','rows'=>$rows,'columns'=>$columns);
                return true;
            }
        }
        error_log("BasicForm: add_textarea_field_to_group: Attempt was made to add a textarea field ($field_name) to a group which couldnt be found ($group_name)");
        return false;
    }
    
    //add a single label field to group, a label is linked to derived data from another field and is read only
    //for example an upload file field might need to show the files size or file type in a label field below the upload file field
    public function add_annotation_field_to_group($group_name,$field_name,$field_length,$no_match_value,$lookup_query,$anchor) {
        foreach(array_keys($this->page_fields) as $i) {
            if ($this->page_fields[$i]['sub_heading']==$group_name) {
                $this->page_fields[$i]['fields'][]=array('field_name'=>$field_name,'field_type'=>'annotation','length'=>$field_length,'lookup_no_match'=>$no_match_value,'lookup_query'=>$lookup_query,'anchor'=>$anchor);
                error_log(print_r($this->page_fields[$i]['fields'],true));
                return true;
            }
        }
        error_log("BasicForm: add_annotation_field_to_group: Attempt was made to add an annotation field ($field_name) to a group which couldnt be found ($group_name)");
        return false;
    }
    
    //there should only be one of these, as we cant yet provide support for multiple uploads on a single form yet
    public function add_upload_field_to_group($group_name,$field_name,$field_length,$placeholder_text,$max_filesize,$upload_button_text) {
        foreach(array_keys($this->page_fields) as $i) {
            if ($this->page_fields[$i]['sub_heading']==$group_name) {
                $this->page_fields[$i]['fields'][]=array('field_name'=>$field_name,'field_type'=>'upload','length'=>$field_length,'placeholder'=>$placeholder_text,'max_size'=>$max_filesize,'upload_button_text'=>$upload_button_text);
                return true;
            }
        }
        error_log("BasicForm: add_annotation_field_to_group: Attempt was made to add an annotation field ($field_name) to a group which couldnt be found ($group_name)");
        return false;
    }
    
    //returns all the HTML and Javascript required to render this simple form.
    public function render_page() {
        $form_id=str_replace(' ','',$this->form_name).'Form';
        $form_field_ids=array();
        $html='';
        $associated_upload_field=NULL;
        foreach ($this->page_fields as $single_group) {
            foreach($single_group['fields'] as $single_field) {
                if($single_field['field_type']=="upload") {
                    $associated_upload_field=$single_field;
                    
                }
            }
        }
        
        //sort out the dialog first
        if($this->id_field != '') {
            
            $sql_result=$this->db->query($this->selector_query);
            $table_data="";
            
            foreach($sql_result as $row) {
                $table_data.="<tr id='".$row[$this->id_field]."'>";
                foreach($this->selector_field_list as $selector_field) {
                    $table_data.="<td>".$row[$selector_field]."</td>";
                }
                $table_data.="</tr>";
            }
            
            $html.='<div id="'.str_replace(' ','_',$this->form_name).'_dialog" title="Dialog Title">';
            //put the html together for the selector and send it off with the JS
            $html.='<table id="'.str_replace(' ','_',$this->form_name).'_'.$this->id_field.'_selector" class="display" cellspacing="0"" width="100%"><thead><tr>';
            foreach($this->selector_field_list as $field) {
                $html.='<th>'.$field.'</th>';
            }
            $html.='</tr></thead></tbody>'.$table_data."</tbody></table>";
            $html.='</div>';
        }
            
        
        
        
        
        //add the start of the form using a table for layout
        $upload_warning_field_id=implode('_',array('content',$this->feature,str_replace(' ','_',$this->form_name),str_replace(' ','_',$associated_upload_field['field_name'])));
        $html.='
            <form id="'.$form_id.'" method="POST" >';
        if (!is_null($associated_upload_field)) {
            $html.='<div id="'.$upload_warning_field_id.'_dialog"></div>';
        }
        $html.='
                <table class="page_container">
                <tr><td colspan="2"><h1>'.$this->form_name.'</h1></td></tr>';
        
        //iterate over the fields, and groups of fields, and render the form
        foreach ($this->page_fields as $single_group) {
            $sub_heading=ucwords($single_group['sub_heading']);
            
            //add the sub heading for this group of fields
            $html.='<tr><th colspan="2" class="sub_heading"><h2>'.$sub_heading.'</h2></th></tr>';
            
            //add the fields, including the sub headings, for this grouping
            foreach($single_group['fields'] as $single_field) {
                
                //set the id that will be used to extract data from this field in JS and define it on the page
                $input_id=implode('_',array('content',$this->feature,str_replace(' ','_',$this->form_name),str_replace(' ','_',$single_field['field_name'])));
                $form_field_ids[str_replace(' ','_',$single_field['field_name'])]=$input_id;
                //add each field into the HTML
                switch ($single_field['field_type']) {
                    //for a text field, that is to be on a single row
                    case 'text':
                        $html.='<tr><th class="left_col">'.ucwords($single_field['field_name']).'</th><td><input id="'.$input_id.'" type="text" style="width:'.$single_field['length'].'px;" /></td></tr>
                            <tr><th></th><td><span id="'.$input_id.'_error"></span></td></tr>';
                    break;
                    
                    //for a text field, that is to be on a single row
                    case 'currency':
                        $html.='<tr><th class="left_col">'.ucwords($single_field['field_name']).' $</th><td><input id="'.$input_id.'" type="text" style="width:'.$single_field['length'].'px;" /></td></tr>
                            <tr><th></th><td><span id="'.$input_id.'_error"></span></td></tr>';
                    break;
                    
                    //for a text area which is supposed to be multiple rows and columns
                    case 'textarea':
                        $html.='<tr><th class="left_col">'.ucwords($single_field['field_name']).'</th><td><textarea id="'.$input_id.'" cols="'.$single_field['columns'].'" rows="'.$single_field['rows'].'"></textarea></td></tr>
                            <tr><th></th><td><span id="'.$input_id.'_error"></span></td></tr>';
                    break;
                    
                    //for a label area which gets its content from another field
                    case 'annotation':
                    //TODO: test annotation types on something other than an upload field
                        //'field_name'=>$field_name,
                        //'field_type'=>'annotation',
                        //'length'=>$field_length,
                        //'lookup_no_match'=>$no_match_value,
                        //'lookup_query'=>$lookup_query,
                        //'anchor'=>$anchor
                        $anchors=explode('|',$single_field['anchor']);
                        $html.='<tr><th class="left_col">'.ucwords($single_field['field_name']).'</th><td><input id="'.$input_id.'" disabled type="text" style="width:'.$single_field['length'].'px;" /></td></tr>
                            <tr><th></th><td><span id="'.$input_id.'_error"></span></td></tr>';
                        $lookup_data=$this->db->query($single_field['lookup_query']);
                        
                        if ($anchors[2]=="mime_type") {
                            $anchor_id=implode('_',array('content',$this->feature,str_replace(' ','_',$this->form_name),str_replace(' ','_',$anchors[1]),'fileToUpload'));
                            $annotation_js='
                                var mime_type=$("#'.$anchor_id.'")[0].files[0].type;
                                var document_lookup={';
                            foreach($lookup_data as $single_entry) {
                                $annotation_js.='"'.$single_entry['data_key'].'":"'.$single_entry['data_value'].'",';
                            }
                            $annotation_js.='};
                            var converted_type=document_lookup[mime_type];
                            if (converted_type==null) {
                                converted_type="'.$single_field['lookup_no_match'].'";
                            }
                            $("#'.$input_id.'").val(converted_type);';
                            
                            $this->annotations[]=$annotation_js;
                        }
                        
                        
                    break;
                    
                    //for an upload field which gets its content with a file input
                    case 'upload':
                        $html.='<tr><td>
                            <input style="display:none" type="file" id="'.$input_id.'_fileToUpload" />
                            <label class="file_upload_label" for="'.$input_id.'_fileToUpload">'.$single_field['upload_button_text'].'</label>
                            </td><td>
                            <input id="'.$input_id.'_filename" disabled type="text" placeholder="'.$single_field['placeholder'].'" style="width: '.$single_field['length'].'px;" />
                            </td></tr>';
                        //need a maximum upload size hidden field somewhere
                        $html.='<input type="hidden" id="'.$input_id.'_max_filesize" value="'.$single_field['max_size'].'" />';
                            
                        //also need an error message container here
                        $html.='<tr><td colspan="2">
                            <div id="'.$input_id.'_error_message_container" class="ui-widget">
                            <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
                            <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span><strong>Alert:</strong><span id="'.$input_id.'_error_message"></span></p>
                            </div></div></td></tr>';
                            
                    break;
                    
                    //for a combo or drop down box which should have a lookup defined
                    case 'combo':
                        //'combodata'=>'salutation|salutation_id|description'
                        $html.='<tr><th class="left_col">'.ucwords($single_field['field_name']).'</th><td><select id="'.$input_id.'">';
                        $combo_fields=explode('|',$single_field['combodata']);
                        $queryResult=$this->db->query("select * from ".$combo_fields[0]);
                        foreach ($queryResult as $row) {
                            $html.='<option value="'.$row[$combo_fields[1]].'">'.ucwords($row[$combo_fields[2]]).'</option>';
                        }
                        $html.='</select></td></tr><tr><th></th><td><span id="'.$input_id.'_error"></span></td></tr>';
                    break;
                }
            }
        }
        
        //add the buttons
        $counter=1;
        foreach($this->buttons as $button) {
            
            //at the start, if the column count is odd, start a new line for the buttons
            if($counter%2!=0) {
                $html.='<tr>';
            }
            
            //right column check and set TD class
            $this_class='left_col button_row';
            if ($counter%2==0) {
                $this_class='right_col button_row';
            }
            
            //place the button
            $this_button_id=implode('',array(str_replace(' ','',$this->form_name), $button, 'Button')); //clientDeleteCancelButton
            if (in_array($button,$this->available_buttons)) {
                $html.='<td class="'.$this_class.'"><button type="button" id="'.$this_button_id.'">'.ucwords($button).'</button></td>';
            }
            else {
                error_log("BasicForm: Unable to interpret button request from basic_form->render_page call, button requested was: ".$button);
            }
            
            //after the counter increases, if the counter is odd, we need to close this line of buttons
            $counter+=1;
            if($counter%2!=0) {
                $html.='</tr>';
            }
        }
        
        //finish off the button rows if there were an odd number of buttons, and the row ender above (inside the loop) didnt fire
        if($counter%2==0) {
            $html.='</tr>';
        }
        
        //close the table, set an identifier field if required, ie, a hidden id field for updates or removes from the DB and close the form.
        $html.='</table>';
        if($this->id_field != '') {
            $this_id=implode('_',array('content',$this->feature,str_replace(' ','_',$this->form_name),$this->id_field));
            $html.='<input id="'.$this_id.'" type="hidden" value="'.$this->id_field_value.'" />';
        }
        $html.='</form>';
        
        //make the javascript part of the response
        $js='<script>
            $(document).ready(function () {
        ';
        
        //if a hidden id field has been added and a value set, then we need to render a selector window to populate the table correctly
        if($this->id_field != '') {
            $js.='
            
                //define the data table
                var table=$("#'.str_replace(' ','_',$this->form_name).'_'.$this->id_field.'_selector").DataTable({paging: true, searching: true, destroy: true});
                
                //setup the dialog box for this use case
                $( "#'.str_replace(' ','_',$this->form_name).'_dialog" ).dialog({autoOpen: false,width: 700,modal: true,
                    buttons: [
                        {
                            text: "Select",
                            click: function() {
                                var rowSelected=$(".selected").attr("id");
                                if (!rowSelected) {
                                    alert("You need to select a row by clicking on it first.");
                                }
                                else {
                                    load_data_into_form(rowSelected);
                                }
                            }
                        },
                        {
                            text: "Cancel",
                            click: function() {
                                $( this ).dialog( "destroy" ).remove();
                                close_current_tab();
                            }
                        }
                    ]
                });
                
                //toggle selected row
                $("#'.str_replace(' ','_',$this->form_name).'_'.$this->id_field.'_selector tbody").on( "click", "tr", function () {
                    if ( $(this).hasClass("selected") ) {
                        $(this).removeClass("selected");
                    }
                    else {
                        table.$("tr.selected").removeClass("selected");
                        $(this).addClass("selected");
                    }
                });
                
                //load the record into the form
                function load_data_into_form(selected_id) {
                    $.ajax({
                        type: "POST",
                        url: "page_responder.php",
                        data:{
                            page:"'.$this->feature.'_Load_'.str_replace(' ','_',$this->form_name).'_Data",
                            '.$this->id_field.':selected_id,
                        }
                    })
                    .done(function(e) {
                        var data=JSON.parse(e);';
                    
                    
                    
                    
                    
                    $first_done=false;
                    foreach($this->full_field_list as $field) {
                        if($field==$this->id_field) {
                            $js.='
                            $("#content_'.$this->feature.'_'.str_replace(' ','_',$this->form_name).'_'.$field.'").val(data["'.$field.'"]);
                            $("#content_'.$this->feature.'_'.str_replace(' ','_',$this->form_name).'_'.$field.'").trigger("change");';
                        }
                        else {
                            $js.='$("#content_'.$this->feature.'_'.str_replace(' ','_',$this->form_name).'_'.$field.'").val(data["'.$field.'"]);';
                        }
                        $first_done=true;
                    }
                    $js.='$("#'.str_replace(' ','_',$this->form_name).'_dialog").dialog( "destroy" ).remove();
                    });
                }';
            
            $js.='
                $("#'.str_replace(' ','_',$this->form_name).'_dialog").dialog("option","title","'.$this->selector_title.'");
                $("#'.str_replace(' ','_',$this->form_name).'_dialog").dialog("open");
            ';
        }
        
        
        
        //add the validation function, if required
        $validate_page_name=implode('_',array($this->feature,'Validate',str_replace(' ','_',ucwords($this->form_name))));
        if (count($this->validators)>0) {
            $js.='
                function '.$validate_page_name.'_validate() {
                    //hide all validation fields, in case they were populated last time
                    ';
                    foreach($form_field_ids as $field_name=>$field_id) {
                        if (in_array($field_name,$this->validators)) {
                            $js.='$("#'.$field_id.'_error").hide();';
                        }
                    }
                    $js.='
                    var id_lookups={';
                    foreach($form_field_ids as $field_name=>$field_id) {
                        if (in_array($field_name,$this->validators)) {
                            $js.='"'.$field_name.'":"'.$field_id.'",';
                        }
                    }
                    $js.='};
                    
                    $.ajax({
                        type: "POST",
                        url: "page_responder.php",
                        data: 
                        {
                            page:"'.$validate_page_name.'",';
            foreach($form_field_ids as $field_name=>$field_id) {
                if (in_array($field_name,$this->validators)) {
                    $js.=$field_name.':$("#'.$field_id.'").val(),';
                }
            }
            $js.='      }
                    })
                    .done(function(e){
                        //if e is true, then there is nothing to do, otherwise, 
                        var errors=JSON.parse(e);
                        //if theres no errors, return true
                        if (Object.keys(errors).length==0) {
                            blind_submit();
                        }
                        //populate the error messages, and return false
                        for (var error in errors) {
                            if (errors.hasOwnProperty(error)) {
                                var this_id=id_lookups[error];
                                $("#"+this_id+"_error").html("<p style=\'color:red;\'>"+errors[error]+"</p>");
                                $("#"+this_id+"_error").show();
                            }
                        }
                    });
                }
            ';
        }
        
        //add any button responders needed
        foreach ($this->buttons as $button) {
            if (in_array($button,$this->available_buttons)) {
                $button_id=implode('',array(str_replace(' ','',$this->form_name), $button, 'Button'));;
                
                //javascript response to the CANCEL button
                if ($button=='cancel') {
                    $js.='
                        $("#'.$button_id.'").click(function() {
                            close_current_tab();
                        });';
                }
                //javascript response to the SAVE button
                if ($button=='save') {
                    $page_string=implode('_',array($this->feature,'Submit',str_replace(' ','_',ucwords($this->form_name))));
                    $js.='//handle the save button
                        $("#'.$button_id.'").click(function() {
                            ';
                    //if there is validation on this form, use the validation function generated above, otherwise use the blind submit
                    if (count($this->validators)>0) {
                        $js.=$validate_page_name.'_validate();
                        });';
                    }
                    else {
                        $js.='blind_submit();
                        });';
                    }
                    
                    //define the blind submit function
                    $js.='
                        function blind_submit() {
                            $.ajax({
                                type: "POST",
                                url: "page_responder.php",
                                data: 
                                {
                                    page:"'.$page_string.'",';
                    foreach($form_field_ids as $field_name=>$field_id) {
                        $js.=$field_name.':$("#'.$field_id.'").val(),';
                    }
                    $js.='      }
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
                        }';
                            
                }
                
                //javascript repsponse to the DELETE button
                if ($button=='delete') {
                    $page_string=implode('_',array($this->feature,'Delete',str_replace(' ','_',ucwords($this->form_name))));
                    $js.='//handle the delete button
                        $("#'.$button_id.'").click(function() {
                            ';
                    //if there is validation on this form, use the validation function generated above, otherwise use the blind submit
                    if (count($this->validators)>0) {
                        $js.=$validate_page_name.'_validate();
                        });';
                    }
                    else {
                        $js.='blind_submit();
                        });';
                    }        
                    //define the blind submit function
                    $js.='
                        function blind_submit() {
                            $.ajax({
                                type: "POST",
                                url: "page_responder.php",
                                data: 
                                {
                                    page:"'.$page_string.'",';
                    $js.=$this->id_field.':$("#'.implode('_',array('content',$this->feature,str_replace(' ','_',$this->form_name),$this->id_field)).'").val(),';
                    $js.='      }
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
                        }';
                }
                
                //javascript response to an UPLOAD button, meant to be used in conjunction with a form containing a file upload field
                if($button=='upload') {
                    $associated_upload_field=NULL;
                    foreach ($this->page_fields as $single_group) {
                        foreach($single_group['fields'] as $single_field) {
                            if($single_field['field_type']=="upload") {
                                $associated_upload_field=$single_field;
                                
                            }
                        }
                    }
                    $field_id=implode('_',array('content',$this->feature,str_replace(' ','_',$this->form_name)));//,str_replace(' ','_',$associated_upload_field['field_name'])
                    $upload_field_id=implode('_',array('content',$this->feature,str_replace(' ','_',$this->form_name),str_replace(' ','_',$associated_upload_field['field_name'])));
                    $this_button_id=implode('',array(str_replace(' ','',$this->form_name), $button, 'Button'));
                    $js.='
                        //hide the error container at page load
                        $("#'.$upload_field_id.'_error_message_container").hide();
                        
                        //handle any hooks in the filename update changes
                        $("#'.$upload_field_id.'_fileToUpload").change(function(){
                            if ($("#'.$upload_field_id.'_fileToUpload")[0].files.length>0) {
                                $("#'.$upload_field_id.'_filename").val($("#'.$upload_field_id.'_fileToUpload")[0].files[0].name);';
                                
                                //check for annotations
                                foreach($this->annotations as $single_hook) {
                                    $js.=$single_hook;
                                }
                                
                                $js.='}
                            $("#'.$upload_field_id.'_error_message_container").hide();
                        });
                        
                        //handle the upload button
                        $("#'.$button_id.'").click(function(e) {
                            e.preventDefault();
                            if ($("#'.$upload_field_id.'_fileToUpload")[0].files.length>0) {
                                var file_size=$("#'.$upload_field_id.'_fileToUpload")[0].files[0].size;
                                var allowed_filesize=parseInt($("#'.$upload_field_id.'_max_filesize").val());
                                if(file_size<=allowed_filesize) {
                                    var mydata=new FormData();
                                    mydata.append("page","'.implode('_',array($this->feature,'Submit',str_replace(' ','_',ucwords($this->form_name)))).'");
                                    ';
                                    
                                    //add field submission data
                                    foreach ($this->page_fields as $single_group) {
                                        foreach($single_group['fields'] as $single_field) {
                                            if ($single_field['field_type']=='text' or $single_field['field_type']=='currency' or $single_field['field_type']=='textarea' or $single_field['field_type']=='annotation' or $single_field['field_type']=='combo') {
                                                $js.='mydata.append("'.str_replace(' ','_',$single_field['field_name']).'",$("#'.$field_id.'_'.str_replace(' ','_',$single_field['field_name']).'").val());';
                                            }
                                            if ($single_field['field_type']=='upload') {
                                                $js.='mydata.append("datafile",$("#'.$upload_field_id.'_fileToUpload")[0].files[0]);';
                                            }
                                            
                                        }
                                    }
                                    
                                    $js.='
                                    $( "#'.$upload_field_id.'_dialog" ).dialog({
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
                                        $( "#'.$upload_field_id.'_dialog" ).dialog( "destroy" ).remove();
                                        if (e.length>=5 && e.substr(0,5)=="error") {
                                            $("#'.$upload_field_id.'_error_message").html(e.substr(5));
                                            $("#'.$upload_field_id.'_error_message_container").show();
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
                                    $("#'.$field_id.'_error_message").html("Filesize of "+reported_file_size+" is too big");
                                    $("#'.$field_id.'_error_message_container").show();
                                }
                            }
                            else {
                                alert("Choose a file to upload first");
                            }
                        });';
                }
                
                //javascript response to the UPDATE button
                if ($button=='update') {
                    $page_string=implode('_',array($this->feature,'Update',str_replace(' ','_',ucwords($this->form_name))));
                    $js.='//handle the update button
                        $("#'.$button_id.'").click(function() {
                            ';
                    //if there is validation on this form, use the validation function generated above, otherwise use the blind submit
                    if (count($this->validators)>0) {
                        $js.=$validate_page_name.'_validate();
                        });';
                    }
                    else {
                        $js.='blind_submit();
                        });';
                    } 
                    //define the blind submit function
                    $js.='
                        function blind_submit() {
                            $.ajax({
                                type: "POST",
                                url: "page_responder.php",
                                data: 
                                {
                                    page:"'.$page_string.'",';
                    foreach($form_field_ids as $field_name=>$field_id) {
                        $js.=$field_name.':$("#'.$field_id.'").val(),';
                    }
                    $js.=$this->id_field.':$("#'.implode('_',array('content',$this->feature,str_replace(' ','_',$this->form_name),$this->id_field)).'").val(),';
                    $js.='      }
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
                        };';
                }
            }
            else {
                error_log("BasicForm: Unable to interpret button request from basic_form->render_page call, button requested was: ".$button);
            }
        }
        
        //close off and finalise the JS
        $js.='});</script>';
        return $js.$html;
    }
}
?>
