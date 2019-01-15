<?php

use WHMCS\Database\Capsule;

//Check everyday if a recurring ticket is due
add_hook('AfterCronJob', 1, function($vars) {
    logActivity('Checking recurring tickets');
    $tickets = Capsule::table('mod_recurring_tickets')->get();
    
    foreach ($tickets as $ticket) {
        logActivity('Tickets:'.print_r($ticket,1));
        $postData = array(
            'deptid' => $ticket->deptid,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'priority' => $ticket->priority,
        );

        if($ticket->clientid == '0'){
            $postData['name'] = $ticket->name;
            $postData['email'] = $ticket->email;
        }else{
            $postData['clientid'] = $ticket->clientid;
        }
        $send=false;
        switch ($ticket->period) {
            case 'daily':
                $send = true;
                break;
            case 'weekly':
                if(date('N') == $ticket->day){
                    $send = true;
                }
                break;
            case 'monthly': 
                if(date('j') == $ticket->day){
                    $send = true;
                }
                break;
        }
        if($send){
            $command = 'OpenTicket';
            $results = localAPI($command, $postData);
            logActivity('Opening ticket');
        }
    }

});
