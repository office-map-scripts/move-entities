<?php

//NTLM CONFIG
$use_ntlm_auth = true;
$ntlm_user = ''; // domain/user
$ntlm_password = '';

$project_url = "";
$apiKey = "";

$entity = "Desk"; // Desk | Employee | Booking | Asset
/**
 * "" - skip value
 * if "id" exists update entity otherwise create
 */
$fieldnames = [];

$filename = "result.json";

//AjaxAction.aspx, то, с помощью чего авторизуемся
$pre_resuest_url = '';
$pre_resuest_postdata = '';

// SQL: select [<field>] from [_Desks]
$desk_list_url = "";
// SQL: select [<field>] from [_Employees]
$employee_list_url = "";

$desk_id_by = "deskname";
$employee_id_by = "username";
