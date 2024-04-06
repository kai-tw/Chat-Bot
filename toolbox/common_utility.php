<?php
class CommonUtility
{
    public static function includeAllFile(string $path)
    {
        $files = scandir($path);

        $files = array_filter($files, function ($value) {
            return pathinfo($value, PATHINFO_EXTENSION) === 'php';
        });

        foreach ($files as $file) {
            include CommonUtility::joinPaths($path, $file);
        }
    }

    /**
     * Source: https://stackoverflow.com/questions/1091107/how-to-join-filesystem-path-strings-in-php
     */
    public static function joinPaths()
    {
        $paths = array();

        foreach (func_get_args() as $arg) {
            if ($arg !== '') {
                $paths[] = $arg;
            }
        }

        return preg_replace('#/+#', '/', join('/', $paths));
    }
}
