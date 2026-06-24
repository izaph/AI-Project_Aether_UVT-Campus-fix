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

        $menu['options']['ticket'] = [
            'title' => 'Raportează Incident',
            'page'  => '/plugins/uvtcampusfix/front/ticket.php',
            'icon'  => 'fas fa-exclamation-triangle',
        ];

        $menu['options']['dashboard'] = [
            'title' => 'Dashboard',
            'page'  => '/plugins/uvtcampusfix/front/dashboard.php',
            'icon'  => 'fas fa-chart-bar',
        ];

        $menu['options']['qr'] = [
            'title' => 'Generator QR',
            'page'  => '/plugins/uvtcampusfix/front/qr.php',
            'icon'  => 'fas fa-qrcode',
        ];

        return $menu;
    }

    static function canView(): bool {
        return Session::haveRight('config', READ);
    }
}
