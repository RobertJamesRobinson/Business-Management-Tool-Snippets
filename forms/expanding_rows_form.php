<?php
class ExpandingRowsForm {
    protected $db;
    private $messages;
    
    private $source_data_table;
    private $editable_columns;
    private $id_column;
    private $form_name;
    private $sub_heading;
    private $feature;
    
    //standard constructor, takes a database connection
    public function __construct(&$db) {
        $this->db=$db;
        $this->form_name='';
        $this->source_data_table='';
        $this->editable_columns=array();
        $this->id_column='';
        $this->sub_heading='';
        $this->feature='';
    }
    
    //add a new text field column to this form
    public function new_text_field($sub_heading,$table_field_name,$length) {
        $this->editable_columns[]=array('heading'=>$sub_heading,'field'=>$table_field_name,'type'=>'text','length'=>$length);
    }
    
    //add another textarea field column to this form
    public function new_textarea_field($sub_heading,$table_field_name,$rows,$cols) {
        $this->editable_columns[]=array('heading'=>$sub_heading,'field'=>$table_field_name,'type'=>'textarea','rows'=>$rows,'cols'=>$cols);
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
    
    //set the feature name, used to guid the page responder, this should match the main menu name used to store the menu for the page being rendered
    public function set_feature($feature_name) {
        $this->feature=$feature_name;
    }
    
    //set the name of the form, is used for id prefixes throughout the form
    public function set_form_name($form_name) {
        $this->form_name=$form_name;
    }
    
    //set the name of the table we will get our data from
    public function set_source_table($source_table_name) {
        $this->source_data_table=$source_table_name;
    }
    
    //set the column in the DB which hold the unique id column
    public function set_id_column($id_column) {
        $this->id_column=$id_column;
    }
    
    //set a page sub heading as a further descriptor for the form
    public function set_sub_heading($sub_heading) {
        $this->sub_heading=$sub_heading;
    }
    
    //returns all the HTML and Javascript required to render this simple form.
    public function render_form() {
        $short_form_name=str_replace(' ','',$this->form_name);
        
        //extract just the field names to get the correct data from the DB
        $editable_columns2=array();
        foreach($this->editable_columns as $single_field) {
            $editable_columns2[]=$single_field['field'];
        }
        
        //also add the unique identifier field
        $editable_columns2[]=$this->id_column;
        
        //get the existing data from the DB
        $fields=implode(',',$editable_columns2);
        $sql="select ".$fields." from ".$this->source_data_table;
        $rows=$this->db->query($sql);
        
        //start drawing the initial table
        $html='
            <table class="page_container" id="'.$short_form_name.'Table">
            <tr><td colspan="4"><h1>'.$this->form_name.'</h1></td></tr>
            <form id="'.$this->form_name.'Form" method="POST" >
                <tr><th colspan="2" class="sub_heading"><h2>'.$this->sub_heading.'</h2></th></tr><tr>';
                
                //place the column headings
                foreach($this->editable_columns as $field) {
                    $html.='<th style="text-align:left;">'.$field['heading'].'</th>';
                }
                $html.='</tr>';
                $counter=1;
                //place the existing data
                foreach($rows as $single_row) {
                    $html.='<tr id="'.$short_form_name.'_'.$counter.'">';
                    $single_row_id=$single_row[$this->id_column];
                    foreach($this->editable_columns as $field) {
                        $field_value=$single_row[$field['field']];
                        if($field['type']=="text") {
                            $html.='<td class="left_col"><input id="'.implode("-",array($short_form_name,$field["field"],'dbid',$single_row_id)).'" type="text" style="width:'.$field['length'].'px;" value="'.$field_value.'" /></td>';
                        }
                        elseif($field['type']=="textarea") {
                            $html.='<td class="left_col"><textarea id="'.implode("-",array($short_form_name,$field["field"],'dbid',$single_row_id)).'" type="textarea" rows="'.$field['rows'].'" cols="'.$field['cols'].'" value="">'.$field_value.'</textarea></td>';
                        }
                    }
                    $counter+=1;
                    $html.='<td class="vertical_center"><button style="width:25px;height:25px;" type="button" class="delete_button"></button></td></tr>';
                }
                
                //place the first blank row
                $html.='<tr id="'.$short_form_name.'_'.$counter.'" class="last_row">';
                foreach($this->editable_columns as $field) {
                    $tmp_date=time();
                    if($field['type']=="text") {
                        $html.='<td class="left_col"><input disabled id="'.implode("-",array($short_form_name,$field["field"],'noid',$tmp_date)).'" type="text" style="width:'.$field['length'].'px;" value="" /></td>';
                    }
                    elseif($field['type']=="textarea") {
                        $html.='<td class="left_col"><textarea disabled id="'.implode("-",array($short_form_name,$field["field"],'noid',$tmp_date)).'" type="textarea" rows="'.$field['rows'].'" cols="'.$field['cols'].'" value=""></textarea></td>';
                    }
                }
                $html.='<td class="vertical_center"><button style="width:25px;height:25px;" type="button" class="add_button"></button></td></tr>';
                
                //rows are done, put the page level buttons below the main table
                $html.='<tr><td class="left_col button_row"><button type="button" id="'.$short_form_name.'_Button_Cancel">Cancel</button></td><td class="right_col button_row"><button type="button" id="'.$short_form_name.'_Button_Save">Save</button></td></tr>
            </form>
            </table>
            ';
        $js='<script>
            $(document).ready(function(){
                $(".delete_button").button({icons: {primary: "ui-icon-trash"},text:false});
                $(".add_button").button({icons: {primary: "ui-icon-plus"},text:false});
                
                //handle the add button located at the end of the last row
                $("#'.$short_form_name.'Table").on("click", ".add_button",function(e) {
                    
                    //remove the old last row add icon and replace it with a delete icon 
                    $(".last_row").find("button").empty();
                    $(".last_row").find("button").addClass("delete_button");
                    $(".last_row").find("button").removeClass("add_button");
                    $(".last_row").find("input,textarea").prop("disabled",false);
                    $(".last_row").removeClass("last_row");
                    var rowCount=$("#'.$short_form_name.'Table").find("tr").length-3;
                    
                    //add a new last row
                    var tmp_date=new Date();
                    var tmp_row_id=tmp_date.getTime();
                    var new_row="<tr id=\"'.$short_form_name.'_"+rowCount+"\"class=\"last_row\">';
                    foreach($this->editable_columns as $field) {
                        if($field['type']=="text") {
                            $js.='<td class=\"left_col\"><input disabled id=\"'.implode("-",array($short_form_name,$field["field"],'noid','')).'"+tmp_row_id+"\" type=\"text\" style=\"width:'.$field['length'].'px;\" value=\"\" /></td>';
                        }
                        elseif($field['type']=="textarea") {
                            $js.='<td class=\"left_col\"><textarea disabled id=\"'.implode("-",array($short_form_name,$field["field"],'noid','')).'"+tmp_row_id+"\" type=\"text\" rows=\"'.$field['rows'].'\" cols=\"'.$field['cols'].'\" value=\"\"></textarea></td>';
                        }
                        
                    } 
                    $js.='<td class=\"vertical_center\"><button style=\"width:25px;height:25px;\" type=\"button\" class=\"add_button\"></button></td></tr>";
                    $("#'.$short_form_name.'Table").find("tr").eq(-1).before(new_row);
                
                    //reset the button icons
                    $(".delete_button").button({icons: {primary: "ui-icon-trash"},text:false});
                    $(".add_button").button({icons: {primary: "ui-icon-plus"},text:false});
                });
                
                //handle the individual delete buttons on each row
                $("#'.$short_form_name.'Table").on("click", ".delete_button",function(e) {
                    var row_id_label=$(this).parents("tr").attr("id");
                    var chunks=row_id_label.split("_");
                    var row_id=chunks[chunks.length-1];
                    
                    //if this is a row initialised by the DB, then dont delete the row, instead toggle the "to_be_deleted" class
                    var first_inputs_id=$("#"+row_id_label).find("input,textarea")[0].id;
                    var chunks2=first_inputs_id.split("-");
                    if(chunks2[2]==="dbid") {
                        $("#"+row_id_label).find("input,textarea").toggleClass("to_be_deleted");
                    }
                    else {
                        //delete the row we just clicked on
                        $("#'.$short_form_name.'_"+row_id).remove();
                    
                        //bubble up the row id numbering to maintain order
                        var rowCount=$("#'.$short_form_name.'Table").find("tr").length-3;
                        for(var i=parseInt(row_id)-1; i<=rowCount; i++) {
                            var id_to_change="'.$short_form_name.'_"+(i+1);
                            var id_to_change2="'.$short_form_name.'_"+(i);
                            $("#"+id_to_change).attr("id",id_to_change2);
                        }
                    }
                });
                
                //handle the page wide cancel button
                $("#'.$short_form_name.'_Button_Cancel").click(function() {
                    close_current_tab();
                });
                
                //handle the page wide save button
                $("#'.$short_form_name.'_Button_Save").click(function() {
                    var existing=[];
                    var newentries=[];
                    var tobedeleted=[];
                    //get all the relevant form data
                    var last_id=0;
                    $.each($("tr[id^=\"'.$short_form_name.'_\"]").find("input,textarea"),function(index,value) {
                        var this_input_id=value.id;
                        var thisClassName=value.className;
                        var tmp_chunks=value.id.split("-");
                        var fieldName=tmp_chunks[1];
                        var dbflag=tmp_chunks[2];
                        var dbid=tmp_chunks[3];
                        var value=value.value;
                        if (dbflag==="noid") {
                            if (!$("#"+this_input_id).parents("tr")[0].className.includes("last_row")) {
                                if(last_id===dbid) {
                                    newentries[newentries.length-1][fieldName]=value;
                                }
                                else {
                                    var obj=new Object();
                                    obj[fieldName]=value;
                                    newentries.push(obj);
                                }
                            }
                        }
                        else {
                            if (!$("#"+this_input_id).parents("tr")[0].className.includes("last_row")) {
                                //existing fileds that should be deleted
                                if(thisClassName.includes("to_be_deleted")) {
                                    if(last_id===dbid) {
                                        tobedeleted[tobedeleted.length-1][fieldName]=value;
                                    }
                                    else {
                                        var obj=new Object();
                                        obj[fieldName]=value;
                                        obj["id"]=dbid;
                                        tobedeleted.push(obj);
                                    }
                                }
                                //existing fields that have been modified, not to be deleted
                                else {
                                    if(last_id===dbid) {
                                        existing[existing.length-1][fieldName]=value;
                                    }
                                    else {
                                        var obj=new Object();
                                        obj[fieldName]=value;
                                        obj["id"]=dbid;
                                        existing.push(obj);
                                    }
                                }
                            }
                        }
                        last_id=dbid;
                    });
                    
                    //send the form data back to the server
                    $.ajax({
                        type: "POST",
                        url: "page_responder.php",
                        data: {
                            page:"'.$this->feature.'_Submit_'.str_replace(' ','_',$this->form_name).'", 
                            new_data:JSON.stringify(newentries),
                            existing_data:JSON.stringify(existing),
                            tobedeleted_data:JSON.stringify(tobedeleted),
                        }
                    }).done(function(e){
                        var tabs=$("div#tabs ul").children();
                        for (var i=0; i<tabs.length; i++) {
                            if ($("div#tabs ul").children()[i].getAttribute("aria-controls")=="about") {
                                $("div#about").append(e);
                            }
                         }
                         close_current_tab();
                    });
                });
            });
            </script>';
        return $js.$html;
    }
}
?>
