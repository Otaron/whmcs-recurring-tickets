<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function recurring_tickets_config()
{
    return [
        // Display name for your module
        'name' => 'Recurring tickets',
        // Description displayed within the admin interface
        'description' => 'This module aims to provide a simple way for Admins to schedule recurring tickets',
        // Module author name
        'author' => 'Alexandre Blanchard',
        // Default language
        'language' => 'english',
        // Version number
        'version' => '0.1',
        'fields' => [
//            
        ]
    ];
}


function recurring_tickets_activate()
{
    // Create custom tables and schema required by your module
    try {
        Capsule::schema()
            ->create(
                'mod_recurring_tickets',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->string('period');
                    $table->integer('day');
                    $table->integer('deptid');
                    $table->string('subject');
                    $table->text('message');  
                    $table->integer('clientid')->nullable();  
                    $table->string('name')->nullable();  
                    $table->string('email')->nullable();  
                    $table->string('priority');
                }
            );

        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'You can now use the module from Addons > Recurring tickets',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            'status' => "error",
            'description' => 'Unable to create mod_addonexample: ' . $e->getMessage(),
        ];
    }
}

function recurring_tickets_deactivate()
{
    // Undo any database and schema modifications made by your module here
    try {
        Capsule::schema()
            ->dropIfExists('mod_recurring_tickets');

        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'The addon has been deactivated and all data has been deleted.',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            "status" => "error",
            "description" => "Unable to drop mod_addonexample: {$e->getMessage()}",
        ];
    }
}

function recurring_tickets_upgrade($vars)
{
    $currentlyInstalledVersion = $vars['version'];
}


function recurring_tickets_output($vars)
{
//    // Get common module parameters
    $modulelink = $vars['modulelink']; // addonmodules.php?module=recurring_tickets

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
 
    switch ($action) {
        case 'add':
            try {
                Capsule::table('mod_recurring_tickets')->insert([
                    [   
                        'period' => $_REQUEST['period'], 
                        'day' => $_REQUEST['day'], 
                        'deptid' => $_REQUEST['deptid'], 
                        'subject' => $_REQUEST['subject'], 
                        'message' => $_REQUEST['message'], 
                        'clientid' => $_REQUEST['clientid'], 
                        'name' => $_REQUEST['name'], 
                        'email' => $_REQUEST['email'], 
                        'priority' => $_REQUEST['priority']
                    ]
                ]);
                echo '<div class="successbox"><strong><span class="title">Action result</span></strong><br>The ticket has been programmed.</div>';
            } catch (Exception $e) {
                echo "<div class='errorbox'><strong><span class='title'>Insert error</span></strong><br>{$e->getMessage()}</div>";
            }
            break;
        case 'delete':
            if(isset($_REQUEST['id'])){
                try {
                    Capsule::table('mod_recurring_tickets')->where('id',$_REQUEST['id'])->delete();
                    echo '<div class="successbox"><strong><span class="title">Action result/span></strong><br>The ticket has been deleted.</div>';
                } catch (Exception $e) {
                    echo "<div class='errorbox'><strong><span class='title'>Delete error</span></strong><br>{$e->getMessage()}</div>";
                }
            }
            break;
    }
    
    $command = 'GetClients';
    $postData = array();
    $results = localAPI($command, $postData);
    
    $clients = $results['clients']['client'];

    $command = 'GetSupportDepartments';
    $postData = array();
    $results = localAPI($command, $postData);
    
    $supportDepts = $results['departments']['department'];
    
    $recurringTickets = Capsule::table('mod_recurring_tickets')->get();
    $result = '';
    foreach ($recurringTickets as $ticket) {
        $dept = array_search($ticket->deptid, array_column($supportDepts, 'id'));
        if(is_null($ticket->clientid)){
            $recipient = $ticket->name .' '.$ticket->email;
        }else{
            $cli = array_search($ticket->clientid, array_column($clients, 'id'));
            $recipient = $clients[$cli]['firstname'] . ' ' . $clients[$cli]['lastname'] . ' ' . $clients[$cli]['email'] ;
        }
        $result.='
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel">Occurences</td>
                    <td class="fieldarea">'.$ticket->period.'</td>
                    <td class="fieldlabel">Day</td>
                    <td class="fieldarea">'.$ticket->day.'</td>
                    <td class="fieldlabel">Department</td>
                    <td class="fieldarea">'.$supportDepts[$dept]['name'].'</td>
                    <td class="fieldlabel">Priority</td>
                    <td class="fieldarea">'.$ticket->priority.'</td>
                    <td class="fieldlabel">Recipient</td>
                    <td class="fieldarea">'.$recipient.'</td>
                </tr>
                <tr>
                    <td class="fieldlabel">Subject</td>
                    <td class="fieldarea" colspan="7">'.$ticket->subject.'</td>
                    <td class="fieldlabel">Delete</td>
                    <td class="fieldarea"><a href="/admin/addonmodules.php?module=recurring_tickets&action=delete&id='.$ticket->id.'"><i class="fas fa-times-circle"></i></a></td>
                </tr>
                <tr>
                    <td class="fieldlabel">Message</td>
                    <td class="fieldarea" colspan="9">'.$ticket->message.'</td>
                </tr>
            </table>
            ';
    }
    
    $result .= '

<script>function getClientSearchPostUrl() { return "/admin/index.php?rp=/admin/search/client"; }</script>
<script type="text/javascript" src="../assets/js/AdminClientDropdown.js"></script>
    
<div class="infobox"><strong><span class="title">Validation rules</span></strong><ul><li>You must enter a subject for the ticket</li>
<li>You must specify either an existing client or enter the receipients email address manually</li>
<li>You must enter either the name of the person this ticket is being created for or specify an existing client</li></ul></div>

<form method="post" action="/admin/addonmodules.php?module=recurring_tickets" enctype="multipart/form-data" id="frmOpenTicket" data-no-clear="true">
    <input type="hidden" name="action" id="action" value="add" />
    <table class="form" width="100%" border="0">
        <tr>
            <td class="fieldlabel">Occurences</td>
            <td class="fieldarea"><select name="period" class="form-control select-inline" tabindex="8">
                    <option value="daily">Daily</option>
                    <option value="weekly" selected>Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">Occurences</td>
            
            <td class="fieldarea"><select name="day" class="form-control select-inline" tabindex="8">
                    <option value="0">0</option>
                    <option value="1" selected>1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                    <option value="13">13</option>
                    <option value="14">14</option>
                    <option value="15">15</option>
                    <option value="16">16</option>
                    <option value="17">17</option>
                    <option value="18">18</option>
                    <option value="19">19</option>
                    <option value="20">20</option>
                    <option value="21">21</option>
                    <option value="22">22</option>
                    <option value="23">23</option>
                    <option value="24">24</option>
                    <option value="25">25</option>
                    <option value="26">26</option>
                    <option value="27">27</option>
                    <option value="28">28</option>
                    <option value="29">29</option>
                    <option value="30">30</option>
                    <option value="31">31</option>
                </select>    
            </td>
        </tr>
        
    </table>

    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td class="fieldlabel">Client</td>
            <td class="fieldarea" colspan="3">
                <select name="clientid" class="form-control select-inline" tabindex="1">';
    $result .= '<option value=""></option>';
    foreach ($clients as $client) {
        $result .= '<option value="'.$client['id'].'">'.$client['firstname'].' '.$client['lastname'].' '.$client['companyname'].' '.$client['email'].'   </option>';
    }
    
    $result .='</select>    </td>
        </tr>
        <tr>
            <td width="15%" class="fieldlabel">Name</td>
            <td class="fieldarea" colspan="3">
                <input type="text" name="name" id="name" class="form-control input-300" tabindex="2" value="" />
            </td>
        </tr>
        <tr><td class="fieldlabel">Email Address</td><td class="fieldarea" colspan="3"><input type="text" name="email" id="email" class="form-control input-400 input-inline" tabindex="3" value=""> <label class="checkbox-inline"><input type="checkbox" name="sendemail" tabindex="4" checked /> Send Email</label></td></tr>
        <tr><td class="fieldlabel">Subject</td><td class="fieldarea" colspan="3"><input type="text" name="subject" class="form-control" tabindex="6" value=""></td></tr>
        <tr><td class="fieldlabel">Department</td><td class="fieldarea"><select name="deptid" class="form-control select-inline" tabindex="7">';
        
    foreach ($supportDepts as $supportDept) {
        $result .= '<option value="'.$supportDept['id'].'">'.$supportDept['name'].'</option>';
    }
    
    $result .='</select></td><td class="fieldlabel">Priority</td><td class="fieldarea">
                <select name="priority" class="form-control select-inline" tabindex="8">
                    <option value="High">High</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="Low">Low</option>
                </select>
            </td>
        </tr>
    </table>

    <div class="margin-top-bottom-20">
        <textarea name="message" id="message" rows="10" tabindex="9" class="form-control"></textarea>
    </div>

    <div class="btn-container">
        <button type="submit" class="btn btn-primary" id="btnOpenTicket">
            <i class="fas fa-plus"></i>
            Open Ticket    </button>
    </div>

</form>
';
        
        echo $result;
}

function recurring_tickets_sidebar($vars)
{
    return null;
}

function recurring_tickets_clientarea($vars)
{
    return null;
}
