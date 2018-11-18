<?php

namespace Cdf\BiCoreBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Cache\Simple\FilesystemCache;

class VersioneExtension extends \Twig_Extension
{

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('versione_tag_git', array($this, 'versioneTagGit', 'is_safe' => array('html'))),
        );
    }
    public function versioneTagGit()
    {
        if ($this->isWindows()) {
            return 0;
        }
        $cache = new FilesystemCache();
        if ($cache->has("git_tag")) {
            $version = $cache->get("git_tag");
        } else {
            $projectDir = substr($this->container->get('kernel')->getRootDir(), 0, -4);

            $cmd = 'cd ' . $projectDir;
            $process = new Process($cmd . ';git describe --tags');
            $process->setTimeout(60 * 100);
            $process->run();
            if ($process->isSuccessful()) {
                $out = explode(chr(10), $process->getOutput());

                $version = isset($out[0]) ? $out[0] : "0";
                $cache->set("git_tag", $version);
            } else {
                $version = "0";
            }
        }
        return $version;
    }
    private function isWindows()
    {
        {
        if (PHP_OS == 'WINNT') {
            return true;
        } else {
            return false;
        }
        }
    }
}