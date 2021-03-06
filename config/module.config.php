<?php

namespace ZeDb;

return array(
    'zendexperts_zedb' =>  array(
        'adapter' => array(
            'driver'    => 'Pdo_Mysql', //'MySqli',
            'database'  => 'test',
            'username'  => 'root',
            'password'  => ''
        ),
        'models' => array(
            /**
             * Here you can define the relation between your models, entities and database tables.
             * Add definitions by specifying what entithe class and what table the model should use, like so:
             * 'MyModule\UserModel'=>array(
             *     'tableName' => 'user',
             *     'entityClass' => 'MyModule\UserEntity'
             * )
             */
        ),
    ),
    'di'=>array(),
    'controller_plugins' => [ 
        'invokables' => [ 
            'db'    => Plugin\Plugin::class,
        ]
    ],
);
