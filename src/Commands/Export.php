<?php

namespace SocialEngine\Console\Commands;

use SocialEngine\Console\Command;

/**
 * SE Builder
 */
class Export extends Command
{
    /**
     * @cli-command export:module
     * @cli-argument name
     * @cli-info Export a module
     */
    public function module()
    {
        $package = $this->getArgument('name');
        $path = SE_CONSOLE_DIR . 'application/modules/' . $package . '/';

        if (!is_dir($path)) {
            throw new \Exception('Directory not found: ' . $path);
        }

        $manifest = require($path . 'manifest.php');
        $manifest['package']['files'] = [];
        $temp = $this->tempDir() . 'export-module-' . $package . '/';
        if (!is_dir($temp)) {
            mkdir($temp);
        }

        $skip = [];
        $distFile = SE_CONSOLE_DIR . '.dist-ignore';
        if (file_exists($distFile)) {
            $skip = array_map('trim', explode("\n", trim(file_get_contents($distFile))));
        }

        $exec = 'cd ' . SE_CONSOLE_DIR . ' && ';
        $exec .= $this->getBin('git') . ' ls-tree --full-tree --name-only -r HEAD';
        $files = $this->exec($exec);
        foreach (explode("\n", $files) as $file) {
            if (empty($file) || substr($file, 0, 1) == '.' || in_array($file, $skip)) {
                continue;
            }
            $manifest['package']['files'][] = $file;

            $info = new \SplFileInfo($temp . $file);

            mkdir($info->getPath(), 0777, true);

            copy(SE_CONSOLE_DIR . $file, $temp . $file);
        }

        $enginePackage = new \Engine_Package_Manifest_Entity_Package($manifest['package'], [
            'path' => 'application/modules/' . $package . '/',
            'basePath' => SE_CONSOLE_DIR
        ]);

        file_put_contents($temp . 'package.json', json_encode($enginePackage->toArray(), JSON_PRETTY_PRINT));

        $name = 'module-' . $manifest['package']['name'] . '-' . $manifest['package']['version'] . '.json';
        $tarName = str_replace('.json', '.tar', $name);
        mkdir($temp . 'application/packages/');

        copy($temp . 'package.json', $temp . 'application/packages/' . $name);

        $this->exec('cd ' . $temp . ' &&  tar -zcf ../' . $tarName . ' .');
        $this->exec('rm -rf ' . $temp);
        $this->write('Done!');
        $this->write('Exported to: ' . $this->tempDir() . $tarName);
    }
}
