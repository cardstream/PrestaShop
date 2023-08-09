<?php return array(
    'root' => array(
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'type' => 'prestashop-module',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'p3/prestashop-gateway',
        'dev' => true,
    ),
    'versions' => array(
        'p3/php-sdk' => array(
            'pretty_version' => '1.3.4',
            'version' => '1.3.4.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../p3/php-sdk',
            'aliases' => array(),
            'reference' => '8831790e8a273694ce3a7a08eec6211466ab57a5',
            'dev_requirement' => false,
        ),
        'p3/prestashop-gateway' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'type' => 'prestashop-module',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);
