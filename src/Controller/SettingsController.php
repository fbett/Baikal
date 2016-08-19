<?php

namespace Baikal\Controller;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class SettingsController implements ControllerProviderInterface {

    function connect(Application $app) {

        $controllers = $app['controllers_factory'];
        $controllers->get('/', [$this, 'indexAction'])->bind('admin_settings');
        return $controllers;
    }

    function indexAction(Application $app) {

        return $app['twig']->render('admin/settings.html', [
            'config' => $app['service.config']->get(),
            'is_writable' => $app['service.config']->isWritable()
        ]);
    }

}