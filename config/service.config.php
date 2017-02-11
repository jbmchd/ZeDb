<?php

namespace ZeDb;

return array(
    'factories'=>array(
        'ZeDbManager' => 'ZeDb\Service\DatabaseManagerFactory',
        'Zend\Db\Adapter\Adapter'=>'ZeDb\Service\AdapterFactory',
        Plugin\PluginService::class => function( $sm ) {
            return new Plugin\PluginService($sm->get('ZeDbManager'));
        },
    ),
);