<?php namespace Sce\RepoMan\Domain;

/**
 * Class GitRepo
 * @package Sce\Repo
 * Represents a git repo
 */
class Repository
{
    /**
     * @var string url of remote git repo
     */
    private $url;

    /**
     * @var string location to clone remote repo into
     */
    private $directory;

    /**
     * @var string name of checkout
     */
    private $name;

    /**
     * @var string
     * Optional token to authenticate access to the remote repo
     */
    private $token;

    /**
     * @param $url string location of remote repo
     * @param $directory string directory location to clone repo into
     * @param null $token authentication token
     */
    public function __construct($url, $directory, $token = null)
    {
        $this->url = $url;
        $this->directory = $directory;
        $parts = explode('/', $this->url);
        $this->name = array_pop($parts);
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Return an id string for this repo
     * @return string
     */
    public function getId()
    {
        return base64_encode($this->url);
    }

    /**
     * Update the local repo from the remote
     * clones repo if it has not been checked out out yet
     * runs git remote update and git fetch --tags
     */
    public function update()
    {
        // cd to dir
        chdir($this->directory);

        // check if local repo exists
        if (!is_dir($this->directory . '/' . $this->name)) {
            exec('git clone ' . $this->generateUrl(), $output);
        }

        try {
            $this->execGitCommand('git remote update');
            $this->execGitCommand('git fetch --tags origin');
            $this->execGitCommand('git pull origin');
            return true;
        } catch (NoDirectoryException $ex){
            return false;
        }
    }
    /**
     * return a list of branch names for the local repo
     *
     * @return array
     */
    public function listLocalBranches()
    {
        try {
            $branches = $this->execGitCommand('git branch');

            return array_map(function ($name) {
                return trim($name, '* ');
            }, $branches);
        } catch (NoDirectoryException $ex){
            return [];
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function isLocalBranch($name)
    {
        return in_array($name, $this->listLocalBranches());
    }

    /**
     * @return array
     */
    public function listAllBranches()
    {
        try {
            $branches = $this->execGitCommand('git branch -a');

            $branches = array_map(function ($name) {
                return trim($name, '* ');
            }, $branches);

            $branches = array_map(function ($name) {
                return preg_replace('/^remotes\/origin\//', '', $name);
            }, $branches);

            // de-duplicate array and remove HEAD
            $branches = array_filter($branches, function ($name) {
                return !preg_match('/^HEAD/', $name);
            });

            return array_unique($branches);

        } catch (NoDirectoryException $ex){
            return [];
        }
    }

    /**
     * Get the list of tags for the local repo
     * @return array
     */
    public function listTags()
    {
        return $this->execGitCommand('git tag -l');
    }

    /**
     * Checkout the branch or tag with this name
     * @param $name
     */
    public function checkout($name)
    {

    }

    /**
     * @param $name
     * @return bool
     */
    public function hasFile($name)
    {
        return file_exists($this->directory . '/' . $this->name . '/' . $name);
    }

    /**
     * @param $name
     * @return string the contents of file named $name in checkout
     */
    public function getFile($name)
    {
        if ($this->hasFile($name)) {
            return file_get_contents($this->directory . '/' . $this->name . '/' . $name);
        }
    }

    /**
     * Create a new branch
     * @param $name
     */
    public function branch($name)
    {

    }

    /**
     * Create a new tag
     * @param $name string
     * @param $comment string
     */
    public function tag($name, $comment)
    {

    }

    /**
     * Add a file
     * @param $name
     */
    public function add($name)
    {

    }

    public function commit()
    {

    }

    public function push()
    {

    }

    /**
     * Insert the token into the url if it is set
     * @return string
     */
    private function generateUrl()
    {
        if (!is_null($this->token)){
            $parts = parse_url($this->url);
            // format is http://token@host/path for github at least
            $url = sprintf('%s://%s@%s%s', $parts['scheme'], $this->token, $parts['host'], $parts['path']);
            return $url;
        }

        return $this->url;
    }

    /**
     * @param $cmd string
     * @return array
     */
    private function execGitCommand($cmd)
    {
        if (is_dir($this->directory . '/' . $this->name)) {
            chdir($this->directory . '/' . $this->name);
            exec($cmd, $output);
            return $output;
        } else {
            throw new NoDirectoryException();
        }
    }

}