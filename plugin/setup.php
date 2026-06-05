<?php
function plugin_init_uvtcampusfix() {
    global $PLUGIN_HOOKS;
    $PLUGIN_HOOKS['csrf_compliant']['uvtcampusfix'] = true;
}

function plugin_version_uvtcampusfix() {
    return [
        'name'         => 'UVT Campus Fix',
        'version'      => '1.0.0',
        'author'       => 'Team Aether',
        'license'      => 'GPLv3',
        'homepage'     => 'https://github.com/izaph/AI-Project_Aether_UVT-Campus-fix',
        'requirements' => ['glpi' => ['min' => '10.0']]
    ];
}

function plugin_uvtcampusfix_check_prerequisites() {
    return true;
}

function plugin_uvtcampusfix_check_config() {
    return true;
} 
