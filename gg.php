<?php

class gg
{
    private $commits = array('a','b','c', 'd');
    private $commitsMerged = array('a' => array('b', 'c'));

    public function __construct($cible)
    {
        $this->generateLog($cible);

        if (file_exists('/tmp/toto.txt')) {
            $this->commits = file('/tmp/toto.txt');
        }

        if (file_exists('/tmp/merged.txt')) {
            $this->commitsMerged = $this->formatMerged(file('/tmp/merged.txt'));
        }
    }

    public function generateLog($cible)
    {
        exec('git clone https://github.com/'.$cible.' /tmp/test');
        chdir('/tmp/test');
        exec('git log --all | grep commit | awk \'{print $2}\' | xargs -l > /tmp/toto.txt');
        exec('git log --merges --all > /tmp/merged.txt');
    }

    public function __destruct()
    {
        chdir(__DIR__);
        unlink('/tmp/merged.txt');
        unlink('/tmp/toto.txt');
        $this->delTree('/tmp/test');
    }

    public function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function formatMerged(array $array)
    {
        $key = $values = null;
        $merged = array();
        foreach ($array as $string) {
            $string = trim($string);
            if (empty($string)) {
                continue;
            }

            if (strstr($string, "commit ")) {
                $key = substr($string, 7);
            }

            if (strstr($string, "Merge: ")) {
                $values = substr($string, 7);
            }

            if (!is_null($key) && !is_null($values)) {
                $merged[$key] = explode(' ', $values);
                $key = $values = null;
            }
        }

        return $merged;
    }

    public function __toString()
    {
        return "digraph G {\n". PHP_EOL .
            $this->writeGraph().
            '}' . PHP_EOL;
    }

    public function writeGraph()
    {
        $src = null;
        $graph = array();
        foreach ($this->commits as $commit) {
            if (is_null($src)) {
                $src = $commit;
                continue;
            }

            $graph[] = $this->writeElements(trim($src), trim($commit));
            if (array_key_exists(trim($src), $this->commitsMerged)) {
                foreach ($this->commitsMerged[trim($src)] as $merged) {
                    $graph[] = $this->writeElements(trim($src), trim($merged));
                }
            }
            $src = $commit;
        }

        return join(PHP_EOL, array_reverse(array_unique($graph)));
    }

    public function writeElements($src, $dest)
    {
        return "\t\""
            .substr($src, 0, 7)
            ."\" -> \""
            .substr($dest, 0, 7)
            ."\"";
    }
}

if (!isset($_SERVER['argv'][1])) {
    exit('No cible found please choose project');
}

$graph = new gg($_SERVER['argv'][1]);
echo $graph;
