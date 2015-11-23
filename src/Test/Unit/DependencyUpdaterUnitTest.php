<?php

use Sce\RepoMan\Command\DependencyUpdater;
use Sce\RepoMan\Exception\DirectoryNotFoundException;

/**
 * @group unit
 * @author timrodger
 * Date: 26/07/15
 */
class DependencyUpdaterUnitTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sce\RepoMan\Domain\Repository
     */
    private $mock_repository;

    /**
     * @var Sce\RepoMan\Domain\ComposerDependencySet
     */
    private $mock_dependency_set;

    /**
     * @var Sce\RepoMan\Command\DependencyUpdater
     */
    private $command;

    /**
     * @expectedException \Sce\RepoMan\Exception\DirectoryNotFoundException
     */
    public function testExecuteThrowsExceptionIfUpdateFails()
    {
        $this->givenAMockDependencySet();
        $this->givenAMockRepository();

        $this->mock_repository->expects($this->once())
            ->method('update')
            ->will($this->throwException(new DirectoryNotFoundException()));

        $this->givenACommand();

        $data = ['require' => []];

        $result = $this->command->execute($data);
        $this->assertFalse($result);
    }

    /**
     * @expectedException Sce\RepoMan\Exception\FileNotFoundException
     */
    public function testExecuteThrowsExceptionIfComposerFilesAreMissing()
    {
        $this->givenAMockDependencySet();
        $this->givenAMockRepository();

        $this->mock_repository->expects($this->once())
            ->method('update');

        $this->mock_dependency_set->expects($this->once())
            ->method('setRequiredVersions')
            ->will($this->throwException(new \Sce\RepoMan\Exception\FileNotFoundException()));

        $this->givenACommand();

        $data = ['require' => []];

        $this->command->execute($data);
    }

    /**
     * @expectedException \Sce\RepoMan\Exception\InvalidFileContentsException
     */
    public function testExecuteThrowsExceptionIfComposerFileIsNotJson()
    {
        $this->givenAMockDependencySet();
        $this->givenAMockRepository();

        $this->mock_repository->expects($this->once())
            ->method('update');

        $this->mock_dependency_set->expects($this->once())
            ->method('setRequiredVersions')
            ->will($this->throwException(new \Sce\RepoMan\Exception\InvalidFileContentsException()));

        $this->givenACommand();

        $data = ['require' => []];

        $this->command->execute($data);
    }

    public function testExecuteUsesExistingBranchIfPresent()
    {
        $this->givenAMockDependencySet();
        $this->givenAMockRepository();

        $latest_tag = 'v2.4.0';
        $new_branch = 'feature/update-' . $latest_tag;

        $this->givenALatestTag($latest_tag);
        $this->whenABranchExists($new_branch);

        $this->mock_repository->expects($this->once())
            ->method('update');

        $this->mock_repository->expects($this->never())
            ->method('branch');

        $this->mock_repository->expects($this->once())
            ->method('checkout')
            ->with($new_branch);

        $this->mock_dependency_set->expects($this->once())
            ->method('setRequiredVersions');

        $this->givenACommand();

        $data = ['require' => ['company/libx' => '2.0.0']];

        // commands throw exceptions on error do not return true from execute
        $this->command->execute($data);
    }

    public function testExecuteCallsSetRequiredVersionsWhenRequireIsSet()
    {
        $this->givenAMockDependencySet();
        $this->givenAMockRepository();

        $latest_tag = 'v1.3.6';
        $new_branch = 'feature/update-' . $latest_tag;

        $this->givenALatestTag($latest_tag);
        $this->whenABranchDoesNotExist($new_branch);

        $this->mock_repository->expects($this->once())
            ->method('update')
            ->will($this->returnValue(true));

        $this->mock_repository->expects($this->once())
            ->method('branch')
            ->with($new_branch, $latest_tag);

        $this->mock_repository->expects($this->once())
            ->method('checkout')
            ->with($new_branch);

        $this->mock_dependency_set->expects($this->once())
            ->method('setRequiredVersions');

        $this->givenACommand();

        $data = ['require' => ['company/libx' => '2.0.0']];

        // commands throw exceptions on error do not return true from execute
        $this->command->execute($data);
    }

    private function whenABranchExists($branch)
    {
        $this->mock_repository->expects($this->once())
            ->method('isLocalBranch')
            ->with($branch)
            ->will($this->returnValue(true));
    }

    private function whenABranchDoesNotExist($branch)
    {
        $this->mock_repository->expects($this->once())
            ->method('isLocalBranch')
            ->with($branch)
            ->will($this->returnValue(false));
    }

    private function givenALatestTag($latest_tag)
    {
        $this->mock_repository->expects($this->any())
            ->method('getLatestTag')
            ->will($this->returnValue($latest_tag));
    }

    private function givenACommand()
    {
        $this->command = new DependencyUpdater(
            $this->mock_repository
        );
    }

    private function givenAMockDependencySet()
    {
        $this->mock_dependency_set = $this->getMockBuilder('Sce\RepoMan\Domain\ComposerDependencySet')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function givenAMockRepository()
    {
        $this->mock_repository = $this->getMockBuilder('Sce\RepoMan\Domain\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mock_repository->expects($this->any())
            ->method('getDependencySet')
            ->will($this->returnValue($this->mock_dependency_set));
    }
}