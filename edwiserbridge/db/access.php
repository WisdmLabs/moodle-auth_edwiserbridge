<?php
/**
 * @package local
 * @subpackage wdmgroupregistration
 */

$capabilities = array(

    "local/wdmgroupregistration:add" => array(
        "captype" => "write",
        "contextlevel" => CONTEXT_SYSTEM,
        "archetypes" => array(
            "student" => CAP_ALLOW,
            "teacher" => CAP_ALLOW,
            "editingteacher" => CAP_ALLOW,
            "manager" => CAP_ALLOW
            )
        )

    );
