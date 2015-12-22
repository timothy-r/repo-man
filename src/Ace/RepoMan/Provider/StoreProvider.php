<?php namespace Ace\RepoMan\Provider;

use Ace\RepoMan\Store\RDBMSStoreFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * @author timrodger
 * Date: 23/06/15
 */
class StoreProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {

    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
        $config = $app['config'];

        $factory = new RDBMSStoreFactory(
            $config->getDbHost(),
            $config->getDbName(),
            $config->getDbUser(),
            $config->getDbPassword(),
            'dir'
        );
        $app['store'] = $factory->create();
    }
}
