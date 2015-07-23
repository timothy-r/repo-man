<?php

use Sce\RepoMan\Store\Redis as RedisStore;

/**
 * @group unit
 * @author timrodger
 * Date: 18/07/15
 */
class GitStoreUnitTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var RedisStore
     */
    private $store;

    /**
     * @var Predis\Client
     */
    private $mock_client;

    /**
     * @var Sce\RepoMan\Configuration
     */
    private $mock_config;

    public function setUp()
    {
        parent::setUp();
        $dir = '/tmp/repos';
        $this->givenAMockConfig($dir);
        $this->givenAMockClient();
        $this->givenAStore();
    }

    /**
     *
     */
    public function testGetAll()
    {
        $this->givenSomeMockedData([
            'https://github.com/user/repo-name'
        ]);

        $result = $this->store->getAll();
        $this->assertTrue(is_array($result));
        $this->assertSame(1, count($result));
    }

    /**
     *
     */
    public function testGetAllReturnsAnEmptyArrayWhenNoRepositoriesExist()
    {
        $result = $this->store->getAll();
        $this->assertTrue(is_array($result));
        $this->assertSame(0, count($result));
    }

    /**
     * @expectedException Sce\RepoMan\Store\UnavailableException
     */
    public function testGetAllThrowsExceptionWhenServerIsUnavailable()
    {
        $this->mock_client->expects($this->once())
            ->method('smembers')
            ->with(RedisStore::REPO_SET_NAME)
            ->will($this->throwException(new \Predis\Response\ServerException));

        $this->store->getAll();
    }

    public function testAddRepository()
    {
        $url = 'https://github.com/user/repo-name';

        $this->mock_client->expects($this->once())
            ->method('sadd')
            ->with(RedisStore::REPO_SET_NAME, $url);

        $repository = $this->store->add($url);
        $this->assertInstanceOf('Sce\RepoMan\Domain\Repository', $repository);
    }

    /**
     * @expectedException Sce\RepoMan\Store\UnavailableException
     */
    public function testAddRepositoryThrowsExceptionWhenServerIsUnavailable()
    {
        $url = 'https://github.com/user/repo-name';

        $this->mock_client->expects($this->once())
            ->method('sadd')
            ->with(RedisStore::REPO_SET_NAME, $url)
            ->will($this->throwException(new \Predis\Response\ServerException));

        $this->store->add($url);
    }

    public function testAddToken()
    {
        $host = 'https://github.com';
        $token = 'abcdef';

        $this->mock_client->expects($this->once())
            ->method('set')
            ->with(RedisStore::TOKEN_SET_NAME . ':' . $host, $token);

        $this->store->addToken($host, $token);
    }

    /**
     * @expectedException Sce\RepoMan\Store\UnavailableException
     */
    public function testAddTokenThrowsExceptionWhenServerIsUnavailable()
    {
        $host = 'https://github.com';
        $token = 'abcdef';

        $this->mock_client->expects($this->once())
            ->method('set')
            ->with(RedisStore::TOKEN_SET_NAME . ':' . $host, $token)
            ->will($this->throwException(new \Predis\Response\ServerException));

        $this->store->addToken($host, $token);
    }

    private function givenSomeMockedData(array $repos)
    {
        // set up some contents of the mock redis instance
        $this->mock_client->expects($this->once())
            ->method('smembers')
            ->with(RedisStore::REPO_SET_NAME)
            ->will($this->returnValue($repos));
    }

    /**
     *
     */
    private function givenAStore()
    {
        $this->store = new RedisStore($this->mock_config, $this->mock_client);
    }

    /**
     * @param $dir
     * @param array $repos
     */
    private function givenAMockConfig($dir)
    {
        $this->mock_config = $this->getMockBuilder('Sce\RepoMan\Configuration')
            ->setMethods(['getRepoDir', 'getRepositoryNames'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mock_config->expects($this->any())
            ->method('getRepoDir')
            ->will($this->returnValue($dir));
    }

    private function givenAMockClient()
    {
        $this->mock_client = $this->getMockBuilder('Predis\Client')
            ->setMethods(['hmset', 'hmget', 'sadd', 'smembers', 'set', 'get'])
            ->disableOriginalConstructor()
            ->getMock();
    }
}