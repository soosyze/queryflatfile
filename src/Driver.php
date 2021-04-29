<?php

declare(strict_types=1);

/**
 * Queryflatfile
 *
 * @license https://github.com/soosyze/queryflatfile/blob/master/LICENSE (MIT License)
 */

namespace Queryflatfile;

use Queryflatfile\Exception\Driver\DriverException;
use Queryflatfile\Exception\Driver\FileNotFoundException;
use Queryflatfile\Exception\Driver\FileNotReadableException;
use Queryflatfile\Exception\Driver\FileNotWritableException;

/**
 * Implementation partiel Queryflatfile\DriverInterface.
 *
 * @author Mathieu NOËL <mathieu@soosyze.com>
 */
abstract class Driver implements DriverInterface
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * Déclenche une exception si l'extension du fichier n'est pas chargée.
     *
     * @codeCoverageIgnore has
     *
     * @throws Exception\Driver\ExtensionNotLoadedException
     *
     * @return void
     */
    abstract public function checkExtension(): void;

    /**
     * Renvoie les données séréalisées.
     *
     * @param array $data
     *
     * @return string
     */
    abstract public function serializeData(array $data): string;

    /**
     * Renvoie les données désérialisées.
     *
     * @param string $data
     *
     * @return array
     */
    abstract public function unserializeData(string $data): array;

    /**
     * {@inheritDoc}
     */
    public function create(string $path, string $fileName, array $data = []): bool
    {
        $this->checkExtension();
        $file = $this->getFile($path, $fileName);

        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }
        if (file_exists($file)) {
            return false;
        }

        $handle = fopen($file, 'w+');
        if ($handle === false) {
            throw new DriverException("$file file cannot be opened");
        }
        fwrite($handle, $this->serializeData($data));

        return fclose($handle);
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $path, string $fileName): array
    {
        $this->checkExtension();
        $file = $this->getFile($path, $fileName);

        $this->isExist($file);
        $this->isRead($file);

        $data = file_get_contents($file);

        return $data === false
            ? []
            : $this->unserializeData($data);
    }

    /**
     * {@inheritDoc}
     */
    public function save(string $path, string $fileName, array $data): bool
    {
        $this->checkExtension();
        $file = $this->getFile($path, $fileName);

        $this->isExist($file);
        $this->isWrite($file);

        $handle = fopen($file, 'w');
        if ($handle === false) {
            throw new DriverException("$file file cannot be opened");
        }
        fwrite($handle, $this->serializeData($data));

        return fclose($handle);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $path, string $fileName): bool
    {
        return unlink($this->getFile($path, $fileName));
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $path, string $fileName): bool
    {
        return file_exists($this->getFile($path, $fileName));
    }

    /**
     * Concatène le chemin, le nom du fichier et l'extension.
     *
     * @param string $path     Chemin de la table.
     * @param string $fileName Nom du fichier.
     *
     * @return string Chemin complet du fichier.
     */
    public function getFile(string $path, string $fileName): string
    {
        $file = $path . self::DS . $fileName . '.' . $this->getExtension();

        return str_replace('\\', self::DS, $file);
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre d'existe pas.
     *
     * @param string $file Chemin complet du fichier.
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    protected function isExist(string $file): void
    {
        if (!file_exists($file)) {
            throw new FileNotFoundException("The $file file is missing.");
        }
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre n'a pas le droit d'écriture.
     *
     * @codeCoverageIgnore has
     *
     * @param string $file Chemin complet du fichier.
     *
     * @throws FileNotWritableException
     *
     * @return void
     */
    protected function isWrite(string $file): void
    {
        if (!\is_writable($file)) {
            throw new FileNotWritableException("The $file file is not writable.");
        }
    }

    /**
     * Déclenche une exception si le fichier passé en paramètre n'a pas le droit d'être lu.
     *
     * @codeCoverageIgnore has
     *
     * @param string $file Chemin complet du fichier.
     *
     * @throws FileNotReadableException
     *
     * @return void
     */
    protected function isRead(string $file): void
    {
        if (!\is_readable($file)) {
            throw new FileNotReadableException("The $file file is not readable.");
        }
    }
}
