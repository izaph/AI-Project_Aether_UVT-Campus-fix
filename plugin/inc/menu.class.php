<?php
class PluginUvtcampusfixMenu extends CommonGLPI {

    static function getMenuName() {
        return 'UVT Campus Fix';
    }

    static function getMenuContent() {
        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page']  = '/plugins/uvtcampusfix/front/index.php';
        $menu['icon']  = 'fas fa-tools';

        $menu['options']['main'] = [
            'title' => 'Dashboard',
            'page'  => '/plugins/uvtcampusfix/front/index.php',
            'icon'  => 'fas fa-chart-bar'
        ];

        return $menu;
    }

    static function canView() {
        return Session::haveRight('config', READ);
    }
}